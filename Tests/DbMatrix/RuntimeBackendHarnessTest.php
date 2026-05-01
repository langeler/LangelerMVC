<?php

declare(strict_types=1);

namespace Tests\DbMatrix;

use App\Drivers\Caching\MemCache;
use App\Drivers\Caching\RedisCache;
use App\Drivers\Session\RedisSessionDriver;
use App\Providers\CryptoProvider;
use App\Providers\ExceptionProvider;
use App\Utilities\Handlers\DataHandler;
use App\Utilities\Managers\Data\CryptoManager;
use App\Utilities\Managers\DateTimeManager;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\SettingsManager;
use App\Utilities\Managers\System\ErrorManager;
use PHPUnit\Framework\TestCase;
use Throwable;

final class RuntimeBackendHarnessTest extends TestCase
{
    public function testRedisCacheAndSessionRoundTripWhenRuntimeIsAvailable(): void
    {
        if (!class_exists(\Redis::class)) {
            self::markTestSkipped('The ext-redis PHP extension is not loaded.');
        }

        $client = $this->connectedRedisClient();
        $settings = $this->makeSettingsManager([
            'DRIVER' => 'redis',
            'PREFIX' => 'langelermvc_runtime_redis_' . bin2hex(random_bytes(4)),
            'COMPRESSION' => false,
            'ENCRYPT' => false,
            'REDIS_HOST' => $this->envString('LANGELER_REDIS_HOST', '127.0.0.1'),
            'REDIS_PORT' => $this->envInt('LANGELER_REDIS_PORT', 6379),
            'REDIS_PASSWORD' => $this->envString('LANGELER_REDIS_PASSWORD', ''),
            'REDIS_DATABASE' => $this->envInt('LANGELER_REDIS_DATABASE', 0),
        ]);
        $cache = new RedisCache(
            new FileManager(),
            new DataHandler(),
            $this->makeCryptoManager($settings),
            new DateTimeManager(),
            $settings,
            $this->makeErrorManager(),
            $client
        );

        self::assertTrue($cache->set('runtime.backend', ['backend' => 'redis'], 30));
        self::assertSame(['backend' => 'redis'], $cache->get('runtime.backend'));
        self::assertTrue($cache->delete('runtime.backend'));
        self::assertNull($cache->get('runtime.backend'));

        $sessionId = 'runtime-' . bin2hex(random_bytes(8));
        $session = new RedisSessionDriver([
            'host' => $this->envString('LANGELER_REDIS_HOST', '127.0.0.1'),
            'port' => $this->envInt('LANGELER_REDIS_PORT', 6379),
            'timeout' => 1.0,
            'password' => $this->envString('LANGELER_REDIS_PASSWORD', ''),
            'database' => $this->envInt('LANGELER_REDIS_DATABASE', 0),
            'prefix' => 'langelermvc_runtime_session',
            'ttl' => 30,
        ]);

        self::assertTrue($session->open('', 'langelermvc_runtime'));
        self::assertTrue($session->write($sessionId, 'redis-session-payload'));
        self::assertSame('redis-session-payload', $session->read($sessionId));
        self::assertTrue($session->destroy($sessionId));
        self::assertTrue($session->close());
    }

    public function testMemcachedCacheRoundTripWhenRuntimeIsAvailable(): void
    {
        if (!class_exists(\Memcached::class)) {
            self::markTestSkipped('The ext-memcached PHP extension is not loaded.');
        }

        $client = $this->connectedMemcachedClient();
        $settings = $this->makeSettingsManager([
            'DRIVER' => 'memcache',
            'PREFIX' => 'langelermvc_runtime_memcached_' . bin2hex(random_bytes(4)),
            'COMPRESSION' => false,
            'ENCRYPT' => false,
            'MEMCACHE_HOST' => $this->envString('LANGELER_MEMCACHED_HOST', '127.0.0.1'),
            'MEMCACHE_PORT' => $this->envInt('LANGELER_MEMCACHED_PORT', 11211),
        ]);
        $cache = new MemCache(
            new FileManager(),
            new DataHandler(),
            $this->makeCryptoManager($settings),
            new DateTimeManager(),
            $settings,
            $this->makeErrorManager(),
            $client
        );

        self::assertTrue($cache->set('runtime.backend', ['backend' => 'memcached'], 30));
        self::assertSame(['backend' => 'memcached'], $cache->get('runtime.backend'));
        self::assertTrue($cache->delete('runtime.backend'));
        self::assertNull($cache->get('runtime.backend'));
    }

