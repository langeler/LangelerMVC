<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Async;

use App\Contracts\Async\FailedJobStoreInterface;
use App\Core\Database;
use App\Utilities\Traits\ManipulationTrait;

class DatabaseFailedJobStore implements FailedJobStoreInterface
{
    use ManipulationTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    private const TABLE = 'framework_failed_jobs';

    public function __construct(private readonly Database $database)
    {
    }

    public function record(array $envelope, \Throwable $exception): string
    {
        $this->ensureTable();

        $id = bin2hex(random_bytes(16));
        $record = [
            'id' => $id,
            'queue' => (string) ($envelope['queue'] ?? 'default'),
            'type' => (string) ($envelope['type'] ?? 'job'),
            'class' => (string) ($envelope['class'] ?? ''),
            'handler' => json_encode($envelope['handler'] ?? null, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'payload' => json_encode($envelope['payload'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'attempts' => (int) ($envelope['attempts'] ?? 0),
            'exception' => $exception::class . ': ' . $exception->getMessage(),
            'failed_at' => time(),
        ];

        $query = $this->database
            ->dataQuery(self::TABLE)
            ->insert(self::TABLE, $record)
            ->toExecutable();

        $this->database->execute($query['sql'], $query['bindings']);

        return $id;
    }

    public function all(): array
    {
        $this->ensureTable();

        $query = $this->database
            ->dataQuery(self::TABLE)
            ->select(['*'])
            ->orderBy('failed_at')
            ->toExecutable();

        return array_map(
            fn(array $row): array => $this->hydrate($row),
            $this->database->fetchAll($query['sql'], $query['bindings'])
        );
    }

    public function find(string $id): ?array
    {
        $this->ensureTable();

        $query = $this->database
            ->dataQuery(self::TABLE)
            ->select(['*'])
            ->where('id', '=', $id)
            ->limit(1)
            ->toExecutable();

        $row = $this->database->fetchOne($query['sql'], $query['bindings']);

        return $row !== null ? $this->hydrate($row) : null;
    }

    public function delete(string $id): bool
    {
        $this->ensureTable();

        $query = $this->database
            ->dataQuery(self::TABLE)
            ->delete(self::TABLE)
            ->where('id', '=', $id)
            ->toExecutable();

        return $this->database->execute($query['sql'], $query['bindings']) > 0;
    }

    private function ensureTable(): void
    {
        if ($this->tableExists()) {
            return;
        }

        $statement = match ($this->driver()) {
            'pgsql' => 'CREATE TABLE "framework_failed_jobs" ("id" VARCHAR(64) PRIMARY KEY, "queue" VARCHAR(120) NOT NULL, "type" VARCHAR(60) NOT NULL, "class" VARCHAR(255) NOT NULL, "handler" TEXT NULL, "payload" TEXT NOT NULL, "attempts" INT NOT NULL, "exception" TEXT NOT NULL, "failed_at" BIGINT NOT NULL)',
            'sqlite' => 'CREATE TABLE "framework_failed_jobs" ("id" TEXT PRIMARY KEY, "queue" TEXT NOT NULL, "type" TEXT NOT NULL, "class" TEXT NOT NULL, "handler" TEXT NULL, "payload" TEXT NOT NULL, "attempts" INTEGER NOT NULL, "exception" TEXT NOT NULL, "failed_at" INTEGER NOT NULL)',
            'sqlsrv' => 'CREATE TABLE [framework_failed_jobs] ([id] NVARCHAR(64) PRIMARY KEY, [queue] NVARCHAR(120) NOT NULL, [type] NVARCHAR(60) NOT NULL, [class] NVARCHAR(255) NOT NULL, [handler] NVARCHAR(MAX) NULL, [payload] NVARCHAR(MAX) NOT NULL, [attempts] INT NOT NULL, [exception] NVARCHAR(MAX) NOT NULL, [failed_at] BIGINT NOT NULL)',
            default => 'CREATE TABLE `framework_failed_jobs` (`id` VARCHAR(64) PRIMARY KEY, `queue` VARCHAR(120) NOT NULL, `type` VARCHAR(60) NOT NULL, `class` VARCHAR(255) NOT NULL, `handler` TEXT NULL, `payload` LONGTEXT NOT NULL, `attempts` INT NOT NULL, `exception` LONGTEXT NOT NULL, `failed_at` BIGINT NOT NULL)',
        };

        $this->database->query($statement);
    }

    private function tableExists(): bool
    {
        return match ($this->driver()) {
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

    private function driver(): string
    {
        return $this->toLowerString((string) $this->database->getAttribute('driverName'));
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrate(array $row): array
    {
        $handler = json_decode((string) ($row['handler'] ?? 'null'), true);
        $payload = json_decode((string) ($row['payload'] ?? '{}'), true);

        return [
            'id' => (string) ($row['id'] ?? ''),
            'queue' => (string) ($row['queue'] ?? 'default'),
            'type' => (string) ($row['type'] ?? 'job'),
            'class' => (string) ($row['class'] ?? ''),
            'handler' => is_array($handler) ? $handler : $handler,
            'payload' => is_array($payload) ? $payload : [],
            'attempts' => (int) ($row['attempts'] ?? 0),
            'exception' => (string) ($row['exception'] ?? ''),
            'failed_at' => (int) ($row['failed_at'] ?? 0),
        ];
    }
}
