<?php

declare(strict_types=1);

namespace App\Drivers\Caching;

use App\Abstracts\Data\Cache;
use App\Utilities\Handlers\DataHandler;
use App\Utilities\Managers\Data\CryptoManager;
use App\Utilities\Managers\DateTimeManager;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\SettingsManager;
use App\Utilities\Managers\System\ErrorManager;
use Memcached;

class MemCache extends Cache
{
    private ?Memcached $memcached = null;

    public function __construct(
        FileManager $fileManager,
        DataHandler $dataHandler,
        CryptoManager $cryptoManager,
        DateTimeManager $dateTimeManager,
        SettingsManager $settingsManager,
        ErrorManager $errorManager,
        ?Memcached $memcached = null
    ) {
        $this->memcached = $memcached;

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
        return 'memcache';
    }

    /**
     * @return array<string, mixed>
     */
    public function capabilities(): array
    {
        return [
            'extension' => class_exists(Memcached::class),
            'persistent' => true,
            'shared_store' => true,
            'prefix_scoped_clear' => true,
            'compression' => true,
            'encryption' => true,
            'pruning' => true,
        ];
    }

    protected function putRaw(string $storageKey, string $payload, ?int $ttl = null): bool
    {
        return $this->client()->set($storageKey, $payload, $ttl ?? 0);
    }

    protected function getRaw(string $storageKey): ?string
    {
        $result = $this->client()->get($storageKey);
        $code = $this->client()->getResultCode();

        if ($result === false && $code !== Memcached::RES_SUCCESS) {
            return $code === Memcached::RES_NOTFOUND
                ? null
                : $this->throwCacheException('Failed to read from the Memcached cache store.');
        }

        return $result === false ? null : (string) $result;
    }

    protected function deleteRaw(string $storageKey): bool
    {
        $deleted = $this->client()->delete($storageKey);
        $code = $this->client()->getResultCode();

        return $deleted || $code === Memcached::RES_NOTFOUND;
    }

    private function client(): Memcached
    {
        if ($this->memcached instanceof Memcached) {
            return $this->memcached;
        }

        if (!$this->supports('extension')) {
            $this->throwCacheException('Memcached cache driver is unavailable on this PHP runtime.');
        }

        $client = new Memcached();
        $host = $this->cleanStringSetting('MEMCACHE_HOST', $this->cleanStringSetting('MEMCACHED_HOST', '127.0.0.1'));
        $port = $this->toInt($this->cacheSetting('MEMCACHE_PORT', $this->cacheSetting('MEMCACHED_PORT', 11211)));
        $weight = $this->toInt($this->cacheSetting('MEMCACHE_WEIGHT', $this->cacheSetting('MEMCACHED', 0)));

        if ($client->getServerList() === [] && !$client->addServer($host, $port, $weight > 0 ? $weight : 0)) {
            $this->throwCacheException("Unable to add the Memcached cache server {$host}:{$port}.");
        }

        return $this->memcached = $client;
    }

    private function cleanStringSetting(string $key, string $default): string
    {
        $value = $this->cacheSetting($key, $default);
        $stringValue = $this->isString($value) ? $value : (string) $value;
        $withoutComment = (string) ($this->replaceByPattern('/\s+#.*$/', '', $stringValue) ?? $stringValue);
        $normalized = $this->trimString($withoutComment);

        return $normalized !== '' ? $normalized : $default;
    }
}
