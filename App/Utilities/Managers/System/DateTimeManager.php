<?php

namespace App\Utilities\Managers;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use DateInterval;
use DatePeriod;
use App\Utilities\Traits\DateTimeTrait;

/**
 * DateTimeManager provides utilities for managing PHP DateTime-related classes.
 * It includes handling for constants, methods for creating and manipulating
 * DateTime, DateTimeImmutable, DateTimeZone, DateInterval, and DatePeriod instances.
 * Additionally, it integrates common date/time-related utilities through the DateTimeTrait.
 */
class DateTimeManager
{
	use DateTimeTrait; // Integrates utility methods for direct PHP native date/time functions.

	/**
	 * Read-only property containing all constants from imported classes.
	 */
	public readonly array $classConstants;

	/**
	 * Initializes the DateTimeManager classConstants with constants grouped by class.
	 */

	public function __construct()
	{
		$this->classConstants = [
			'datetime' => [
				'atom'              => DateTime::ATOM,               // Atom format: "Y-m-d\TH:i:sP"
				'cookie'            => DateTime::COOKIE,             // Cookie format: "l, d-M-Y H:i:s T"
				'iso8601'           => DateTime::ISO8601,           // ISO-8601 format (legacy): "Y-m-d\TH:i:sO"
				'iso8601Expanded'   => DateTime::ISO8601_EXPANDED,  // ISO-8601 expanded: "X-m-d\TH:i:sP"
				'rfc822'            => DateTime::RFC822,            // RFC 822: "D, d M y H:i:s O"
				'rfc850'            => DateTime::RFC850,            // RFC 850: "l, d-M-y H:i:s T"
				'rfc1036'           => DateTime::RFC1036,           // RFC 1036: "D, d M y H:i:s O"
				'rfc1123'           => DateTime::RFC1123,           // RFC 1123: "D, d M Y H:i:s O"
				'rfc7231'           => DateTime::RFC7231,           // RFC 7231 (HTTP): "D, d M Y H:i:s \G\M\T"
				'rfc2822'           => DateTime::RFC2822,           // RFC 2822: "D, d M Y H:i:s O"
				'rfc3339'           => DateTime::RFC3339,           // RFC 3339: "Y-m-d\TH:i:sP"
				'rfc3339Extended'   => DateTime::RFC3339_EXTENDED,  // RFC 3339 extended: "Y-m-d\TH:i:s.vP"
				'rss'               => DateTime::RSS,               // RSS format: "D, d M Y H:i:s O"
				'w3c'               => DateTime::W3C,               // W3C format: "Y-m-d\TH:i:sP"
			],
			'immutable' => [
				'atom'              => DateTimeImmutable::ATOM,     // Atom format: "Y-m-d\TH:i:sP"
				'cookie'            => DateTimeImmutable::COOKIE,   // Cookie format: "l, d-M-Y H:i:s T"
				'iso8601'           => DateTimeImmutable::ISO8601, // ISO-8601 format (legacy): "Y-m-d\TH:i:sO"
				'iso8601Expanded'   => DateTimeImmutable::ISO8601_EXPANDED, // ISO-8601 expanded: "X-m-d\TH:i:sP"
				'rfc822'            => DateTimeImmutable::RFC822,   // RFC 822: "D, d M y H:i:s O"
				'rfc850'            => DateTimeImmutable::RFC850,   // RFC 850: "l, d-M-y H:i:s T"
				'rfc1036'           => DateTimeImmutable::RFC1036,  // RFC 1036: "D, d M y H:i:s O"
				'rfc1123'           => DateTimeImmutable::RFC1123,  // RFC 1123: "D, d M Y H:i:s O"
				'rfc7231'           => DateTimeImmutable::RFC7231,  // RFC 7231 (HTTP): "D, d M Y H:i:s \G\M\T"
				'rfc2822'           => DateTimeImmutable::RFC2822,  // RFC 2822: "D, d M Y H:i:s O"
				'rfc3339'           => DateTimeImmutable::RFC3339,  // RFC 3339: "Y-m-d\TH:i:sP"
				'rfc3339Extended'   => DateTimeImmutable::RFC3339_EXTENDED, // RFC 3339 extended: "Y-m-d\TH:i:s.vP"
				'rss'               => DateTimeImmutable::RSS,      // RSS format: "D, d M Y H:i:s O"
				'w3c'               => DateTimeImmutable::W3C,      // W3C format: "Y-m-d\TH:i:sP"
			],
			'timezone' => [
				'africa'            => DateTimeZone::AFRICA,        // Africa region timezones.
				'america'           => DateTimeZone::AMERICA,       // Americas region timezones.
				'antarctica'        => DateTimeZone::ANTARCTICA,    // Antarctica region timezones.
				'arctic'            => DateTimeZone::ARCTIC,        // Arctic region timezones.
				'asia'              => DateTimeZone::ASIA,          // Asia region timezones.
				'atlantic'          => DateTimeZone::ATLANTIC,      // Atlantic region timezones.
				'australia'         => DateTimeZone::AUSTRALIA,     // Australia region timezones.
				'europe'            => DateTimeZone::EUROPE,        // Europe region timezones.
				'indian'            => DateTimeZone::INDIAN,        // Indian Ocean region timezones.
				'pacific'           => DateTimeZone::PACIFIC,       // Pacific region timezones.
				'utc'               => DateTimeZone::UTC,           // UTC timezone.
				'all'               => DateTimeZone::ALL,           // All available timezones.
				'allWithBc'         => DateTimeZone::ALL_WITH_BC,   // All timezones including those before the Common Era.
				'perCountry'        => DateTimeZone::PER_COUNTRY,   // Timezones specific to countries.
			],
			'period' => [
				'excludeStartDate'  => DatePeriod::EXCLUDE_START_DATE, // Excludes the start date from the period.
				'includeEndDate'    => DatePeriod::INCLUDE_END_DATE,   // Includes the end date in the period.
			],
		];
	}

