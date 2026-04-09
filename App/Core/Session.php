<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use SessionHandler;

use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\SessionManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Traits\ApplicationPathTrait;
use App\Utilities\Traits\Filters\FiltrationTrait;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Traits\{
    ArrayTrait,
    CheckerTrait,
    ErrorTrait,
    ManipulationTrait,
    TypeCheckerTrait
};

/**
 * Core session facade.
 *
 * Bridges runtime configuration and the low-level SessionManager into a focused
 * application-facing API for starting, reading, mutating, and invalidating the
 * active PHP session.
 */
class Session
{
    use ApplicationPathTrait;
    use ArrayTrait, ManipulationTrait, PatternTrait {
        ManipulationTrait::toLower as private toLowerString;
        PatternTrait::match as private matchPattern;
    }
    use ErrorTrait, TypeCheckerTrait, CheckerTrait, FiltrationTrait;

    private SessionHandler $handler;
    private bool $started = false;
    private bool $ephemeral = false;

    public function __construct(
        private Config $config,
        private FileManager $fileManager,
        private SessionManager $sessionManager,
        private ErrorManager $errorManager
    ) {
        $this->handler = $this->sessionManager->createHandler();
    }

    /**
     * Start the PHP session using configured cookie and save-handler settings.
     *
     * @param array<string, mixed> $options
     * @return bool
     */
    public function start(array $options = []): bool
    {
        return $this->wrapInTry(function () use ($options): bool {
            $this->guardSupportedDriver();

            if ($this->isStarted()) {
                return true;
            }

            if ($this->shouldUseEphemeralStore()) {
                return $this->startEphemeralSession();
            }

            if (headers_sent($file, $line)) {
                $location = $file !== '' ? sprintf(' (%s:%d)', $file, $line) : '';

                throw new RuntimeException(
                    sprintf('Session cannot be started after headers have been sent%s.', $location)
                );
            }

            $this->sessionManager->registerHandler($this->handler, true);

            $savePath = $this->resolveSavePath();

            if ($savePath !== null) {
                session_save_path($savePath);
            }

            session_set_cookie_params($this->buildCookieOptions($options));

            $this->ephemeral = false;
            $this->started = session_start($options);

            return $this->started;
        }, 'runtime');
    }

    /**
     * Retrieve all current session attributes.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->wrapInTry(
            fn(): array => isset($_SESSION) && $this->isArray($_SESSION) ? $_SESSION : [],
            'runtime'
        );
    }

    /**
     * Determine whether the active session contains the given key.
     */
    public function has(string $key): bool
    {
        return $this->keyExists($this->all(), $key);
    }

    /**
     * Retrieve a session attribute.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->all();

        return $this->keyExists($values, $key) ? $values[$key] : $default;
    }

    /**
     * Store a value in the active session.
     */
    public function put(string $key, mixed $value): void
    {
        $this->wrapInTry(function () use ($key, $value): void {
            $this->ensureStarted();
            $_SESSION[$key] = $value;
        }, 'runtime');
    }

    /**
     * Remove a value from the active session.
     */
    public function forget(string $key): void
    {
        $this->wrapInTry(function () use ($key): void {
            $this->ensureStarted();
            unset($_SESSION[$key]);
        }, 'runtime');
    }

    /**
     * Regenerate the current session identifier.
     */
    public function regenerate(bool $deleteOldSession = false): bool
    {
        return $this->wrapInTry(function () use ($deleteOldSession): bool {
            $this->ensureStarted();

            if ($this->ephemeral) {
                return true;
            }

            return session_regenerate_id($deleteOldSession);
        }, 'runtime');
    }

    /**
     * Close the active session for writing.
     */
    public function close(): bool
    {
        return $this->wrapInTry(function (): bool {
            if ($this->ephemeral) {
                $this->started = false;

                return true;
            }

            $closed = session_status() === PHP_SESSION_ACTIVE ? session_write_close() : true;

            if ($closed) {
                $this->started = false;
            }

            return $closed;
        }, 'runtime');
    }

