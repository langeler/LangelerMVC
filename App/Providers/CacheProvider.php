<?php

namespace App\Providers;

use App\Core\Container;
use App\Drivers\Caching\{
    DatabaseCache,
    FileCache,
    MemCache,
    RedisCache
};
use App\Exceptions\ContainerException;

/**
 * CacheProvider Class
 *
 * This class extends the `Container` to provide cache-related services.
 * It dynamically maps cache drivers and supports resolution based on application configuration.
 */
class CacheProvider extends Container
{
    /**
     * A mapping of cache driver aliases to their fully qualified class names.
     *
     * @var array<string, string> Map of cache driver aliases.
     */
    protected readonly array $cacheMap;

    /**
     * Constructor for CacheProvider.
     *
     * Initializes the cache map.
     */
    public function __construct()
    {
        $this->cacheMap = [
            'database' => DatabaseCache::class,
            'file'     => FileCache::class,
            'memcache' => MemCache::class,
            'redis'    => RedisCache::class,
        ];
    }

    /**
     * Registers the cache services in the container.
     *
     * Maps cache drivers to aliases and registers them as lazy singletons.
     *
     * @return void
     * @throws ContainerException If an error occurs during registration.
     */
    public function registerServices(): void
    {
        $this->wrapInTry(
            fn() => (!$this->isArray($this->cacheMap) || $this->isEmpty($this->cacheMap))
                ? throw new ContainerException("The cache map must be a non-empty array of aliases.")
                : $this->walk(
                    $this->cacheMap,
                    fn($class, $alias) => [
                        $this->registerAlias($alias, $class),
                        $this->registerLazy($class, fn() => $this->resolveInstance($class))
                    ]
                ),
            new ContainerException("Error registering cache services.")
        );
    }

    /**
     * Retrieves the appropriate cache driver based on the provided configuration.
     *
     * @param array $cacheSettings Configuration array specifying the cache driver.
     * @return object The resolved cache driver instance.
     * @throws ContainerException If the specified cache driver is invalid or unsupported.
     */
    public function getCacheDriver(array $cacheSettings): object
    {
        return $this->wrapInTry(
            fn() => $this->getInstance(
                $this->cacheMap[$cacheSettings['DRIVER']
                    ?? throw new ContainerException("Cache driver alias is missing or invalid.")]
            ),
            new ContainerException("Error retrieving cache driver.")
        );
    }
}
