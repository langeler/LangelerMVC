<?php

namespace App\Utilities\Validation\Traits;

trait LogValidationTrait
{
	use BaseValidationTrait;

	/**
	 * Validate that a log entry is within the retention period.
	 *
	 * @param int $logTimestamp
	 * @param int $retentionPeriod
	 * @return bool
	 */
	public function validateLogRetention(int $logTimestamp, int $retentionPeriod): bool
	{
		return $this->validateRange(time() - $logTimestamp, 0, $retentionPeriod);
	}

	/**
	 * Validate that a log entry is consistent (e.g., well-formed JSON).
	 *
	 * @param string $logEntry
	 * @return bool
	 */
	public function validateLogConsistency(string $logEntry): bool
	{
		return $this->validateJson($logEntry);
	}
}
