<?php

declare(strict_types=1);

namespace App\Drivers\Queue;

use App\Contracts\Async\QueueDriverInterface;
use App\Core\Database;
use App\Exceptions\AppException;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\ConversionTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;

class DatabaseQueueDriver implements QueueDriverInterface
{
    use ArrayTrait, ConversionTrait, ManipulationTrait, TypeCheckerTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    private const TABLE = 'framework_jobs';

    public function __construct(private readonly Database $database)
    {
    }

    public function driverName(): string
    {
        return 'database';
    }

    public function capabilities(): array
    {
        return [
            'immediate' => false,
            'persistent' => true,
            'delay' => true,
            'inspect' => true,
            'retry' => true,
        ];
    }

    public function supports(string $feature): bool
    {
        $value = $this->capabilities();

        foreach (explode('.', trim($feature)) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }

            $value = $value[$segment];
        }

        return $value === true;
    }

    public function push(array $envelope, string $queue = 'default', int $delay = 0): string
    {
        $this->assertTableAvailable();

        $id = bin2hex(random_bytes(16));
        $record = [
            'id' => $id,
            'queue' => $queue,
            'type' => (string) ($envelope['type'] ?? 'job'),
            'class' => (string) ($envelope['class'] ?? ''),
            'handler' => $this->toJson($envelope['handler'] ?? null, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'payload' => $this->toJson($envelope['payload'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'attempts' => (int) ($envelope['attempts'] ?? 0),
            'available_at' => time() + max(0, $delay),
            'reserved_at' => null,
            'created_at' => time(),
        ];

        $query = $this->database
            ->dataQuery(self::TABLE)
            ->insert(self::TABLE, $record)
            ->toExecutable();

        $this->database->execute($query['sql'], $query['bindings']);

        return $id;
    }

    public function pop(string $queue = 'default'): ?array
    {
        $this->assertTableAvailable();

        $query = $this->database
            ->dataQuery(self::TABLE)
            ->select(['*'])
            ->where('queue', '=', $queue)
            ->where('available_at', '<=', time())
            ->whereNull('reserved_at')
            ->orderBy('created_at')
            ->limit(1)
            ->toExecutable();

        $row = $this->database->fetchOne($query['sql'], $query['bindings']);

        if ($row === null) {
            return null;
        }

        $attempts = ((int) ($row['attempts'] ?? 0)) + 1;

        $update = $this->database
            ->dataQuery(self::TABLE)
            ->update(self::TABLE, [
                'attempts' => $attempts,
                'reserved_at' => time(),
            ])
            ->where('id', '=', (string) $row['id'])
            ->toExecutable();

        $this->database->execute($update['sql'], $update['bindings']);

        return $this->hydrateEnvelope($row, $attempts);
    }

    public function delete(string $id): bool
    {
        $this->assertTableAvailable();

        $query = $this->database
            ->dataQuery(self::TABLE)
            ->delete(self::TABLE)
            ->where('id', '=', $id)
            ->toExecutable();

        return $this->database->execute($query['sql'], $query['bindings']) > 0;
    }

    public function release(array $envelope, int $delay = 0): bool
    {
        $this->assertTableAvailable();

        $query = $this->database
            ->dataQuery(self::TABLE)
            ->update(self::TABLE, [
                'reserved_at' => null,
                'available_at' => time() + max(0, $delay),
                'attempts' => (int) ($envelope['attempts'] ?? 0),
            ])
            ->where('id', '=', (string) ($envelope['id'] ?? ''))
            ->toExecutable();

        return $this->database->execute($query['sql'], $query['bindings']) > 0;
    }

    public function pending(string $queue = 'default'): array
    {
        $this->assertTableAvailable();

        $query = $this->database
            ->dataQuery(self::TABLE)
            ->select(['*'])
            ->where('queue', '=', $queue)
            ->orderBy('created_at')
            ->toExecutable();

        return array_map(
            fn(array $row): array => $this->hydrateEnvelope($row, (int) ($row['attempts'] ?? 0)),
            $this->database->fetchAll($query['sql'], $query['bindings'])
        );
    }

    private function assertTableAvailable(): void
    {
        if (!$this->tableExists()) {
            throw new AppException('Queue storage table [framework_jobs] is missing. Run the framework migrations before using the database queue driver.');
        }
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
    private function hydrateEnvelope(array $row, int $attempts): array
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
            'attempts' => $attempts,
            'available_at' => (int) ($row['available_at'] ?? 0),
            'reserved_at' => isset($row['reserved_at']) ? (int) $row['reserved_at'] : null,
            'created_at' => (int) ($row['created_at'] ?? 0),
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
