<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Async;

use App\Contracts\Async\FailedJobStoreInterface;
use App\Core\Database;
use App\Utilities\Traits\ConversionTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;

class DatabaseFailedJobStore implements FailedJobStoreInterface
{
    use ConversionTrait, ManipulationTrait, TypeCheckerTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    private const TABLE = 'framework_failed_jobs';

    public function __construct(private readonly Database $database)
    {
    }

    public function record(array $envelope, \Throwable $exception): string
    {
        if (!$this->tableExists()) {
            return '';
        }

        $id = bin2hex(random_bytes(16));
        $record = [
            'id' => $id,
            'queue' => (string) ($envelope['queue'] ?? 'default'),
            'type' => (string) ($envelope['type'] ?? 'job'),
            'class' => (string) ($envelope['class'] ?? ''),
            'handler' => $this->toJson($envelope['handler'] ?? null, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'payload' => $this->toJson($envelope['payload'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
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
        if (!$this->tableExists()) {
            return [];
        }

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
        if (!$this->tableExists()) {
            return null;
        }

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
        if (!$this->tableExists()) {
            return false;
        }

        $query = $this->database
            ->dataQuery(self::TABLE)
            ->delete(self::TABLE)
            ->where('id', '=', $id)
            ->toExecutable();

        return $this->database->execute($query['sql'], $query['bindings']) > 0;
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
        $handler = $this->decodeJsonValue((string) ($row['handler'] ?? 'null'), null);
        $payload = $this->decodeJsonValue((string) ($row['payload'] ?? '{}'), []);

        return [
            'id' => (string) ($row['id'] ?? ''),
            'queue' => (string) ($row['queue'] ?? 'default'),
            'type' => (string) ($row['type'] ?? 'job'),
            'class' => (string) ($row['class'] ?? ''),
            'handler' => $handler,
            'payload' => $this->isArray($payload) ? $payload : [],
            'attempts' => (int) ($row['attempts'] ?? 0),
            'exception' => (string) ($row['exception'] ?? ''),
            'failed_at' => (int) ($row['failed_at'] ?? 0),
        ];
    }

    private function decodeJsonValue(string $payload, mixed $fallback): mixed
    {
        try {
            return $this->fromJson($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $fallback;
        }
    }
}
