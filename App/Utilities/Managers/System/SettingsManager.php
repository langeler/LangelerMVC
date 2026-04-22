<?php

namespace App\Utilities\Managers\System;

use Throwable;
use App\Utilities\Finders\{
    DirectoryFinder,
    FileFinder
};
use App\Utilities\Traits\{
    ApplicationPathTrait,
    ArrayTrait,
    TypeCheckerTrait,
    CheckerTrait,
    ErrorTrait,
    ManipulationTrait
};
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Sanitation\{
    PatternSanitizer
};
use App\Utilities\Validation\{
    PatternValidator
};

/**
 * Class SettingsManager
 *
 * Reads configuration from the Config directory, merges runtime environment
 * overrides without mutating files, and exposes settings through a
 * case-insensitive lookup API.
 */
class SettingsManager
{
    use ApplicationPathTrait;
    use ArrayTrait, ErrorTrait, ManipulationTrait, PatternTrait {
        ManipulationTrait::toLower as private toLowerString;
        ManipulationTrait::toUpper as private toUpperString;
        ManipulationTrait::substring as private substringString;
        PatternTrait::replaceByPattern as private patternReplace;
    }
    use CheckerTrait, TypeCheckerTrait;

    /**
     * Validated path to the Config directory.
     */
    private string $folder;

    /**
     * Index of config files keyed by lower-case basename.
     *
     * @var array<string, string>
     */
    private array $files = [];

    /**
     * Cached config payloads keyed by lower-case basename.
     *
     * @var array<string, array>
     */
    private array $data = [];

