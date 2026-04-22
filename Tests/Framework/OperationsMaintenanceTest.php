<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Console\Commands\AuditListCommand;
use App\Console\Commands\AuditPruneCommand;
use App\Core\Config;
use App\Core\Database;
use App\Providers\ExceptionProvider;
use App\Utilities\Managers\SettingsManager;
use App\Utilities\Managers\Support\AuditLogger;
use App\Utilities\Managers\System\ErrorManager;
use PDO;
use PHPUnit\Framework\TestCase;

final class OperationsMaintenanceTest extends TestCase
{
    public function testAuditLoggerSummaryIncludesRetentionAndSeverityBreakdown(): void
    {
        $database = $this->makeSqliteDatabase();
        $this->createAuditTable($database);
        $audit = $this->makeAuditLogger($database, [
            'AUDIT' => [
                'ENABLED' => true,
                'SUMMARY_LIMIT' => 50,
                'RETENTION_HOURS' => 72,
            ],
        ]);

        $audit->record('framework.booted', ['actor_id' => 'system'], 'framework', 'info');
        $audit->record('queue.warning', ['actor_id' => 'worker'], 'queue', 'warning');
        $audit->record('queue.warning.repeat', ['actor_id' => 'worker'], 'queue', 'warning');

        $summary = $audit->summary(86400);

        self::assertTrue((bool) $summary['available']);
        self::assertSame(3, $summary['stored']);
        self::assertSame(3, $summary['recent_count']);
        self::assertSame(['framework' => 1, 'queue' => 2], $summary['categories']);
        self::assertSame(['info' => 1, 'warning' => 2], $summary['severities']);
        self::assertSame(72, $summary['retention_hours']);
        self::assertIsInt($summary['oldest_at']);
        self::assertIsInt($summary['newest_at']);
    }

    public function testAuditCriteriaAndPruneCommandRemoveMatchingHistoricalRecords(): void
    {
        $database = $this->makeSqliteDatabase();
        $this->createAuditTable($database);
        $audit = $this->makeAuditLogger($database);
        $now = time();

        $database->execute(
            'INSERT INTO "framework_audit_log" ("category", "event", "severity", "actor_type", "actor_id", "context", "created_at") VALUES (?, ?, ?, ?, ?, ?, ?)',
            ['framework', 'framework.old', 'info', 'system', '1', '{}', $now - 7200]
        );
        $database->execute(
            'INSERT INTO "framework_audit_log" ("category", "event", "severity", "actor_type", "actor_id", "context", "created_at") VALUES (?, ?, ?, ?, ?, ?, ?)',
            ['auth', 'auth.recent', 'warning', 'user', '42', '{"id":42}', $now]
        );

        $records = $audit->recent(10, ['category' => 'auth']);

        self::assertCount(1, $records);
        self::assertSame('auth.recent', $records[0]['event']);

        $list = new class($audit) extends AuditListCommand {
            protected function line(string $message = ''): void
            {
            }

            protected function info(string $message): void
            {
            }
        };
        self::assertSame(0, $list->handle([], ['category' => 'auth', 'limit' => 10, 'json' => false]));

        $prune = new class($audit) extends AuditPruneCommand {
            protected function line(string $message = ''): void
            {
            }

            protected function info(string $message): void
            {
            }
        };
        $exitCode = $prune->handle([], ['hours' => 1, 'category' => 'framework']);

        self::assertSame(0, $exitCode);
        self::assertCount(1, $audit->recent(10));
        self::assertSame('auth.recent', $audit->recent(10)[0]['event']);
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

    private function createAuditTable(Database $database): void
    {
        $database->query(
            'CREATE TABLE "framework_audit_log" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "category" TEXT, "event" TEXT, "severity" TEXT, "actor_type" TEXT, "actor_id" TEXT, "context" TEXT, "created_at" INTEGER)'
        );
    }

    /**
     * @param array<string, mixed> $operationsConfig
     */
    private function makeAuditLogger(Database $database, array $operationsConfig = []): AuditLogger
    {
        $config = new class($operationsConfig) extends Config {
            /**
             * @param array<string, mixed> $operationsConfig
             */
            public function __construct(private readonly array $operationsConfig)
            {
            }

            public function get(string $file, ?string $key = null, mixed $default = null): mixed
            {
                if (strtolower($file) !== 'operations') {
                    return $default;
                }

                $config = array_replace_recursive([
                    'AUDIT' => [
                        'ENABLED' => true,
                        'SUMMARY_LIMIT' => 250,
                        'RETENTION_HOURS' => 720,
                    ],
                ], $this->operationsConfig);

                if ($key === null || $key === '') {
                    return $config;
                }

                $current = $config;

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

        return new AuditLogger($database, $config, new ErrorManager(new ExceptionProvider()));
    }
}