	/**
	 * Handle errors consistently with a try/catch block.
	 */
	public function wrapInTry(callable $callback, ...$args)
	{
		try {
			return $callback(...$args);
		} catch (\Exception $e) {
			// Log or handle errors here if needed
			throw new \Exception("An error occurred: " . $e->getMessage());
		}
	}

	// ------------------------------------------------------------------
	//                          DateTime Methods
	// ------------------------------------------------------------------

	/**
	 * Creates a new DateTime instance.
	 *
	 * @param string $datetime   The date and time string (default: 'now').
	 * @param DateTimeZone|null $timezone Optional timezone.
	 * @return DateTime          The created DateTime instance.
	 * @throws \Exception        If the DateTime object creation fails.
	 */
	public function createDateTime(string $datetime = 'now', ?DateTimeZone $timezone = null): DateTime
	{
		return $this->wrapInTry(fn() => new DateTime($datetime, $timezone));
	}

	/**
	 * Adds an interval to a DateTime instance.
	 *
	 * @param DateTime $dateTime The DateTime instance to modify.
	 * @param DateInterval $interval The interval to add.
	 * @return DateTime          The modified DateTime instance.
	 * @throws \Exception        If the interval addition fails.
	 */
	public function addInterval(DateTime $dateTime, DateInterval $interval): DateTime
	{
		return $this->wrapInTry(fn() => $dateTime->add($interval));
	}

	/**
	 * Creates a DateTime instance from a DateTimeImmutable object.
	 *
	 * @param DateTimeImmutable $immutable The immutable DateTime object.
	 * @return DateTime          The created DateTime instance.
	 * @throws \Exception        If conversion fails.
	 */
	public function fromImmutable(DateTimeImmutable $immutable): DateTime
	{
		return $this->wrapInTry(fn() => DateTime::createFromImmutable($immutable));
	}