    /**
     * Environment aliases for config keys whose runtime names contain nested or
     * compound segments that do not map 1:1 from underscore-separated env vars.
     *
     * @var array<string, array{file:string, path:list<string>, type?:string}>
     */
    private const ENVIRONMENT_ALIASES = [
        'APP_FALLBACK_LOCALE' => ['file' => 'app', 'path' => ['FALLBACK']],
        'AUTH_VERIFY_EMAIL' => ['file' => 'auth', 'path' => ['VERIFY_EMAIL']],
        'AUTH_EMAIL_VERIFY_EXPIRES' => ['file' => 'auth', 'path' => ['EMAIL_VERIFY_EXPIRES'], 'type' => 'int'],
        'AUTH_PASSWORD_RESET_EXPIRES' => ['file' => 'auth', 'path' => ['PASSWORD_RESET_EXPIRES'], 'type' => 'int'],
        'AUTH_REMEMBER_ME_DAYS' => ['file' => 'auth', 'path' => ['REMEMBER_ME_DAYS'], 'type' => 'int'],
        'AUTH_DEFAULT_ROLE' => ['file' => 'auth', 'path' => ['DEFAULT_ROLE']],
        'AUTH_ADMIN_ROLE' => ['file' => 'auth', 'path' => ['ADMIN_ROLE']],
        'AUTH_OTP_TRUSTED_DEVICE_DAYS' => ['file' => 'auth', 'path' => ['OTP', 'TRUSTED_DEVICE_DAYS'], 'type' => 'int'],
        'AUTH_OTP_TRUSTED_DEVICE_COOKIE' => ['file' => 'auth', 'path' => ['OTP', 'TRUSTED_DEVICE_COOKIE']],
        'AUTH_PASSKEY_RP_ID' => ['file' => 'auth', 'path' => ['PASSKEY', 'RP_ID']],
        'AUTH_PASSKEY_RP_NAME' => ['file' => 'auth', 'path' => ['PASSKEY', 'RP_NAME']],
        'AUTH_PASSKEY_ALLOW_SUBDOMAINS' => ['file' => 'auth', 'path' => ['PASSKEY', 'ALLOW_SUBDOMAINS']],
        'AUTH_PASSKEY_CHALLENGE_TTL' => ['file' => 'auth', 'path' => ['PASSKEY', 'CHALLENGE_TTL'], 'type' => 'int'],
        'AUTH_PASSKEY_CHALLENGE_BYTES' => ['file' => 'auth', 'path' => ['PASSKEY', 'CHALLENGE_BYTES'], 'type' => 'int'],
        'AUTH_PASSKEY_RESIDENT_KEY' => ['file' => 'auth', 'path' => ['PASSKEY', 'RESIDENT_KEY']],
        'CACHE_FILE_PATH' => ['file' => 'cache', 'path' => ['FILE']],
        'CACHE_MAX_ITEMS' => ['file' => 'cache', 'path' => ['MAX_ITEMS'], 'type' => 'int'],
        'CACHE_REDIS_HOST' => ['file' => 'cache', 'path' => ['REDIS_HOST']],
        'CACHE_REDIS_PORT' => ['file' => 'cache', 'path' => ['REDIS_PORT'], 'type' => 'int'],
        'CACHE_REDIS_TIMEOUT' => ['file' => 'cache', 'path' => ['REDIS_TIMEOUT'], 'type' => 'int'],
        'CACHE_REDIS_PASSWORD' => ['file' => 'cache', 'path' => ['REDIS_PASSWORD']],
        'CACHE_REDIS_DATABASE' => ['file' => 'cache', 'path' => ['REDIS_DATABASE'], 'type' => 'int'],
        'CACHE_MEMCACHED_HOST' => ['file' => 'cache', 'path' => ['MEMCACHE_HOST']],
        'CACHE_MEMCACHED_PORT' => ['file' => 'cache', 'path' => ['MEMCACHE_PORT'], 'type' => 'int'],
        'CACHE_MEMCACHE_HOST' => ['file' => 'cache', 'path' => ['MEMCACHE_HOST']],
        'CACHE_MEMCACHE_PORT' => ['file' => 'cache', 'path' => ['MEMCACHE_PORT'], 'type' => 'int'],
        'DB_POOL_SIZE' => ['file' => 'db', 'path' => ['POOL'], 'type' => 'int'],
        'DB_RETRY_DELAY' => ['file' => 'db', 'path' => ['RETRY'], 'type' => 'int'],
        'DB_SSL_MODE' => ['file' => 'db', 'path' => ['SSL']],
        'ENCRYPTION_HASH_ALGO' => ['file' => 'encryption', 'path' => ['HASH_ALGORITHM']],
        'ENCRYPTION_PBKDF2_ITERATIONS' => ['file' => 'encryption', 'path' => ['PBKDF2_ITERATIONS'], 'type' => 'int'],
        'ENCRYPTION_OPENSSL_CIPHER' => ['file' => 'encryption', 'path' => ['OPENSSL_CIPHER']],
        'ENCRYPTION_OPENSSL_KEY' => ['file' => 'encryption', 'path' => ['OPENSSL_KEY']],
        'ENCRYPTION_SODIUM_KEY' => ['file' => 'encryption', 'path' => ['SODIUM_KEY']],
        'FEATURE_VERIFY_EMAIL' => ['file' => 'feature', 'path' => ['VERIFY']],
        'FEATURE_2FA' => ['file' => 'feature', 'path' => ['2FA']],
        'HTTP_CSRF_ENABLED' => ['file' => 'http', 'path' => ['CSRF', 'ENABLED']],
        'HTTP_CSRF_FIELD' => ['file' => 'http', 'path' => ['CSRF', 'FIELD']],
        'HTTP_CSRF_HEADER' => ['file' => 'http', 'path' => ['CSRF', 'HEADER']],
        'HTTP_HEADER_CONTENT_SECURITY_POLICY' => ['file' => 'http', 'path' => ['HEADERS', 'CONTENT_SECURITY_POLICY']],
        'HTTP_HEADER_PERMISSIONS_POLICY' => ['file' => 'http', 'path' => ['HEADERS', 'PERMISSIONS_POLICY']],
        'HTTP_HEADER_REFERRER_POLICY' => ['file' => 'http', 'path' => ['HEADERS', 'REFERRER_POLICY']],
        'HTTP_HEADER_STRICT_TRANSPORT_SECURITY' => ['file' => 'http', 'path' => ['HEADERS', 'STRICT_TRANSPORT_SECURITY']],
        'HTTP_HEADER_X_CONTENT_TYPE_OPTIONS' => ['file' => 'http', 'path' => ['HEADERS', 'X_CONTENT_TYPE_OPTIONS']],
        'HTTP_HEADER_X_FRAME_OPTIONS' => ['file' => 'http', 'path' => ['HEADERS', 'X_FRAME_OPTIONS']],
        'HTTP_HEADER_CROSS_ORIGIN_OPENER_POLICY' => ['file' => 'http', 'path' => ['HEADERS', 'CROSS_ORIGIN_OPENER_POLICY']],
        'HTTP_HEADER_CROSS_ORIGIN_RESOURCE_POLICY' => ['file' => 'http', 'path' => ['HEADERS', 'CROSS_ORIGIN_RESOURCE_POLICY']],
        'HTTP_SIGNED_URL_KEY' => ['file' => 'http', 'path' => ['SIGNED_URL', 'KEY']],
        'HTTP_THROTTLE_MAX_ATTEMPTS' => ['file' => 'http', 'path' => ['THROTTLE', 'MAX_ATTEMPTS'], 'type' => 'int'],
        'HTTP_THROTTLE_DECAY_SECONDS' => ['file' => 'http', 'path' => ['THROTTLE', 'DECAY_SECONDS'], 'type' => 'int'],
        'MAIL_FROM_ADDRESS' => ['file' => 'mail', 'path' => ['FROM']],
        'MAIL_FROM_NAME' => ['file' => 'mail', 'path' => ['FROM_NAME']],
        'MAIL_REPLY_TO' => ['file' => 'mail', 'path' => ['REPLY']],
        'MAIL_LOG_ENABLED' => ['file' => 'mail', 'path' => ['LOG']],
        'NOTIFICATIONS_DEFAULT_CHANNELS' => ['file' => 'notifications', 'path' => ['DEFAULT_CHANNELS']],
        'NOTIFICATIONS_QUEUE_NAME' => ['file' => 'notifications', 'path' => ['QUEUE_NAME']],
        'OPERATIONS_HEALTH_ENABLED' => ['file' => 'operations', 'path' => ['HEALTH', 'ENABLED']],
        'OPERATIONS_AUDIT_ENABLED' => ['file' => 'operations', 'path' => ['AUDIT', 'ENABLED']],
        'OPERATIONS_AUDIT_SUMMARY_LIMIT' => ['file' => 'operations', 'path' => ['AUDIT', 'SUMMARY_LIMIT'], 'type' => 'int'],
        'OPERATIONS_AUDIT_RETENTION_HOURS' => ['file' => 'operations', 'path' => ['AUDIT', 'RETENTION_HOURS'], 'type' => 'int'],
        'PAYMENT_DEFAULT_METHOD' => ['file' => 'payment', 'path' => ['DEFAULT_METHOD']],
        'PAYMENT_DEFAULT_FLOW' => ['file' => 'payment', 'path' => ['DEFAULT_FLOW']],
        'QUEUE_DEFAULT_QUEUE' => ['file' => 'queue', 'path' => ['DEFAULT_QUEUE']],
        'QUEUE_RETRY_AFTER' => ['file' => 'queue', 'path' => ['RETRY_AFTER'], 'type' => 'int'],
        'QUEUE_MAX_ATTEMPTS' => ['file' => 'queue', 'path' => ['MAX_ATTEMPTS'], 'type' => 'int'],
        'QUEUE_BACKOFF_STRATEGY' => ['file' => 'queue', 'path' => ['BACKOFF', 'STRATEGY']],
        'QUEUE_BACKOFF_SECONDS' => ['file' => 'queue', 'path' => ['BACKOFF', 'SECONDS'], 'type' => 'int'],
        'QUEUE_BACKOFF_MAX_SECONDS' => ['file' => 'queue', 'path' => ['BACKOFF', 'MAX_SECONDS'], 'type' => 'int'],
        'QUEUE_WORKER_SLEEP' => ['file' => 'queue', 'path' => ['WORKER', 'SLEEP'], 'type' => 'int'],
        'QUEUE_WORKER_MAX_RUNTIME' => ['file' => 'queue', 'path' => ['WORKER', 'MAX_RUNTIME'], 'type' => 'int'],
        'QUEUE_WORKER_MAX_MEMORY_MB' => ['file' => 'queue', 'path' => ['WORKER', 'MAX_MEMORY_MB'], 'type' => 'int'],
        'QUEUE_WORKER_CONTROL_PATH' => ['file' => 'queue', 'path' => ['WORKER', 'CONTROL_PATH']],
        'QUEUE_FAILED_PRUNE_AFTER_HOURS' => ['file' => 'queue', 'path' => ['FAILED', 'PRUNE_AFTER_HOURS'], 'type' => 'int'],
        'SESSION_EXPIRE_ON_CLOSE' => ['file' => 'session', 'path' => ['EXPIRE_ON_CLOSE']],
        'SESSION_SAVE_PATH' => ['file' => 'session', 'path' => ['SAVE', 'PATH']],
        'SESSION_SECURE_COOKIE' => ['file' => 'session', 'path' => ['COOKIE', 'SECURE']],
        'SESSION_HTTPONLY_COOKIE' => ['file' => 'session', 'path' => ['COOKIE', 'HTTPONLY']],
        'SESSION_SAME_SITE' => ['file' => 'session', 'path' => ['COOKIE', 'SAME_SITE']],
        'WEBMODULE_CONTENT_SOURCE' => ['file' => 'webmodule', 'path' => ['CONTENT_SOURCE']],
    ];

