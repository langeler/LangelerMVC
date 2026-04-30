<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Support;

use App\Contracts\Support\FrameworkDoctorInterface;
use App\Core\Config;
use App\Core\Database;
use App\Core\Router;
use App\Utilities\Managers\Data\ModuleManager;
use App\Utilities\Managers\System\FileManager;
use App\Utilities\Managers\System\SettingsManager;
use App\Utilities\Traits\ApplicationPathTrait;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;

class FrameworkDoctor implements FrameworkDoctorInterface
{
    use ApplicationPathTrait;
    use ArrayTrait, ManipulationTrait, TypeCheckerTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    /**
     * @var list<string>
     */
    private const REQUIRED_CONFIG_FILES = [
        'app',
        'auth',
        'cache',
        'commerce',
        'cookie',
        'db',
        'encryption',
        'http',
        'mail',
        'notifications',
        'operations',
        'payment',
        'queue',
        'session',
        'webmodule',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_MODULE_SURFACES = [
        'Controllers',
        'Middlewares',
        'Migrations',
        'Models',
        'Presenters',
        'Repositories',
        'Requests',
        'Responses',
        'Routes',
        'Seeds',
        'Services',
        'Views',
    ];

    /**
     * @var list<string>
     */
    private const DEFAULT_CRYPTO_SECRETS = [
        'base64:S0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0s=',
        'base64:U1NTU1NTU1NTU1NTU1NTU1NTU1NTU1NTU1NTU1NTU1M=',
    ];

    public function __construct(
        private readonly Config $config,
        private readonly SettingsManager $settingsManager,
        private readonly ModuleManager $moduleManager,
        private readonly Router $router,
        private readonly Database $database,
        private readonly FileManager $fileManager
    ) {
    }

    public function inspect(bool $strict = false): array
    {
        $checks = [
            'configuration' => $this->configurationCheck(),
            'environment' => $this->environmentCheck(),
            'app_security' => $this->appSecurityCheck(),
            'crypto' => $this->cryptoCheck(),
            'storage' => $this->storageCheck(),
            'operations' => $this->operationsCheck(),
            'queue' => $this->queueCheck(),
            'modules' => $this->moduleSurfaceCheck(),
            'routes' => $this->routeSurfaceCheck(),
        ];

        $errors = [];
        $warnings = [];

        foreach ($checks as $check) {
            $errors = array_values(array_unique(array_merge($errors, $this->extractMessages($check, 'errors'))));
            $warnings = array_values(array_unique(array_merge($warnings, $this->extractMessages($check, 'warnings'))));
        }

        $healthy = $errors === [] && (!$strict || $warnings === []);

        return [
            'status' => $healthy ? 200 : 503,
            'healthy' => $healthy,
            'strict' => $strict,
            'timestamp' => gmdate('c'),
            'checks' => $checks,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function configurationCheck(): array
    {
        $loadedFiles = array_keys($this->config->all());
        $loadedLookup = array_map(fn(string $file): string => $this->toLowerString($file), $loadedFiles);
        $missing = array_values(array_diff(self::REQUIRED_CONFIG_FILES, $loadedLookup));
        $invalid = $this->settingsManager->getInvalidFiles();
        $errors = [];

        if ($missing !== []) {
            $errors[] = 'Missing required config files: ' . implode(', ', $missing);
        }

        if ($invalid !== []) {
            $errors[] = 'Invalid config files detected: ' . implode(', ', array_keys($invalid));
        }

        return [
            'ok' => $errors === [],
            'required' => self::REQUIRED_CONFIG_FILES,
            'loaded_count' => count($loadedLookup),
            'missing' => $missing,
            'invalid_files' => $invalid,
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function environmentCheck(): array
    {
        $report = method_exists($this->settingsManager, 'environmentReport')
            ? $this->settingsManager->environmentReport()
            : [
                'exists' => false,
                'recognized' => [],
                'recognized_count' => 0,
                'unknown' => [],
                'unknown_count' => 0,
            ];

        $warnings = [];

        if ((bool) ($report['exists'] ?? false) && !empty($report['unknown'])) {
            $warnings[] = 'Unknown .env keys detected: ' . implode(', ', array_map('strval', (array) $report['unknown']));
        }

        return [
            'ok' => true,
            'exists' => (bool) ($report['exists'] ?? false),
            'recognized_count' => (int) ($report['recognized_count'] ?? 0),
            'unknown_count' => (int) ($report['unknown_count'] ?? 0),
            'unknown' => array_values(array_map('strval', (array) ($report['unknown'] ?? []))),
            'override_files' => array_values(array_map('strval', (array) ($report['override_files'] ?? []))),
            'errors' => [],
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function appSecurityCheck(): array
    {
        $environment = $this->toLowerString((string) $this->config->get('app', 'ENV', 'production'));
        $debug = $this->toBool($this->config->get('app', 'DEBUG', false));
        $installed = $this->toBool($this->config->get('app', 'INSTALLED', false));
        $appUrl = $this->trimString((string) $this->config->get('app', 'URL', ''));
        $sessionSecure = $this->toBool($this->config->get('session', 'COOKIE.SECURE', true));
        $signedKey = $this->trimString((string) $this->config->get('http', 'SIGNED_URL.KEY', ''));

        $errors = [];
        $warnings = [];

        if ($environment === 'production' && $debug) {
            $errors[] = 'APP_DEBUG is enabled in production.';
        }

        if ($environment === 'production' && !$sessionSecure) {
            $errors[] = 'Session cookie secure flag is disabled in production.';
        }

        if ($signedKey === '') {
            $errors[] = 'HTTP signed-url key is empty.';
        }

        if ($environment === 'production' && !$installed) {
            $warnings[] = 'APP_INSTALLED is false while APP_ENV is production.';
        }

        if ($environment === 'production' && $appUrl !== '' && str_starts_with($this->toLowerString($appUrl), 'http://')) {
            $warnings[] = 'APP_URL uses HTTP in production.';
        }

        if ($signedKey === 'langelermvc-signed-url') {
            $warnings[] = 'HTTP signed-url key still uses the default framework value.';
        }

        if ($signedKey !== '' && mb_strlen($signedKey) < 16) {
            $warnings[] = 'HTTP signed-url key is shorter than 16 characters.';
        }

        return [
            'ok' => $errors === [],
            'environment' => $environment,
            'debug' => $debug,
            'installed' => $installed,
            'app_url' => $appUrl,
            'session_secure' => $sessionSecure,
            'signed_url_key_length' => mb_strlen($signedKey),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cryptoCheck(): array
    {
        $enabled = $this->toBool($this->config->get('encryption', 'ENABLED', true));
        $driver = $this->toLowerString((string) $this->config->get('encryption', 'DRIVER', 'openssl'));
        $iterations = (int) $this->config->get('encryption', 'PBKDF2_ITERATIONS', 100000);
        $key = $this->normalizeSecret((string) $this->config->get('encryption', 'KEY', ''));
        $openSslKey = $this->normalizeSecret((string) $this->config->get('encryption', 'OPENSSL_KEY', $key));
        $sodiumKey = $this->normalizeSecret((string) $this->config->get('encryption', 'SODIUM_KEY', ''));

        $errors = [];
        $warnings = [];
        $supportedDrivers = ['openssl', 'sodium'];

        if (!in_array($driver, $supportedDrivers, true)) {
            $errors[] = sprintf('Unsupported encryption driver [%s].', $driver);
        }

        if ($enabled && $key === '') {
            $errors[] = 'Primary encryption key is empty.';
        }

        if ($enabled && $driver === 'openssl' && $openSslKey === '') {
            $errors[] = 'OpenSSL encryption key is empty.';
        }

        if ($enabled && $driver === 'sodium' && $sodiumKey === '') {
            $errors[] = 'Sodium encryption key is empty.';
        }

        if ($enabled && $this->isDefaultCryptoSecret($key)) {
            $warnings[] = 'Primary encryption key still uses the default framework value.';
        }

        if ($enabled && $driver === 'openssl' && $this->isDefaultCryptoSecret($openSslKey)) {
            $warnings[] = 'OpenSSL encryption key still uses the default framework value.';
        }

        if ($enabled && $driver === 'sodium' && $this->isDefaultCryptoSecret($sodiumKey)) {
            $warnings[] = 'Sodium encryption key still uses the default framework value.';
        }

        if ($enabled && $iterations > 0 && $iterations < 100000) {
            $warnings[] = 'PBKDF2 iterations are below the framework hardening baseline (100000).';
        }

        return [
            'ok' => $errors === [],
            'enabled' => $enabled,
            'driver' => $driver,
            'supported_drivers' => $supportedDrivers,
            'pbkdf2_iterations' => $iterations,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storageCheck(): array
    {
        $paths = [
            'storage' => $this->frameworkStoragePath(),
            'cache' => $this->frameworkStoragePath('Cache'),
            'logs' => $this->frameworkStoragePath('Logs'),
            'sessions' => $this->frameworkStoragePath('Sessions'),
            'uploads' => $this->frameworkStoragePath('Uploads'),
            'secure' => $this->frameworkStoragePath('Secure'),
        ];

        $surface = [];
        $missing = [];
        $unwritable = [];

        foreach ($paths as $name => $path) {
            $exists = $this->fileManager->isDirectory($path);
            $writable = $exists && $this->fileManager->isWritable($path);

            $surface[$name] = [
                'path' => $path,
                'exists' => $exists,
                'writable' => $writable,
            ];

            if (!$exists) {
                $missing[] = $path;
                continue;
            }

            if (!$writable) {
                $unwritable[] = $path;
            }
        }

        $errors = [];

        if ($missing !== []) {
            $errors[] = 'Missing storage directories: ' . implode(', ', $missing);
        }

        if ($unwritable !== []) {
            $errors[] = 'Unwritable storage directories: ' . implode(', ', $unwritable);
        }

        return [
            'ok' => $errors === [],
            'paths' => $surface,
            'missing' => $missing,
            'unwritable' => $unwritable,
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function operationsCheck(): array
    {
        $queueDriver = $this->toLowerString((string) $this->config->get('queue', 'DRIVER', 'sync'));
        $auditEnabled = $this->toBool($this->config->get('operations', 'AUDIT.ENABLED', true));
        $auditRetentionHours = max(0, (int) $this->config->get('operations', 'AUDIT.RETENTION_HOURS', 720));
        $required = [
            'framework_migrations' => true,
            'framework_migration_locks' => true,
            'framework_jobs' => $queueDriver === 'database',
            'framework_failed_jobs' => true,
            'framework_audit_log' => $auditEnabled,
        ];
        $surface = [];
        $missing = [];

        foreach ($required as $table => $enabled) {
            $exists = !$enabled || $this->databaseTableExists($table);
            $surface[$table] = [
                'required' => $enabled,
                'exists' => $exists,
            ];

            if ($enabled && !$exists) {
                $missing[] = $table;
            }
        }

        $errors = [];
        $warnings = [];

        if ($missing !== []) {
            $errors[] = 'Missing required framework operations tables: ' . implode(', ', $missing);
        }

        if ($auditEnabled && $auditRetentionHours < 1) {
            $warnings[] = 'Audit retention is disabled; audit records will grow without automatic pruning guidance.';
        }

        return [
            'ok' => $errors === [],
            'tables' => $surface,
            'queue_driver' => $queueDriver,
            'audit_enabled' => $auditEnabled,
            'audit_retention_hours' => $auditRetentionHours,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function queueCheck(): array
    {
        $driver = $this->toLowerString((string) $this->config->get('queue', 'DRIVER', 'sync'));
        $maxAttempts = (int) $this->config->get('queue', 'MAX_ATTEMPTS', 3);
        $strategy = $this->toLowerString((string) $this->config->get('queue', 'BACKOFF.STRATEGY', 'fixed'));
        $seconds = (int) $this->config->get('queue', 'BACKOFF.SECONDS', 5);
        $maxSeconds = (int) $this->config->get('queue', 'BACKOFF.MAX_SECONDS', 300);
        $controlPath = $this->resolveQueueControlPath();
        $controlExists = $this->fileManager->isDirectory($controlPath);
        $controlWritable = $controlExists
            ? $this->fileManager->isWritable($controlPath)
            : is_writable(dirname($controlPath));
        $warnings = [];
        $errors = [];

        if ($maxAttempts < 1) {
            $errors[] = 'QUEUE_MAX_ATTEMPTS must be at least 1.';
        }

        if (!in_array($strategy, ['none', 'fixed', 'linear', 'exponential'], true)) {
            $errors[] = sprintf('Unsupported queue backoff strategy [%s].', $strategy);
        }

        if ($seconds < 0 || $maxSeconds < 0) {
            $errors[] = 'Queue backoff timing values must be non-negative.';
        }

        if ($maxSeconds > 0 && $seconds > $maxSeconds) {
            $warnings[] = 'QUEUE_BACKOFF_SECONDS exceeds QUEUE_BACKOFF_MAX_SECONDS and will be capped at runtime.';
        }

        if ($driver === 'database' && !$this->databaseTableExists('framework_jobs')) {
            $errors[] = 'Database queue driver is enabled but framework_jobs is missing.';
        }

        if (!$controlWritable) {
            $errors[] = sprintf('Queue worker control path [%s] is not writable.', $controlPath);
        }

        return [
            'ok' => $errors === [],
            'driver' => $driver,
            'max_attempts' => $maxAttempts,
            'backoff' => [
                'strategy' => $strategy,
                'seconds' => $seconds,
                'max_seconds' => $maxSeconds,
            ],
            'control' => [
                'path' => $controlPath,
                'exists' => $controlExists,
                'writable' => $controlWritable,
            ],
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function moduleSurfaceCheck(): array
    {
        $modules = $this->moduleManager->getModules();
        $missing = [];
        $errors = [];

        foreach ($modules as $module => $path) {
            $missingSurfaces = [];

            foreach (self::REQUIRED_MODULE_SURFACES as $surface) {
                $surfacePath = rtrim((string) $path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $surface;

                if (!$this->fileManager->isDirectory($surfacePath)) {
                    $missingSurfaces[] = $surface;
                }
            }

            if ($missingSurfaces !== []) {
                $missing[$module] = $missingSurfaces;
            }
        }

        if ($modules === []) {
            $errors[] = 'No modules were discovered under App/Modules.';
        }

        if ($missing !== []) {
            $errors[] = 'Some modules are missing required backend surfaces.';
        }

        return [
            'ok' => $errors === [],
            'count' => count($modules),
            'required_surfaces' => self::REQUIRED_MODULE_SURFACES,
            'missing_surfaces' => $missing,
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function routeSurfaceCheck(): array
    {
        $routes = $this->router->listRoutes();
        $diagnostics = method_exists($this->router, 'diagnostics')
            ? (array) $this->router->diagnostics()
            : ['route_overrides' => [], 'name_overrides' => []];
        $routeCount = count($routes);
        $namedRoutes = count(array_filter(
            $routes,
            fn(array $route): bool => isset($route['name']) && $route['name'] !== null && $route['name'] !== ''
        ));
        $unsafeRoutes = array_values(array_filter(
            $routes,
            fn(array $route): bool => !in_array($this->toLowerString((string) ($route['method'] ?? 'get')), ['get', 'head', 'options'], true)
        ));
        $unsafeWithoutCsrf = array_values(array_filter(
            $unsafeRoutes,
            static fn(array $route): bool => array_key_exists('csrf', $route) && ($route['csrf'] === false)
        ));

        $errors = [];
        $warnings = [];

        if ($routeCount === 0) {
            $errors[] = 'No routes are currently registered.';
        }

        if ($namedRoutes === 0) {
            $warnings[] = 'No named routes are registered.';
        }

        if ($unsafeWithoutCsrf !== []) {
            $warnings[] = 'Some unsafe routes explicitly disable CSRF protection.';
        }

        if (!empty($diagnostics['route_overrides']) || !empty($diagnostics['name_overrides'])) {
            $warnings[] = 'Route registration collisions were detected while building the route surface.';
        }

        return [
            'ok' => $errors === [],
            'count' => $routeCount,
            'named' => $namedRoutes,
            'unsafe' => count($unsafeRoutes),
            'diagnostics' => $diagnostics,
            'unsafe_without_csrf' => array_map(
                fn(array $route): string => sprintf('%s %s', (string) ($route['method'] ?? 'POST'), (string) ($route['path'] ?? '/')),
                $unsafeWithoutCsrf
            ),
            'methods' => $this->indexRouteMethods($routes),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param array<string, mixed> $check
     * @return list<string>
     */
    private function extractMessages(array $check, string $key): array
    {
        $messages = $check[$key] ?? [];

        if (!$this->isArray($messages)) {
            return [];
        }

        return array_values(array_map('strval', $messages));
    }

    private function resolveQueueControlPath(): string
    {
        $path = $this->trimString((string) $this->config->get('queue', 'WORKER.CONTROL_PATH', 'Storage/Framework/Queue'));

        if ($path === '') {
            return $this->frameworkStoragePath('Framework/Queue');
        }

        if ($path[0] === '/' || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
            return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        }

        $normalized = ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);

        if (str_starts_with($normalized, 'Storage' . DIRECTORY_SEPARATOR)) {
            return $this->frameworkBasePath() . DIRECTORY_SEPARATOR . $normalized;
        }

        return $this->frameworkStoragePath($normalized);
    }

    /**
     * @param list<array<string, mixed>> $routes
     * @return array<string, int>
     */
    private function indexRouteMethods(array $routes): array
    {
        $methods = [];

        foreach ($routes as $route) {
            $method = $this->toLowerString((string) ($route['method'] ?? 'GET'));
            $methods[$method] = ($methods[$method] ?? 0) + 1;
        }

        ksort($methods);

        return $methods;
    }

    private function isDefaultCryptoSecret(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        $normalized = $this->normalizeSecret($value);

        foreach (self::DEFAULT_CRYPTO_SECRETS as $secret) {
            if ($normalized === $this->normalizeSecret($secret)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeSecret(string $value): string
    {
        return $this->trimString((string) preg_replace('/\s+/', '', $value));
    }

    private function databaseTableExists(string $table): bool
    {
        try {
            return match ($this->toLowerString((string) $this->database->getAttribute('driverName'))) {
                'sqlite' => $this->database->fetchColumn(
                    "SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?",
                    [$table]
                ) !== false,
                'pgsql' => $this->database->fetchColumn(
                    'SELECT 1 FROM information_schema.tables WHERE table_name = ?',
                    [$table]
                ) !== false,
                'sqlsrv' => $this->database->fetchColumn(
                    'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?',
                    [$table]
                ) !== false,
                default => $this->database->fetchColumn(
                    'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
                    [$table]
                ) !== false,
            };
        } catch (\Throwable) {
            return false;
        }
    }

    private function toBool(mixed $value): bool
    {
        if ($this->isBool($value)) {
            return $value;
        }

        if ($this->isInt($value)) {
            return $value !== 0;
        }

        if ($this->isString($value)) {
            $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            return $parsed ?? false;
        }

        return (bool) $value;
    }
}
