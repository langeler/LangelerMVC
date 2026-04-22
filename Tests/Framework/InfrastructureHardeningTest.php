<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Console\Commands\ConfigShowCommand;
use App\Core\Config;
use App\Core\Database;
use App\Core\MigrationRunner;
use App\Core\SeedRunner;
use App\Framework\Migrations\CreateFrameworkOperationsTables;
use App\Installer\InstallerWizard;
use App\Modules\WebModule\Migrations\CreatePagesTable;
use App\Providers\CoreProvider;
use App\Providers\ExceptionProvider;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\SettingsManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Managers\Data\ModuleManager;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class InfrastructureHardeningTest extends TestCase
{
    private array $pathsToDelete = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->pathsToDelete) as $path) {
            if (is_file($path)) {
                @unlink($path);
                continue;
            }

            if (is_dir($path)) {
                @rmdir($path);
            }
        }

        $this->pathsToDelete = [];
    }

    public function testDatabaseSupportsNestedTransactionsWithSavepoints(): void
    {
        $database = $this->makeSqliteDatabase();

        $database->query('CREATE TABLE "transaction_demo" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "value" TEXT NOT NULL)');

        self::assertTrue($database->beginTransaction());
        $database->execute('INSERT INTO "transaction_demo" ("value") VALUES (?)', ['outer']);

        self::assertTrue($database->beginTransaction());
        $database->execute('INSERT INTO "transaction_demo" ("value") VALUES (?)', ['inner']);
        self::assertTrue($database->rollBack());
        self::assertTrue($database->commit());

        self::assertSame(1, (int) $database->fetchColumn('SELECT COUNT(*) FROM "transaction_demo"'));
        self::assertSame('outer', $database->fetchColumn('SELECT "value" FROM "transaction_demo" LIMIT 1'));
    }

    public function testMigrationRunnerIncludesFrameworkOwnedOperationalTables(): void
    {
        $database = $this->makeSqliteDatabase();
        $modules = $this->makeModuleManager([CreatePagesTable::class]);
        $runner = new MigrationRunner($database, $modules, new ErrorManager(new ExceptionProvider()));

        $executed = $runner->migrate('Framework');

        self::assertContains('CreateFrameworkOperationsTables', $executed);
        self::assertNotFalse($database->fetchColumn(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'framework_audit_log'"
        ));
        self::assertNotFalse($database->fetchColumn(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'framework_failed_jobs'"
        ));
    }

    public function testMigrationRunnerRefusesToRunWhenMigrationLockIsHeld(): void
    {
        $database = $this->makeSqliteDatabase();
        $modules = $this->makeModuleManager([CreatePagesTable::class]);
        $runner = new MigrationRunner($database, $modules, new ErrorManager(new ExceptionProvider()));

        $database->query('CREATE TABLE "framework_migration_locks" ("name" TEXT PRIMARY KEY, "owner" TEXT NOT NULL, "acquired_at" INTEGER NOT NULL)');
        $database->execute(
            'INSERT INTO "framework_migration_locks" ("name", "owner", "acquired_at") VALUES (?, ?, ?)',
            ['framework-migrations', 'spec-holder', time()]
        );

        $this->expectException(\App\Exceptions\Database\MigrationException::class);
        $this->expectExceptionMessage('Migration lock [framework-migrations] is already held by [spec-holder].');

        $runner->migrate('WebModule');
    }

    public function testConfigShowRedactsSecretsUnlessRawOutputIsRequested(): void
    {
        $command = new class(new class extends Config {
            public function __construct()
            {
            }
        }) extends ConfigShowCommand {
            public function exposeRedaction(array $payload): array
            {
                return $this->redactPayload($payload);
            }

            public function exposeOptionEnabled(mixed $value): bool
            {
                return $this->optionEnabled($value);
            }
        };

        $payload = [
            'db' => [
                'PASSWORD' => 'super-secret',
            ],
            'encryption' => [
                'KEY' => 'base64:abcd',
            ],
            'app' => [
                'NAME' => 'LangelerMVC',
            ],
        ];

        $redacted = $command->exposeRedaction($payload);

        self::assertSame('[redacted]', $redacted['db']['PASSWORD']);
        self::assertSame('[redacted]', $redacted['encryption']['KEY']);
        self::assertSame('LangelerMVC', $redacted['app']['NAME']);
        self::assertTrue($command->exposeOptionEnabled('true'));
        self::assertFalse($command->exposeOptionEnabled('false'));
    }

    public function testInstallerRollsBackMigrationsAndRestoresEnvironmentWhenInstallationFails(): void
    {
        $temporaryRoot = sys_get_temp_dir() . '/langelermvc-installer-rollback-' . bin2hex(random_bytes(4));

        foreach ([
            $temporaryRoot,
            $temporaryRoot . '/Storage',
            $temporaryRoot . '/Storage/Database',
        ] as $path) {
            mkdir($path, 0777, true);
            $this->pathsToDelete[] = $path;
        }

        copy(dirname(__DIR__, 2) . '/.env.example', $temporaryRoot . '/.env.example');
        $this->pathsToDelete[] = $temporaryRoot . '/.env.example';

        file_put_contents($temporaryRoot . '/.env', "APP_NAME=BeforeInstall\nAPP_INSTALLED=false\n");
        $this->pathsToDelete[] = $temporaryRoot . '/.env';
        $this->pathsToDelete[] = $temporaryRoot . '/Storage/Database/test.sqlite';

        $migrationRunner = new class extends MigrationRunner {
            public array $rollbackRequests = [];

            public function __construct()
            {
            }

            public function migrate(?string $module = null): array
            {
                return match ($module) {
                    'Framework' => ['CreateFrameworkOperationsTables'],
                    'WebModule' => ['CreatePagesTable'],
                    default => [],
                };
            }

            public function rollbackNamed(array $migrations): array
            {
                $this->rollbackRequests[] = array_values($migrations);

                return array_values($migrations);
            }
        };

        $seedRunner = new class extends SeedRunner {
            public function __construct()
            {
            }

            public function run(?string $module = null, ?string $seed = null): array
            {
                return [];
            }
        };

        $provider = new class($migrationRunner, $seedRunner) extends CoreProvider {
            public function __construct(
                private readonly MigrationRunner $migrationRunner,
                private readonly SeedRunner $seedRunner
            ) {
            }

            public function registerServices(): void
            {
            }

            public function getCoreService(string $serviceAlias): object
            {
                return match ($serviceAlias) {
                    'migrationRunner' => $this->migrationRunner,
                    'seedRunner' => $this->seedRunner,
                    default => throw new \RuntimeException('Unsupported test service: ' . $serviceAlias),
                };
            }

            public function resolveClass(string $classOrAlias): object
            {
                return new \stdClass();
            }
        };

        $wizard = new InstallerWizard(
            new FileManager(),
            $temporaryRoot,
            static fn(): CoreProvider => $provider
        );

        $payload = array_replace($wizard->defaults(), [
            'APP_URL' => 'https://example.test',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => 'Storage/Database/test.sqlite',
            'ADMIN_NAME' => 'Installer Admin',
            'ADMIN_EMAIL' => 'admin@example.test',
            'ADMIN_PASSWORD' => 'password123',
        ]);

        try {
            $wizard->install($payload);
            self::fail('Expected installer failure during administrator provisioning.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('rollback was applied', $exception->getMessage());
        }

        self::assertSame("APP_NAME=BeforeInstall\nAPP_INSTALLED=false\n", file_get_contents($temporaryRoot . '/.env'));

        $journalPath = $temporaryRoot . '/Storage/Installer/install-state.json';
        $this->pathsToDelete[] = $journalPath;
        $this->pathsToDelete[] = dirname($journalPath);

        self::assertFileExists($journalPath);

        $journal = json_decode((string) file_get_contents($journalPath), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('rolled_back', $journal['status']);
        self::assertSame('administrator', $journal['stage']);
        self::assertSame(['CreateFrameworkOperationsTables'], $journal['rolled_back']['Framework']);
        self::assertSame(['CreatePagesTable'], $journal['rolled_back']['WebModule']);
        self::assertSame(
            [['CreatePagesTable'], ['CreateFrameworkOperationsTables']],
            $migrationRunner->rollbackRequests
        );
    }

    private function makeSqliteDatabase(): Database
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return new Database(
            $this->makeSettingsManager(),
            new ErrorManager(new ExceptionProvider()),
            pdo: $pdo,
            config: [
                'CONNECTION' => 'sqlite',
                'DRIVER' => 'sqlite',
                'DATABASE' => ':memory:',
            ]
        );
    }

    private function makeSettingsManager(): SettingsManager
    {
        return new class extends SettingsManager {
            public function __construct()
            {
            }

            public function getAllSettings(string $fileName): array
            {
                return match (strtolower($fileName)) {
                    'db' => [
                        'CONNECTION' => 'sqlite',
                        'DRIVER' => 'sqlite',
                        'DATABASE' => ':memory:',
                    ],
                    default => [],
                };
            }
        };
    }

    /**
     * @param list<class-string> $classes
     */
    private function makeModuleManager(array $classes): ModuleManager
    {
        return new class($classes) extends ModuleManager {
            /**
             * @param list<class-string> $classes
             */
            public function __construct(private readonly array $classes)
            {
            }

            public function getModules(): array
            {
                return [
                    'WebModule' => sys_get_temp_dir() . '/WebModule',
                ];
            }

            public function getClasses(string $module, string $subDir, array $filter = ['extension' => 'php'], array $sort = []): array
            {
                return array_values(array_filter(
                    $this->collectClasses($subDir, $filter, $sort),
                    static fn(array $class): bool => (string) ($class['module'] ?? '') === $module
                ));
            }

            public function collectClasses(string $subDir, array $filter = ['extension' => 'php'], array $sort = []): array
            {
                if ($subDir !== '' && strcasecmp($subDir, 'Migrations') !== 0) {
                    return [];
                }

                return array_map(function (string $class): array {
                    $reflection = new ReflectionClass($class);

                    return [
                        'file' => $reflection->getFileName() ?: $class,
                        'namespace' => $reflection->getNamespaceName(),
                        'class' => $class,
                        'shortName' => $reflection->getShortName(),
                        'module' => preg_match('/App\\\\Modules\\\\([^\\\\]+)/', $class, $matches) === 1
                            ? (string) $matches[1]
                            : 'Framework',
                    ];
                }, $this->classes);
            }
        };
    }
}
