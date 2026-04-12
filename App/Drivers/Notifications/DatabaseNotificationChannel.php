<?php

declare(strict_types=1);

namespace App\Drivers\Notifications;

use App\Contracts\Support\NotificationChannelInterface;
use App\Contracts\Support\NotificationInterface;
use App\Core\Database;
use App\Utilities\Traits\ConversionTrait;
use App\Utilities\Traits\ManipulationTrait;

class DatabaseNotificationChannel implements NotificationChannelInterface
{
    use ConversionTrait, ManipulationTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    private const TABLE = 'framework_notifications';

    public function __construct(private readonly Database $database)
    {
        $this->ensureTable();
    }

    public function name(): string
    {
        return 'database';
    }

    public function send(array $notifiable, NotificationInterface $notification): array|bool|null
    {
        $record = [
            'notifiable_type' => (string) ($notifiable['type'] ?? 'Anonymous'),
            'notifiable_id' => isset($notifiable['id']) ? (string) $notifiable['id'] : null,
            'channel' => $this->name(),
            'notification' => $notification->type(),
            'data' => $this->toJson(
                $notification->toDatabase($notifiable),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ),
            'read_at' => null,
            'created_at' => time(),
        ];

        $query = $this->database
            ->dataQuery(self::TABLE)
            ->insert(self::TABLE, $record)
            ->toExecutable();

        $this->database->execute($query['sql'], $query['bindings']);

        return [
            'channel' => $this->name(),
            'stored' => true,
            'notification' => $notification->type(),
        ];
    }

    private function ensureTable(): void
    {
        if ($this->tableExists()) {
            return;
        }

        $statement = match ($this->driver()) {
            'pgsql' => 'CREATE TABLE "framework_notifications" ("id" BIGSERIAL PRIMARY KEY, "notifiable_type" VARCHAR(255) NOT NULL, "notifiable_id" VARCHAR(255) NULL, "channel" VARCHAR(60) NOT NULL, "notification" VARCHAR(255) NOT NULL, "data" TEXT NOT NULL, "read_at" BIGINT NULL, "created_at" BIGINT NOT NULL)',
            'sqlite' => 'CREATE TABLE "framework_notifications" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "notifiable_type" TEXT NOT NULL, "notifiable_id" TEXT NULL, "channel" TEXT NOT NULL, "notification" TEXT NOT NULL, "data" TEXT NOT NULL, "read_at" INTEGER NULL, "created_at" INTEGER NOT NULL)',
            'sqlsrv' => 'CREATE TABLE [framework_notifications] ([id] BIGINT IDENTITY(1,1) PRIMARY KEY, [notifiable_type] NVARCHAR(255) NOT NULL, [notifiable_id] NVARCHAR(255) NULL, [channel] NVARCHAR(60) NOT NULL, [notification] NVARCHAR(255) NOT NULL, [data] NVARCHAR(MAX) NOT NULL, [read_at] BIGINT NULL, [created_at] BIGINT NOT NULL)',
            default => 'CREATE TABLE `framework_notifications` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `notifiable_type` VARCHAR(255) NOT NULL, `notifiable_id` VARCHAR(255) NULL, `channel` VARCHAR(60) NOT NULL, `notification` VARCHAR(255) NOT NULL, `data` LONGTEXT NOT NULL, `read_at` BIGINT NULL, `created_at` BIGINT NOT NULL)',
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
}
