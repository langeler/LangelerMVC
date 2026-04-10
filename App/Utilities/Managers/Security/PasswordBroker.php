<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Security;

use App\Abstracts\Support\Mailable;
use App\Contracts\Auth\PasswordBrokerInterface;
use App\Core\Config;
use App\Exceptions\AuthException;
use App\Modules\UserModule\Repositories\UserAuthTokenRepository;
use App\Modules\UserModule\Repositories\UserRepository;
use App\Utilities\Managers\Data\CryptoManager;
use App\Utilities\Managers\Support\MailManager;
use App\Utilities\Managers\System\ErrorManager;
use Throwable;

class PasswordBroker implements PasswordBrokerInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly DatabaseUserProvider $provider,
        private readonly UserRepository $users,
        private readonly UserAuthTokenRepository $tokens,
        private readonly CryptoManager $cryptoManager,
        private readonly MailManager $mailManager,
        private readonly ErrorManager $errorManager
    ) {
    }

    public function sendResetLink(string $email): bool
    {
        $user = $this->provider->retrieveByCredentials(['email' => $email]);

        if ($user === null) {
            return false;
        }

        $token = $this->issueToken((int) $user->getAuthIdentifier(), 'password_reset', $this->resetExpiryMinutes());

        return $this->mailManager->send(new class($email, $token) extends Mailable {
            public function __construct(private readonly string $emailAddress, private readonly string $token)
            {
            }

            protected function build(): void
            {
                $this->to($this->emailAddress)
                    ->subject('Reset your LangelerMVC password')
                    ->text('Use this password reset token: ' . $this->token)
                    ->html('<p>Use this password reset token:</p><pre>' . htmlspecialchars($this->token, ENT_QUOTES, 'UTF-8') . '</pre>');
            }
        });
    }

    public function reset(string $email, string $token, string $password): bool
    {
        $user = $this->provider->retrieveByCredentials(['email' => $email]);

        if ($user === null) {
            return false;
        }

        $record = $this->consumeToken((int) $user->getAuthIdentifier(), 'password_reset', $token);

        if ($record === null) {
            return false;
        }

        $hash = $this->provider->hashValue($password);
        $this->users->updatePassword($user->getAuthIdentifier(), $hash);

        return true;
    }

    public function issueToken(int $userId, string $type, int $expiresMinutes): string
    {
        $token = bin2hex($this->cryptoManager->generateRandom('default', 32));
        $hash = $this->provider->hashValue($token);
        $expiresAt = gmdate('Y-m-d H:i:s', time() + ($expiresMinutes * 60));

        $this->tokens->revokeOutstanding($userId, $type);
        $this->tokens->issueToken($userId, $type, $hash, $expiresAt);

        return $token;
    }

    public function consumeToken(int $userId, string $type, string $token): ?array
    {
        foreach ($this->tokens->activeTokens($userId, $type) as $record) {
            $hash = (string) ($record['token_hash'] ?? '');

            if ($hash === '' || !$this->provider->verifyHash($hash, $token)) {
                continue;
            }

            $this->tokens->markUsed((int) $record['id']);

            return $record;
        }

        return null;
    }

    private function resetExpiryMinutes(): int
    {
        return max(1, (int) $this->config->get('auth', 'PASSWORD_RESET_EXPIRES', 60));
    }
}
