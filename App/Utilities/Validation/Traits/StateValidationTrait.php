<?php

namespace App\Utilities\Validation\Traits;

trait StateValidationTrait
{
	use BaseValidationTrait;

	/**
	 * Validate that a session has not expired.
	 *
	 * @param int $sessionStart
	 * @param int $maxDuration
	 * @return bool
	 */
	public function validateSessionExpiration(int $sessionStart, int $maxDuration): bool
	{
		return (time() - $sessionStart) <= $maxDuration;
	}

	/**
	 * Validate that session data contains all required keys.
	 *
	 * @param array $sessionData
	 * @param array $requiredKeys
	 * @return bool
	 */
	public function validateSessionConsistency(array $sessionData, array $requiredKeys): bool
	{
		foreach ($requiredKeys as $key) {
			if (!isset($sessionData[$key])) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Validate that cache data is still valid based on its timestamp.
	 *
	 * @param int $cacheTimestamp
	 * @param int $expirationTime
	 * @return bool
	 */
	public function validateCacheValidity(int $cacheTimestamp, int $expirationTime): bool
	{
		return $this->validateRange(time() - $cacheTimestamp, 0, $expirationTime);
	}
}
