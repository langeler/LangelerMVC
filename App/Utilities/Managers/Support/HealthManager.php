<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Support;

use App\Contracts\Async\EventDispatcherInterface;
use App\Contracts\Support\AuditLoggerInterface;
use App\Contracts\Support\FrameworkDoctorInterface;
use App\Contracts\Support\HealthManagerInterface;
use App\Core\Config;
use App\Core\Database;
use App\Core\Router;
use App\Utilities\Managers\Async\QueueManager;
use App\Utilities\Managers\Data\CacheManager;
use App\Utilities\Managers\Data\ModuleManager;
use App\Utilities\Managers\Data\SessionManager;
use Throwable;

class HealthManager implements HealthManagerInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly Database $database,
        private readonly CacheManager $cache,
        private readonly SessionManager $sessionManager,
        private readonly QueueManager $queue,
        private readonly NotificationManager $notifications,
        private readonly PaymentManager $payments,
        private readonly PasskeyManager $passkeys,
        private readonly MailManager $mail,
        private readonly OtpManager $otp,
        private readonly ModuleManager $modules,
        private readonly Router $router,
        private readonly EventDispatcherInterface $events,
        private readonly AuditLoggerInterface $audit,
        private readonly FrameworkDoctorInterface $doctor
    ) {
    }

    public function liveness(): array
    {
        return [
            'status' => 200,
            'live' => true,
            'timestamp' => gmdate('c'),
            'app' => [
                'name' => (string) $this->config->get('app', 'NAME', 'LangelerMVC'),
                'version' => (string) $this->config->get('app', 'VERSION', 'unknown'),
            ],
            'runtime' => [
                'php' => PHP_VERSION,
                'sapi' => PHP_SAPI,
            ],
        ];
    }

    public function readiness(): array
    {
        $checks = [
            'database' => $this->databaseCheck(),
            'cache' => $this->cacheCheck(),
            'session' => $this->sessionCheck(),
            'queue' => $this->queueCheck(),
            'notifications' => $this->notificationCheck(),
            'payments' => $this->paymentCheck(),
            'mail' => $this->mailCheck(),
            'passkeys' => $this->passkeyCheck(),
            'audit' => $this->auditCheck(),
            'framework' => $this->frameworkCheck(),
        ];

        $ready = !in_array(false, array_map(
            static fn(array $check): bool => (bool) ($check['ok'] ?? false),
            $checks
        ), true);

        return [
            'status' => $ready ? 200 : 503,
            'ready' => $ready,
            'timestamp' => gmdate('c'),
            'checks' => $checks,
        ];
    }

    public function capabilities(): array
    {
        return [
            'runtime' => [
                'php' => PHP_VERSION,
                'sapi' => PHP_SAPI,
                'extensions' => [
                    'pdo' => extension_loaded('pdo'),
                    'redis' => extension_loaded('redis'),
                    'memcached' => extension_loaded('memcached'),
                    'imagick' => extension_loaded('imagick'),
                ],
            ],
            'database' => [
                'driver' => (string) $this->database->getAttribute('driverName'),
            ],
            'cache' => [
                'driver' => $this->cache->getDriverName(),
                'capabilities' => $this->cache->capabilities(),
            ],
            'session' => $this->sessionManager->capabilities(),
            'queue' => [
                'driver' => $this->queue->driverName(),
                'drivers' => $this->queue->availableDrivers(),
                'capabilities' => $this->queue->capabilities(),
            ],
            'notifications' => [
                'channels' => $this->notifications->availableChannels(),
            ],
            'payments' => [
                'driver' => $this->payments->driverName(),
                'drivers' => $this->payments->availableDrivers(),
                'methods' => $this->payments->supportedMethods(),
                'flows' => $this->payments->supportedFlows(),
                'capabilities' => $this->payments->capabilities(),
                'catalog' => $this->payments->driverCatalog(),
            ],
            'mail' => [
                'driver' => $this->mail->driverName(),
                'capabilities' => $this->mail->capabilities(),
            ],
            'passkeys' => [
                'driver' => $this->passkeys->driverName(),
                'capabilities' => $this->passkeys->capabilities(),
            ],
            'otp' => [
                'digits' => (int) $this->config->get('auth', 'OTP.DIGITS', 6),
                'period' => (int) $this->config->get('auth', 'OTP.PERIOD', 30),
                'trusted_device_days' => (int) $this->config->get('auth', 'OTP.TRUSTED_DEVICE_DAYS', 30),
            ],
            'audit' => $this->audit->capabilities(),
            'modules' => array_keys($this->modules->getModules()),
            'routes' => [
                'count' => count($this->router->listRoutes()),
            ],
            'events' => [
                'registered' => array_keys($this->events->listeners()),
            ],
        ];
    }

    public function report(): array
    {
        $live = $this->liveness();
        $ready = $this->readiness();

        return [
            'status' => (int) ($ready['status'] ?? 503),
            'live' => (bool) ($live['live'] ?? true),
            'ready' => (bool) ($ready['ready'] ?? false),
            'timestamp' => gmdate('c'),
            'checks' => $ready['checks'] ?? [],
            'capabilities' => $this->capabilities(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function databaseCheck(): array
    {
        try {
            $connected = $this->database->fetchColumn('SELECT 1') !== false;

            return [
                'ok' => $connected,
                'driver' => (string) $this->database->getAttribute('driverName'),
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'driver' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function cacheCheck(): array
    {
        if (!$this->cache->isEnabled()) {
            return [
                'ok' => true,
                'enabled' => false,
                'driver' => $this->cache->getDriverName(),
            ];
        }

        $key = 'health:cache:' . bin2hex(random_bytes(8));
        $payload = [
            'checked_at' => time(),
            'driver' => $this->cache->getDriverName(),
        ];

        try {
            $stored = $this->cache->put($key, $payload, 30);
            $fetched = $this->cache->get($key);
            $forgotten = $this->cache->forget($key);

            return [
                'ok' => $stored && $forgotten && $fetched === $payload,
                'enabled' => true,
                'driver' => $this->cache->getDriverName(),
                'roundtrip' => [
                    'stored' => $stored,
                    'fetched' => $fetched === $payload,
                    'forgotten' => $forgotten,
                ],
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'enabled' => true,
                'driver' => $this->cache->getDriverName(),
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionCheck(): array
    {
        $normalized = $this->sessionManager->normalizeConfiguration(
            (array) $this->config->get('session', null, [])
        );
        $driver = (string) ($normalized['DRIVER'] ?? 'native');
        $errors = [];
        $surface = [
            'driver' => $driver,
            'capabilities' => $this->sessionManager->capabilities(),
        ];

        try {
            $this->sessionManager->assertSupportedConfiguration($normalized);
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }

        if (in_array($driver, ['native', 'file'], true)) {
            $path = $this->resolveSessionSavePath($normalized);
            $surface['path'] = $path;
            $surface['path_exists'] = $path !== null && is_dir($path);
            $surface['path_writable'] = $path !== null && is_writable($path);

            if ($path === null || !is_dir($path)) {
                $errors[] = 'Session save path is missing.';
            } elseif (!is_writable($path)) {
                $errors[] = 'Session save path is not writable.';
            }
        }

        if ($driver === 'database') {
            $table = (string) (($normalized['DATABASE']['TABLE'] ?? 'framework_sessions'));
            $surface['table'] = $table;
            $surface['table_exists'] = $this->databaseTableExists($table);

            if (!$surface['table_exists']) {
                $errors[] = sprintf('Session database table [%s] is missing.', $table);
            }
        }

        return [
            'ok' => $errors === [],
            ...$surface,
            'errors' => $errors,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function queueCheck(): array
    {
        $configuration = $this->queue->configuration();
        $driver = (string) ($configuration['driver'] ?? $this->queue->driverName());
        $defaultQueue = (string) ($configuration['default_queue'] ?? 'default');
        $worker = $this->queue->workerState();
        $errors = [];

        try {
            $pendingCount = count($this->queue->pending($defaultQueue));
            $failedCount = count($this->queue->failed());
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'driver' => $driver,
                'queue' => $defaultQueue,
                'error' => $exception->getMessage(),
                'configuration' => $configuration,
            ];
        }

        $tables = [];

        if ($driver === 'database') {
            $tables = [
                'jobs' => $this->databaseTableExists('framework_jobs'),
                'failed_jobs' => $this->databaseTableExists('framework_failed_jobs'),
            ];

            if (!$tables['jobs']) {
                $errors[] = 'Queue storage table [framework_jobs] is missing.';
            }

            if (!$tables['failed_jobs']) {
                $errors[] = 'Failed queue storage table [framework_failed_jobs] is missing.';
            }
        }

        if (!(bool) ($worker['control_path_writable'] ?? false)) {
            $errors[] = 'Queue worker control path is not writable.';
        }

        return [
            'ok' => $errors === [],
            'driver' => $driver,
            'queue' => $defaultQueue,
            'pending' => $pendingCount,
            'failed_jobs' => $failedCount,
            'configuration' => $configuration,
            'worker' => $worker,
            'tables' => $tables,
            'errors' => $errors,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function notificationCheck(): array
    {
        return [
            'ok' => $this->notifications->availableChannels() !== [],
            'channels' => $this->notifications->availableChannels(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentCheck(): array
    {
        return [
            'ok' => $this->payments->availableDrivers() !== [],
            'driver' => $this->payments->driverName(),
            'methods' => $this->payments->supportedMethods(),
            'flows' => $this->payments->supportedFlows(),
            'catalog' => $this->payments->driverCatalog(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mailCheck(): array
    {
        return [
            'ok' => $this->mail->supports('transport.' . $this->mail->driverName()),
            'driver' => $this->mail->driverName(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function passkeyCheck(): array
    {
        return [
            'ok' => true,
            'driver' => $this->passkeys->driverName(),
            'passwordless' => $this->passkeys->supports('passwordless'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function auditCheck(): array
    {
        $summary = $this->audit->summary(86400);
        $available = (bool) ($summary['available'] ?? ($summary['enabled'] ?? false));

        return [
            'ok' => (bool) ($summary['enabled'] ?? false) && $available && $this->databaseTableExists('framework_audit_log'),
            'summary' => $summary,
            'table_exists' => $this->databaseTableExists('framework_audit_log'),
            'retention_hours' => (int) ($summary['retention_hours'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function frameworkCheck(): array
    {
        $report = $this->doctor->inspect(false);

        return [
            'ok' => (bool) ($report['healthy'] ?? false),
            'status' => (int) ($report['status'] ?? 503),
            'errors' => is_array($report['errors'] ?? null) ? $report['errors'] : [],
            'warnings' => is_array($report['warnings'] ?? null) ? $report['warnings'] : [],
        ];
    }

    /**
     * @param array<string, mixed> $normalized
     */
    private function resolveSessionSavePath(array $normalized): ?string
    {
        $path = $normalized['SAVE']['PATH'] ?? null;

        if (!is_string($path) || trim($path) === '') {
            return null;
        }

        $trimmed = trim($path);

        if ($trimmed[0] === '/' || preg_match('/^[A-Za-z]:[\\\\\\/]/', $trimmed) === 1) {
            return $trimmed;
        }

        return dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $trimmed), DIRECTORY_SEPARATOR);
    }

    private function databaseTableExists(string $table): bool
    {
        try {
            return match (strtolower((string) $this->database->getAttribute('driverName'))) {
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
        } catch (Throwable) {
            return false;
        }
    }
}
