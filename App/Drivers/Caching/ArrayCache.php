<?php

declare(strict_types=1);

namespace App\Drivers\Caching;

use App\Abstracts\Data\Cache;

class ArrayCache extends Cache
{
    /**
     * @var array<string, string>
     */
    private array $store = [];

    public function driverName(): string
    {
        return 'array';
    }

    /**
     * @return array<string, mixed>
     */
    public function capabilities(): array
    {
        return [
            'extension' => true,
            'persistent' => false,
            'shared_store' => false,
            'prefix_scoped_clear' => true,
            'compression' => true,
            'encryption' => true,
            'pruning' => true,
        ];
    }

    protected function putRaw(string $storageKey, string $payload, ?int $ttl = null): bool
    {
        $this->store[$storageKey] = $payload;

        return true;
    }

    protected function getRaw(string $storageKey): ?string
    {
        return $this->store[$storageKey] ?? null;
    }

    protected function deleteRaw(string $storageKey): bool
    {
        unset($this->store[$storageKey]);

        return true;
    }

    /**
     * @return array<string, int>
     */
    protected function discoverStoredEntries(): array
    {
        $entries = [];

        foreach ($this->store as $storageKey => $payload) {
            if (!$this->isString($payload) || $payload === '') {
                continue;
            }

            try {
                $record = $this->dataHandler->jsonDecode($payload, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                continue;
            }

            if (!$this->isArray($record)) {
                continue;
            }

            $resolvedKey = (string) ($record['key'] ?? $storageKey);
            $entries[$resolvedKey] = $this->toInt($record['timestamp'] ?? 0);
        }

        return $entries;
    }
}
