<?php

namespace App\Utilities\Managers;

use App\Providers\CacheProvider;
use App\Utilities\Managers\SettingsManager;
use App\Exceptions\Data\CacheException;
use App\Utilities\Traits\TypeCheckerTrait;

/**
 * Manages caching operations by leveraging configurable cache drivers.
 * Provides methods for CRUD operations on cache and manages cache settings.
 */
class CacheManager
{
	use TypeCheckerTrait;

	/**
	 * Constructor to initialize dependencies and properties.
	 */
	public function __construct(
		protected CacheProvider $cacheProvider,
		protected SettingsManager $settingsManager,
		protected object $cacheDriver,
		protected array $cacheSettings = []
	) {
		$this->cacheProvider->registerServices();
		$this->initializeCacheDriver();
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
		$this->cacheDriver = $this->cacheProvider->getCacheDriver($this->cacheSettings);

		if (!$this->cacheDriver) {
			throw new CacheException("No valid cache driver configured.");
		}
	}

	/**
	 * Set a cache entry with an optional TTL.
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
	 */
	public function setMultiple(array $items, ?int $ttl = null): bool
	{
		return $this->wrapInTry(
			fn() => array_walk(
				$items,
				fn($value, $key) => $this->set($this->validateKey($key), $value, $ttl)
			),
			"Error setting multiple cache entries."
		);
	}

	/**
	 * Retrieve multiple cache entries by keys.
	 */
	public function getMultiple(array $keys): array
	{
		return $this->wrapInTry(
			fn() => array_combine(
				$keys,
				array_map(fn($key) => $this->get($this->validateKey($key)), $keys)
			),
			"Error retrieving multiple cache entries."
		);
	}

	/**
	 * Delete multiple cache entries by keys.
	 */
	public function deleteMultiple(array $keys): bool
	{
		return $this->wrapInTry(
			fn() => array_walk(
				$keys,
				fn($key) => $this->delete($this->validateKey($key))
			),
			"Error deleting multiple cache entries."
		);
	}

	/**
	 * Update the cache driver based on new or updated settings.
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
	 */
	protected function reloadCacheDriver(array $newSettings): void
	{
		$this->cacheSettings = array_merge($this->cacheSettings, $newSettings);
		$this->initializeCacheDriver();
	}

	/**
	 * Validate cache key.
	 */
	protected function validateKey(string $key): string
	{
		return $this->wrapInTry(
			fn() => $this->isString($key) && $key !== ''
				? $key
				: throw new CacheException("Invalid cache key: $key"),
			"Error validating cache key: $key"
		);
	}

	/**
	 * Wrapper for consistent error handling.
	 */
	protected function wrapInTry(callable $callback, string $errorMessage): mixed
	{
		try {
			return $callback();
		} catch (\Throwable $e) {
			throw new CacheException($errorMessage, 0, $e);
		}
	}
}
