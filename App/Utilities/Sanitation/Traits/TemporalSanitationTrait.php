<?php

namespace App\Utilities\Sanitation\Traits;

use DateTime;
use DateTimeZone;
use Exception;

trait TemporalSanitationTrait
{
	use BaseSanitationTrait;

	/**
	 * Convert dates and times to a standard format (ISO 8601).
	 *
	 * @param string $datetime
	 * @param string $format
	 * @return string
	 * @throws Exception
	 */
	public function sanitizeDateTime(string $datetime, string $format = 'c'): string
	{
		$date = new DateTime($datetime);
		return $date->format($format);
	}

	/**
	 * Adjust dates and times to a specific timezone.
	 *
	 * @param string $datetime
	 * @param string $timezone
	 * @return string
	 * @throws Exception
	 */
	public function adjustTimezone(string $datetime, string $timezone): string
	{
		$date = new DateTime($datetime, new DateTimeZone($timezone));
		return $date->format('c');
	}

	/**
	 * Normalize date/time inputs to ensure consistency.
	 *
	 * @param string $datetime
	 * @param string $format
	 * @return string
	 * @throws Exception
	 */
	public function normalizeDateTime(string $datetime, string $format = 'Y-m-d H:i:s'): string
	{
		$date = new DateTime($datetime);
		return $date->format($format);
	}

	/**
	 * Adjust datetime to the nearest business day.
	 *
	 * @param string $datetime
	 * @return string
	 * @throws Exception
	 */
	public function adjustToBusinessDay(string $datetime): string
	{
		$date = new DateTime($datetime);
		if ($date->format('N') >= 6) { // Saturday or Sunday
			$date->modify('next Monday');
		}
		return $date->format('Y-m-d');
	}
}
