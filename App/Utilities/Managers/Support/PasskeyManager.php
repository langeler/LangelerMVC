<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Support;

use App\Contracts\Support\PasskeyDriverInterface;
use App\Contracts\Support\PasskeyManagerInterface;
use App\Core\Config;
use App\Core\Session;
use App\Drivers\Passkeys\TestingPasskeyDriver;
use App\Drivers\Passkeys\WebAuthnPasskeyDriver;
use App\Exceptions\AuthException;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ConversionTrait;
use App\Utilities\Traits\ErrorTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;
class PasskeyManager implements PasskeyManagerInterface
{
    use ArrayTrait, CheckerTrait, ConversionTrait, ErrorTrait, ManipulationTrait, TypeCheckerTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    private const REGISTRATION_KEY = 'auth.passkeys.registration';
    private const AUTHENTICATION_KEY = 'auth.passkeys.authentication';

    private ?PasskeyDriverInterface $driver = null;

    public function __construct(
        private readonly Config $config,
        private readonly Session $session,
        private readonly ErrorManager $errorManager
    ) {
    }

    public function driverName(): string
    {
        return $this->toLowerString((string) $this->config->get('auth', 'PASSKEY.DRIVER', 'webauthn'));
    }

    public function capabilities(): array
    {
        return [
            'drivers' => [
                'webauthn' => class_exists(\Webauthn\PublicKeyCredential::class),
                'testing' => true,
            ],
            'flows' => [
                'registration' => true,
                'authentication' => true,
            ],
            'storage' => [
                'session_challenges' => true,
            ],
            'resident_keys' => true,
            'passwordless' => true,
            'user_verification' => true,
        ];
    }

    public function supports(string $feature): bool
    {
        $resolved = $this->resolveCapability($feature);

        return match (true) {
            $this->isBool($resolved) => $resolved,
            $this->isString($resolved) => $resolved !== '',
            $this->isArray($resolved) => $resolved !== [],
            default => $resolved !== null,
        };
    }

    public function beginRegistration(
        int $userId,
        string $userName,
        string $displayName,
        array $excludeCredentials = [],
        array $context = []
    ): array {
        return $this->wrapInTry(function () use ($userId, $userName, $displayName, $excludeCredentials, $context): array {
            $payload = $this->driver()->beginRegistration([
                'challenge' => random_bytes($this->challengeBytes()),
                'timeout' => $this->timeout(),
                'rp_id' => $this->rpId(),
                'rp_name' => $this->rpName(),
                'user_name' => $userName,
                'user_handle' => $this->userHandle($userId),
                'display_name' => $displayName,
                'exclude_credentials' => $excludeCredentials,
                'user_verification' => $this->registrationUserVerification(),
                'resident_key' => $this->residentKey(),
                'attachment' => $this->authenticatorAttachment(),
                'attestation' => $this->attestationPreference(),
            ]);

            $record = [
                'options' => (array) ($payload['options'] ?? []),
                'expires_at' => time() + $this->challengeTtl(),
                'context' => array_merge($context, [
                    'user_id' => $userId,
                    'user_handle' => $this->userHandle($userId),
                ]),
            ];

            $this->storePending(self::REGISTRATION_KEY, $record);

            return [
                'driver' => $this->driverName(),
                'flow' => 'registration',
                'options' => $record['options'],
                'expiresAt' => gmdate('c', (int) $record['expires_at']),
            ];
        }, AuthException::class);
    }

    public function finishRegistration(array|string $credentialPayload): array
    {
        return $this->wrapInTry(function () use ($credentialPayload): array {
            $pending = $this->pending(self::REGISTRATION_KEY);
            $credential = $this->normalizeCredentialPayload($credentialPayload);

            $result = $this->driver()->finishRegistration($credential, [
                'options' => (array) ($pending['options'] ?? []),
                'allowed_origins' => $this->allowedOrigins(),
                'allow_subdomains' => $this->allowSubdomains(),
                'rp_id' => $this->rpId(),
                'user_handle' => (string) (($pending['context']['user_handle'] ?? '')),
            ]);

            $this->clearPending('registration');

            return [
                'driver' => $this->driverName(),
                'flow' => 'registration',
                'context' => (array) ($pending['context'] ?? []),
                'credential' => $result,
            ];
        }, AuthException::class);
    }

