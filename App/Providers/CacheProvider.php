<?php

namespace App\Providers;

use App\Core\Container;
use App\Drivers\Caching\DatabaseCache;
use App\Drivers\Caching\FileCache;
use App\Drivers\Caching\MemCache;
use App\Drivers\Caching\RedisCache;
use App\Exceptions\ContainerException;

/**
 * Cache services injector for registering core application services.
 *
 * This class registers and resolves different types of cache services:
 * - `DatabaseCache`
 * - `FileCache`
 * - `MemCache`
 * - `RedisCache`
 *
 * It also dynamically retrieves the appropriate cache driver based on the application's configuration.
 */
class CacheContainer extends Container
{
	/**
	 * Registers the cache services and their aliases in the application's service container.
	 *
	 * @return void
	 * @throws ContainerException If an error occurs during service registration.
	 */
	public function registerServices(): void
	{
		$this->wrapInTry(
			function () {
				$this->registerAliases();
				$this->registerLazySingletons();
			},
			"Error registering cache services."
		);
	}

	/**
	 * Registers aliases for cache drivers to enable shorthand usage.
	 *
	 * @return void
	 */
	protected function registerAliases(): void
	{
		$this->registerAlias('DatabaseCache', DatabaseCache::class);
		$this->registerAlias('FileCache', FileCache::class);
		$this->registerAlias('MemCache', MemCache::class);
		$this->registerAlias('RedisCache', RedisCache::class);
	}

	/**
	 * Registers lazy singletons for cache drivers to ensure services are instantiated only when needed.
	 *
	 * @return void
	 */
	protected function registerLazySingletons(): void
	{
		$this->registerLazySingleton(DatabaseCache::class, fn() => $this->resolve(DatabaseCache::class));
		$this->registerLazySingleton(FileCache::class, fn() => $this->resolve(FileCache::class));
		$this->registerLazySingleton(MemCache::class, fn() => $this->resolve(MemCache::class));
		$this->registerLazySingleton(RedisCache::class, fn() => $this->resolve(RedisCache::class));
	}

	/**
	 * Retrieves the appropriate cache driver based on the provided configuration.
	 *
	 * @param array $cacheSettings The cache configuration array specifying the cache driver (e.g., 'file', 'redis').
	 * @return object The resolved cache driver instance.
	 * @throws ContainerException If the specified cache driver is not supported or another error occurs.
	 */
	public function getCacheDriver(array $cacheSettings): object
	{
		return $this->wrapInTry(
			fn() => $this->resolveCacheDriver($cacheSettings),
			"Error retrieving cache driver."
		);
	}

	/**
	 * Resolves the cache driver based on the configuration.
	 *
	 * @param array $cacheSettings The cache configuration array.
	 * @return object The resolved cache driver instance.
	 * @throws ContainerException If the specified cache driver is not supported.
	 */
	protected function resolveCacheDriver(array $cacheSettings): object
	{
		return match ($cacheSettings['DRIVER'] ?? null) {
			'database' => $this->getService(DatabaseCache::class),
			'file' => $this->getService(FileCache::class),
			'memcache' => $this->getService(MemCache::class),
			'redis' => $this->getService(RedisCache::class),
			default => throw new ContainerException("Unsupported cache driver: " . ($cacheSettings['DRIVER'] ?? 'none')),
		};
	}
}
