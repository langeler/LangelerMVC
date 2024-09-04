<?php

namespace App\Utilities\Handlers;

use DateTime;
use DateTimeZone;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;

/**
 * Class DateTimeHandler
 *
 * Provides utility methods for working with date and time using DateTime, DateTimeImmutable, DateTimeZone, DateInterval, DatePeriod, and other related functions.
 */
class DateTimeHandler
{
	// DateTime Methods

	/**
	 * Create a DateTime instance from a string.
	 *
	 * @param string $time The time string.
	 * @param DateTimeZone|null $timezone The timezone.
	 * @return DateTime The DateTime instance.
	 */
	public function createDateTime(string $time = "now", ?DateTimeZone $timezone = null): DateTime
	{
		return new DateTime($time, $timezone);
	}

	/**
	 * Format a DateTime object.
	 *
	 * @param DateTime $dateTime The DateTime instance.
	 * @param string $format The date format.
	 * @return string The formatted date string.
	 */
	public function formatDateTime(DateTime $dateTime, string $format): string
	{
		return $dateTime->format($format);
	}

	/**
	 * Set the timestamp for a DateTime instance.
	 *
	 * @param DateTime $datetime The DateTime instance.
	 * @param int $timestamp The timestamp value.
	 * @return DateTime The modified DateTime instance.
	 */
	public function setDateTimeTimestamp(DateTime $datetime, int $timestamp): DateTime
	{
		return $datetime->setTimestamp($timestamp);
	}

	/**
	 * Get the timestamp of a DateTime instance.
	 *
	 * @param DateTime $datetime The DateTime instance.
	 * @return int The timestamp.
	 */
	public function getDateTimeTimestamp(DateTime $datetime): int
	{
		return $datetime->getTimestamp();
	}

	// DateTimeZone Methods

	/**
	 * Create a DateTimeZone instance from a timezone string.
	 *
	 * @param string $timezone The timezone string.
	 * @return DateTimeZone The DateTimeZone instance.
	 */
	public function createDateTimeZone(string $timezone): DateTimeZone
	{
		return new DateTimeZone($timezone);
	}

	/**
	 * Get the name of the timezone.
	 *
	 * @param DateTimeZone $timezone The DateTimeZone instance.
	 * @return string The name of the timezone.
	 */
	public function getTimeZoneName(DateTimeZone $timezone): string
	{
		return $timezone->getName();
	}

	/**
	 * Get the offset of a timezone relative to a DateTime instance.
	 *
	 * @param DateTimeZone $timezone The DateTimeZone instance.
	 * @param DateTime $datetime The DateTime instance.
	 * @return int The timezone offset in seconds.
	 */
	public function getTimezoneOffset(DateTimeZone $timezone, DateTime $datetime): int
	{
		return $timezone->getOffset($datetime);
	}

	/**
	 * Get the transitions for a timezone between two timestamps.
	 *
	 * @param DateTimeZone $timezone The DateTimeZone instance.
	 * @param int|null $timestampBegin The beginning timestamp.
	 * @param int|null $timestampEnd The ending timestamp.
	 * @return array The array of transitions.
	 */
	public function getTimezoneTransitions(DateTimeZone $timezone, ?int $timestampBegin = null, ?int $timestampEnd = null): array
	{
		return $timezone->getTransitions($timestampBegin, $timestampEnd);
	}

	/**
	 * Get the timezone name from an abbreviation.
	 *
	 * @param string $abbr The timezone abbreviation.
	 * @param int $gmtOffset The GMT offset in seconds.
	 * @param int $isdst Whether daylight saving time is in effect.
	 * @return string|false The timezone name or false on failure.
	 */
	public function getTimezoneNameFromAbbr(string $abbr, int $gmtOffset = -1, int $isdst = -1)
	{
		return timezone_name_from_abbr($abbr, $gmtOffset, $isdst);
	}

	// DateInterval Methods

	/**
	 * Create a DateInterval instance from a date string.
	 *
	 * @param string $interval The interval string (e.g., "P1D" for 1 day).
	 * @return DateInterval The DateInterval instance.
	 */
	public function createDateInterval(string $interval): DateInterval
	{
		return new DateInterval($interval);
	}