    public function beginAuthentication(array $allowedCredentials = [], array $context = []): array
    {
        return $this->wrapInTry(function () use ($allowedCredentials, $context): array {
            $payload = $this->driver()->beginAuthentication([
                'challenge' => random_bytes($this->challengeBytes()),
                'timeout' => $this->timeout(),
                'rp_id' => $this->rpId(),
                'allowed_credentials' => $allowedCredentials,
                'user_verification' => $this->authenticationUserVerification(),
            ]);

            $record = [
                'options' => (array) ($payload['options'] ?? []),
                'expires_at' => time() + $this->challengeTtl(),
                'context' => $context,
            ];

            $this->storePending(self::AUTHENTICATION_KEY, $record);

            return [
                'driver' => $this->driverName(),
                'flow' => 'authentication',
                'options' => $record['options'],
                'expiresAt' => gmdate('c', (int) $record['expires_at']),
            ];
        }, AuthException::class);
    }

    public function finishAuthentication(array|string $credentialPayload, array $storedCredential): array
    {
        return $this->wrapInTry(function () use ($credentialPayload, $storedCredential): array {
            $pending = $this->pending(self::AUTHENTICATION_KEY);
            $credential = $this->normalizeCredentialPayload($credentialPayload);

            $result = $this->driver()->finishAuthentication($credential, $storedCredential, [
                'options' => (array) ($pending['options'] ?? []),
                'allowed_origins' => $this->allowedOrigins(),
                'allow_subdomains' => $this->allowSubdomains(),
                'rp_id' => $this->rpId(),
            ]);

            $this->clearPending('authentication');

            return [
                'driver' => $this->driverName(),
                'flow' => 'authentication',
                'context' => (array) ($pending['context'] ?? []),
                'credential' => $result,
            ];
        }, AuthException::class);
    }

    public function extractCredentialId(array|string $credentialPayload): string
    {
        return $this->driver()->extractCredentialId($this->normalizeCredentialPayload($credentialPayload));
    }

    public function clearPending(?string $flow = null): void
    {
        $this->session->start();

        foreach (match ($flow) {
            'registration' => [self::REGISTRATION_KEY],
            'authentication' => [self::AUTHENTICATION_KEY],
            default => [self::REGISTRATION_KEY, self::AUTHENTICATION_KEY],
        } as $key) {
            if ($this->session->has($key)) {
                $this->session->forget($key);
            }
        }
    }

    private function driver(): PasskeyDriverInterface
    {
        if ($this->driver instanceof PasskeyDriverInterface) {
            return $this->driver;
        }

        $driver = match ($this->driverName()) {
            'testing' => new TestingPasskeyDriver(),
            default => new WebAuthnPasskeyDriver(),
        };

        if (!$this->supports('drivers.' . $driver->name())) {
            throw new AuthException(sprintf('Passkey driver [%s] is not supported by the current runtime.', $driver->name()));
        }

        $this->driver = $driver;

        return $this->driver;
    }