	/**
	 * Creates a DateTime instance from a DateTimeInterface object.
	 *
	 * @param DateTimeInterface $interface The interface-based DateTime object.
	 * @return DateTime          The created DateTime instance.
	 * @throws \Exception        If conversion fails.
	 */
	public function fromInterface(DateTimeInterface $interface): DateTime
	{
		return $this->wrapInTry(fn() => DateTime::createFromInterface($interface));
	}

	/**
	 * Creates a DateTime instance from a formatted date string.
	 *
	 * @param string $format     The format of the input date string.
	 * @param string $datetime   The date string.
	 * @param DateTimeZone|null $timezone Optional timezone.
	 * @return DateTime|false    The created DateTime instance or false on failure.
	 */
	public function createFromFormat(string $format, string $datetime, ?DateTimeZone $timezone = null): DateTime|false
	{
		return $this->wrapInTry(fn() => DateTime::createFromFormat($format, $datetime, $timezone));
	}

	/**
	 * Modifies the DateTime object using a relative date/time string.
	 *
	 * @param DateTime $dateTime The DateTime instance to modify.
	 * @param string $modifier   The relative date/time string.
	 * @return DateTime          The modified DateTime instance.
	 * @throws \Exception        If modification fails.
	 */
	public function modifyDateTime(DateTime $dateTime, string $modifier): DateTime
	{
		return $this->wrapInTry(fn() => $dateTime->modify($modifier));
	}

	/**
	 * Sets the state of a DateTime instance from an array.
	 *
	 * @param array $array       The state array.
	 * @return DateTime          The reconstructed DateTime instance.
	 * @throws \Exception        If state restoration fails.
	 */
	public static function setState(array $array): DateTime
	{
		return self::wrapInTry(fn() => DateTime::__set_state($array));
	}

	/**
	 * Sets the date (year, month, day) for a DateTime instance.
	 *
	 * @param DateTime $dateTime The DateTime instance to modify.
	 * @param int $year          The year to set.
	 * @param int $month         The month to set.
	 * @param int $day           The day to set.
	 * @return DateTime          The modified DateTime instance.
	 * @throws \Exception        If the date cannot be set.
	 */
	public function setDate(DateTime $dateTime, int $year, int $month, int $day): DateTime
	{
		return $this->wrapInTry(fn() => $dateTime->setDate($year, $month, $day));
	}

	/**
	 * Sets the ISO-8601 week-based date for a DateTime instance.
	 *
	 * @param DateTime $dateTime The DateTime instance to modify.
	 * @param int $year          The ISO year to set.
	 * @param int $week          The ISO week to set.
	 * @param int $dayOfWeek     The ISO day of the week (default: 1).
	 * @return DateTime          The modified DateTime instance.
	 * @throws \Exception        If the ISO date cannot be set.
	 */
	public function setISODate(DateTime $dateTime, int $year, int $week, int $dayOfWeek = 1): DateTime
	{
		return $this->wrapInTry(fn() => $dateTime->setISODate($year, $week, $dayOfWeek));
	}

	/**
	 * Sets the time (hour, minute, second, microsecond) for a DateTime instance.
	 *
	 * @param DateTime $dateTime The DateTime instance to modify.
	 * @param int $hour          The hour to set.
	 * @param int $minute        The minute to set.
	 * @param int $second        The second to set (default: 0).
	 * @param int $microsecond   The microsecond to set (default: 0).
	 * @return DateTime          The modified DateTime instance.
	 * @throws \Exception        If the time cannot be set.
	 */
	public function setTime(DateTime $dateTime, int $hour, int $minute, int $second = 0, int $microsecond = 0): DateTime
	{
		return $this->wrapInTry(fn() => $dateTime->setTime($hour, $minute, $second, $microsecond));
	}

