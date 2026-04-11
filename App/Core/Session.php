<?php

declare(strict_types=1);

namespace App\Core;

use App\Utilities\Managers\Data\CryptoManager;
use App\Utilities\Managers\Data\SessionManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\EncodingTrait;
use App\Utilities\Traits\ErrorTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;
use SessionHandler;

/**
 * Core session facade.
 *
 * This class intentionally owns only the framework-facing session store API:
 * reading, writing, flashing, invalidating, and CSRF/token helpers.
 *
 * Native PHP session runtime details are delegated to SessionManager so the
 * subsystem stays extendable and better aligned with SRP/SoC.
 */
class Session
{
    use ArrayTrait, CheckerTrait, EncodingTrait, ErrorTrait, ManipulationTrait, TypeCheckerTrait {
        ArrayTrait::push as private pushToArray;
        ManipulationTrait::toLower as private toLowerString;
    }

    private const INTERNAL_KEY = '__langelermvc_session';
    private const FLASH_NEW_KEY = 'new';
    private const FLASH_OLD_KEY = 'old';
    private const TOKEN_KEY = '_token';

    private SessionHandler $handler;
    private bool $started = false;
    private bool $ephemeral = false;
    private string $ephemeralId = '';

    /**
     * @var array<string, mixed>
     */
    private array $settings = [];