    /**
     * Invalid config files captured during discovery.
     *
     * @var array<string, string>
     */
    private array $invalidFiles = [];

    /**
     * Environment-derived overrides grouped by config file name.
     *
     * @var array<string, array>
     */
    private array $environment = [];

    public function __construct(
        private readonly DirectoryFinder $dirFinder,
        private readonly FileFinder $fileFinder,
        private readonly FileManager $fileManager,
        private readonly PatternSanitizer $patternSanitizer,
        private readonly PatternValidator $patternValidator,
        private readonly ErrorManager $errorManager
    ) {
        $this->folder = $this->wrapInTry(
            fn() => $this->validateFolderPath($this->locateConfigFolder()),
            'settings'
        );

        $this->files = $this->wrapInTry(
            fn() => $this->retrieveConfigFiles(),
            'settings'
        );

        $this->environment = $this->wrapInTry(
            fn() => $this->loadEnvironmentOverrides(),
            'settings'
        );
    }

    /**
     * Retrieves all settings from a config file.
     *
     * @param string $fileName
     * @return array
     */
    public function getAllSettings(string $fileName): array
    {
        return $this->wrapInTry(
            fn() => $this->getConfigData($fileName),
            'settings'
        );
    }

    /**
     * Retrieves a single setting from a config file.
     *
     * @param string $fileName
     * @param string $key
     * @return mixed
     */
    public function getSetting(string $fileName, string $key): mixed
    {
        return $this->wrapInTry(function () use ($fileName, $key) {
            $value = $this->get($this->getConfigData($fileName), $key);

            if ($value !== null) {
                return $value;
            }

            $this->errorManager->logErrorMessage(
                "Key '$key' not found in file '$fileName'.",
                __FILE__,
                __LINE__,
                'userError',
                'settings'
            );

            throw $this->errorManager->resolveException(
                'settings',
                "Key '$key' not found in file '$fileName'."
            );
        }, 'settings');
    }

