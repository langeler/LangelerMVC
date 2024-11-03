<?php

namespace App\Services;

use App\Abstracts\Http\Service;
use App\Abstracts\Data\Cache;
use App\Drivers\DatabaseCache;
use App\Drivers\FileCache;
use App\Drivers\MemCache;
use App\Drivers\RedisCache;
use Exception;

/**
 * Cache services injector for registering core application services.
 *
 * This class is responsible for registering and resolving different types of cache services, such as:
 * - `DatabaseCache`
 * - `FileCache`
 * - `MemCache`
 * - `RedisCache`
 *
 * It also provides a method to dynamically retrieve the appropriate cache driver based on the application's configuration.
 */
class CacheService extends Service
{
	/**
	 * Registers the cache services and their aliases in the application's service container.
	 * It maps cache drivers to their respective implementations and ensures they are loaded lazily.
	 *
	 * @return void
	 * @throws Exception If an error occurs during service registration.
	 */
	public function registerServices(): void
	{
		try {
			// Register aliases for cache drivers, enabling shorthand usage within the application.
			$this->registerAlias('DatabaseCache', DatabaseCache::class);
			$this->registerAlias('FileCache', FileCache::class);
			$this->registerAlias('MemCache', MemCache::class);
			$this->registerAlias('RedisCache', RedisCache::class);

			// Lazily register each cache driver as a singleton, ensuring that services are only instantiated when needed.
			$this->registerLazySingleton('DatabaseCache', fn() => $this->resolve(DatabaseCache::class));
			$this->registerLazySingleton('FileCache', fn() => $this->resolve(FileCache::class));
			$this->registerLazySingleton('MemCache', fn() => $this->resolve(MemCache::class));
			$this->registerLazySingleton('RedisCache', fn() => $this->resolve(RedisCache::class));
		} catch (Exception $e) {
			// Catch any exceptions during registration and throw a more detailed error message.
			throw new Exception("Error registering cache services: " . $e->getMessage());
		}
	}

	/**
	 * Retrieves the appropriate cache driver based on the configuration provided in $cacheSettings.
	 *
	 * This method uses a `match` expression to map the configuration's cache driver type to a registered cache service.
	 * If the cache driver is not supported, an exception is thrown.
	 *
	 * @param array $cacheSettings The cache configuration array which specifies the cache driver (e.g., 'file', 'redis').
	 * @return Cache|null The resolved cache driver instance or null if not found.
	 * @throws Exception If the specified cache driver is not supported or another error occurs.
	 */
	public function getCacheDriver(array $cacheSettings): ?Cache
	{
		try {
			// Map the cache driver type from the settings to the appropriate registered service.
			return match ($cacheSettings['DRIVER']) {
				'database' => $this->getService('DatabaseCache'),
				'file' => $this->getService('FileCache'),
				'memcache' => $this->getService('MemCache'),
				'redis' => $this->getService('RedisCache'),
				default => throw new Exception("Unsupported cache driver: " . $cacheSettings['DRIVER']),
			};
		} catch (Exception $e) {
			// Catch any exceptions during cache driver retrieval and provide a descriptive error message.
			throw new Exception("Error retrieving cache driver: " . $e->getMessage());
		}
	}
}