	/**
	 * Sets the timestamp for a DateTime instance.
	 *
	 * @param DateTime $dateTime The DateTime instance to modify.
	 * @param int $timestamp     The Unix timestamp to set.
	 * @return DateTime          The modified DateTime instance.
	 * @throws \Exception        If the timestamp cannot be set.
	 */
	public function setTimestamp(DateTime $dateTime, int $timestamp): DateTime
	{
		return $this->wrapInTry(fn() => $dateTime->setTimestamp($timestamp));
	}

	/**
	 * Sets the timezone for a DateTime instance.
	 *
	 * @param DateTime $dateTime The DateTime instance to modify.
	 * @param DateTimeZone $timezone The timezone to set.
	 * @return DateTime          The modified DateTime instance.
	 * @throws \Exception        If the timezone cannot be set.
	 */
	public function setTimezone(DateTime $dateTime, DateTimeZone $timezone): DateTime
	{
		return $this->wrapInTry(fn() => $dateTime->setTimezone($timezone));
	}

	/**
	 * Subtracts an interval from a DateTime instance.
	 *
	 * @param DateTime $dateTime The DateTime instance to modify.
	 * @param DateInterval $interval The interval to subtract.
	 * @return DateTime          The modified DateTime instance.
	 * @throws \Exception        If the interval subtraction fails.
	 */
	public function subInterval(DateTime $dateTime, DateInterval $interval): DateTime
	{
		return $this->wrapInTry(fn() => $dateTime->sub($interval));
	}

	/**
	 * Calculates the difference between two DateTime instances.
	 *
	 * @param DateTimeInterface $date1 The starting DateTime instance.
	 * @param DateTimeInterface $date2 The ending DateTime instance.
	 * @param bool $absolute       Whether to return the absolute difference.
	 * @return DateInterval        The interval representing the difference.
	 * @throws \Exception          If the difference cannot be calculated.
	 */
	public function diffDates(DateTimeInterface $date1, DateTimeInterface $date2, bool $absolute = false): DateInterval
	{
		return $this->wrapInTry(fn() => $date1->diff($date2, $absolute));
	}

	/**
	 * Formats a DateTime instance to a string.
	 *
	 * @param DateTime $dateTime The DateTime instance to format.
	 * @param string $format     The format string.
	 * @return string            The formatted date string.
	 * @throws \Exception        If formatting fails.
	 */
	public function formatDateTime(DateTime $dateTime, string $format): string
	{
		return $this->wrapInTry(fn() => $dateTime->format($format));
	}

	/**
	 * Gets the timezone offset of a DateTime instance.
	 *
	 * @param DateTime $dateTime The DateTime instance.
	 * @return int               The timezone offset in seconds.
	 * @throws \Exception        If the offset cannot be retrieved.
	 */
	public function getOffset(DateTime $dateTime): int
	{
		return $this->wrapInTry(fn() => $dateTime->getOffset());
	}

	/**
	 * Gets the timestamp of a DateTime instance.
	 *
	 * @param DateTime $dateTime The DateTime instance.
	 * @return int               The Unix timestamp.
	 * @throws \Exception        If the timestamp cannot be retrieved.
	 */
	public function getTimestamp(DateTime $dateTime): int
	{
		return $this->wrapInTry(fn() => $dateTime->getTimestamp());
	}

	/**
	 * Gets the timezone of a DateTime instance.
	 *
	 * @param DateTime $dateTime The DateTime instance.
	 * @return DateTimeZone|false The timezone object or false on failure.
	 * @throws \Exception         If the timezone cannot be retrieved.
	 */
	public function getTimezone(DateTime $dateTime): DateTimeZone|false
	{
		return $this->wrapInTry(fn() => $dateTime->getTimezone());
	}