    /**
     * Retrieves all valid configuration files.
     *
     * Invalid files are skipped and logged instead of crashing config boot.
     *
     * @return array<string, array>
     */
    public function getAllConfigs(): array
    {
        return $this->wrapInTry(function (): array {
            $configs = [];

            foreach ($this->getKeys($this->files) as $fileName) {
                try {
                    $configs[$fileName] = $this->getConfigData($fileName);
                } catch (Throwable $exception) {
                    $this->invalidFiles[$fileName] = $exception->getMessage();
                }
            }

            return $configs;
        }, 'settings');
    }

    /**
     * Returns invalid config files encountered during discovery.
     *
     * @return array<string, string>
     */
    public function getInvalidFiles(): array
    {
        return $this->invalidFiles;
    }

    /**
     * Summarize recognized and unknown keys from the project .env file.
     *
     * @return array<string, mixed>
     */
    public function environmentReport(): array
    {
        $envFile = dirname($this->folder) . '/.env';
        $variables = $this->fileManager->fileExists($envFile)
            ? $this->parseEnvFile($envFile)
            : [];
        $recognized = [];
        $unknown = [];

        foreach ($variables as $key => $value) {
            if (!$this->isString($key) || $this->trimString($key) === '') {
                continue;
            }

            $target = $this->resolveEnvironmentTarget($key);

            if ($target === null) {
                $unknown[] = $key;
                continue;
            }

            $recognized[] = [
                'key' => $key,
                'file' => (string) ($target['file'] ?? ''),
                'path' => implode('.', array_map('strval', (array) ($target['path'] ?? []))),
                'value' => $this->normalizeEnvironmentValue($value, is_string($target['type'] ?? null) ? (string) $target['type'] : null),
            ];
        }

        usort(
            $recognized,
            static fn(array $left, array $right): int => strcmp(
                (string) ($left['key'] ?? ''),
                (string) ($right['key'] ?? '')
            )
        );
        sort($unknown);

        return [
            'path' => $envFile,
            'exists' => $this->fileManager->fileExists($envFile),
            'recognized' => $recognized,
            'recognized_count' => count($recognized),
            'unknown' => array_values(array_unique($unknown)),
            'unknown_count' => count(array_unique($unknown)),
            'override_files' => array_keys($this->environment),
        ];
    }

