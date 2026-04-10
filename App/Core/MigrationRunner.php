<?php

declare(strict_types=1);

namespace App\Core;

use App\Abstracts\Database\Migration;
use App\Exceptions\Database\MigrationException;
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

    private const TABLE = 'framework_migrations';

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
    }

    /**
     * @return list<string>
     */
    public function rollback(int $steps = 1, ?string $module = null): array
    {
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
    }

    /**
     * @return array<int, array{name:string,module:string,class:string}>
     */
    public function discoverMigrations(?string $module = null): array
    {
        $classes = $module !== null
            ? $this->moduleManager->getClasses($module, 'Migrations')
            : $this->moduleManager->collectClasses('Migrations');

        $migrations = [];

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

        usort(
            $migrations,
            static fn(array $left, array $right): int => strcmp($left['file'], $right['file'])
        );

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

        $this->database->query($statement);
    }

    private function repositoryTableExists(): bool
    {
        $driver = $this->configuredDriver();

        return match ($driver) {
            'sqlite' => $this->database->fetchColumn(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?",
                [self::TABLE]
            ) !== false,
            'pgsql' => $this->database->fetchColumn(
                'SELECT 1 FROM information_schema.tables WHERE table_name = ?',
                [self::TABLE]
            ) !== false,
            'sqlsrv' => $this->database->fetchColumn(
                'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?',
                [self::TABLE]
            ) !== false,
            default => $this->database->fetchColumn(
                'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
                [self::TABLE]
            ) !== false,
        };
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
            : 'Framework';
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
}
