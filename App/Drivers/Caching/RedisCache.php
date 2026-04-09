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
use Redis;

class RedisCache extends Cache
{
    private ?Redis $redis = null;

    public function __construct(
        FileManager $fileManager,
        DataHandler $dataHandler,
        CryptoManager $cryptoManager,
        DateTimeManager $dateTimeManager,
        SettingsManager $settingsManager,
        ErrorManager $errorManager,
        ?Redis $redis = null
    ) {
        $this->redis = $redis;

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
        return 'redis';
    }

    /**
     * @return array<string, mixed>
     */
    public function capabilities(): array
    {
        return [
            'extension' => class_exists(Redis::class),
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
        $client = $this->client();
        $effectiveTtl = $ttl ?? 0;

        if ($effectiveTtl > 0) {
            return $client->setex($storageKey, $effectiveTtl, $payload);
        }

        return $client->set($storageKey, $payload);
    }

    protected function getRaw(string $storageKey): ?string
    {
        $result = $this->client()->get($storageKey);

        return $result === false ? null : (string) $result;
    }

    protected function deleteRaw(string $storageKey): bool
    {
        return $this->client()->del($storageKey) >= 0;
    }

    private function client(): Redis
    {
        if ($this->redis instanceof Redis) {
            return $this->redis;
        }

        if (!$this->supports('extension')) {
            $this->throwCacheException('Redis cache driver is unavailable on this PHP runtime.');
        }

        $client = new Redis();
        $host = $this->cleanStringSetting('REDIS_HOST', '127.0.0.1');
        $port = $this->toInt($this->cacheSetting('REDIS_PORT', 6379));
        $timeout = $this->toFloat($this->cacheSetting('REDIS_TIMEOUT', 1.5));

        $connected = $timeout > 0
            ? $client->connect($host, $port, $timeout)
            : $client->connect($host, $port);

        if (!$connected) {
            $this->throwCacheException("Unable to connect to Redis at {$host}:{$port}.");
        }

        $password = $this->cleanStringSetting('REDIS_PASSWORD', '');

        if ($password !== '' && !$client->auth($password)) {
            $this->throwCacheException('Redis authentication failed for the configured cache connection.');
        }

        $database = $this->toInt($this->cacheSetting('REDIS_DATABASE', $this->cacheSetting('REDIS', 0)));

        if ($database > 0 && !$client->select($database)) {
            $this->throwCacheException("Unable to select Redis cache database {$database}.");
        }

        return $this->redis = $client;
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
