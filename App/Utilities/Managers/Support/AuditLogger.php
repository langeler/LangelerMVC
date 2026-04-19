<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Support;

use App\Contracts\Support\AuditLoggerInterface;
use App\Core\Config;
use App\Core\Database;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Traits\ConversionTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;
use Throwable;

class AuditLogger implements AuditLoggerInterface
{
    use ConversionTrait, ManipulationTrait, TypeCheckerTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    private const TABLE = 'framework_audit_log';

    public function __construct(
        private readonly Database $database,
        private readonly Config $config,
        private readonly ErrorManager $errorManager
    ) {
    }

    public function record(string $event, array $context = [], string $category = 'framework', string $severity = 'info'): bool
    {
        if (!(bool) $this->config->get('operations', 'AUDIT.ENABLED', true)) {
            return false;
        }

        try {
            $this->ensureTable();

            $record = [
                'category' => $this->trimString($category) !== '' ? $this->trimString($category) : 'framework',
                'event' => $this->trimString($event),
                'severity' => $this->trimString($severity) !== '' ? $this->trimString($severity) : 'info',
                'actor_type' => isset($context['actor_type']) ? (string) $context['actor_type'] : null,
                'actor_id' => isset($context['actor_id']) ? (string) $context['actor_id'] : null,
                'context' => $this->toJson($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'created_at' => time(),
            ];

            $query = $this->database
                ->dataQuery(self::TABLE)
                ->insert(self::TABLE, $record)
                ->toExecutable();

            $this->database->execute($query['sql'], $query['bindings']);

            return true;
        } catch (Throwable $exception) {
            $this->errorManager->logThrowable($exception, 'audit', 'userNotice');

            return false;
        }
    }

    public function recent(int $limit = 50, array $criteria = []): array
    {
        try {
            $this->ensureTable();

            $query = $this->database
                ->dataQuery(self::TABLE)
                ->select(['*']);

            foreach ($criteria as $column => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $query->where((string) $column, '=', is_scalar($value) ? $value : (string) $value);
            }

            $executable = $query
                ->orderBy('id', 'DESC')
                ->limit(max(1, $limit))
                ->toExecutable();

            return array_map(
                fn(array $row): array => $this->hydrate($row),
                $this->database->fetchAll($executable['sql'], $executable['bindings'])
            );
        } catch (Throwable $exception) {
            $this->errorManager->logThrowable($exception, 'audit', 'userNotice');

            return [];
        }
    }

    public function summary(int $windowSeconds = 86400): array
    {
        try {
            $this->ensureTable();
            $windowStart = time() - max(60, $windowSeconds);

            $records = $this->recent((int) $this->config->get('operations', 'AUDIT.SUMMARY_LIMIT', 250), []);
            $recentWindow = array_values(array_filter(
                $records,
                static fn(array $record): bool => (int) ($record['created_at'] ?? 0) >= $windowStart
            ));

            $categories = [];

            foreach ($recentWindow as $record) {
                $category = (string) ($record['category'] ?? 'framework');
                $categories[$category] = ($categories[$category] ?? 0) + 1;
            }

            ksort($categories);

            return [
                'enabled' => true,
                'table' => self::TABLE,
                'stored' => count($records),
                'recent_window_seconds' => max(60, $windowSeconds),
                'recent_count' => count($recentWindow),
                'categories' => $categories,
            ];
        } catch (Throwable $exception) {
            $this->errorManager->logThrowable($exception, 'audit', 'userNotice');

            return [
                'enabled' => (bool) $this->config->get('operations', 'AUDIT.ENABLED', true),
                'table' => self::TABLE,
                'stored' => 0,
                'recent_window_seconds' => max(60, $windowSeconds),
                'recent_count' => 0,
                'categories' => [],
            ];
        }
    }

    public function capabilities(): array
    {
        return [
            'enabled' => (bool) $this->config->get('operations', 'AUDIT.ENABLED', true),
            'storage' => [
                'database' => true,
                'table' => self::TABLE,
            ],
            'context' => [
                'actor' => true,
                'json' => true,
            ],
            'categories' => true,
            'severity' => true,
        ];
    }

    private function ensureTable(): void
    {
        if ($this->tableExists()) {
            return;
        }

        $statement = match ($this->driver()) {
            'pgsql' => 'CREATE TABLE "framework_audit_log" ("id" BIGSERIAL PRIMARY KEY, "category" VARCHAR(120) NOT NULL, "event" VARCHAR(255) NOT NULL, "severity" VARCHAR(30) NOT NULL, "actor_type" VARCHAR(255) NULL, "actor_id" VARCHAR(255) NULL, "context" TEXT NOT NULL, "created_at" BIGINT NOT NULL)',
            'sqlite' => 'CREATE TABLE "framework_audit_log" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "category" TEXT NOT NULL, "event" TEXT NOT NULL, "severity" TEXT NOT NULL, "actor_type" TEXT NULL, "actor_id" TEXT NULL, "context" TEXT NOT NULL, "created_at" INTEGER NOT NULL)',
            'sqlsrv' => 'CREATE TABLE [framework_audit_log] ([id] BIGINT IDENTITY(1,1) PRIMARY KEY, [category] NVARCHAR(120) NOT NULL, [event] NVARCHAR(255) NOT NULL, [severity] NVARCHAR(30) NOT NULL, [actor_type] NVARCHAR(255) NULL, [actor_id] NVARCHAR(255) NULL, [context] NVARCHAR(MAX) NOT NULL, [created_at] BIGINT NOT NULL)',
            default => 'CREATE TABLE `framework_audit_log` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `category` VARCHAR(120) NOT NULL, `event` VARCHAR(255) NOT NULL, `severity` VARCHAR(30) NOT NULL, `actor_type` VARCHAR(255) NULL, `actor_id` VARCHAR(255) NULL, `context` LONGTEXT NOT NULL, `created_at` BIGINT NOT NULL)',
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
        return [
            'id' => (string) ($row['id'] ?? ''),
            'category' => (string) ($row['category'] ?? 'framework'),
            'event' => (string) ($row['event'] ?? ''),
            'severity' => (string) ($row['severity'] ?? 'info'),
            'actor_type' => $row['actor_type'] ?? null,
            'actor_id' => $row['actor_id'] ?? null,
            'context' => $this->decodeJsonArray((string) ($row['context'] ?? '{}')),
            'created_at' => (int) ($row['created_at'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonArray(string $payload): array
    {
        try {
            $decoded = $this->fromJson($payload, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : [];
        } catch (\JsonException) {
            return [];
        }
    }
}
