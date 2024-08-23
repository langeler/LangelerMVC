<?php

namespace App\Utilities\Sanitation\Traits;

trait StateSanitationTrait
{
	use BaseSanitationTrait;

	/**
	 * Remove stale session data.
	 *
	 * @param array $sessionData
	 * @return array
	 */
	public function sanitizeSessionData(array $sessionData): array
	{
		return array_filter($sessionData, function ($value) {
			return !is_null($value);
		});
	}

	/**
	 * Sanitize cache data, removing expired entries.
	 *
	 * @param array $cacheData
	 * @param int $expirationTime
	 * @return array
	 */
	public function sanitizeCacheData(array $cacheData, int $expirationTime): array
	{
		return array_filter($cacheData, function ($item) use ($expirationTime) {
			return $item['timestamp'] > time() - $expirationTime;
		});
	}

	/**
	 * Normalize and secure persistent state data.
	 *
	 * @param array $stateData
	 * @return array
	 */
	public function sanitizePersistentStateData(array $stateData): array
	{
		return $this->sanitizeNestedData($stateData);
	}

	/**
	 * Securely manage ephemeral data.
	 *
	 * @param array $ephemeralData
	 * @return array
	 */
	public function sanitizeEphemeralData(array $ephemeralData): array
	{
		return array_map('trim', $ephemeralData);
	}

	/**
	 * Sanitize data for synchronization across distributed systems.
	 *
	 * @param array $stateData
	 * @return array
	 */
	public function sanitizeSynchronizedStateData(array $stateData): array
	{
		return $this->sanitizePersistentStateData($stateData);
	}
}