	/**
	 * Creates a new DateTimeImmutable instance.
	 *
	 * @param string $datetime   The date and time string (default: 'now').
	 * @param DateTimeZone|null $timezone Optional timezone.
	 * @return DateTimeImmutable The created DateTimeImmutable instance.
	 * @throws \Exception        If the DateTimeImmutable object creation fails.
	 */
	public function createImmutable(string $datetime = 'now', ?DateTimeZone $timezone = null): DateTimeImmutable
	{
		return $this->wrapInTry(fn() => new DateTimeImmutable($datetime, $timezone));
	}

	/**
	 * Adds an interval to a DateTimeImmutable instance.
	 *
	 * @param DateTimeImmutable $dateTime The DateTimeImmutable instance to modify.
	 * @param DateInterval $interval The interval to add.
	 * @return DateTimeImmutable The modified DateTimeImmutable instance.
	 * @throws \Exception        If the interval addition fails.
	 */
	public function addImmutableInterval(DateTimeImmutable $dateTime, DateInterval $interval): DateTimeImmutable
	{
		return $this->wrapInTry(fn() => $dateTime->add($interval));
	}

	/**
	 * Creates a DateTimeImmutable instance from a DateTime object.
	 *
	 * @param DateTime $mutable The mutable DateTime object.
	 * @return DateTimeImmutable The created DateTimeImmutable instance.
	 * @throws \Exception        If conversion fails.
	 */
	public function fromMutable(DateTime $mutable): DateTimeImmutable
	{
		return $this->wrapInTry(fn() => DateTimeImmutable::createFromMutable($mutable));
	}

	/**
	 * Creates a DateTimeImmutable instance from a DateTimeInterface object.
	 *
	 * @param DateTimeInterface $interface The interface-based DateTime object.
	 * @return DateTimeImmutable The created DateTimeImmutable instance.
	 * @throws \Exception        If conversion fails.
	 */
	public function fromInterfaceImmutable(DateTimeInterface $interface): DateTimeImmutable
	{
		return $this->wrapInTry(fn() => DateTimeImmutable::createFromInterface($interface));
	}

	/**
	 * Creates a DateTimeImmutable instance from a formatted date string.
	 *
	 * @param string $format     The format of the input date string.
	 * @param string $datetime   The date string.
	 * @param DateTimeZone|null $timezone Optional timezone.
	 * @return DateTimeImmutable|false The created DateTimeImmutable instance or false on failure.
	 * @throws \Exception        If the creation fails.
	 */
	public function createImmutableFromFormat(string $format, string $datetime, ?DateTimeZone $timezone = null): DateTimeImmutable|false
	{
		return $this->wrapInTry(fn() => DateTimeImmutable::createFromFormat($format, $datetime, $timezone));
	}

	/**
	 * Modifies the DateTimeImmutable object using a relative date/time string.
	 *
	 * @param DateTimeImmutable $dateTime The DateTimeImmutable instance to modify.
	 * @param string $modifier   The relative date/time string.
	 * @return DateTimeImmutable The modified DateTimeImmutable instance.
	 * @throws \Exception        If modification fails.
	 */
	public function modifyImmutable(DateTimeImmutable $dateTime, string $modifier): DateTimeImmutable
	{
		return $this->wrapInTry(fn() => $dateTime->modify($modifier));
	}

	/**
	 * Formats a DateTimeImmutable instance to a string.
	 *
	 * @param DateTimeImmutable $dateTime The DateTimeImmutable instance to format.
	 * @param string $format     The format string.
	 * @return string            The formatted date string.
	 * @throws \Exception        If formatting fails.
	 */
	public function formatImmutable(DateTimeImmutable $dateTime, string $format): string
	{
		return $this->wrapInTry(fn() => $dateTime->format($format));
	}

