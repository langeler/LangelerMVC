<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Async;

use App\Contracts\Async\FailedJobStoreInterface;
use App\Contracts\Async\JobInterface;
use App\Contracts\Async\QueueDriverInterface;
use App\Contracts\Support\NotificationManagerInterface;
use App\Core\Config;
use App\Exceptions\AppException;
use App\Providers\CoreProvider;
use App\Providers\QueueProvider;
use App\Utilities\Managers\Data\ModuleManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;

class QueueManager
{
    use ArrayTrait, CheckerTrait, ManipulationTrait, TypeCheckerTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    private ?QueueDriverInterface $driver = null;

    public function __construct(
        private readonly Config $config,
        private readonly QueueProvider $provider,
        private readonly FailedJobStoreInterface $failedJobs,
        private readonly ModuleManager $moduleManager,
        private readonly CoreProvider $coreProvider,
        private readonly ErrorManager $errorManager
    ) {
        $this->provider->registerServices();
    }

    public function driverName(): string
    {
        return $this->toLowerString((string) $this->config->get('queue', 'DRIVER', 'sync'));
    }

    /**
     * @return list<string>
     */
    public function availableDrivers(): array
    {
        return $this->provider->getSupportedDrivers();
    }

    /**
     * @return array<string, mixed>
     */
    public function capabilities(): array
    {
        return $this->driver()->capabilities();
    }

    public function supports(string $feature): bool
    {
        return $this->driver()->supports($feature);
    }

    public function dispatch(JobInterface $job, string $queue = 'default', int $delay = 0): string
    {
        $id = $this->driver()->push([
            'type' => 'job',
            'class' => $job::class,
            'payload' => $job->payload(),
        ], $queue, $delay);

        if ($this->driverName() === 'sync') {
            $this->work($queue, 1);
        }

        return $id;
    }

    public function dispatchListener(string|array $listener, string $event, array $payload = [], string $queue = 'default', int $delay = 0): string
    {
        if (is_callable($listener) && !is_array($listener) && !is_string($listener)) {
            throw new AppException('Queued listeners must be defined as a class name or [class, method] pair.');
        }

        $id = $this->driver()->push([
            'type' => 'listener',
            'handler' => $listener,
            'payload' => [
                'event' => $event,
                'data' => $payload,
            ],
        ], $queue, $delay);

        if ($this->driverName() === 'sync') {
            $this->work($queue, 1);
        }

        return $id;
    }

    /**
     * @param array<string, mixed> $notifiable
     * @param list<string> $channels
     */
    public function dispatchNotification(array $notifiable, string $notificationClass, array $notificationPayload, array $channels, string $queue = 'default', int $delay = 0): string
    {
        $id = $this->driver()->push([
            'type' => 'notification',
            'class' => $notificationClass,
            'payload' => [
                'notifiable' => $notifiable,
                'notification' => $notificationPayload,
                'channels' => $channels,
            ],
        ], $queue, $delay);

        if ($this->driverName() === 'sync') {
            $this->work($queue, 1);
        }

        return $id;
    }

