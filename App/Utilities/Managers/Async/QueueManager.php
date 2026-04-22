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
        return [
            ...$this->driver()->capabilities(),
            'max_attempts' => $this->maxAttempts(),
            'backoff' => $this->backoffConfiguration(),
            'worker' => $this->workerConfiguration(),
        ];
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

        $this->drainSynchronouslyIfNeeded($queue);

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

        $this->drainSynchronouslyIfNeeded($queue);

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

        $this->drainSynchronouslyIfNeeded($queue);

        return $id;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function work(string $queue = 'default', int $max = 1, array $options = []): int
    {
        $processed = 0;
        $limit = $max > 0 ? $max : PHP_INT_MAX;
        $stopWhenEmpty = $this->optionBool($options, 'stop_when_empty', true);
        $sleep = max(0, $this->optionInt($options, 'sleep', (int) ($this->workerConfiguration()['sleep'] ?? 1)));
        $maxRuntime = max(0, $this->optionInt($options, 'max_runtime', (int) ($this->workerConfiguration()['max_runtime'] ?? 0)));
        $maxMemoryMb = max(0, $this->optionInt($options, 'max_memory_mb', (int) ($this->workerConfiguration()['max_memory_mb'] ?? 256)));
        $startedAt = time();

        while ($processed < $limit) {
            if ($maxRuntime > 0 && (time() - $startedAt) >= $maxRuntime) {
                break;
            }

            if ($maxMemoryMb > 0 && memory_get_usage(true) >= ($maxMemoryMb * 1024 * 1024)) {
                $this->errorManager->logErrorMessage(
                    sprintf('Queue worker reached the configured memory ceiling (%d MB).', $maxMemoryMb),
                    __FILE__,
                    __LINE__,
                    'userWarning',
                    'queue'
                );
                break;
            }

            $envelope = $this->driver()->pop($queue);

            if (!$this->isArray($envelope) || $envelope === []) {
                if ($stopWhenEmpty) {
                    break;
                }

                if ($sleep > 0) {
                    sleep($sleep);
                }

                continue;
            }

            $this->processEnvelope($envelope);
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
    public function failed(?string $queue = null): array
    {
        $failed = $this->failedJobs->all();

        if ($queue === null || $queue === '') {
            return $failed;
        }

        return array_values(array_filter(
            $failed,
            static fn(array $job): bool => (string) ($job['queue'] ?? 'default') === $queue
        ));
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
            'attempts' => 0,
        ], $queue);

        $this->failedJobs->delete($failedJobId);

        $this->drainSynchronouslyIfNeeded($queue);

        return $id;
    }

    /**
     * @return list<string>
     */
    public function retryAll(?string $queue = null, string $targetQueue = 'default'): array
    {
        $retried = [];

        foreach ($this->failed($queue) as $failed) {
            $id = (string) ($failed['id'] ?? '');

            if ($id === '') {
                continue;
            }

            $newId = $this->retry($id, $targetQueue);

            if ($newId !== null) {
                $retried[] = $newId;
            }
        }

        return $retried;
    }

    public function pruneFailed(?int $failedBefore = null): int
    {
        return $this->failedJobs->prune($failedBefore);
    }

    /**
     * @return array<string, mixed>
     */
    public function configuration(): array
    {
        return [
            'driver' => $this->driverName(),
            'default_queue' => (string) $this->config->get('queue', 'DEFAULT_QUEUE', 'default'),
            'retry_after' => (int) $this->config->get('queue', 'RETRY_AFTER', 60),
            'max_attempts' => $this->maxAttempts(),
            'backoff' => $this->backoffConfiguration(),
            'worker' => $this->workerConfiguration(),
            'failed' => [
                'prune_after_hours' => (int) $this->config->get('queue', 'FAILED.PRUNE_AFTER_HOURS', 168),
            ],
        ];
    }

    private function driver(): QueueDriverInterface
    {
        if ($this->driver instanceof QueueDriverInterface) {
            return $this->driver;
        }

        $settings = $this->config->get('queue', null, []);
        $this->driver = $this->provider->getQueueDriver(
            is_array($settings) ? $settings : ['DRIVER' => $this->driverName()]
        );

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

    /**
     * @param array<string, mixed> $envelope
     */
    private function processEnvelope(array $envelope): void
    {
        try {
            $this->invokeEnvelope($envelope);
            $this->driver()->delete((string) ($envelope['id'] ?? ''));
        } catch (\Throwable $exception) {
            $attempts = (int) ($envelope['attempts'] ?? 0);
            $maxAttempts = $this->maxAttempts();

            if ($attempts < $maxAttempts) {
                $delay = $this->retryDelay($attempts);
                $this->driver()->release($envelope, $delay);
                $this->errorManager->logThrowable(
                    new AppException(sprintf(
                        'Queued item [%s] failed on attempt %d/%d and was released for retry after %d second(s): %s',
                        (string) ($envelope['id'] ?? ''),
                        $attempts,
                        $maxAttempts,
                        $delay,
                        $exception->getMessage()
                    ), 0, $exception),
                    'queue',
                    'userWarning'
                );

                return;
            }

            $this->failedJobs->record($envelope, $exception);
            $this->driver()->delete((string) ($envelope['id'] ?? ''));
            $this->errorManager->logThrowable($exception, 'queue', 'userWarning');
        }
    }

    private function maxAttempts(): int
    {
        return max(1, (int) $this->config->get('queue', 'MAX_ATTEMPTS', 3));
    }

    private function retryDelay(int $attempts): int
    {
        if ($this->driverName() === 'sync') {
            return 0;
        }

        $backoff = $this->backoffConfiguration();
        $base = max(0, (int) ($backoff['seconds'] ?? 0));
        $max = max($base, (int) ($backoff['max_seconds'] ?? $base));
        $strategy = $this->toLowerString((string) ($backoff['strategy'] ?? 'fixed'));

        $delay = match ($strategy) {
            'none' => 0,
            'linear' => $base * max(1, $attempts),
            'exponential' => $base * (2 ** max(0, $attempts - 1)),
            default => $base,
        };

        return $max > 0 ? min($delay, $max) : $delay;
    }

    /**
     * @return array<string, int|string>
     */
    private function backoffConfiguration(): array
    {
        return [
            'strategy' => $this->toLowerString((string) $this->config->get('queue', 'BACKOFF.STRATEGY', 'fixed')),
            'seconds' => max(0, (int) $this->config->get('queue', 'BACKOFF.SECONDS', 5)),
            'max_seconds' => max(0, (int) $this->config->get('queue', 'BACKOFF.MAX_SECONDS', 300)),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function workerConfiguration(): array
    {
        return [
            'sleep' => max(0, (int) $this->config->get('queue', 'WORKER.SLEEP', 1)),
            'max_runtime' => max(0, (int) $this->config->get('queue', 'WORKER.MAX_RUNTIME', 0)),
            'max_memory_mb' => max(0, (int) $this->config->get('queue', 'WORKER.MAX_MEMORY_MB', 256)),
        ];
    }

    private function drainSynchronouslyIfNeeded(string $queue): void
    {
        if ($this->driverName() !== 'sync') {
            return;
        }

        $this->work($queue, 0, [
            'stop_when_empty' => true,
            'sleep' => 0,
            'max_runtime' => 0,
            'max_memory_mb' => 0,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function optionBool(array $options, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $options)) {
            return $default;
        }

        $value = $options[$key];

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        if (!is_string($value)) {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $parsed ?? $default;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function optionInt(array $options, string $key, int $default): int
    {
        if (!array_key_exists($key, $options)) {
            return $default;
        }

        return (int) $options[$key];
    }
}