	/**
	 * Calculates the difference between two DateTimeImmutable instances.
	 *
	 * @param DateTimeImmutable $date1 The starting DateTimeImmutable instance.
	 * @param DateTimeImmutable $date2 The ending DateTimeImmutable instance.
	 * @param bool $absolute       Whether to return the absolute difference.
	 * @return DateInterval        The interval representing the difference.
	 * @throws \Exception          If the difference cannot be calculated.
	 */
	public function diffImmutable(DateTimeImmutable $date1, DateTimeImmutable $date2, bool $absolute = false): DateInterval
	{
		return $this->wrapInTry(fn() => $date1->diff($date2, $absolute));
	}

	/**
	 * Creates a new DateTimeZone instance.
	 *
	 * @param string $timezone The timezone identifier (e.g., "UTC", "America/New_York").
	 * @return DateTimeZone    The created DateTimeZone instance.
	 * @throws \Exception      If the timezone creation fails.
	 */
	public function createTimeZone(string $timezone): DateTimeZone
	{
		return $this->wrapInTry(fn() => new DateTimeZone($timezone));
	}

	/**
	 * Gets location information for a timezone.
	 *
	 * @param DateTimeZone $timezone The DateTimeZone instance.
	 * @return array|false           The location information or false on failure.
	 * @throws \Exception            If retrieving location information fails.
	 */
	public function getZoneLocation(DateTimeZone $timezone): array|false
	{
		return $this->wrapInTry(fn() => $timezone->getLocation());
	}

	/**
	 * Gets the name of a timezone.
	 *
	 * @param DateTimeZone $timezone The DateTimeZone instance.
	 * @return string                The name of the timezone.
	 * @throws \Exception            If retrieving the name fails.
	 */
	public function getZoneName(DateTimeZone $timezone): string
	{
		return $this->wrapInTry(fn() => $timezone->getName());
	}

	/**
	 * Gets the timezone offset for a given DateTime.
	 *
	 * @param DateTimeZone $timezone The DateTimeZone instance.
	 * @param DateTimeInterface $dateTime The DateTime object.
	 * @return int                   The offset in seconds from UTC.
	 * @throws \Exception            If retrieving the offset fails.
	 */
	public function getZoneOffset(DateTimeZone $timezone, DateTimeInterface $dateTime): int
	{
		return $this->wrapInTry(fn() => $timezone->getOffset($dateTime));
	}

	/**
	 * Gets all transitions for a timezone within a specified range.
	 *
	 * @param DateTimeZone $timezone The DateTimeZone instance.
	 * @param int $start             The starting timestamp (default: PHP_INT_MIN).
	 * @param int $end               The ending timestamp (default: PHP_INT_MAX).
	 * @return array|false           The transitions or false on failure.
	 * @throws \Exception            If retrieving transitions fails.
	 */
	public function getZoneTransitions(DateTimeZone $timezone, int $start = PHP_INT_MIN, int $end = PHP_INT_MAX): array|false
	{
		return $this->wrapInTry(fn() => $timezone->getTransitions($start, $end));
	}

	/**
	 * Lists all timezone abbreviations.
	 *
	 * @return array                An associative array of timezone abbreviations.
	 * @throws \Exception           If the operation fails.
	 */
	public function listZoneAbbreviations(): array
	{
		return $this->wrapInTry(fn() => DateTimeZone::listAbbreviations());
	}

	/**
	 * Lists all timezone identifiers.
	 *
	 * @param int $group            The timezone group (e.g., DateTimeZone::ALL).
	 * @param string|null $country  The ISO 3166-1 alpha-2 country code (optional).
	 * @return array                A numerically indexed array of timezone identifiers.
	 * @throws \Exception           If the operation fails.
	 */
	public function listZoneIdentifiers(int $group = DateTimeZone::ALL, ?string $country = null): array
	{
		return $this->wrapInTry(fn() => DateTimeZone::listIdentifiers($group, $country));
	}

	/**
	 * Creates a new DateInterval instance.
	 *
	 * @param string $spec          The interval specification (e.g., "P1D" for 1 day).
	 * @return DateInterval         The created DateInterval instance.
	 * @throws \Exception           If the interval creation fails.
	 */
	public function createInterval(string $spec): DateInterval
	{
		return $this->wrapInTry(fn() => new DateInterval($spec));
	}

