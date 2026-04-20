<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Support;

use App\Abstracts\Support\Mailable;
use App\Contracts\Support\MailerInterface;
use App\Core\Config;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ConversionTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\Patterns\PatternTrait;
use PHPMailer\PHPMailer\PHPMailer;

class MailManager implements MailerInterface
{
    use ArrayTrait, CheckerTrait, ConversionTrait, ManipulationTrait, PatternTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    /**
     * @var list<array<string, mixed>>
     */
    private array $outbox = [];

    public function __construct(
        private readonly Config $config,
        private readonly FileManager $fileManager,
        private readonly ErrorManager $errorManager
    ) {
    }

    public function send(Mailable $mailable): bool
    {
        $message = $mailable->message();

        return match ($this->driverName()) {
            'array' => $this->queueArrayMessage($message),
            'log' => $this->logMessage($message),
            default => $this->sendWithPhpMailer($message),
        };
    }

    public function driverName(): string
    {
        return $this->toLowerString((string) $this->config->get('mail', 'MAILER', 'smtp'));
    }

    public function capabilities(): array
    {
        return [
            'transport' => [
                'array' => true,
                'log' => true,
                'smtp' => true,
                'sendmail' => true,
                'mail' => true,
            ],
            'html' => true,
            'text' => true,
        ];
    }

    public function supports(string $feature): bool
    {
        $segments = explode('.', trim($feature));
        $value = $this->capabilities();

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }

            $value = $value[$segment];
        }

        return $value === true;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function outbox(): array
    {
        return $this->outbox;
    }

    /**
     * @param array<string, mixed> $message
     */
    private function queueArrayMessage(array $message): bool
    {
        $this->outbox[] = $message;

        return true;
    }

    /**
     * @param array<string, mixed> $message
     */
    private function logMessage(array $message): bool
    {
        $path = $this->fileManager->normalizePath($this->frameworkLogPath('mail.log'));
        $directory = dirname($path);

        if (!$this->fileManager->isDirectory($directory)) {
            $this->fileManager->createDirectory($directory, 0777, true);
        }

        $existing = $this->fileManager->readContents($path) ?? '';
        $payload = $existing . $this->toJson(
            $message,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ) . PHP_EOL;

        return $this->fileManager->writeContents($path, $payload) !== false;
    }

    /**
     * @param array<string, mixed> $message
     */
    private function sendWithPhpMailer(array $message): bool
    {
        $mailer = new PHPMailer(true);
        $driver = $this->driverName();

        match ($driver) {
            'sendmail' => $mailer->isSendmail(),
            'mail' => $mailer->isMail(),
            default => $mailer->isSMTP(),
        };

        if ($driver === 'smtp') {
            $mailer->Host = (string) $this->config->get('mail', 'HOST', 'localhost');
            $mailer->Port = (int) $this->config->get('mail', 'PORT', 25);
            $mailer->SMTPAuth = $this->config->get('mail', 'USERNAME') !== null && $this->config->get('mail', 'USERNAME') !== 'null';
            $mailer->Username = (string) $this->config->get('mail', 'USERNAME', '');
            $mailer->Password = (string) $this->config->get('mail', 'PASSWORD', '');
            $encryption = (string) $this->config->get('mail', 'ENCRYPTION', '');

            if ($encryption !== '' && $encryption !== 'null') {
                $mailer->SMTPSecure = $encryption;
            }
        }

        [$fromAddress, $fromName] = $this->normalizeFrom();
        $mailer->setFrom($fromAddress, $fromName);
        $mailer->Subject = (string) ($message['subject'] ?? '');
        $mailer->Body = (string) ($message['html'] ?? $message['text'] ?? '');
        $mailer->AltBody = (string) ($message['text'] ?? strip_tags((string) ($message['html'] ?? '')));
        $mailer->isHTML(($message['html'] ?? null) !== null);

        foreach ((array) ($message['to'] ?? []) as $recipient) {
            $mailer->addAddress((string) $recipient['address'], (string) ($recipient['name'] ?? ''));
        }

        foreach ((array) ($message['cc'] ?? []) as $recipient) {
            $mailer->addCC((string) $recipient['address'], (string) ($recipient['name'] ?? ''));
        }

        foreach ((array) ($message['bcc'] ?? []) as $recipient) {
            $mailer->addBCC((string) $recipient['address'], (string) ($recipient['name'] ?? ''));
        }

        if (is_array($message['reply_to'] ?? null)) {
            $mailer->addReplyTo((string) $message['reply_to']['address'], (string) ($message['reply_to']['name'] ?? ''));
        } elseif (($replyTo = $this->normalizeReplyTo()) !== null) {
            $mailer->addReplyTo($replyTo['address'], $replyTo['name']);
        }

        return $mailer->send();
    }

    /**
     * @return array{0:string,1:string}
     */
    private function normalizeFrom(): array
    {
        $from = (string) $this->config->get('mail', 'FROM', 'no-reply@example.com');
        $configuredName = trim((string) $this->config->get('mail', 'FROM_NAME', ''));
        $name = $configuredName !== ''
            ? $configuredName
            : (string) $this->config->get('app', 'NAME', 'LangelerMVC');

        if ($this->match('/^"?(.*?)"?\s*<([^>]+)>$/', $from, $matches) === 1) {
            return [(string) $matches[2], $configuredName !== '' ? $configuredName : (string) $matches[1]];
        }

        if (str_contains($from, '@')) {
            return [$from, $name];
        }

        return ['no-reply@example.com', $name];
    }

    /**
     * @return array{address:string,name:string}|null
     */
    private function normalizeReplyTo(): ?array
    {
        $replyTo = trim((string) $this->config->get('mail', 'REPLY', $this->config->get('mail', 'REPLY_TO', '')));

        if ($replyTo === '') {
            return null;
        }

        $name = trim((string) $this->config->get('mail', 'FROM_NAME', $this->config->get('app', 'NAME', 'LangelerMVC')));

        if ($this->match('/^"?(.*?)"?\s*<([^>]+)>$/', $replyTo, $matches) === 1) {
            return [
                'address' => (string) $matches[2],
                'name' => trim((string) $matches[1]) !== '' ? (string) $matches[1] : $name,
            ];
        }

        return str_contains($replyTo, '@')
            ? ['address' => $replyTo, 'name' => $name]
            : null;
    }

    private function frameworkLogPath(string $file): string
    {
        return (defined('STORAGE_PATH') ? STORAGE_PATH : dirname(__DIR__, 4) . '/Storage') . '/Logs/' . ltrim($file, '/');
    }
}