    /**
     * Invalidate the active session and its cookie.
     */
    public function invalidate(): bool
    {
        return $this->wrapInTry(function (): bool {
            $_SESSION = [];

            if ($this->ephemeral) {
                $this->started = false;

                return true;
            }

            if (session_status() !== PHP_SESSION_ACTIVE) {
                return true;
            }

            if ($this->normalizeBool(ini_get('session.use_cookies'), true) && !headers_sent()) {
                $params = session_get_cookie_params();

                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'] ?: '/',
                    $params['domain'] ?: '',
                    (bool) $params['secure'],
                    (bool) $params['httponly']
                );
            }

            $destroyed = session_destroy();

            if ($destroyed) {
                $this->started = false;
            }

            return $destroyed;
        }, 'runtime');
    }

    /**
     * Ensure a session has been started before mutation.
     */
    private function ensureStarted(): void
    {
        if (!$this->isStarted()) {
            $this->start();
        }
    }

    /**
     * Build cookie configuration from runtime config and method overrides.
     *
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function buildCookieOptions(array $overrides = []): array
    {
        $lifetime = $this->normalizeInt(
            $this->config->get('session', 'lifetime', 120),
            120
        );

        return [
            'lifetime' => $this->normalizeInt(
                $this->resolveOption($overrides, ['lifetime', 'cookie_lifetime'], $lifetime * 60),
                $lifetime * 60
            ),
            'path' => $this->normalizeString(
                $this->resolveOption($overrides, ['path', 'cookie_path'], '/'),
                '/'
            ),
            'domain' => $this->normalizeString(
                $this->resolveOption($overrides, ['domain', 'cookie_domain'], ''),
                ''
            ),
            'secure' => $this->normalizeBool(
                $this->resolveOption(
                    $overrides,
                    ['secure', 'cookie_secure'],
                    $this->config->get('session', 'secure.cookie', false)
                ),
                false
            ),
            'httponly' => $this->normalizeBool(
                $this->resolveOption(
                    $overrides,
                    ['httponly', 'cookie_httponly'],
                    $this->config->get('session', 'httponly.cookie', true)
                ),
                true
            ),
            'samesite' => $this->normalizeSameSite(
                $this->resolveOption(
                    $overrides,
                    ['samesite', 'cookie_samesite'],
                    $this->config->get('session', 'same.site', 'Lax')
                )
            ),
        ];
    }

    private function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE || ($this->ephemeral && $this->started);
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

        return true;
    }

    private function guardSupportedDriver(): void
    {
        $driver = $this->toLowerString($this->normalizeString(
            $this->config->get('session', 'driver', 'native'),
            'native'
        ));

        if ($driver !== 'native') {
            throw new RuntimeException(
                sprintf('Session driver "%s" is not supported by the core session service.', $driver)
            );
        }
    }

    private function resolveSavePath(): ?string
    {
        $configuredPath = $this->normalizeString(
            $this->config->get('session', 'save.path', $this->config->get('session', 'save', '')),
            ''
        );

        if ($configuredPath === '') {
            return null;
        }

        $path = $this->isAbsolutePath($configuredPath)
            ? $configuredPath
            : $this->frameworkBasePath() . DIRECTORY_SEPARATOR . $configuredPath;

        $normalizedPath = $this->fileManager->normalizePath($path);

        if ($this->fileManager->isDirectory($normalizedPath)) {
            return $normalizedPath;
        }

        if ($this->fileManager->createDirectory($normalizedPath, 0777, true)) {
            return $normalizedPath;
        }

        throw new RuntimeException(
            sprintf('Configured session save path is not writable: %s', $normalizedPath)
        );
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

    private function normalizeBool(mixed $value, bool $default = false): bool
    {
        if ($this->isBool($value)) {
            return $value;
        }

        if ($this->isString($value)) {
            $normalized = $this->var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            return $normalized ?? $default;
        }

        if ($this->isInt($value) || $this->isFloat($value)) {
            return (bool) $value;
        }

        return $default;
    }

    private function normalizeInt(mixed $value, int $default): int
    {
        return $this->isNumeric($value) ? (int) $value : $default;
    }

    private function normalizeString(mixed $value, string $default = ''): string
    {
        return $this->isScalar($value) ? $this->trimString((string) $value) : $default;
    }

    private function normalizeSameSite(mixed $value): string
    {
        $normalized = $this->toLowerString($this->normalizeString($value, 'lax'));

        return match ($normalized) {
            'strict' => 'Strict',
            'none' => 'None',
            default => 'Lax',
        };
    }

    private function isAbsolutePath(string $path): bool
    {
        return $this->startsWith($path, DIRECTORY_SEPARATOR)
            || $this->matchPattern('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }
}
