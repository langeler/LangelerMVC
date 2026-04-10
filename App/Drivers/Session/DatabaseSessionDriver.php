<?php

declare(strict_types=1);

namespace App\Drivers\Session;

use App\Contracts\Session\SessionDriverInterface;
use App\Core\Database;
use App\Utilities\Traits\ManipulationTrait;
use SessionHandler;

class DatabaseSessionDriver extends SessionHandler implements SessionDriverInterface
{
    use ManipulationTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    public function __construct(
        private readonly Database $database,
        private readonly string $table = 'framework_sessions'
    ) {
    }

    public function driverName(): string
    {
        return 'database';
    }

    public function capabilities(): array
    {
        return [
            'extension' => true,
            'persistent' => true,
            'garbage_collection' => true,
        ];
    }

    public function supports(string $feature): bool
    {
        return ($this->capabilities()[$feature] ?? null) === true;
    }

    public function open(string $savePath, string $sessionName): bool
    {
        $this->ensureTable();

        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $this->ensureTable();
        $query = $this->database
            ->dataQuery($this->table)
            ->select(['payload'])
            ->where('id', '=', $id)
            ->limit(1)
            ->toExecutable();

        $payload = $this->database->fetchColumn($query['sql'], $query['bindings']);

        return $payload === false ? '' : (string) $payload;
    }

    public function write(string $id, string $data): bool
    {
        $this->ensureTable();
        $timestamp = time();

        $update = $this->database
            ->dataQuery($this->table)
            ->update($this->table, [
                'payload' => $data,
                'last_activity' => $timestamp,
            ])
            ->where('id', '=', $id)
            ->toExecutable();

        $updated = $this->database->execute($update['sql'], $update['bindings']);

        if ($updated > 0) {
            return true;
        }

        $insert = $this->database
            ->dataQuery($this->table)
            ->insert($this->table, [
                'id' => $id,
                'payload' => $data,
                'last_activity' => $timestamp,
            ])
            ->toExecutable();

        return $this->database->execute($insert['sql'], $insert['bindings']) > 0;
    }

    public function destroy(string $id): bool
    {
        $this->ensureTable();
        $query = $this->database
            ->dataQuery($this->table)
            ->delete($this->table)
            ->where('id', '=', $id)
            ->toExecutable();

        return $this->database->execute($query['sql'], $query['bindings']) >= 0;
    }

    public function gc(int $max_lifetime): int|false
    {
        $this->ensureTable();
        $query = $this->database
            ->dataQuery($this->table)
            ->delete($this->table)
            ->where('last_activity', '<', time() - $max_lifetime)
            ->toExecutable();

        return $this->database->execute($query['sql'], $query['bindings']);
    }

    private function ensureTable(): void
    {
        if ($this->tableExists()) {
            return;
        }

        $statement = match ($this->driver()) {
            'pgsql' => sprintf('CREATE TABLE "%s" ("id" VARCHAR(255) PRIMARY KEY, "payload" TEXT NOT NULL, "last_activity" BIGINT NOT NULL)', $this->table),
            'sqlite' => sprintf('CREATE TABLE "%s" ("id" TEXT PRIMARY KEY, "payload" TEXT NOT NULL, "last_activity" INTEGER NOT NULL)', $this->table),
            'sqlsrv' => sprintf('CREATE TABLE [%s] ([id] NVARCHAR(255) PRIMARY KEY, [payload] NVARCHAR(MAX) NOT NULL, [last_activity] BIGINT NOT NULL)', $this->table),
            default => sprintf('CREATE TABLE `%s` (`id` VARCHAR(255) PRIMARY KEY, `payload` LONGTEXT NOT NULL, `last_activity` BIGINT NOT NULL)', $this->table),
        };

        $this->database->query($statement);
    }

    private function tableExists(): bool
    {
        return match ($this->driver()) {
            'sqlite' => $this->database->fetchColumn(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?",
                [$this->table]
            ) !== false,
            'pgsql' => $this->database->fetchColumn(
                'SELECT 1 FROM information_schema.tables WHERE table_name = ?',
                [$this->table]
            ) !== false,
            'sqlsrv' => $this->database->fetchColumn(
                'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?',
                [$this->table]
            ) !== false,
            default => $this->database->fetchColumn(
                'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
                [$this->table]
            ) !== false,
        };
    }

    private function driver(): string
    {
        return $this->toLowerString((string) $this->database->getAttribute('driverName'));
    }
}
