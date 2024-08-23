<?php

namespace App\Utilities\Validation\Traits;

use DateTime;
use Exception;

trait TemporalValidationTrait
{
	use BaseValidationTrait;

	/**
	 * Validate that a date falls within a specified range.
	 *
	 * @param string $date
	 * @param string $minDate
	 * @param string $maxDate
	 * @return bool
	 * @throws Exception
	 */
	public function validateDateRange(string $date, string $minDate, string $maxDate): bool
	{
		$dateObj = new DateTime($date);
		$minDateObj = new DateTime($minDate);
		$maxDateObj = new DateTime($maxDate);

		return $dateObj >= $minDateObj && $dateObj <= $maxDateObj;
	}

	/**
	 * Validate that a time falls within business hours.
	 *
	 * @param string $time
	 * @param string $start
	 * @param string $end
	 * @return bool
	 * @throws Exception
	 */
	public function validateBusinessHours(string $time, string $start = '09:00', string $end = '17:00'): bool
	{
		$timeObj = new DateTime($time);
		$startTimeObj = new DateTime($start);
		$endTimeObj = new DateTime($end);

		return $timeObj >= $startTimeObj && $timeObj <= $endTimeObj;
	}

	/**
	 * Validate that a date is a weekend.
	 *
	 * @param string $date
	 * @return bool
	 * @throws Exception
	 */
	public function validateWeekend(string $date): bool
	{
		$dateObj = new DateTime($date);
		$dayOfWeek = $dateObj->format('N'); // 1 (Monday) to 7 (Sunday)

		return $dayOfWeek >= 6;
	}

	/**
	 * Validate that the age based on a birthdate meets the minimum requirement.
	 *
	 * @param string $birthdate
	 * @param int $minAge
	 * @return bool
	 * @throws Exception
	 */
	public function validateMinimumAge(string $
birthdate, int $minAge): bool
	{
	$birthdateObj = new DateTime($birthdate);
	$today = new DateTime(‘today’);
	$age = $today->diff($birthdateObj)->y;

		return $age >= $minAge;
	}
}
