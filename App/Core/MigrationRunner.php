<?php

declare(strict_types=1);

namespace App\Core;

use App\Abstracts\Database\Migration;
use App\Exceptions\Database\MigrationException;
use App\Framework\Migrations\CreateFrameworkOperationsTables;
use App\Utilities\Managers\Data\ModuleManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ErrorTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Traits\TypeCheckerTrait;
use ReflectionClass;

class MigrationRunner
{
    use ArrayTrait, CheckerTrait, ErrorTrait, ManipulationTrait, PatternTrait, TypeCheckerTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    private const FRAMEWORK_MODULE = 'Framework';
    private const TABLE = 'framework_migrations';
    private const LOCK_TABLE = 'framework_migration_locks';
    private const LOCK_NAME = 'framework-migrations';
    private const LOCK_STALE_AFTER = 900;

    /**
     * @var list<class-string<Migration>>
     */
    private const FRAMEWORK_MIGRATIONS = [
        CreateFrameworkOperationsTables::class,
    ];

    public function __construct(
        private readonly Database $database,
        private readonly ModuleManager $moduleManager,
        private readonly ErrorManager $errorManager
    ) {
    }

    /**
     * @return array<int, array{name:string,module:string,class:string,batch:int,ran_at:string|null}>
     */
    public function status(?string $module = null): array
    {
        $this->ensureRepositoryTable();

        $records = $this->ranRecords();
        $status = [];

        foreach ($this->discoverMigrations($module) as $migration) {
            $key = $migration['name'];
            $record = $records[$key] ?? null;

            $status[] = [
                'name' => $migration['name'],
                'module' => $migration['module'],
                'class' => $migration['class'],
                'batch' => (int) ($record['batch'] ?? 0),
                'ran_at' => isset($record['ran_at']) ? (string) $record['ran_at'] : null,
            ];
        }

        return $status;
    }

    /**
     * @return list<string>
     */
    public function migrate(?string $module = null): array
    {
        return $this->withMigrationLock(function () use ($module): array {
            $this->ensureRepositoryTable();
            $ran = $this->ranRecords();
            $pending = array_values(array_filter(
                $this->discoverMigrations($module),
                fn(array $migration): bool => !isset($ran[$migration['name']])
            ));

            if ($pending === []) {
                return [];
            }

            $batch = $this->nextBatch();
            $executed = [];

            try {
                foreach ($pending as $migration) {
                    $instance = $this->resolveMigration($migration['class']);
                    $instance->up();
                    $this->recordMigration($migration, $batch);
                    $executed[] = $migration['name'];
                }
            } catch (\Throwable $exception) {
                throw $this->errorManager->resolveException(
                    'migration',
                    'Migration run failed: ' . $exception->getMessage(),
                    (int) $exception->getCode(),
                    $exception
                );
            }

            return $executed;
        });
    }

    /**
     * @return list<string>
     */
    public function rollback(int $steps = 1, ?string $module = null): array
    {
        return $this->withMigrationLock(function () use ($steps, $module): array {
            $this->ensureRepositoryTable();
            $steps = $steps > 0 ? $steps : 1;
            $records = $this->latestBatches($steps, $module);

            if ($records === []) {
                return [];
            }

            $rolledBack = [];

            try {
                foreach ($records as $record) {
                    $instance = $this->resolveMigration((string) $record['class']);
                    $instance->down();
                    $this->removeMigrationRecord((string) $record['migration']);
                    $rolledBack[] = (string) $record['migration'];
                }
            } catch (\Throwable $exception) {
                throw $this->errorManager->resolveException(
                    'migration',
                    'Migration rollback failed: ' . $exception->getMessage(),
                    (int) $exception->getCode(),
                    $exception
                );
            }

            return $rolledBack;
        });
    }

    /**
     * @param list<string> $migrations
     * @return list<string>
     */
    public function rollbackNamed(array $migrations): array
    {
        if ($migrations === []) {
            return [];
        }

        return $this->withMigrationLock(function () use ($migrations): array {
            $this->ensureRepositoryTable();
            $catalog = $this->indexDiscoveredMigrations();
            $records = $this->ranRecords();
            $rolledBack = [];

            foreach (array_reverse(array_values(array_unique(array_map('strval', $migrations)))) as $migrationName) {
                if (!isset($records[$migrationName])) {
                    continue;
                }

                $candidate = $catalog[$migrationName]
                    ?? $catalog[(string) ($records[$migrationName]['class'] ?? '')]
                    ?? null;

                if (!$this->isArray($candidate)) {
                    continue;
                }

                $instance = $this->resolveMigration((string) $candidate['class']);
                $instance->down();
                $this->removeMigrationRecord($migrationName);
                $rolledBack[] = $migrationName;
            }

            return $rolledBack;
        });
    }

