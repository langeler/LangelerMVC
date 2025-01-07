<?php

namespace App\Utilities\Managers;

use App\Providers\CacheProvider;
use App\Utilities\Managers\SettingsManager;
use App\Exceptions\Data\CacheException;
use App\Utilities\Traits\TypeCheckerTrait;
use App\Utilities\Traits\ArrayTrait;

/**
 * Class CacheManager
 *
 * Manages caching operations by leveraging configurable cache drivers.
 * Provides methods for CRUD operations on cache and manages cache settings.
 */
class CacheManager
{
	use TypeCheckerTrait;
	use ArrayTrait;

	/**
	 * @var array The cache settings.
	 */
	protected array $cacheSettings = [];

	/**
	 * Constructor to initialize dependencies and properties.
	 *
	 * @param CacheProvider $cacheProvider The cache provider instance.
	 * @param SettingsManager $settingsManager The settings manager instance.
	 * @param object $cacheDriver The cache driver instance.
	 */
	public function __construct(
		protected CacheProvider $cacheProvider,
		protected SettingsManager $settingsManager,
		protected object $cacheDriver
	) {
		$this->wrapInTry(fn() => $this->cacheProvider->registerServices(), "Failed to register cache services.");
		$this->wrapInTry(fn() => $this->initializeCacheDriver(), "Failed to initialize cache driver.");
	}

	/**
	 * Initialize the cache driver based on configuration.
	 *
	 * @throws CacheException If unable to initialize cache driver.
	 */
	protected function initializeCacheDriver(): void
	{
		$this->wrapInTry(
			fn() => $this->loadCacheDriver(),
			"Failed to initialize cache driver."
		);
	}

	/**
	 * Load and validate the cache driver and settings.
	 *
	 * @throws CacheException If driver initialization fails.
	 */
	protected function loadCacheDriver(): void
	{
		$this->cacheSettings = $this->settingsManager->getAllSettings('CACHE');
		$this->cacheDriver = $this->cacheProvider->getCacheDriver($this->cacheSettings)
			?? throw new CacheException("No valid cache driver configured.");
	}

	/**
	 * Set a cache entry with an optional TTL.
	 *
	 * @param string $key The cache key.
	 * @param mixed $data The data to cache.
	 * @param int|null $ttl The time-to-live in seconds (optional).
	 * @return bool True if the operation succeeded, false otherwise.
	 */
	public function set(string $key, mixed $data, ?int $ttl = null): bool
	{
		return $this->wrapInTry(
			fn() => $this->cacheDriver->set(
				$this->validateKey($key),
				$data,
				$ttl ?? $this->cacheSettings['TTL'] ?? 3600
			),
			"Error setting cache for key: $key"
		);
	}

	/**
	 * Retrieve a cache entry by key.
	 *
	 * @param string $key The cache key.
	 * @return mixed The cached data.
	 */
	public function get(string $key): mixed
	{
		return $this->wrapInTry(
			fn() => $this->cacheDriver->get($this->validateKey($key)),
			"Error retrieving cache for key: $key"
		);
	}

	/**
	 * Delete a cache entry by key.
	 *
	 * @param string $key The cache key.
	 * @return bool True if the operation succeeded, false otherwise.
	 */
	public function delete(string $key): bool
	{
		return $this->wrapInTry(
			fn() => $this->cacheDriver->delete($this->validateKey($key)),
			"Error deleting cache for key: $key"
		);
	}

	/**
	 * Clear all cache entries.
	 *
	 * @return bool True if the operation succeeded, false otherwise.
	 */
	public function clear(): bool
	{
		return $this->wrapInTry(
			fn() => $this->cacheDriver->clear(),
			"Error clearing cache."
		);
	}

	/**
	 * Set multiple cache entries.
	 *
	 * @param array $items The key-value pairs to set in the cache.
	 * @param int|null $ttl The time-to-live in seconds (optional).
	 * @return bool True if all operations succeeded, false otherwise.
	 */
	public function setMultiple(array $items, ?int $ttl = null): bool
	{
		return $this->wrapInTry(
			fn() => $this->all(
				$items,
				fn($value, $key) => $this->set($this->validateKey($key), $value, $ttl)
			),
			"Error setting multiple cache entries."
		);
	}

	/**
	 * Retrieve multiple cache entries by keys.
	 *
	 * @param array $keys The cache keys to retrieve.
	 * @return array The key-value pairs of the retrieved cache data.
	 */
	public function getMultiple(array $keys): array
	{
		return $this->wrapInTry(
			fn() => $this->reduce(
				$keys,
				fn($carry, $key) => $this->merge($carry, [$key => $this->get($this->validateKey($key))]),
				[]
			),
			"Error retrieving multiple cache entries."
		);
	}

	/**
	 * Delete multiple cache entries by keys.
	 *
	 * @param array $keys The cache keys to delete.
	 * @return bool True if all operations succeeded, false otherwise.
	 */
	public function deleteMultiple(array $keys): bool
	{
		return $this->wrapInTry(
			fn() => $this->all($keys, fn($key) => $this->delete($this->validateKey($key))),
			"Error deleting multiple cache entries."
		);
	}

	/**
	 * Update the cache driver based on new or updated settings.
	 *
	 * @param array $newSettings The new settings to apply.
	 */
	public function updateCacheDriver(array $newSettings = []): void
	{
		$this->wrapInTry(
			fn() => $this->reloadCacheDriver($newSettings),
			"Error updating cache driver."
		);
	}

	/**
	 * Reload cache driver with new settings.
	 *
	 * @param array $newSettings The new settings to apply.
	 */
	protected function reloadCacheDriver(array $newSettings): void
	{
		$this->cacheSettings = $this->merge($this->cacheSettings, $newSettings);
		$this->initializeCacheDriver();
	}

	/**
	 * Validate cache key.
	 *
	 * @param string $key The cache key to validate.
	 * @return string The validated key.
	 * @throws CacheException If the key is invalid.
	 */
	protected function validateKey(string $key): string
	{
		return $this->wrapInTry(
			fn() => $this->isString($key) && !$this->isEmpty($key)
				? $key
				: throw new CacheException("Invalid cache key: $key"),
			"Error validating cache key: $key"
		);
	}

	/**
	 * Wrapper for consistent error handling.
	 *
	 * @param callable $callback The callback to execute.
	 * @param string $errorMessage The error message to use in the exception.
	 * @return mixed The result of the callback.
	 * @throws CacheException
	 */
	protected function wrapInTry(callable $callback, string $errorMessage): mixed
	{
		try {
			return $callback();
		} catch (Throwable $e) {
			throw new CacheException($errorMessage, 0, $e);
		}
	}
}