    /**
     * Locates the Config directory.
     *
     * @return string
     */
    private function locateConfigFolder(): string
    {
        return $this->wrapInTry(function (): string {
            $directoryPath = $this->frameworkBasePath()
                . DIRECTORY_SEPARATOR
                . 'Config';

            if ($this->isString($directoryPath) && $this->fileManager->isDirectory($directoryPath)) {
                return $directoryPath;
            }

            $this->errorManager->logErrorMessage(
                'Configuration directory not found.',
                __FILE__,
                __LINE__,
                'userError',
                'settings'
            );

            throw $this->errorManager->resolveException('settings', 'Configuration directory not found.');
        }, 'settings');
    }

    /**
     * Sanitizes and validates the Config directory path.
     *
     * @param string $folderPath
     * @return string
     */
    private function validateFolderPath(string $folderPath): string
    {
        return $this->wrapInTry(function () use ($folderPath): string {
            $sanitizedPath = $this->patternSanitizer->sanitizePathUnix($folderPath) ?? '';
            $validatedPath = $this->patternValidator->validatePathUnix($sanitizedPath)
                ? $sanitizedPath
                : null;

            if ($this->isString($validatedPath) && $this->isValidFilePath($validatedPath)) {
                return $validatedPath;
            }

            $this->errorManager->logErrorMessage(
                "Invalid configuration directory path: {$folderPath}",
                __FILE__,
                __LINE__,
                'userError',
                'settings'
            );

            throw $this->errorManager->resolveException(
                'settings',
                "Invalid configuration directory path: {$folderPath}"
            );
        }, 'settings');
    }

    /**
     * Retrieves all PHP config files in the Config directory.
     *
     * @return array<string, string>
     */
    private function retrieveConfigFiles(): array
    {
        return $this->wrapInTry(function (): array {
            $files = $this->fileFinder->find(['extension' => 'php'], $this->folder);

            if (!$this->isArray($files) || $this->isEmpty($files)) {
                return [];
            }

            return $this->reduce(
                $this->getKeys($files),
                function (array $carry, string $filePath): array {
                    $name = $this->toLowerString((string) $this->fileManager->getBaseName($filePath, '.php'));

                    if ($name !== '') {
                        $carry[$name] = $filePath;
                    }

                    return $carry;
                },
                []
            );
        }, 'settings');
    }

    /**
     * Retrieves and caches a config payload.
     *
     * @param string $fileName
     * @return array
     */
    private function getConfigData(string $fileName): array
    {
        return $this->wrapInTry(function () use ($fileName): array {
            $normalizedName = $this->normalizeFileName($fileName);

            if (isset($this->data[$normalizedName])) {
                return $this->data[$normalizedName];
            }

            $filePath = $this->buildFilePath($normalizedName);

            if (!$this->fileManager->fileExists($filePath)) {
                $this->errorManager->logErrorMessage(
                    "Configuration file '$fileName' not found.",
                    __FILE__,
                    __LINE__,
                    'userError',
                    'settings'
                );

                throw $this->errorManager->resolveException(
                    'settings',
                    "Configuration file '$fileName' not found."
                );
            }

            return $this->data[$normalizedName] = $this->mergeEnvironmentOverrides(
                $normalizedName,
                $this->parseFile($filePath)
            );
        }, 'settings');
    }

