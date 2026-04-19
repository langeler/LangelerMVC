<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Support;

use App\Contracts\Async\EventDispatcherInterface;
use App\Contracts\Support\AuditLoggerInterface;
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
        private readonly AuditLoggerInterface $audit
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
        return [
            'ok' => $this->cache->isEnabled(),
            'driver' => $this->cache->getDriverName(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionCheck(): array
    {
        return [
            'ok' => true,
            'capabilities' => $this->sessionManager->capabilities(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function queueCheck(): array
    {
        return [
            'ok' => true,
            'driver' => $this->queue->driverName(),
            'failed_jobs' => count($this->queue->failed()),
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

        return [
            'ok' => (bool) ($summary['enabled'] ?? false),
            'summary' => $summary,
        ];
    }
}