	/**
	 * Format a DateInterval.
	 *
	 * @param DateInterval $interval The DateInterval instance.
	 * @param string $format The interval format.
	 * @return string The formatted interval string.
	 */
	public function formatDateInterval(DateInterval $interval, string $format): string
	{
		return $interval->format($format);
	}

	// DatePeriod Methods

	/**
	 * Create a DatePeriod instance for recurring dates.
	 *
	 * @param DateTime $start The start date.
	 * @param DateInterval $interval The interval between periods.
	 * @param int $recurrences The number of recurrences.
	 * @return DatePeriod The DatePeriod instance.
	 */
	public function createDatePeriod(DateTime $start, DateInterval $interval, int $recurrences): DatePeriod
	{
		return new DatePeriod($start, $interval, $recurrences);
	}

	/**
	 * Get all dates from a DatePeriod.
	 *
	 * @param DatePeriod $period The DatePeriod instance.
	 * @return array The array of DateTime instances.
	 */
	public function getDatesFromPeriod(DatePeriod $period): array
	{
		return iterator_to_array($period);
	}

	// DateTimeImmutable Methods

	/**
	 * Create a DateTimeImmutable instance from a format string.
	 *
	 * @param string $format The date format.
	 * @param string $time The time string.
	 * @param DateTimeZone|null $timezone The timezone.
	 * @return DateTimeImmutable The DateTimeImmutable instance.
	 */
	public function createImmutableFromFormat(string $format, string $time, ?DateTimeZone $timezone = null): DateTimeImmutable
	{
		return DateTimeImmutable::createFromFormat($format, $time, $timezone);
	}

	/**
	 * Create a DateTimeImmutable instance from a DateTime object.
	 *
	 * @param DateTime $datetime The DateTime instance.
	 * @return DateTimeImmutable The DateTimeImmutable instance.
	 */
	public function createImmutableFromMutable(DateTime $datetime): DateTimeImmutable
	{
		return DateTimeImmutable::createFromMutable($datetime);
	}

	/**
	 * Add a DateInterval to a DateTimeImmutable object.
	 *
	 * @param DateTimeImmutable $datetime The DateTimeImmutable instance.
	 * @param DateInterval $interval The DateInterval instance.
	 * @return DateTimeImmutable The modified DateTimeImmutable instance.
	 */
	public function addIntervalToImmutable(DateTimeImmutable $datetime, DateInterval $interval): DateTimeImmutable
	{
		return $datetime->add($interval);
	}

	/**
	 * Subtract a DateInterval from a DateTimeImmutable object.
	 *
	 * @param DateTimeImmutable $datetime The DateTimeImmutable instance.
	 * @param DateInterval $interval The DateInterval instance.
	 * @return DateTimeImmutable The modified DateTimeImmutable instance.
	 */
	public function subtractIntervalFromImmutable(DateTimeImmutable $datetime, DateInterval $interval): DateTimeImmutable
	{
		return $datetime->sub($interval);
	}

	/**
	 * Modify a DateTimeImmutable object.
	 *
	 * @param DateTimeImmutable $datetime The DateTimeImmutable instance.
	 * @param string $modifier The modification string.
	 * @return DateTimeImmutable The modified DateTimeImmutable instance.
	 */
	public function modifyImmutable(DateTimeImmutable $datetime, string $modifier): DateTimeImmutable
	{
		return $datetime->modify($modifier);
	}

	/**
	 * Set the date of a DateTimeImmutable object.
	 *
	 * @param DateTimeImmutable $datetime The DateTimeImmutable instance.
	 * @param int $year The year value.
	 * @param int $month The month value.
	 * @param int $day The day value.
	 * @return DateTimeImmutable The modified DateTimeImmutable instance.
	 */
	public function setImmutableDate(DateTimeImmutable $datetime, int $year, int $month, int $day): DateTimeImmutable
	{
		return $datetime->setDate($year, $month, $day);
	}