    /**
     * Parses a config file and normalizes its values.
     *
     * @param string $filePath
     * @return array
     */
    private function parseFile(string $filePath): array
    {
        return $this->wrapInTry(function () use ($filePath): array {
            $parsed = include $filePath;

            if ($this->isArray($parsed)) {
                return $this->normalizeConfigValues($parsed);
            }

            $this->errorManager->logErrorMessage(
                "Invalid config format in '$filePath'. Expected an array.",
                __FILE__,
                __LINE__,
                'userError',
                'settings'
            );

            throw $this->errorManager->resolveException(
                'settings',
                "Invalid config format in '$filePath'."
            );
        }, 'settings');
    }

    /**
     * Builds a case-insensitive file path for a config file.
     *
     * @param string $fileName
     * @return string
     */
    private function buildFilePath(string $fileName): string
    {
        return $this->wrapInTry(function () use ($fileName): string {
            $normalizedName = $this->normalizeFileName($fileName);
            $filePath = $this->files[$normalizedName]
                ?? ($this->folder . '/' . $normalizedName . '.php');

            return $this->patternSanitizer->sanitizePathUnix($filePath) ?? $filePath;
        }, 'settings');
    }

    /**
     * Loads environment-derived overrides without mutating config files on disk.
     *
     * @return array<string, array>
     */
    private function loadEnvironmentOverrides(): array
    {
        $variables = [];
        $envFile = dirname($this->folder) . '/.env';

        if ($this->fileManager->fileExists($envFile)) {
            $variables = $this->replaceElements($variables, $this->parseEnvFile($envFile));
        }

        foreach ([getenv(), $_ENV, $_SERVER] as $source) {
            if (!$this->isArray($source)) {
                continue;
            }

            foreach ($source as $key => $value) {
                if ($this->isString($key) && ($this->isString($value) || $this->isNumeric($value) || $this->isBool($value))) {
                    $variables[$key] = (string) $value;
                }
            }
        }

        return $this->groupEnvironmentByConfig($variables);
    }

    /**
     * Parses a .env file into a flat key/value map.
     *
     * @param string $envFile
     * @return array<string, mixed>
     */
    private function parseEnvFile(string $envFile): array
    {
        $contents = $this->fileManager->readContents($envFile);

        if (!$this->isString($contents) || $contents === '') {
            return [];
        }

        $variables = [];

        foreach (($this->splitByPattern('/\R/', $contents) ?: []) as $line) {
            $line = $this->trimString($line);

            if ($line === '' || $this->substringString($line, 0, 1) === '#' || !$this->contains($line, '=')) {
                continue;
            }

            [$key, $value] = $this->splitString('=', $line, 2) + [null, null];
            $variables[$this->trimString($key)] = $this->normalizeScalarValue($value);
        }

        return $variables;
    }

    /**
     * Groups raw environment variables by config file prefix and nested key path.
     *
     * @param array<string, mixed> $variables
     * @return array<string, array>
     */
    private function groupEnvironmentByConfig(array $variables): array
    {
        $grouped = [];

        foreach ($variables as $key => $value) {
            $target = $this->resolveEnvironmentTarget($key);

            if ($target === null) {
                continue;
            }

            $this->setNestedValue(
                $grouped[$target['file']],
                $target['path'],
                $this->normalizeEnvironmentValue($value, is_string($target['type'] ?? null) ? (string) $target['type'] : null)
            );
        }

        return $grouped;
    }

    /**
     * Resolves an environment variable name into its target config file/path.
     *
     * @param string $key
     * @return array{file:string, path:list<string>, type?:string}|null
     */
    private function resolveEnvironmentTarget(string $key): ?array
    {
        $normalizedKey = $this->toUpperString($this->trimString($key));

        if ($normalizedKey === '') {
            return null;
        }

        if (isset(self::ENVIRONMENT_ALIASES[$normalizedKey])) {
            $alias = self::ENVIRONMENT_ALIASES[$normalizedKey];
            $file = $this->toLowerString((string) ($alias['file'] ?? ''));
            $path = $this->map(
                fn(string $segment): string => $this->toUpperString($segment),
                (array) ($alias['path'] ?? [])
            );

            return $file !== '' && $path !== [] && isset($this->files[$file])
                ? [
                    'file' => $file,
                    'path' => $path,
                    'type' => isset($alias['type']) ? (string) $alias['type'] : null,
                ]
                : null;
        }

        $segments = $this->getValues($this->filter(
            $this->splitString('_', $normalizedKey),
            fn($segment) => $segment !== ''
        ));

        if ($this->countElements($segments) < 2) {
            return null;
        }

        $file = $this->toLowerString((string) array_shift($segments));

        if (!isset($this->files[$file])) {
            return null;
        }

        return [
            'file' => $file,
            'path' => $this->map(fn(string $segment): string => $this->toUpperString($segment), $segments),
        ];
    }