    public function __construct(
        private Config $config,
        private SessionManager $sessionManager,
        private CryptoManager $cryptoManager,
        private ErrorManager $errorManager
    ) {
        $this->settings = $this->sessionManager->normalizeConfiguration(
            (array) $this->config->get('session', null, [])
        );
        $this->handler = $this->sessionManager->createHandler($this->settings, cryptoManager: $this->cryptoManager);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function start(array $options = []): bool
    {
        return $this->wrapInTry(function () use ($options): bool {
            if ($this->isStarted()) {
                return true;
            }

            $this->sessionManager->assertSupportedConfiguration($this->settings);

            if ($this->shouldUseEphemeralStore()) {
                return $this->startEphemeralSession();
            }

            $this->sessionManager->registerHandler($this->handler, true);
            $this->sessionManager->applyRuntimeConfiguration($this->settings);
            $this->sessionManager->setCookieParameters($this->buildCookieOptions($options));

            $this->ephemeral = false;
            $this->started = $this->sessionManager->start($options);

            if ($this->started) {
                $this->initializeMetadata();
                $this->ageFlashData();
            }

            return $this->started;
        }, 'session');
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->sessionPayload();
    }

    /**
     * @param array<int, string> $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        $values = $this->all();
        $subset = [];

        foreach ($keys as $key) {
            if ($this->keyExists($values, $key)) {
                $subset[$key] = $values[$key];
            }
        }

        return $subset;
    }

    /**
     * @param array<int, string> $keys
     * @return array<string, mixed>
     */
    public function except(array $keys): array
    {
        $values = $this->all();

        foreach ($keys as $key) {
            unset($values[$key]);
        }

        return $values;
    }

    public function has(string $key): bool
    {
        return $this->keyExists($this->sessionPayload(), $key);
    }

    public function missing(string $key): bool
    {
        return !$this->has($key);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->sessionPayload();

        return $this->keyExists($values, $key) ? $values[$key] : $default;
    }

    public function put(string $key, mixed $value): void
    {
        $this->wrapInTry(function () use ($key, $value): void {
            $this->ensureStarted();
            $this->guardReservedKey($key);
            $_SESSION[$key] = $value;
        }, 'session');
    }

    /**
     * @param array<string, mixed> $values
     */
    public function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->put((string) $key, $value);
        }
    }

    /**
     * Replace visible session data while preserving internal metadata.
     *
     * @param array<string, mixed> $values
     */
    public function replace(array $values): void
    {
        $this->wrapInTry(function () use ($values): void {
            $this->ensureStarted();
            $metadata = $this->metadata();
            $_SESSION = [self::INTERNAL_KEY => $metadata];

            foreach ($values as $key => $value) {
                $this->guardReservedKey((string) $key);
                $_SESSION[(string) $key] = $value;
            }
        }, 'session');
    }

    public function push(string $key, mixed $value): void
    {
        $this->wrapInTry(function () use ($key, $value): void {
            $this->ensureStarted();
            $existing = $this->get($key, []);

            if (!$this->isArray($existing)) {
                $existing = [$existing];
            }

            $this->pushToArray($existing, $value);
            $_SESSION[$key] = $existing;
        }, 'session');
    }

    public function increment(string $key, int|float $amount = 1): int|float
    {
        return $this->adjustNumericValue($key, $amount);
    }

    public function decrement(string $key, int|float $amount = 1): int|float
    {
        return $this->adjustNumericValue($key, $amount * -1);
    }

    public function forget(string $key): void
    {
        $this->wrapInTry(function () use ($key): void {
            $this->ensureStarted();
            $this->guardReservedKey($key);
            unset($_SESSION[$key]);
            $this->unmarkFlashKey($key);
        }, 'session');
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->forget($key);

        return $value;
    }

    public function flush(): void
    {
        $this->wrapInTry(function (): void {
            $this->ensureStarted();
            $_SESSION = [];
            $this->initializeMetadata();
        }, 'session');
    }

    public function flash(string $key, mixed $value): void
    {
        $this->put($key, $value);
        $this->markFlashKey($key, self::FLASH_NEW_KEY);
    }

    public function now(string $key, mixed $value): void
    {
        $this->put($key, $value);
        $this->markFlashKey($key, self::FLASH_OLD_KEY);
    }

    public function reflash(): void
    {
        $this->keep($this->flashKeys(self::FLASH_OLD_KEY));
    }

    /**
     * @param string|array<int, string>|null $keys
     */
    public function keep(string|array|null $keys = null): void
    {
        $this->wrapInTry(function () use ($keys): void {
            $this->ensureStarted();
            $selection = match (true) {
                $keys === null => $this->flashKeys(self::FLASH_OLD_KEY),
                $this->isString($keys) => [$keys],
                default => $this->getValues((array) $keys),
            };

            $metadata = $this->metadata();
            $new = $this->flashKeys(self::FLASH_NEW_KEY);

            foreach ($selection as $key) {
                if (!$this->isString($key) || !$this->has($key)) {
                    continue;
                }

                $new[] = $key;
            }

            $metadata['flash'][self::FLASH_NEW_KEY] = array_values($this->unique($new));
            $metadata['flash'][self::FLASH_OLD_KEY] = array_values(
                array_diff($this->flashKeys(self::FLASH_OLD_KEY), $metadata['flash'][self::FLASH_NEW_KEY])
            );

            $this->writeMetadata($metadata);
        }, 'session');
    }

    public function token(): string
    {
        $this->ensureStarted();

        $token = $this->get(self::TOKEN_KEY);

        return $this->isString($token) && $token !== ''
            ? $token
            : $this->regenerateToken();
    }

    public function regenerateToken(): string
    {
        $this->ensureStarted();
        $token = $this->generateTokenValue();
        $_SESSION[self::TOKEN_KEY] = $token;

        return $token;
    }

    public function regenerate(bool $deleteOldSession = false): bool
    {
        return $this->wrapInTry(function () use ($deleteOldSession): bool {
            $this->ensureStarted();

            if ($this->ephemeral) {
                $this->ephemeralId = $this->generateEphemeralId();

                return true;
            }

            return $this->sessionManager->regenerateId($deleteOldSession);
        }, 'session');
    }

    public function close(): bool
    {
        return $this->wrapInTry(function (): bool {
            if ($this->ephemeral) {
                $this->started = false;

                return true;
            }

            $closed = $this->sessionManager->close();

            if ($closed) {
                $this->started = false;
            }

            return $closed;
        }, 'session');
    }

    public function invalidate(): bool
    {
        return $this->wrapInTry(function (): bool {
            if ($this->ephemeral) {
                $_SESSION = [];
                $this->started = false;
                $this->ephemeralId = '';

                return true;
            }

            if (!$this->sessionManager->isActive()) {
                $_SESSION = [];
                $this->started = false;

                return true;
            }

            $_SESSION = [];

            if ($this->usesCookies()) {
                $this->sessionManager->expireCookie();
            }

            $destroyed = $this->sessionManager->destroy();

            if ($destroyed) {
                $this->started = false;
            }

            return $destroyed;
        }, 'session');
    }

    public function isStarted(): bool
    {
        return $this->sessionManager->isActive() || ($this->ephemeral && $this->started);
    }

    public function isEphemeral(): bool
    {
        return $this->ephemeral;
    }

    public function id(): string
    {
        return $this->ephemeral
            ? $this->ephemeralId
            : $this->sessionManager->getId();
    }

    public function name(): string
    {
        return $this->ephemeral
            ? (string) ($this->settings['NAME'] ?? 'langelermvc_session')
            : $this->sessionManager->getName();
    }

    private function ensureStarted(): void
    {
        if (!$this->isStarted()) {
            $this->start();
        }
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function buildCookieOptions(array $overrides = []): array
    {
        $lifetimeMinutes = (int) ($this->settings['LIFETIME'] ?? 120);
        $expireOnClose = (bool) ($this->settings['EXPIRE_ON_CLOSE'] ?? false);
        $cookie = (array) ($this->settings['COOKIE'] ?? []);

        return [
            'lifetime' => $expireOnClose ? 0 : $this->resolveIntOption($overrides, ['lifetime', 'cookie_lifetime'], $lifetimeMinutes * 60),
            'path' => $this->resolveStringOption($overrides, ['path', 'cookie_path'], (string) ($cookie['PATH'] ?? '/'), '/'),
            'domain' => $this->resolveStringOption($overrides, ['domain', 'cookie_domain'], (string) ($cookie['DOMAIN'] ?? ''), ''),
            'secure' => $this->resolveBoolOption($overrides, ['secure', 'cookie_secure'], (bool) ($cookie['SECURE'] ?? true), true),
            'httponly' => $this->resolveBoolOption($overrides, ['httponly', 'cookie_httponly'], (bool) ($cookie['HTTPONLY'] ?? true), true),
            'samesite' => $this->normalizeSameSite(
                $this->resolveOption($overrides, ['samesite', 'cookie_samesite'], (string) ($cookie['SAME_SITE'] ?? 'Lax'))
            ),
        ];
    }

    private function usesCookies(): bool
    {
        return (bool) (($this->settings['NATIVE']['USE_COOKIES'] ?? true) === true);
    }

    private function shouldUseEphemeralStore(): bool
    {
        return $this->isInArray(PHP_SAPI, ['cli', 'phpdbg'], true);
    }

    private function startEphemeralSession(): bool
    {
        if (!isset($_SESSION) || !$this->isArray($_SESSION)) {
            $_SESSION = [];
        }

        $this->ephemeral = true;
        $this->started = true;
        $this->ephemeralId = $this->ephemeralId !== '' ? $this->ephemeralId : $this->generateEphemeralId();

        $this->initializeMetadata();
        $this->ageFlashData();

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionPayload(): array
    {
        $values = isset($_SESSION) && $this->isArray($_SESSION) ? $_SESSION : [];
        unset($values[self::INTERNAL_KEY]);

        return $values;
    }

    private function initializeMetadata(): void
    {
        $metadata = $this->metadata();

        if (!isset($metadata['flash']) || !$this->isArray($metadata['flash'])) {
            $metadata['flash'] = [];
        }

        if (!isset($metadata['flash'][self::FLASH_NEW_KEY]) || !$this->isArray($metadata['flash'][self::FLASH_NEW_KEY])) {
            $metadata['flash'][self::FLASH_NEW_KEY] = [];
        }

        if (!isset($metadata['flash'][self::FLASH_OLD_KEY]) || !$this->isArray($metadata['flash'][self::FLASH_OLD_KEY])) {
            $metadata['flash'][self::FLASH_OLD_KEY] = [];
        }

        $this->writeMetadata($metadata);
    }

    /**
     * @return array<string, mixed>
     */
    private function metadata(): array
    {
        return isset($_SESSION[self::INTERNAL_KEY]) && $this->isArray($_SESSION[self::INTERNAL_KEY])
            ? $_SESSION[self::INTERNAL_KEY]
            : [];
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function writeMetadata(array $metadata): void
    {
        $_SESSION[self::INTERNAL_KEY] = $metadata;
    }

    private function ageFlashData(): void
    {
        $metadata = $this->metadata();
        $old = $this->flashKeys(self::FLASH_OLD_KEY);

        foreach ($old as $key) {
            unset($_SESSION[$key]);
        }

        $metadata['flash'][self::FLASH_OLD_KEY] = $this->flashKeys(self::FLASH_NEW_KEY);
        $metadata['flash'][self::FLASH_NEW_KEY] = [];

        $this->writeMetadata($metadata);
    }

    private function markFlashKey(string $key, string $bucket): void
    {
        $this->wrapInTry(function () use ($key, $bucket): void {
            $this->ensureStarted();
            $metadata = $this->metadata();
            $buckets = [self::FLASH_NEW_KEY, self::FLASH_OLD_KEY];

            foreach ($buckets as $name) {
                $metadata['flash'][$name] = array_values(
                    array_filter(
                        $this->flashKeys($name, $metadata),
                        fn(string $existing): bool => $existing !== $key
                    )
                );
            }

            $metadata['flash'][$bucket][] = $key;
            $metadata['flash'][$bucket] = array_values($this->unique($metadata['flash'][$bucket]));

            $this->writeMetadata($metadata);
        }, 'session');
    }

    private function unmarkFlashKey(string $key): void
    {
        $metadata = $this->metadata();

        foreach ([self::FLASH_NEW_KEY, self::FLASH_OLD_KEY] as $bucket) {
            $metadata['flash'][$bucket] = array_values(
                array_filter(
                    $this->flashKeys($bucket, $metadata),
                    fn(string $existing): bool => $existing !== $key
                )
            );
        }

        $this->writeMetadata($metadata);
    }

    /**
     * @param array<string, mixed>|null $metadata
     * @return array<int, string>
     */
    private function flashKeys(string $bucket, ?array $metadata = null): array
    {
        $source = $metadata ?? $this->metadata();
        $keys = (array) (($source['flash'] ?? [])[$bucket] ?? []);

        return array_values(
            array_filter(
                $this->getValues($keys),
                fn(mixed $key): bool => $this->isString($key) && $key !== ''
            )
        );
    }

    private function guardReservedKey(string $key): void
    {
        if ($key === self::INTERNAL_KEY) {
            throw $this->errorManager->resolveException(
                'session',
                'The internal session metadata key is reserved and cannot be overwritten.'
            );
        }
    }

    private function adjustNumericValue(string $key, int|float $amount): int|float
    {
        return $this->wrapInTry(function () use ($key, $amount): int|float {
            $this->ensureStarted();
            $current = $this->get($key, 0);

            if (!$this->isNumeric($current)) {
                throw $this->errorManager->resolveException(
                    'session',
                    sprintf('Session value for "%s" is not numeric and cannot be adjusted.', $key)
                );
            }

            $updated = $current + $amount;
            $_SESSION[$key] = $updated;

            return $updated;
        }, 'session');
    }

    private function generateTokenValue(): string
    {
        try {
            $bytes = $this->cryptoManager->generateRandom('custom', 32);

            if ($this->isString($bytes) && $bytes !== '') {
                return bin2hex($bytes);
            }
        } catch (\Throwable) {
            // Fall back to PHP native secure randomness below.
        }

        return bin2hex(random_bytes(32));
    }

    private function generateEphemeralId(): string
    {
        return bin2hex(random_bytes(20));
    }

    /**
     * @param array<string, mixed> $options
     * @param array<int, string> $keys
     */
    private function resolveOption(array $options, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            if ($this->keyExists($options, $key)) {
                return $options[$key];
            }
        }

        return $default;
    }

    /**
     * @param array<string, mixed> $options
     * @param array<int, string> $keys
     */
    private function resolveBoolOption(array $options, array $keys, bool $default, bool $fallback = false): bool
    {
        $value = $this->resolveOption($options, $keys, $default);

        if ($this->isBool($value)) {
            return $value;
        }

        if ($this->isInt($value) || $this->isFloat($value)) {
            return (int) $value !== 0;
        }

        if ($this->isString($value)) {
            return match ($this->toLowerString($this->trimString($value))) {
                '1', 'true', 'yes', 'on' => true,
                '0', 'false', 'no', 'off', '' => false,
                default => $default,
            };
        }

        return $fallback;
    }

    /**
     * @param array<string, mixed> $options
     * @param array<int, string> $keys
     */
    private function resolveIntOption(array $options, array $keys, int $default): int
    {
        $value = $this->resolveOption($options, $keys, $default);

        return $this->isNumeric($value) ? (int) $value : $default;
    }

    /**
     * @param array<string, mixed> $options
     * @param array<int, string> $keys
     */
    private function resolveStringOption(array $options, array $keys, string $default, string $fallback = ''): string
    {
        $value = $this->resolveOption($options, $keys, $default);

        return $this->isScalar($value)
            ? $this->trimString((string) $value)
            : $fallback;
    }

    private function normalizeSameSite(mixed $value): string
    {
        return match ($this->toLowerString($this->isScalar($value) ? $this->trimString((string) $value) : 'lax')) {
            'strict' => 'Strict',
            'none' => 'None',
            default => 'Lax',
        };
    }
}