	/**
	 * Set the ISO date of a DateTimeImmutable object.
	 *
	 * @param DateTimeImmutable $datetime The DateTimeImmutable instance.
	 * @param int $year The year value.
	 * @param int $week The week value.
	 * @param int $dayOfWeek The day of the week value.
	 * @return DateTimeImmutable The modified DateTimeImmutable instance.
	 */
	public function setImmutableISODate(DateTimeImmutable $datetime, int $year, int $week, int $dayOfWeek = 1): DateTimeImmutable
	{
		return $datetime->setISODate($year, $week, $dayOfWeek);
	}

	/**
	 * Set the time of a DateTimeImmutable object.
	 *
	 * @param DateTimeImmutable $datetime The DateTimeImmutable instance.
	 * @param int $hour The hour value.
	 * @param int $minute The minute value.
	 * @param int $second The second value.
	 * @param int $microsecond The microsecond value.
	 * @return DateTimeImmutable The modified DateTimeImmutable instance.
	 */
	public function setImmutableTime(DateTimeImmutable $datetime, int $hour, int $minute, int $second = 0, int $microsecond = 0): DateTimeImmutable
	{
		return $datetime->setTime($hour, $minute, $second, $microsecond);
	}

	/**
	 * Set the timestamp of a DateTimeImmutable object.
	 *
	 * @param DateTimeImmutable $datetime The DateTimeImmutable instance.
	 * @param int $timestamp The timestamp value.
	 * @return DateTimeImmutable The modified DateTimeImmutable instance.
	 */
	public function setImmutableTimestamp(DateTimeImmutable $datetime, int $timestamp): DateTimeImmutable
	{
		return $datetime->setTimestamp($timestamp);
	}

	/**
	 * Get the timestamp of a DateTimeImmutable object.
	 *
	 * @param DateTimeImmutable $datetime The DateTimeImmutable instance.
	 * @return int The timestamp.
	 */
	public function getImmutableTimestamp(DateTimeImmutable $datetime): int
	{
		return $datetime->getTimestamp();
	}

	/**
	 * Get the timezone of a DateTimeImmutable object.
	 *
	 * @param DateTimeImmutable $datetime The DateTimeImmutable instance.
	 * @return DateTimeZone The DateTimeZone instance.
	 */
	public function getImmutableTimezone(DateTimeImmutable $datetime): DateTimeZone
	{
		return $datetime->getTimezone();
	}

	/**
	 * Format a DateTimeImmutable object.
	 *
	 * @param DateTimeImmutable $datetime The DateTimeImmutable instance.
	 * @param string $format The date format.
	 * @return string The formatted date string.
	 */
	public function formatImmutable(DateTimeImmutable $datetime, string $format): string
	{
		return $datetime->format($format);
	}

	// Timestamp Handling Methods

	/**
	 * Get a Unix timestamp for a specific date and time.
	 *
	 * @param int|null $hour The hour.
	 * @param int|null $minute The minute.
	 * @param int|null $second The second.
	 * @param int|null $month The month.
	 * @param int|null $day The day.
	 * @param int|null $year The year.
	 * @return int The Unix timestamp.
	 */
	public function getUnixTimestamp(?int $hour = null, ?int $minute = null, ?int $second = null, ?int $month = null, ?int $day = null, ?int $year = null): int
	{
		return mktime($hour, $minute, $second, $month, $day, $year);
	}

	// DateInfo Methods

	/**
	 * Get date information for a given timestamp.
	 *
	 * @param int|null $timestamp The timestamp.
	 * @return array The date information.
	 */
	public function getDateInfo(?int $timestamp = null): array
	{
		return getdate($timestamp);
	}

	/**
	 * Check if a date is valid.
	 *
	 * @param int $month The month value.
	 * @param int $day The day value.
	 * @param int $year The year value.
	 * @return bool True if the date is valid, false otherwise.
	 */
	public function isValidDate(int $month, int $day, int $year): bool
	{
		return checkdate($month, $day, $year);
	}

	/**
	 * Parse a date and time string.
	 *
	 * @param string $time The time string.
	 * @param int|null $now The current timestamp.
	 * @return int The parsed timestamp.
	 */
	public function parseDateTime(string $time, ?int $now = null): int
	{
		return strtotime($time, $now);
	}

	/**
	 * Get the current timestamp.
	 *
	 * @return int The current timestamp.
	 */
	public function getCurrentTimestamp(): int
	{
		return time();
	}
}