    /**
     * @return array<int, array{name:string,module:string,class:string}>
     */
    public function discoverMigrations(?string $module = null): array
    {
        $migrations = $this->discoverFrameworkMigrations($module);

        if ($module === null || $this->toLowerString($module) !== $this->toLowerString(self::FRAMEWORK_MODULE)) {
            $classes = $module !== null
                ? $this->moduleManager->getClasses($module, 'Migrations')
                : $this->moduleManager->collectClasses('Migrations');

            foreach ($classes as $class) {
                if (!$this->isArray($class) || !$this->isString($class['class'] ?? null)) {
                    continue;
                }

                if (!is_subclass_of($class['class'], Migration::class)) {
                    continue;
                }

                $migrations[] = [
                    'name' => (string) ($class['shortName'] ?? $class['class']),
                    'module' => $this->resolveModuleName((string) $class['class']),
                    'class' => (string) $class['class'],
                    'file' => (string) ($class['file'] ?? ''),
                ];
            }
        }

        usort(
            $migrations,
            static fn(array $left, array $right): int => strcmp($left['file'], $right['file'])
        );
        $migrations = $this->sortDiscoveredMigrations($migrations);

        return array_map(
            static fn(array $migration): array => [
                'name' => $migration['name'],
                'module' => $migration['module'],
                'class' => $migration['class'],
            ],
            $migrations
        );
    }

    private function resolveMigration(string $class): Migration
    {
        $reflection = new ReflectionClass($class);
        $instance = $reflection->newInstance($this->database);

        if (!$instance instanceof Migration) {
            throw new MigrationException(sprintf('Resolved migration [%s] is invalid.', $class));
        }

        return $instance;
    }

    /**
     * @param array<int, array{name:string,module:string,class:string,file:string}> $migrations
     * @return array<int, array{name:string,module:string,class:string,file:string}>
     */
    private function sortDiscoveredMigrations(array $migrations): array
    {
        $byClass = [];
        $byName = [];

        foreach ($migrations as $migration) {
            $byClass[$migration['class']] = $migration;
            $byName[$migration['name']] = $migration['class'];
        }

        $ordered = [];
        $visiting = [];
        $visited = [];

        $visit = function (string $class) use (&$visit, &$ordered, &$visiting, &$visited, $byClass, $byName): void {
            if (isset($visited[$class])) {
                return;
            }

            if (isset($visiting[$class])) {
                throw new MigrationException(sprintf('Circular migration dependency detected for [%s].', $class));
            }

            $candidate = $byClass[$class] ?? null;

            if ($candidate === null) {
                return;
            }

            $visiting[$class] = true;
            $dependencies = is_callable([$class, 'dependencies']) ? $class::dependencies() : [];

            foreach ($dependencies as $dependency) {
                $dependencyClass = $byClass[(string) $dependency]['class']
                    ?? $byName[(string) $dependency]
                    ?? (is_string($dependency) ? $dependency : '');

                if ($dependencyClass !== '' && isset($byClass[$dependencyClass])) {
                    $visit($dependencyClass);
                }
            }

            unset($visiting[$class]);
            $visited[$class] = true;
            $ordered[] = $candidate;
        };

        foreach ($migrations as $migration) {
            $visit($migration['class']);
        }

        return $ordered;
    }

    private function ensureRepositoryTable(): void
    {
        if ($this->repositoryTableExists()) {
            return;
        }

        $driver = $this->configuredDriver();

        $statement = match ($driver) {
            'pgsql' => 'CREATE TABLE "framework_migrations" ("id" BIGSERIAL PRIMARY KEY, "migration" VARCHAR(255) NOT NULL UNIQUE, "module" VARCHAR(255) NOT NULL, "class" VARCHAR(255) NOT NULL, "batch" INT NOT NULL, "ran_at" TIMESTAMP NOT NULL)',
            'sqlite' => 'CREATE TABLE "framework_migrations" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "migration" TEXT NOT NULL UNIQUE, "module" TEXT NOT NULL, "class" TEXT NOT NULL, "batch" INTEGER NOT NULL, "ran_at" TEXT NOT NULL)',
            'sqlsrv' => 'CREATE TABLE [framework_migrations] ([id] BIGINT IDENTITY(1,1) PRIMARY KEY, [migration] NVARCHAR(255) NOT NULL UNIQUE, [module] NVARCHAR(255) NOT NULL, [class] NVARCHAR(255) NOT NULL, [batch] INT NOT NULL, [ran_at] DATETIME2 NOT NULL)',
            default => 'CREATE TABLE `framework_migrations` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `migration` VARCHAR(255) NOT NULL UNIQUE, `module` VARCHAR(255) NOT NULL, `class` VARCHAR(255) NOT NULL, `batch` INT NOT NULL, `ran_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)',
        };

        try {
            $this->database->query($statement);
        } catch (\Throwable $exception) {
            if (!$this->repositoryTableExists()) {
                throw $exception;
            }
        }
    }