	/**
	 * Creates a DateInterval instance from a relative date string.
	 *
	 * @param string $datetime      The relative date string (e.g., "1 day").
	 * @return DateInterval         The created DateInterval instance.
	 * @throws \Exception           If the operation fails.
	 */
	public function intervalFromDateString(string $datetime): DateInterval
	{
		return $this->wrapInTry(fn() => DateInterval::createFromDateString($datetime));
	}

	/**
	 * Formats a DateInterval instance.
	 *
	 * @param DateInterval $interval The DateInterval instance.
	 * @param string $format         The format string (e.g., "%d days").
	 * @return string                The formatted interval string.
	 * @throws \Exception            If the formatting fails.
	 */
	public function formatInterval(DateInterval $interval, string $format): string
	{
		return $this->wrapInTry(fn() => $interval->format($format));
	}

	/**
	 * Creates a new DatePeriod instance using a start, interval, and end.
	 *
	 * @param DateTimeInterface $start The starting date.
	 * @param DateInterval $interval   The interval between periods.
	 * @param DateTimeInterface $end   The ending date.
	 * @return DatePeriod              The created DatePeriod instance.
	 * @throws \Exception              If the operation fails.
	 */
	public function createPeriod(DateTimeInterface $start, DateInterval $interval, DateTimeInterface $end): DatePeriod
	{
		return $this->wrapInTry(fn() => new DatePeriod($start, $interval, $end));
	}

	/**
	 * Creates a new DatePeriod instance from an ISO 8601 string.
	 *
	 * @param string $isoString        The ISO 8601 string (e.g., "R5/2008-03-01T13:00:00Z/P1Y2M10DT2H30M").
	 * @param int $options             Options for the DatePeriod (default: 0).
	 * @return DatePeriod              The created DatePeriod instance.
	 * @throws \Exception              If the operation fails.
	 */
	public function createPeriodFromIsoString(string $isoString, int $options = 0): DatePeriod
	{
		return $this->wrapInTry(fn() => DatePeriod::createFromISO8601String($isoString, $options));
	}

	/**
	 * Gets the start date of a DatePeriod.
	 *
	 * @param DatePeriod $period       The DatePeriod instance.
	 * @return DateTimeInterface       The start date.
	 * @throws \Exception              If the operation fails.
	 */
	public function getPeriodStart(DatePeriod $period): DateTimeInterface
	{
		return $this->wrapInTry(fn() => $period->getStartDate());
	}

	/**
	 * Gets the end date of a DatePeriod.
	 *
	 * @param DatePeriod $period       The DatePeriod instance.
	 * @return DateTimeInterface|null  The end date or null if none exists.
	 * @throws \Exception              If the operation fails.
	 */
	public function getPeriodEnd(DatePeriod $period): ?DateTimeInterface
	{
		return $this->wrapInTry(fn() => $period->getEndDate());
	}

	/**
	 * Gets the interval of a DatePeriod.
	 *
	 * @param DatePeriod $period       The DatePeriod instance.
	 * @return DateInterval            The interval of the DatePeriod.
	 * @throws \Exception              If the operation fails.
	 */
	public function getPeriodInterval(DatePeriod $period): DateInterval
	{
		return $this->wrapInTry(fn() => $period->getDateInterval());
	}

	/**
	 * Gets the number of recurrences in a DatePeriod.
	 *
	 * @param DatePeriod $period       The DatePeriod instance.
	 * @return int|null                The number of recurrences or null if infinite.
	 * @throws \Exception              If the operation fails.
	 */
	public function getPeriodRecurrences(DatePeriod $period): ?int
	{
		return $this->wrapInTry(fn() => $period->getRecurrences());
	}
}