    /**
     * @param array<string, mixed>|string $credentialPayload
     * @return array<string, mixed>
     */
    private function normalizeCredentialPayload(array|string $credentialPayload): array
    {
        if ($this->isArray($credentialPayload)) {
            return $credentialPayload;
        }

        try {
            $decoded = $this->fromJson($credentialPayload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new AuthException('The submitted passkey payload is invalid.', 0, $exception);
        }

        if (!$this->isArray($decoded)) {
            throw new AuthException('The submitted passkey payload is invalid.');
        }

        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    private function pending(string $key): array
    {
        $this->session->start();
        $record = $this->session->get($key, []);

        if (!$this->isArray($record) || $record === []) {
            throw new AuthException('The passkey challenge could not be found. Start the flow again.');
        }

        $expiresAt = (int) ($record['expires_at'] ?? 0);

        if ($expiresAt < time()) {
            $this->session->forget($key);
            throw new AuthException('The passkey challenge has expired. Start the flow again.');
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function storePending(string $key, array $record): void
    {
        $this->session->start();
        $this->session->put($key, $record);
    }

    private function resolveCapability(string $feature): mixed
    {
        $value = $this->capabilities();

        foreach (explode('.', $this->trimString($feature)) as $segment) {
            if (!$this->isArray($value) || !array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    private function challengeBytes(): int
    {
        return max(16, (int) $this->config->get('auth', 'PASSKEY.CHALLENGE_BYTES', 32));
    }

    private function timeout(): int
    {
        return max(1000, (int) $this->config->get('auth', 'PASSKEY.TIMEOUT', 60000));
    }

    private function challengeTtl(): int
    {
        return max(30, (int) $this->config->get('auth', 'PASSKEY.CHALLENGE_TTL', 300));
    }

    private function rpId(): string
    {
        $configured = (string) $this->config->get('auth', 'PASSKEY.RP_ID', '');

        if ($this->trimString($configured) !== '') {
            return $this->trimString($configured);
        }

        $url = (string) $this->config->get('app', 'URL', '');
        $host = is_string(parse_url($url, PHP_URL_HOST)) ? (string) parse_url($url, PHP_URL_HOST) : '';

        return $host !== '' && !$this->isPlaceholderHost($host) ? $host : 'localhost';
    }

    private function rpName(): string
    {
        $configured = (string) $this->config->get('auth', 'PASSKEY.RP_NAME', '');

        return $this->trimString($configured) !== ''
            ? $this->trimString($configured)
            : (string) $this->config->get('app', 'NAME', 'LangelerMVC');
    }

    /**
     * @return list<string>
     */
    private function allowedOrigins(): array
    {
        $configured = $this->config->get('auth', 'PASSKEY.ORIGINS', []);
        $origins = [];

        if ($this->isString($configured) && $this->trimString($configured) !== '') {
            $origins[] = $this->trimString($configured);
        }

        if ($this->isArray($configured)) {
            foreach ($configured as $origin) {
                if ($this->isString($origin) && $this->trimString($origin) !== '') {
                    $origins[] = $this->trimString($origin);
                }
            }
        }

        $appUrl = (string) $this->config->get('app', 'URL', '');

        if ($this->trimString($appUrl) !== '' && !$this->isPlaceholderUrl($appUrl)) {
            $origins[] = $this->trimString($appUrl);
        }

        if ($origins === []) {
            $rpId = $this->rpId();
            $origins[] = $this->isInArray($rpId, ['localhost', '127.0.0.1'], true)
                ? 'http://' . $rpId
                : 'https://' . $rpId;
        }

        return array_values(array_unique($origins));
    }

    private function allowSubdomains(): bool
    {
        return (bool) $this->config->get('auth', 'PASSKEY.ALLOW_SUBDOMAINS', false);
    }

    private function authenticatorAttachment(): ?string
    {
        $value = (string) $this->config->get('auth', 'PASSKEY.ATTACHMENT', '');

        return $this->trimString($value) !== '' ? $this->trimString($value) : null;
    }

    private function residentKey(): ?string
    {
        $value = (string) $this->config->get('auth', 'PASSKEY.RESIDENT_KEY', 'preferred');

        return $this->trimString($value) !== '' ? $this->trimString($value) : null;
    }

    private function attestationPreference(): ?string
    {
        $value = (string) $this->config->get('auth', 'PASSKEY.ATTESTATION', 'none');

        return $this->trimString($value) !== '' ? $this->trimString($value) : null;
    }

    private function registrationUserVerification(): string
    {
        return (string) $this->config->get('auth', 'PASSKEY.REGISTRATION.USER_VERIFICATION', 'preferred');
    }

    private function authenticationUserVerification(): string
    {
        return (string) $this->config->get('auth', 'PASSKEY.AUTHENTICATION.USER_VERIFICATION', 'preferred');
    }

    private function userHandle(int $userId): string
    {
        return 'user:' . $userId;
    }

    private function isPlaceholderHost(string $host): bool
    {
        return $this->isInArray($this->toLowerString($host), ['your-domain.com', 'example.com'], true);
    }

    private function isPlaceholderUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $this->isString($host) && $this->isPlaceholderHost($host);
    }
}