    private function connectedRedisClient(): \Redis
    {
        $client = new \Redis();
        $host = $this->envString('LANGELER_REDIS_HOST', '127.0.0.1');
        $port = $this->envInt('LANGELER_REDIS_PORT', 6379);

        try {
            if (!$client->connect($host, $port, 1.0)) {
                self::markTestSkipped(sprintf('Redis is not reachable at %s:%d.', $host, $port));
            }

            $password = $this->envString('LANGELER_REDIS_PASSWORD', '');

            if ($password !== '' && !$client->auth($password)) {
                self::markTestSkipped('Redis authentication failed for the runtime harness.');
            }

            $database = $this->envInt('LANGELER_REDIS_DATABASE', 0);

            if ($database > 0 && !$client->select($database)) {
                self::markTestSkipped(sprintf('Redis database [%d] could not be selected.', $database));
            }

            return $client;
        } catch (Throwable $exception) {
            self::markTestSkipped('Redis runtime harness could not connect: ' . $exception->getMessage());
        }
    }

    private function connectedMemcachedClient(): \Memcached
    {
        $client = new \Memcached('langelermvc_runtime_' . bin2hex(random_bytes(4)));
        $host = $this->envString('LANGELER_MEMCACHED_HOST', '127.0.0.1');
        $port = $this->envInt('LANGELER_MEMCACHED_PORT', 11211);
        $probeKey = 'langelermvc_runtime_probe_' . bin2hex(random_bytes(4));

        if ($client->getServerList() === [] && !$client->addServer($host, $port)) {
            self::markTestSkipped(sprintf('Memcached server could not be added at %s:%d.', $host, $port));
        }

        if (!$client->set($probeKey, 'ok', 10)) {
            self::markTestSkipped(sprintf('Memcached is not reachable at %s:%d.', $host, $port));
        }

        $client->delete($probeKey);

        return $client;
    }

    private function makeCryptoManager(SettingsManager $settingsManager): CryptoManager
    {
        return new CryptoManager(new CryptoProvider(), $settingsManager);
    }

    private function makeErrorManager(): ErrorManager
    {
        return new ErrorManager(new ExceptionProvider());
    }

    /**
     * @param array<string, mixed> $cacheSettings
     */
    private function makeSettingsManager(array $cacheSettings): SettingsManager
    {
        $cacheDefaults = [
            'ENABLED' => true,
            'DRIVER' => 'array',
            'PREFIX' => 'langelermvc_runtime',
            'TTL' => 3600,
            'COMPRESSION' => false,
            'SERIALIZATION' => 'php',
            'ENCRYPT' => false,
            'MAX_ITEMS' => 0,
            'FILE' => 'Storage/Cache',
            'TABLE' => 'cache',
        ];
        $encryptionDefaults = [
            'ENABLED' => true,
            'DRIVER' => 'openssl',
            'OPENSSL_CIPHER' => 'AES-256-CBC',
            'KEY' => 'base64:' . base64_encode(str_repeat('a', 32)),
            'OPENSSL_KEY' => 'base64:' . base64_encode(str_repeat('b', 32)),
        ];

        return new class(array_merge($cacheDefaults, $cacheSettings), $encryptionDefaults) extends SettingsManager {
            /**
             * @param array<string, mixed> $cacheConfig
             * @param array<string, mixed> $encryptionConfig
             */
            public function __construct(
                private readonly array $cacheConfig,
                private readonly array $encryptionConfig
            ) {
            }

            public function getAllSettings(string $fileName): array
            {
                return match (strtolower($fileName)) {
                    'cache' => $this->cacheConfig,
                    'encryption' => $this->encryptionConfig,
                    default => [],
                };
            }
        };
    }

    private function envString(string $key, string $default): string
    {
        $value = getenv($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    private function envInt(string $key, int $default): int
    {
        $value = getenv($key);

        return is_string($value) && ctype_digit($value) ? (int) $value : $default;
    }
}
