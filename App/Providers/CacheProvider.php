<?php

namespace App\Providers;

use App\Contracts\Data\CacheDriverInterface;
use App\Core\Container;
use App\Drivers\Caching\{
    ArrayCache,
    DatabaseCache,
    FileCache,
    MemCache,
    RedisCache
};
use App\Exceptions\ContainerException;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\Patterns\PatternTrait;

/**
 * CacheProvider Class
 *
 * Framework-level resolver for cache drivers. The provider owns alias mapping,
 * lazy registration, runtime support checks, and normalization of driver names
 * so the rest of the backend can depend on the cache contract instead of
 * backend-specific wiring.
 */
class CacheProvider extends Container
{
    use ManipulationTrait, PatternTrait;

    /**
     * @var array<string, string>
     */
    protected readonly array $cacheMap;

    private bool $servicesRegistered = false;

    public function __construct()
    {
        parent::__construct();

        $this->cacheMap = [
            'array' => ArrayCache::class,
            'database' => DatabaseCache::class,
            'file' => FileCache::class,
            'memcache' => MemCache::class,
            'redis' => RedisCache::class,
        ];
    }

    public function registerServices(): void
    {
        if ($this->servicesRegistered) {
            return;
        }

        $this->wrapInTry(
            function (): void {
                if (!$this->isArray($this->cacheMap) || $this->isEmpty($this->cacheMap)) {
                    throw new ContainerException('The cache map must be a non-empty array of aliases.');
                }

                foreach ($this->cacheMap as $alias => $class) {
                    $this->registerAlias($alias, $class);
                    $this->registerLazy($class, fn() => $this->registerInstance($class));
                }

                $this->servicesRegistered = true;
            },
            new ContainerException('Error registering cache services.')
        );
    }

    public function getCacheDriver(array $cacheSettings): CacheDriverInterface
    {
        return $this->wrapInTry(
            function () use ($cacheSettings): CacheDriverInterface {
                $driver = $this->normalizeDriverAlias((string) ($cacheSettings['DRIVER'] ?? ''));
                $instance = $this->getInstance(
                    $this->cacheMap[$driver
                        ?: throw new ContainerException('Cache driver alias is missing or invalid.')]
                    ?? throw new ContainerException("Unsupported cache driver alias: {$driver}")
                );

                if (!$instance instanceof CacheDriverInterface) {
                    throw new ContainerException("Resolved cache driver [{$driver}] does not implement the cache contract.");
                }

                if (!$instance->supports('extension')) {
                    throw new ContainerException("Cache driver [{$driver}] is not supported by this PHP runtime.");
                }

                return $instance;
            },
            new ContainerException('Error retrieving cache driver.')
        );
    }

    /**
     * @return array<string>
     */
    public function getSupportedDrivers(): array
    {
        return $this->getKeys($this->cacheMap);
    }

    private function normalizeDriverAlias(string $driver): string
    {
        $normalized = $this->toLower(
            $this->trimString(
                (string) ($this->replaceByPattern('/\s+#.*$/', '', $driver) ?? $driver)
            )
        );

        return match ($normalized) {
            'memcached' => 'memcache',
            default => $normalized,
        };
    }
}