    /**
     * Sets a nested value inside an array using a list of path segments.
     *
     * @param array|null $target
     * @param array $path
     * @param mixed $value
     * @return void
     */
    private function setNestedValue(?array &$target, array $path, mixed $value): void
    {
        $target ??= [];
        $cursor = &$target;

        foreach ($path as $index => $segment) {
            if ($index === $this->countElements($path) - 1) {
                $cursor[$segment] = $value;
                return;
            }

            if (!isset($cursor[$segment]) || !$this->isArray($cursor[$segment])) {
                $cursor[$segment] = [];
            }

            $cursor = &$cursor[$segment];
        }
    }

    /**
     * Merges environment overrides into a parsed config array.
     *
     * @param string $fileName
     * @param array $config
     * @return array
     */
    private function mergeEnvironmentOverrides(string $fileName, array $config): array
    {
        return isset($this->environment[$fileName])
            ? $this->replaceRecursive($config, $this->environment[$fileName])
            : $config;
    }

    /**
     * Normalizes nested config values.
     *
     * @param mixed $value
     * @return mixed
     */
    private function normalizeConfigValues(mixed $value): mixed
    {
        if ($this->isArray($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeConfigValues($item);
            }

            return $normalized;
        }

        if ($this->isString($value)) {
            return $this->normalizeScalarValue($value);
        }

        return $value;
    }

    private function normalizeEnvironmentValue(mixed $value, ?string $type = null): mixed
    {
        $normalized = $this->normalizeScalarValue($value);

        return match ($type) {
            'int' => $this->coerceIntegerValue($normalized),
            default => $normalized,
        };
    }

    /**
     * Normalizes scalar config values by trimming whitespace, comments, and quotes.
     *
     * Boolean-ish strings are converted to native booleans so downstream config
     * consumers can safely cast values without treating `'false'` as truthy.
     *
     * @param mixed $value
     * @return mixed
     */
    private function normalizeScalarValue(mixed $value): mixed
    {
        if ($value === null || $this->isBool($value) || $this->isInt($value) || $this->isFloat($value)) {
            return $value;
        }

        if (!$this->isString($value)) {
            return $value;
        }

        $trimmed = $this->trimString($value);

        if ($trimmed === '') {
            return '';
        }

        $firstChar = $this->substringString($trimmed, 0, 1);
        $lastChar = $this->substringString($trimmed, -1);

        if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
            $trimmed = $this->substringString($trimmed, 1, -1);
        } else {
            $trimmed = $this->trimString((string) ($this->patternReplace('/\s+#.*$/', '', $trimmed) ?? $trimmed));
        }

        if ($this->toLowerString($trimmed) === 'null') {
            return null;
        }

        $boolean = filter_var($trimmed, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if (
            $boolean !== null
            && $this->match('/^(true|false|on|off|yes|no|1|0)$/i', $trimmed) === 1
        ) {
            return $boolean;
        }

        return $trimmed;
    }

    private function coerceIntegerValue(mixed $value): mixed
    {
        if ($value === null || $this->isInt($value)) {
            return $value;
        }

        if ($this->isBool($value)) {
            return $value ? 1 : 0;
        }

        if ($this->isFloat($value)) {
            return (int) $value;
        }

        if ($this->isString($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        return $value;
    }

    /**
     * Normalizes a config file name for case-insensitive lookup.
     *
     * @param string $fileName
     * @return string
     */
    private function normalizeFileName(string $fileName): string
    {
        return $this->toLowerString(
            (string) ($this->patternReplace('/\.php$/i', '', $this->trimString($fileName)) ?? $this->trimString($fileName))
        );
    }

    /**
     * Checks if a path is a readable file or directory.
     *
     * @param string $filePath
     * @return bool
     */
    private function isValidFilePath(string $filePath): bool
    {
        return $this->fileManager->isReadable($filePath)
            && (
                $this->fileManager->isDirectory($filePath)
                || $this->fileManager->fileExists($filePath)
            );
    }
}