    public function work(string $queue = 'default', int $max = 1): int
    {
        $processed = 0;
        $limit = max(1, $max);

        while ($processed < $limit) {
            $envelope = $this->driver()->pop($queue);

            if (!$this->isArray($envelope) || $envelope === []) {
                break;
            }

            try {
                $this->invokeEnvelope($envelope);
                $this->driver()->delete((string) ($envelope['id'] ?? ''));
            } catch (\Throwable $exception) {
                $this->failedJobs->record($envelope, $exception);
                $this->driver()->delete((string) ($envelope['id'] ?? ''));
                $this->errorManager->logThrowable($exception, 'queue', 'userWarning');
            }

            $processed++;
        }

        return $processed;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pending(string $queue = 'default'): array
    {
        return $this->driver()->pending($queue);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function failed(): array
    {
        return $this->failedJobs->all();
    }

    public function retry(string $failedJobId, string $queue = 'default'): ?string
    {
        $failed = $this->failedJobs->find($failedJobId);

        if (!$this->isArray($failed) || $failed === []) {
            return null;
        }

        $id = $this->driver()->push([
            'type' => (string) ($failed['type'] ?? 'job'),
            'class' => (string) ($failed['class'] ?? ''),
            'handler' => $failed['handler'] ?? null,
            'payload' => is_array($failed['payload'] ?? null) ? $failed['payload'] : [],
            'attempts' => (int) ($failed['attempts'] ?? 0),
        ], $queue);

        $this->failedJobs->delete($failedJobId);

        if ($this->driverName() === 'sync') {
            $this->work($queue, 1);
        }

        return $id;
    }

    private function driver(): QueueDriverInterface
    {
        if ($this->driver instanceof QueueDriverInterface) {
            return $this->driver;
        }

        $this->driver = $this->provider->getQueueDriver([
            'DRIVER' => $this->driverName(),
        ]);

        return $this->driver;
    }

    /**
     * @param array<string, mixed> $envelope
     */
    private function invokeEnvelope(array $envelope): mixed
    {
        return match ((string) ($envelope['type'] ?? 'job')) {
            'listener' => $this->invokeListenerEnvelope($envelope),
            'notification' => $this->invokeNotificationEnvelope($envelope),
            default => $this->invokeJobEnvelope($envelope),
        };
    }

    /**
     * @param array<string, mixed> $envelope
     */
    private function invokeJobEnvelope(array $envelope): mixed
    {
        $class = (string) ($envelope['class'] ?? '');
        $job = $this->resolveClass($class);

        if (!$job instanceof JobInterface) {
            throw new AppException(sprintf('Queued class [%s] does not implement the job contract.', $class));
        }

        $job->withPayload(is_array($envelope['payload'] ?? null) ? $envelope['payload'] : []);

        return $job->handle();
    }

    /**
     * @param array<string, mixed> $envelope
     */
    private function invokeListenerEnvelope(array $envelope): mixed
    {
        $handler = $envelope['handler'] ?? null;
        $payload = is_array($envelope['payload'] ?? null) ? $envelope['payload'] : [];
        $event = (string) ($payload['event'] ?? '');
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        return $this->invokeListener($handler, $event, $data);
    }

    /**
     * @param array<string, mixed> $envelope
     */
    private function invokeNotificationEnvelope(array $envelope): mixed
    {
        $class = (string) ($envelope['class'] ?? '');
        $payload = is_array($envelope['payload'] ?? null) ? $envelope['payload'] : [];
        $notificationManager = $this->resolveClass(NotificationManagerInterface::class);

        if (!$notificationManager instanceof NotificationManagerInterface) {
            throw new AppException('Queued notifications require a resolvable notification manager.');
        }

        if (!method_exists($notificationManager, 'deliverSnapshot')) {
            throw new AppException('The resolved notification manager does not support queued delivery.');
        }

        return $notificationManager->deliverSnapshot(
            is_array($payload['notifiable'] ?? null) ? $payload['notifiable'] : [],
            $class,
            is_array($payload['notification'] ?? null) ? $payload['notification'] : [],
            array_values(array_map('strval', (array) ($payload['channels'] ?? [])))
        );
    }

    private function invokeListener(mixed $listener, string $event, array $payload): mixed
    {
        if (is_string($listener)) {
            $instance = $this->resolveClass($listener);

            if (method_exists($instance, 'handle')) {
                return $instance->handle($event, $payload);
            }

            return $instance($event, $payload);
        }

        if (is_array($listener) && count($listener) === 2) {
            [$target, $method] = $listener;
            $instance = is_object($target) ? $target : $this->resolveClass((string) $target);

            if (!method_exists($instance, (string) $method)) {
                throw new AppException(sprintf('Listener method [%s] is not available.', (string) $method));
            }

            return $instance->{(string) $method}($event, $payload);
        }

        if (is_callable($listener)) {
            return $listener($event, $payload);
        }

        throw new AppException('Invalid queue listener definition.');
    }

    private function resolveClass(string $class): object
    {
        if ($class === \App\Contracts\Support\NotificationManagerInterface::class) {
            return $this->coreProvider->resolveClass($class);
        }

        if ($class !== '' && str_starts_with($class, 'App\\Modules\\')) {
            return $this->moduleManager->resolveModule($class);
        }

        return $this->coreProvider->resolveClass($class);
    }
}
