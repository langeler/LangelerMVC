<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Contracts\Async\FailedJobStoreInterface;
use App\Contracts\Async\JobInterface;
use App\Contracts\Async\QueueDriverInterface;
use App\Core\Config;
use App\Core\Database;
use App\Drivers\Queue\DatabaseQueueDriver;
use App\Drivers\Queue\SyncQueueDriver;
use App\Exceptions\AppException;
use App\Providers\CoreProvider;
use App\Providers\ExceptionProvider;
use App\Providers\QueueProvider;
use App\Utilities\Managers\Async\DatabaseFailedJobStore;
use App\Utilities\Managers\Async\QueueManager;
use App\Utilities\Managers\Data\ModuleManager;
use App\Utilities\Managers\SettingsManager;
use App\Utilities\Managers\System\ErrorManager;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AsyncOperationsHardeningTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $pathsToDelete = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->pathsToDelete) as $path) {
            if (is_file($path)) {
                @unlink($path);
                continue;
            }

            if (is_dir($path)) {
                $entries = scandir($path);

                if (is_array($entries)) {
                    foreach ($entries as $entry) {
                        if ($entry === '.' || $entry === '..') {
                            continue;
                        }

                        @unlink($path . DIRECTORY_SEPARATOR . $entry);
                    }
                }

                @rmdir($path);
            }
        }

        $this->pathsToDelete = [];
    }

    public function testQueueManagerRetriesSyncJobsUntilTerminalFailure(): void
    {
        $failedJobs = new InMemoryFailedJobStore();
        $job = new AlwaysFailingQueueJob();
        $queue = $this->makeQueueManager(
            [
                'DRIVER' => 'sync',
                'DEFAULT_QUEUE' => 'default',
                'MAX_ATTEMPTS' => 3,
                'BACKOFF' => [
                    'STRATEGY' => 'exponential',
                    'SECONDS' => 5,
                    'MAX_SECONDS' => 60,
                ],
                'WORKER' => [
                    'SLEEP' => 0,
                    'MAX_RUNTIME' => 0,
                    'MAX_MEMORY_MB' => 0,
                ],
                'FAILED' => [
                    'PRUNE_AFTER_HOURS' => 168,
                ],
            ],
            new SyncQueueDriver(),
            $failedJobs,
            [
                AlwaysFailingQueueJob::class => $job,
            ]
        );

        $queue->dispatch($job);

        self::assertSame([], $queue->pending());
        self::assertCount(1, $queue->failed());
        self::assertSame(3, $queue->failed()[0]['attempts']);
        self::assertSame(3, $job->attempts);
        self::assertSame(AlwaysFailingQueueJob::class, $queue->failed()[0]['class']);
    }

    public function testQueueManagerRetryAllRequeuesFailedJobsAndClearsFailedStore(): void
    {
        $failedJobs = new InMemoryFailedJobStore();
        $job = new SuccessfulQueueJob();
        $failedJobs->seed([
            'id' => 'failed-job-1',
            'queue' => 'default',
            'type' => 'job',
            'class' => SuccessfulQueueJob::class,
            'handler' => null,
            'payload' => ['reference' => 'retry-spec'],
            'attempts' => 3,
            'exception' => 'RuntimeException: simulated failure',
            'failed_at' => time() - 120,
        ]);

        $queue = $this->makeQueueManager(
            [
                'DRIVER' => 'sync',
                'DEFAULT_QUEUE' => 'default',
                'MAX_ATTEMPTS' => 3,
                'BACKOFF' => [
                    'STRATEGY' => 'fixed',
                    'SECONDS' => 0,
                    'MAX_SECONDS' => 0,
                ],
                'WORKER' => [
                    'SLEEP' => 0,
                    'MAX_RUNTIME' => 0,
                    'MAX_MEMORY_MB' => 0,
                ],
                'FAILED' => [
                    'PRUNE_AFTER_HOURS' => 168,
                ],
            ],
            new SyncQueueDriver(),
            $failedJobs,
            [
                SuccessfulQueueJob::class => $job,
            ]
        );

        $retried = $queue->retryAll();

        self::assertCount(1, $retried);
        self::assertSame([], $queue->failed());
        self::assertSame([['reference' => 'retry-spec']], $job->handledPayloads);
    }

    public function testDatabaseFailedJobStorePrunesRowsBeforeCutoff(): void
    {
        $database = $this->makeSqliteDatabase();
        $database->query(
            'CREATE TABLE "framework_failed_jobs" ("id" TEXT PRIMARY KEY, "queue" TEXT, "type" TEXT, "class" TEXT, "handler" TEXT, "payload" TEXT, "attempts" INTEGER, "exception" TEXT, "failed_at" INTEGER)'
        );
        $database->execute(
            'INSERT INTO "framework_failed_jobs" ("id", "queue", "type", "class", "handler", "payload", "attempts", "exception", "failed_at") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            ['old-job', 'default', 'job', 'DemoJob', 'null', '{}', 3, 'RuntimeException: old', time() - 7200]
        );
        $database->execute(
            'INSERT INTO "framework_failed_jobs" ("id", "queue", "type", "class", "handler", "payload", "attempts", "exception", "failed_at") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            ['new-job', 'default', 'job', 'DemoJob', 'null', '{}', 1, 'RuntimeException: new', time()]
        );

        $store = new DatabaseFailedJobStore($database);
        $deleted = $store->prune(time() - 3600);

        self::assertSame(1, $deleted);
        self::assertCount(1, $store->all());
        self::assertSame('new-job', $store->all()[0]['id']);
    }

    public function testDatabaseQueueDriverRequiresFrameworkJobsTable(): void
    {
        $driver = new DatabaseQueueDriver($this->makeSqliteDatabase());

        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Queue storage table [framework_jobs] is missing.');

        $driver->pending();
    }

    public function testQueueStopSignalPreventsWorkerFromClaimingNextJob(): void
    {
        $database = $this->makeSqliteDatabase();
        $this->createQueueTables($database);
        $controlPath = sys_get_temp_dir() . '/langelermvc-queue-stop-' . bin2hex(random_bytes(4));
        $this->pathsToDelete[] = $controlPath;
        $job = new SuccessfulQueueJob();
        $queue = $this->makeQueueManager(
            [
                'DRIVER' => 'database',
                'DEFAULT_QUEUE' => 'default',
                'MAX_ATTEMPTS' => 3,
                'BACKOFF' => [
                    'STRATEGY' => 'fixed',
                    'SECONDS' => 0,
                    'MAX_SECONDS' => 0,
                ],
                'WORKER' => [
                    'SLEEP' => 0,
                    'MAX_RUNTIME' => 0,
                    'MAX_MEMORY_MB' => 0,
                    'CONTROL_PATH' => $controlPath,
                ],
                'FAILED' => [
                    'PRUNE_AFTER_HOURS' => 168,
                ],
            ],
            new DatabaseQueueDriver($database),
            new DatabaseFailedJobStore($database),
            [
                SuccessfulQueueJob::class => $job,
            ]
        );

        $queue->dispatch($job);

        self::assertTrue($queue->signalStop(false));
        self::assertTrue((bool) $queue->workerState()['stop_requested']);
        self::assertSame(0, $queue->work('default', 0, ['stop_when_empty' => false, 'sleep' => 0, 'max_runtime' => 1, 'max_memory_mb' => 0]));
        self::assertCount(1, $queue->pending());
        self::assertSame([], $job->handledPayloads);
        self::assertFalse((bool) $queue->workerState()['stop_requested']);
    }

    public function testQueueDrainSignalProcessesPendingJobsThenStops(): void
    {
        $database = $this->makeSqliteDatabase();
        $this->createQueueTables($database);
        $controlPath = sys_get_temp_dir() . '/langelermvc-queue-drain-' . bin2hex(random_bytes(4));
        $this->pathsToDelete[] = $controlPath;
        $job = new SuccessfulQueueJob();
        $queue = $this->makeQueueManager(
            [
                'DRIVER' => 'database',
                'DEFAULT_QUEUE' => 'default',
                'MAX_ATTEMPTS' => 3,
                'BACKOFF' => [
                    'STRATEGY' => 'fixed',
                    'SECONDS' => 0,
                    'MAX_SECONDS' => 0,
                ],
                'WORKER' => [
                    'SLEEP' => 0,
                    'MAX_RUNTIME' => 0,
                    'MAX_MEMORY_MB' => 0,
                    'CONTROL_PATH' => $controlPath,
                ],
                'FAILED' => [
                    'PRUNE_AFTER_HOURS' => 168,
                ],
            ],
            new DatabaseQueueDriver($database),
            new DatabaseFailedJobStore($database),
            [
                SuccessfulQueueJob::class => $job,
            ]
        );

        $queue->dispatch($job);

        self::assertTrue($queue->signalStop(true));
        self::assertTrue((bool) $queue->workerState()['drain_requested']);
        self::assertSame(1, $queue->work('default', 0, ['stop_when_empty' => false, 'sleep' => 0, 'max_runtime' => 1, 'max_memory_mb' => 0]));
        self::assertSame([], $queue->pending());
        self::assertCount(1, $job->handledPayloads);
        self::assertFalse((bool) $queue->workerState()['drain_requested']);
    }

    /**
     * @param array<string, mixed> $queueConfig
     * @param array<string, object> $resolved
     */
    private function makeQueueManager(
        array $queueConfig,
        QueueDriverInterface $driver,
        FailedJobStoreInterface $failedJobs,
        array $resolved
    ): QueueManager {
        $config = new class($queueConfig) extends Config {
            /**
             * @param array<string, mixed> $queueConfig
             */
            public function __construct(private readonly array $queueConfig)
            {
            }

            public function all(): array
            {
                return ['queue' => $this->queueConfig];
            }

            public function get(string $file, ?string $key = null, mixed $default = null): mixed
            {
                if (strtolower($file) !== 'queue') {
                    return $default;
                }

                if ($key === null || $key === '') {
                    return $this->queueConfig;
                }

                $current = $this->queueConfig;

                foreach (explode('.', $key) as $segment) {
                    if (!is_array($current)) {
                        return $default;
                    }

                    $resolvedSegment = null;

                    foreach ($current as $candidate => $value) {
                        if (strcasecmp((string) $candidate, $segment) === 0) {
                            $resolvedSegment = $candidate;
                            break;
                        }
                    }

                    if ($resolvedSegment === null) {
                        return $default;
                    }

                    $current = $current[$resolvedSegment];
                }

                return $current;
            }
        };

        $provider = new class($driver) extends QueueProvider {
            public function __construct(private readonly QueueDriverInterface $driver)
            {
                parent::__construct();
            }

            public function registerServices(): void
            {
            }

            public function getQueueDriver(array $settings): QueueDriverInterface
            {
                return $this->driver;
            }

            public function getSupportedDrivers(): array
            {
                return [$this->driver->driverName()];
            }
        };

        $modules = new class extends ModuleManager {
            public function __construct()
            {
            }
        };

        $core = new class($resolved) extends CoreProvider {
            /**
             * @param array<string, object> $resolved
             */
            public function __construct(private readonly array $resolved)
            {
                parent::__construct();
            }

            public function resolveClass(string $classOrAlias): object
            {
                return $this->resolved[$classOrAlias]
                    ?? throw new RuntimeException(sprintf('Unresolved queue test dependency [%s].', $classOrAlias));
            }
        };

        return new QueueManager(
            $config,
            $provider,
            $failedJobs,
            $modules,
            $core,
            new ErrorManager(new ExceptionProvider())
        );
    }

    private function makeSqliteDatabase(): Database
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return new Database(
            new class extends SettingsManager {
                public function __construct()
                {
                }

                public function getAllSettings(string $fileName): array
                {
                    return [
                        'CONNECTION' => 'sqlite',
                        'DRIVER' => 'sqlite',
                        'DATABASE' => ':memory:',
                    ];
                }
            },
            new ErrorManager(new ExceptionProvider()),
            pdo: $pdo,
            config: [
                'CONNECTION' => 'sqlite',
                'DRIVER' => 'sqlite',
                'DATABASE' => ':memory:',
            ]
        );
    }

    private function createQueueTables(Database $database): void
    {
        $database->query(
            'CREATE TABLE "framework_jobs" ("id" TEXT PRIMARY KEY, "queue" TEXT, "type" TEXT, "class" TEXT, "handler" TEXT, "payload" TEXT, "attempts" INTEGER, "available_at" INTEGER, "reserved_at" INTEGER NULL, "created_at" INTEGER)'
        );
        $database->query(
            'CREATE TABLE "framework_failed_jobs" ("id" TEXT PRIMARY KEY, "queue" TEXT, "type" TEXT, "class" TEXT, "handler" TEXT, "payload" TEXT, "attempts" INTEGER, "exception" TEXT, "failed_at" INTEGER)'
        );
    }
}

