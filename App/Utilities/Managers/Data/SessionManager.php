<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Data;

use App\Contracts\Session\SessionDriverInterface;
use App\Core\Database;
use App\Drivers\Session\DatabaseSessionDriver;
use App\Drivers\Session\FileSessionDriver;
use App\Drivers\Session\RedisSessionDriver;
use App\Exceptions\SessionException;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Traits\ApplicationPathTrait;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ErrorTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;
use SessionHandler;

/**
 * Framework-aligned runtime manager for PHP session handling.
 *
 * Responsibilities:
 * - normalize raw session configuration into one canonical structure
 * - expose runtime capabilities and supported driver information
 * - mediate all low-level PHP session runtime calls
 * - keep native session behavior out of the high-level Session facade
 */
class SessionManager
{
    use ApplicationPathTrait;
    use ArrayTrait, CheckerTrait, ErrorTrait, ManipulationTrait, TypeCheckerTrait;

    public function __construct(
        private readonly FileManager $fileManager,
        private readonly ErrorManager $errorManager,
        private readonly ?Database $database = null
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public function createHandler(array $config = [], ?SessionHandler $handler = null): SessionHandler
    {
        if ($handler instanceof SessionHandler) {
            return $handler;
        }

        $normalized = $this->normalizeConfiguration($config);

        return match ($normalized['DRIVER']) {
            'file' => new FileSessionDriver(
                $this->fileManager,
                $this->resolveSavePath($normalized) ?? $this->frameworkStoragePath('Sessions')
            ),
            'database' => new DatabaseSessionDriver(
                $this->database ?? throw new SessionException('Database session driver requires the framework Database service.'),
                $normalized['DATABASE']['TABLE']
            ),
            'redis' => new RedisSessionDriver([
                'host' => $normalized['REDIS']['HOST'],
                'port' => $normalized['REDIS']['PORT'],
                'timeout' => $normalized['REDIS']['TIMEOUT'],
                'password' => $normalized['REDIS']['PASSWORD'],
                'database' => $normalized['REDIS']['DATABASE'],
                'prefix' => $normalized['REDIS']['PREFIX'],
                'ttl' => $normalized['GC']['MAX_LIFETIME'],
            ]),
            default => new SessionHandler(),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function capabilities(): array
    {
        return [
            'runtime' => [
                'native' => true,
                'cli_ephemeral' => $this->isInArray(PHP_SAPI, ['cli', 'phpdbg'], true),
            ],
            'drivers' => [
                'native' => true,
                'file' => true,
                'database' => $this->database instanceof Database,
                'redis' => class_exists(\Redis::class),
            ],
            'handlers' => [
                'files' => true,
                'redis' => class_exists(\Redis::class),
                'memcached' => class_exists(\Memcached::class),
            ],
            'cookies' => true,
            'strict_mode' => true,
            'csrf_token' => true,
            'flash_data' => true,
        ];
    }

    public function supports(string $feature): bool
    {
        $resolved = $this->resolveCapability($feature);

        return match (true) {
            $this->isBool($resolved) => $resolved,
            $this->isArray($resolved) => !$this->isEmpty($resolved),
            $this->isString($resolved) => $resolved !== '',
            default => $resolved !== null,
        };
    }

    /**
     * Normalize raw config from file/environment into a canonical session map.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public function normalizeConfiguration(array $config): array
    {
        return [
            'DRIVER' => $this->normalizeDriverName((string) $this->resolveConfigPath($config, [['DRIVER']], 'native')),
            'NAME' => $this->normalizeStringValue(
                $this->resolveConfigPath($config, [['NAME']], 'langelermvc_session'),
                'langelermvc_session'
            ),
            'LIFETIME' => $this->normalizePositiveInt(
                $this->resolveConfigPath($config, [['LIFETIME']], 120),
                120
            ),
            'EXPIRE_ON_CLOSE' => $this->normalizeBoolValue(
                $this->resolveConfigPath($config, [['EXPIRE_ON_CLOSE']], false),
                false
            ),
            'SAVE' => [
                'PATH' => $this->normalizeStringValue(
                    $this->resolveConfigPath($config, [['SAVE', 'PATH'], ['SAVE']], 'Storage/Sessions'),
                    'Storage/Sessions'
                ),
            ],
            'COOKIE' => [
                'PATH' => $this->normalizeStringValue(
                    $this->resolveConfigPath($config, [['COOKIE', 'PATH']], '/'),
                    '/'
                ),
                'DOMAIN' => $this->normalizeStringValue(
                    $this->resolveConfigPath($config, [['COOKIE', 'DOMAIN']], ''),
                    ''
                ),
                'SECURE' => $this->normalizeBoolValue(
                    $this->resolveConfigPath($config, [['COOKIE', 'SECURE'], ['SECURE']], true),
                    true
                ),
                'HTTPONLY' => $this->normalizeBoolValue(
                    $this->resolveConfigPath($config, [['COOKIE', 'HTTPONLY'], ['HTTPONLY']], true),
                    true
                ),
                'SAME_SITE' => $this->normalizeSameSite(
                    $this->resolveConfigPath($config, [['COOKIE', 'SAME_SITE'], ['SAME']], 'Lax')
                ),
            ],
            'GC' => [
                'PROBABILITY' => $this->normalizePositiveInt(
                    $this->resolveConfigPath($config, [['GC', 'PROBABILITY']], 1),
                    1
                ),
                'DIVISOR' => $this->normalizePositiveInt(
                    $this->resolveConfigPath($config, [['GC', 'DIVISOR']], 100),
                    100
                ),
                'MAX_LIFETIME' => $this->normalizePositiveInt(
                    $this->resolveConfigPath($config, [['GC', 'MAX_LIFETIME'], ['GC']], 1440),
                    1440
                ),
            ],
            'NATIVE' => [
                'HANDLER' => $this->normalizeHandlerName(
                    (string) $this->resolveConfigPath($config, [['NATIVE', 'HANDLER'], ['NATIVE']], 'files')
                ),
                'STRICT_MODE' => $this->normalizeBoolValue(
                    $this->resolveConfigPath($config, [['NATIVE', 'STRICT_MODE']], true),
                    true
                ),
                'USE_COOKIES' => $this->normalizeBoolValue(
                    $this->resolveConfigPath($config, [['NATIVE', 'USE_COOKIES']], true),
                    true
                ),
                'USE_ONLY_COOKIES' => $this->normalizeBoolValue(
                    $this->resolveConfigPath($config, [['NATIVE', 'USE_ONLY_COOKIES']], true),
                    true
                ),
                'SID_LENGTH' => $this->normalizePositiveInt(
                    $this->resolveConfigPath($config, [['NATIVE', 'SID_LENGTH']], 48),
                    48
                ),
            ],
            'DATABASE' => [
                'TABLE' => $this->normalizeStringValue(
                    $this->resolveConfigPath($config, [['DATABASE', 'TABLE']], 'framework_sessions'),
                    'framework_sessions'
                ),
            ],
            'REDIS' => [
                'HOST' => $this->normalizeStringValue(
                    $this->resolveConfigPath($config, [['REDIS', 'HOST']], '127.0.0.1'),
                    '127.0.0.1'
                ),
                'PORT' => $this->normalizePositiveInt(
                    $this->resolveConfigPath($config, [['REDIS', 'PORT']], 6379),
                    6379
                ),
                'TIMEOUT' => (float) $this->resolveConfigPath($config, [['REDIS', 'TIMEOUT']], 0.0),
                'PASSWORD' => $this->normalizeStringValue(
                    $this->resolveConfigPath($config, [['REDIS', 'PASSWORD']], ''),
                    ''
                ),
                'DATABASE' => $this->normalizePositiveInt(
                    $this->resolveConfigPath($config, [['REDIS', 'DATABASE']], 0),
                    1
                ) - 1,
                'PREFIX' => $this->normalizeStringValue(
                    $this->resolveConfigPath($config, [['REDIS', 'PREFIX']], 'langelermvc_session'),
                    'langelermvc_session'
                ),
            ],
            'LEGACY' => [
                'ENCRYPT' => $this->normalizeBoolValue(
                    $this->resolveConfigPath($config, [['ENCRYPT']], false),
                    false
                ),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    public function assertSupportedConfiguration(array $config): void
    {
        $normalized = $this->normalizeConfiguration($config);
        $driver = $normalized['DRIVER'];
        $handler = $normalized['NATIVE']['HANDLER'];

        if (!$this->isInArray($driver, ['native', 'file', 'database', 'redis'], true)) {
            throw new SessionException(
                sprintf('Session driver "%s" is not supported by the current session subsystem.', $driver)
            );
        }

        if ($driver === 'native' && $handler !== 'files') {
            throw new SessionException(
                sprintf('Native session handler "%s" is not supported by the current session subsystem.', $handler)
            );
        }

        if ($driver === 'database' && !$this->supports('drivers.database')) {
            throw new SessionException('Database session driver requires the framework Database service.');
        }

        if ($driver === 'redis' && !$this->supports('drivers.redis')) {
            throw new SessionException('Redis session driver requires the ext-redis PHP extension.');
        }

        if ($normalized['LEGACY']['ENCRYPT']) {
            throw new SessionException(
                'Session encryption is not implemented in the current session subsystem. Use the crypto layer explicitly where needed.'
            );
        }
    }

    /**
     * Apply native PHP session runtime configuration before session start.
     *
     * @param array<string, mixed> $config
     * @return string|null
     */
    public function applyRuntimeConfiguration(array $config): ?string
    {
        return $this->wrapInTry(function () use ($config): ?string {
            $normalized = $this->normalizeConfiguration($config);

            $this->assertSupportedConfiguration($normalized);
            $this->assertCanMutateRuntime();

            $this->setName($normalized['NAME']);
            $this->setIniOption('session.use_strict_mode', $normalized['NATIVE']['STRICT_MODE'] ? '1' : '0');
            $this->setIniOption('session.use_cookies', $normalized['NATIVE']['USE_COOKIES'] ? '1' : '0');
            $this->setIniOption('session.use_only_cookies', $normalized['NATIVE']['USE_ONLY_COOKIES'] ? '1' : '0');
            $this->setIniOption('session.gc_probability', (string) $normalized['GC']['PROBABILITY']);
            $this->setIniOption('session.gc_divisor', (string) $normalized['GC']['DIVISOR']);
            $this->setIniOption('session.gc_maxlifetime', (string) $normalized['GC']['MAX_LIFETIME']);
            $this->setIniOption(
                'session.cookie_lifetime',
                $normalized['EXPIRE_ON_CLOSE'] ? '0' : (string) ($normalized['LIFETIME'] * 60)
            );

            if ($normalized['NATIVE']['SID_LENGTH'] > 0) {
                $this->setIniOption('session.sid_length', (string) $normalized['NATIVE']['SID_LENGTH']);
            }

            if ($normalized['DRIVER'] === 'native') {
                $this->setIniOption('session.save_handler', $normalized['NATIVE']['HANDLER']);
            }

            $savePath = $normalized['DRIVER'] === 'native'
                ? $this->resolveSavePath($normalized)
                : null;

            if ($savePath !== null) {
                session_save_path($savePath);
            }

            return $savePath;
        }, 'session');
    }

    public function registerHandler(SessionHandler $handler, bool $registerShutdown = true): bool
    {
        return $this->wrapInTry(function () use ($handler, $registerShutdown): bool {
            if (!$this->requiresCustomRegistration($handler)) {
                return true;
            }

            $this->assertCanMutateRuntime();

            return session_set_save_handler($handler, $registerShutdown);
        }, 'session');
    }

    public function requiresCustomRegistration(SessionHandler $handler): bool
    {
        return $handler::class !== SessionHandler::class || $handler instanceof SessionDriverInterface;
    }

    public function start(array $options = []): bool
    {
        return $this->wrapInTry(function () use ($options): bool {
            if ($this->isActive()) {
                return true;
            }

            return session_start($options);
        }, 'session');
    }

    public function regenerateId(bool $deleteOldSession = false): bool
    {
        return $this->wrapInTry(
            fn(): bool => session_regenerate_id($deleteOldSession),
            'session'
        );
    }

    public function close(): bool
    {
        return $this->wrapInTry(
            fn(): bool => $this->isActive() ? session_write_close() : true,
            'session'
        );
    }

    public function destroy(): bool
    {
        return $this->wrapInTry(
            fn(): bool => $this->isActive() ? session_destroy() : true,
            'session'
        );
    }

    /**
     * @param array<string, mixed> $params
     */
    public function setCookieParameters(array $params): void
    {
        $this->wrapInTry(function () use ($params): void {
            $this->assertCanMutateRuntime();
            session_set_cookie_params($params);
        }, 'session');
    }

    public function expireCookie(): void
    {
        $this->wrapInTry(function (): void {
            if (headers_sent()) {
                return;
            }

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
        }, 'session');
    }

    public function status(): int
    {
        return session_status();
    }

    public function isActive(): bool
    {
        return $this->status() === PHP_SESSION_ACTIVE;
    }

    public function getId(): string
    {
        return session_id();
    }

    public function setId(string $id): void
    {
        $this->wrapInTry(function () use ($id): void {
            $this->assertCanMutateRuntime();
            session_id($id);
        }, 'session');
    }

    public function getName(): string
    {
        return session_name();
    }

    public function setName(string $name): void
    {
        $this->wrapInTry(function () use ($name): void {
            $this->assertCanMutateRuntime();

            if ($name === '') {
                throw new SessionException('Session name must be a non-empty string.');
            }

            session_name($name);
        }, 'session');
    }

    public function read(SessionHandler $handler, string $sessionId): string
    {
        return $this->wrapInTry(
            fn(): string => $handler->read($sessionId),
            'session'
        );
    }

    public function write(SessionHandler $handler, string $sessionId, string $data): bool
    {
        return $this->wrapInTry(
            fn(): bool => $handler->write($sessionId, $data),
            'session'
        );
    }

    public function destroyById(SessionHandler $handler, string $sessionId): bool
    {
        return $this->wrapInTry(
            fn(): bool => $handler->destroy($sessionId),
            'session'
        );
    }

    public function cleanup(SessionHandler $handler, int $maxLifetime): bool
    {
        return $this->wrapInTry(
            fn(): bool => $handler->gc($maxLifetime),
            'session'
        );
    }

    public function open(SessionHandler $handler, string $savePath, string $sessionName): bool
    {
        return $this->wrapInTry(
            fn(): bool => $handler->open($savePath, $sessionName),
            'session'
        );
    }

    public function closeHandler(SessionHandler $handler): bool
    {
        return $this->wrapInTry(
            fn(): bool => $handler->close(),
            'session'
        );
    }

    private function assertCanMutateRuntime(): void
    {
        if ($this->isActive()) {
            throw new SessionException('Session runtime configuration cannot be changed after the session has started.');
        }

        if (headers_sent($file, $line)) {
            $location = $file !== '' ? sprintf(' (%s:%d)', $file, $line) : '';

            throw new SessionException(
                sprintf('Session runtime configuration cannot be changed after headers have been sent%s.', $location)
            );
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function resolveSavePath(array $config): ?string
    {
        $configuredPath = $this->normalizeStringValue($config['SAVE']['PATH'] ?? '', '');

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

        throw new SessionException(
            sprintf('Configured session save path is not writable: %s', $normalizedPath)
        );
    }

    private function setIniOption(string $key, string $value): void
    {
        if (ini_set($key, $value) === false) {
            throw new SessionException(sprintf('Unable to set session runtime option "%s".', $key));
        }
    }

    /**
     * @param array<string, mixed> $config
     * @param array<int, array<int, string>> $paths
     */
    private function resolveConfigPath(array $config, array $paths, mixed $default = null): mixed
    {
        foreach ($paths as $path) {
            $value = $config;
            $resolved = true;

            foreach ($path as $segment) {
                if (!$this->isArray($value)) {
                    $resolved = false;
                    break;
                }

                $key = $this->resolveSegmentKey($value, $segment);

                if ($key === null) {
                    $resolved = false;
                    break;
                }

                $value = $value[$key];
            }

            if ($resolved) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * @param array<int|string, mixed> $value
     */
    private function resolveSegmentKey(array $value, string $segment): int|string|null
    {
        if ($this->keyExists($value, $segment)) {
            return $segment;
        }

        $normalizedSegment = $this->toLower($segment);

        foreach ($value as $key => $_) {
            if ($this->isString($key) && $this->toLower($key) === $normalizedSegment) {
                return $key;
            }
        }

        return null;
    }

    private function normalizeDriverName(string $driver): string
    {
        return match ($this->toLower($this->normalizeStringValue($driver, 'native'))) {
            'files' => 'file',
            default => $this->toLower($this->normalizeStringValue($driver, 'native')),
        };
    }

    private function normalizeHandlerName(string $handler): string
    {
        return $this->toLower($this->normalizeStringValue($handler, 'files'));
    }

    private function normalizeBoolValue(mixed $value, bool $default): bool
    {
        if ($this->isBool($value)) {
            return $value;
        }

        if ($this->isInt($value) || $this->isFloat($value)) {
            return (int) $value !== 0;
        }

        if (!$this->isString($value)) {
            return $default;
        }

        return match ($this->toLower($this->normalizeStringValue($value, ''))) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off', '' => false,
            default => $default,
        };
    }

    private function normalizePositiveInt(mixed $value, int $default): int
    {
        if (!$this->isNumeric($value)) {
            return $default;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : $default;
    }

    private function normalizeSameSite(mixed $value): string
    {
        return match ($this->toLower($this->normalizeStringValue($value, 'lax'))) {
            'strict' => 'Strict',
            'none' => 'None',
            default => 'Lax',
        };
    }

    private function normalizeStringValue(mixed $value, string $default = ''): string
    {
        if (!$this->isScalar($value)) {
            return $default;
        }

        return $this->trimString((string) $value);
    }

    private function isAbsolutePath(string $path): bool
    {
        return $this->startsWith($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }

    private function resolveCapability(string $feature): mixed
    {
        $normalized = $this->toLower($this->trimString($feature));

        if ($normalized === '') {
            return null;
        }

        $value = $this->capabilities();

        foreach ($this->splitString('.', $normalized) as $segment) {
            if (!$this->isArray($value) || !$this->keyExists($value, $segment)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
