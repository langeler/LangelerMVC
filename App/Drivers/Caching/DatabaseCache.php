<?php

declare(strict_types=1);

namespace App\Drivers\Caching;

use App\Abstracts\Data\Cache;
use App\Core\Database;
use App\Utilities\Handlers\DataHandler;
use App\Utilities\Managers\Data\CryptoManager;
use App\Utilities\Managers\DateTimeManager;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\SettingsManager;
use App\Utilities\Managers\System\ErrorManager;

class DatabaseCache extends Cache
{
    public function __construct(
        private readonly Database $database,
        FileManager $fileManager,
        DataHandler $dataHandler,
        CryptoManager $cryptoManager,
        DateTimeManager $dateTimeManager,
        SettingsManager $settingsManager,
        ErrorManager $errorManager
    ) {
        parent::__construct(
            $fileManager,
            $dataHandler,
            $cryptoManager,
            $dateTimeManager,
            $settingsManager,
            $errorManager
        );
    }

    public function driverName(): string
    {
        return 'database';
    }

    /**
     * @return array<string, mixed>
     */
    public function capabilities(): array
    {
        return [
            'extension' => true,
            'persistent' => true,
            'shared_store' => true,
            'prefix_scoped_clear' => true,
            'compression' => true,
            'encryption' => true,
            'pruning' => true,
            'query_builder' => true,
        ];
    }

    protected function putRaw(string $storageKey, string $payload, ?int $ttl = null): bool
    {
        $table = $this->cacheTable();
        $timestamp = $this->extractTimestamp($payload);

        $this->database->beginTransaction();

        try {
            $delete = $this->database
                ->dataQuery($table)
                ->delete($table)
                ->where('cache_key', '=', $storageKey)
                ->toExecutable();

            $this->database->execute($delete['sql'], $delete['bindings']);

            $insert = $this->database
                ->dataQuery($table)
                ->insert($table, [
                    'cache_key' => $storageKey,
                    'cache_data' => $payload,
                    'timestamp' => $timestamp,
                    'ttl' => $ttl ?? 0,
                ])
                ->toExecutable();

            $this->database->execute($insert['sql'], $insert['bindings']);
            $this->database->commit();

            return true;
        } catch (\Throwable $exception) {
            $this->database->rollBack();
            throw $exception;
        }
    }

    protected function getRaw(string $storageKey): ?string
    {
        $table = $this->cacheTable();
        $select = $this->database
            ->dataQuery($table)
            ->select(['cache_data'])
            ->where('cache_key', '=', $storageKey)
            ->limit(1)
            ->toExecutable();

        $record = $this->database->fetchOne($select['sql'], $select['bindings']);

        if (!$this->isArray($record) || !$this->keyExists($record, 'cache_data')) {
            return null;
        }

        return (string) $record['cache_data'];
    }

    protected function deleteRaw(string $storageKey): bool
    {
        $table = $this->cacheTable();
        $delete = $this->database
            ->dataQuery($table)
            ->delete($table)
            ->where('cache_key', '=', $storageKey)
            ->toExecutable();

        $this->database->execute($delete['sql'], $delete['bindings']);

        return true;
    }

    /**
     * @return array<string, int>
     */
    protected function discoverStoredEntries(): array
    {
        $table = $this->cacheTable();
        $select = $this->database
            ->dataQuery($table)
            ->select(['cache_key', 'timestamp'])
            ->toExecutable();

        $records = $this->database->fetchAll($select['sql'], $select['bindings']);
        $entries = [];

        foreach ($records as $record) {
            if (!$this->isArray($record)) {
                continue;
            }

            $storageKey = (string) ($record['cache_key'] ?? '');

            if ($storageKey === '') {
                continue;
            }

            $entries[$storageKey] = $this->toInt($record['timestamp'] ?? 0);
        }

        return $entries;
    }

    private function extractTimestamp(string $payload): int
    {
        try {
            $record = $this->dataHandler->jsonDecode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return $this->dateTimeManager->getCurrentTimestamp();
        }

        return $this->isArray($record)
            ? $this->toInt($record['timestamp'] ?? $this->dateTimeManager->getCurrentTimestamp())
            : $this->dateTimeManager->getCurrentTimestamp();
    }
}