final class InMemoryFailedJobStore implements FailedJobStoreInterface
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $records = [];

    /**
     * @param array<string, mixed> $record
     */
    public function seed(array $record): void
    {
        $id = (string) ($record['id'] ?? bin2hex(random_bytes(16)));
        $this->records[$id] = [
            'id' => $id,
            'queue' => (string) ($record['queue'] ?? 'default'),
            'type' => (string) ($record['type'] ?? 'job'),
            'class' => (string) ($record['class'] ?? ''),
            'handler' => $record['handler'] ?? null,
            'payload' => is_array($record['payload'] ?? null) ? $record['payload'] : [],
            'attempts' => (int) ($record['attempts'] ?? 0),
            'exception' => (string) ($record['exception'] ?? ''),
            'failed_at' => (int) ($record['failed_at'] ?? time()),
        ];
    }

    public function record(array $envelope, \Throwable $exception): string
    {
        $id = bin2hex(random_bytes(16));
        $this->seed([
            'id' => $id,
            'queue' => (string) ($envelope['queue'] ?? 'default'),
            'type' => (string) ($envelope['type'] ?? 'job'),
            'class' => (string) ($envelope['class'] ?? ''),
            'handler' => $envelope['handler'] ?? null,
            'payload' => is_array($envelope['payload'] ?? null) ? $envelope['payload'] : [],
            'attempts' => (int) ($envelope['attempts'] ?? 0),
            'exception' => $exception::class . ': ' . $exception->getMessage(),
            'failed_at' => time(),
        ]);

        return $id;
    }

    public function all(): array
    {
        return array_values($this->records);
    }

    public function find(string $id): ?array
    {
        return $this->records[$id] ?? null;
    }

    public function delete(string $id): bool
    {
        if (!isset($this->records[$id])) {
            return false;
        }

        unset($this->records[$id]);

        return true;
    }

    public function prune(?int $failedBefore = null): int
    {
        if ($failedBefore === null) {
            $deleted = count($this->records);
            $this->records = [];

            return $deleted;
        }

        $deleted = 0;

        foreach ($this->records as $id => $record) {
            if ((int) ($record['failed_at'] ?? 0) < $failedBefore) {
                unset($this->records[$id]);
                $deleted++;
            }
        }

        return $deleted;
    }
}

final class AlwaysFailingQueueJob implements JobInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $payload = [];

    public int $attempts = 0;

    public function name(): string
    {
        return 'always-failing';
    }

    public function withPayload(array $payload): static
    {
        $this->payload = $payload;

        return $this;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function handle(): mixed
    {
        $this->attempts++;

        throw new RuntimeException('Simulated queue failure.');
    }
}

final class SuccessfulQueueJob implements JobInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $payload = [];

    /**
     * @var list<array<string, mixed>>
     */
    public array $handledPayloads = [];

    public function name(): string
    {
        return 'successful';
    }

    public function withPayload(array $payload): static
    {
        $this->payload = $payload;

        return $this;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function handle(): mixed
    {
        $this->handledPayloads[] = $this->payload;

        return true;
    }
}