    private function repositoryTableExists(): bool
    {
        return $this->tableExists(self::TABLE);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function ranRecords(): array
    {
        $query = $this->database
            ->dataQuery(self::TABLE)
            ->select(['migration', 'module', 'class', 'batch', 'ran_at'])
            ->orderBy('id')
            ->toExecutable();

        $rows = $this->database->fetchAll($query['sql'], $query['bindings']);

        $indexed = [];

        foreach ($rows as $row) {
            $indexed[(string) $row['migration']] = $row;
        }

        return $indexed;
    }

    private function nextBatch(): int
    {
        $query = $this->database
            ->dataQuery(self::TABLE)
            ->select(['MAX(batch) AS aggregate'])
            ->toExecutable();

        $current = $this->database->fetchColumn($query['sql'], $query['bindings']);

        return ((int) $current) + 1;
    }

    /**
     * @param array{name:string,module:string,class:string} $migration
     */
    private function recordMigration(array $migration, int $batch): void
    {
        $query = $this->database
            ->dataQuery(self::TABLE)
            ->insert(self::TABLE, [
                'migration' => $migration['name'],
                'module' => $migration['module'],
                'class' => $migration['class'],
                'batch' => $batch,
                'ran_at' => date('Y-m-d H:i:s'),
            ])
            ->toExecutable();

        $this->database->execute($query['sql'], $query['bindings']);
    }

    private function removeMigrationRecord(string $name): void
    {
        $query = $this->database
            ->dataQuery(self::TABLE)
            ->delete(self::TABLE)
            ->where('migration', '=', $name)
            ->toExecutable();

        $this->database->execute($query['sql'], $query['bindings']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function latestBatches(int $steps, ?string $module = null): array
    {
        $all = $this->database->fetchAll(
            'SELECT * FROM ' . $this->quoteIdentifier(self::TABLE) . ' ORDER BY batch DESC, id DESC'
        );

        $batches = [];

        foreach ($all as $record) {
            if ($module !== null && $this->toLowerString((string) ($record['module'] ?? '')) !== $this->toLowerString($module)) {
                continue;
            }

            $batch = (int) ($record['batch'] ?? 0);

            if ($batch === 0) {
                continue;
            }

            $batches[$batch][] = $record;

            if (count($batches) >= $steps) {
                continue;
            }
        }

        krsort($batches);

        if ($batches === []) {
            return [];
        }

        return array_merge(...array_values(array_slice($batches, 0, $steps, true)));
    }

    private function resolveModuleName(string $class): string
    {
        return $this->match('/App\\\\Modules\\\\([^\\\\]+)/', $class, $matches) === 1
            ? (string) $matches[1]
            : self::FRAMEWORK_MODULE;
    }

    private function configuredDriver(): string
    {
        return $this->toLowerString((string) $this->database->getAttribute('driverName'));
    }

    private function quoteIdentifier(string $identifier): string
    {
        return match ($this->configuredDriver()) {
            'pgsql', 'sqlite' => '"' . $identifier . '"',
            'sqlsrv' => '[' . $identifier . ']',
            default => '`' . $identifier . '`',
        };
    }

    /**
     * @return array<string, array{name:string,module:string,class:string}>
     */
    private function indexDiscoveredMigrations(): array
    {
        $indexed = [];

        foreach ($this->discoverMigrations() as $migration) {
            $indexed[$migration['name']] = $migration;
            $indexed[$migration['class']] = $migration;
        }

        return $indexed;
    }

    /**
     * @return array<int, array{name:string,module:string,class:string,file:string}>
     */
    private function discoverFrameworkMigrations(?string $module = null): array
    {
        if ($module !== null && $this->toLowerString($module) !== $this->toLowerString(self::FRAMEWORK_MODULE)) {
            return [];
        }

        $migrations = [];

        foreach (self::FRAMEWORK_MIGRATIONS as $class) {
            if (!class_exists($class) || !is_subclass_of($class, Migration::class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            $migrations[] = [
                'name' => $reflection->getShortName(),
                'module' => self::FRAMEWORK_MODULE,
                'class' => $class,
                'file' => $reflection->getFileName() ?: $class,
            ];
        }

        return $migrations;
    }

    private function ensureLockTable(): void
    {
        if ($this->tableExists(self::LOCK_TABLE)) {
            return;
        }

        $statement = match ($this->configuredDriver()) {
            'pgsql' => 'CREATE TABLE "framework_migration_locks" ("name" VARCHAR(120) PRIMARY KEY, "owner" VARCHAR(120) NOT NULL, "acquired_at" BIGINT NOT NULL)',
            'sqlite' => 'CREATE TABLE "framework_migration_locks" ("name" TEXT PRIMARY KEY, "owner" TEXT NOT NULL, "acquired_at" INTEGER NOT NULL)',
            'sqlsrv' => 'CREATE TABLE [framework_migration_locks] ([name] NVARCHAR(120) PRIMARY KEY, [owner] NVARCHAR(120) NOT NULL, [acquired_at] BIGINT NOT NULL)',
            default => 'CREATE TABLE `framework_migration_locks` (`name` VARCHAR(120) PRIMARY KEY, `owner` VARCHAR(120) NOT NULL, `acquired_at` BIGINT NOT NULL)',
        };

        try {
            $this->database->query($statement);
        } catch (\Throwable $exception) {
            if (!$this->tableExists(self::LOCK_TABLE)) {
                throw $exception;
            }
        }
    }

    private function acquireMigrationLock(): string
    {
        $this->ensureLockTable();

        $owner = bin2hex(random_bytes(8));
        $acquiredAt = time();

        if ($this->tryInsertMigrationLock($owner, $acquiredAt)) {
            return $owner;
        }

        $existing = $this->currentMigrationLock();

        if (
            $this->isArray($existing)
            && (int) ($existing['acquired_at'] ?? 0) <= ($acquiredAt - self::LOCK_STALE_AFTER)
        ) {
            $this->clearMigrationLock();

            if ($this->tryInsertMigrationLock($owner, $acquiredAt)) {
                return $owner;
            }

            $existing = $this->currentMigrationLock();
        }

        $holder = $this->isArray($existing) ? (string) ($existing['owner'] ?? 'unknown') : 'unknown';
        throw new MigrationException(sprintf(
            'Migration lock [%s] is already held by [%s].',
            self::LOCK_NAME,
            $holder
        ));
    }

    private function releaseMigrationLock(string $owner): void
    {
        if ($owner === '' || !$this->tableExists(self::LOCK_TABLE)) {
            return;
        }

        $query = $this->database
            ->dataQuery(self::LOCK_TABLE)
            ->delete(self::LOCK_TABLE)
            ->where('name', '=', self::LOCK_NAME)
            ->where('owner', '=', $owner)
            ->toExecutable();

        $this->database->execute($query['sql'], $query['bindings']);
    }

    private function clearMigrationLock(): void
    {
        $query = $this->database
            ->dataQuery(self::LOCK_TABLE)
            ->delete(self::LOCK_TABLE)
            ->where('name', '=', self::LOCK_NAME)
            ->toExecutable();

        $this->database->execute($query['sql'], $query['bindings']);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function currentMigrationLock(): ?array
    {
        $query = $this->database
            ->dataQuery(self::LOCK_TABLE)
            ->select(['name', 'owner', 'acquired_at'])
            ->where('name', '=', self::LOCK_NAME)
            ->limit(1)
            ->toExecutable();

        return $this->database->fetchOne($query['sql'], $query['bindings']);
    }

    private function tryInsertMigrationLock(string $owner, int $acquiredAt): bool
    {
        try {
            $query = $this->database
                ->dataQuery(self::LOCK_TABLE)
                ->insert(self::LOCK_TABLE, [
                    'name' => self::LOCK_NAME,
                    'owner' => $owner,
                    'acquired_at' => $acquiredAt,
                ])
                ->toExecutable();

            $this->database->execute($query['sql'], $query['bindings']);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function tableExists(string $table): bool
    {
        $driver = $this->configuredDriver();

        return match ($driver) {
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
    }

    private function withMigrationLock(callable $callback): mixed
    {
        $owner = $this->acquireMigrationLock();

        try {
            return $callback();
        } finally {
            try {
                $this->releaseMigrationLock($owner);
            } catch (\Throwable) {
                // Preserve the migration result/error; lock cleanup is best effort.
            }
        }
    }
}
