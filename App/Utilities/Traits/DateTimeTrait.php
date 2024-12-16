<?php

namespace App\Utilities\Traits;

/**
 * DateTimeTrait provides utility methods for handling various date and time operations.
 */
trait DateTimeTrait
{

	/**
	 * Read-only property containing all constants grouped by type.
	 */
	public readonly array $traitConstants;

	public function __construct()
	{
		$this->traitConstants = [
			'sunFunctions' => [
				'timestamp' => SUNFUNCS_RET_TIMESTAMP, // Returns time as a Unix timestamp.
				'string'    => SUNFUNCS_RET_STRING,    // Returns time in "hours:minutes" format (e.g., "08:02").
				'double'    => SUNFUNCS_RET_DOUBLE,    // Returns time as a floating-point number (e.g., 8.75).
			],
			'dateFormats' => [
				'atom'              => DATE_ATOM,               // Atom format: "Y-m-d\TH:i:sP" (e.g., "2005-08-15T15:52:01+00:00").
				'cookie'            => DATE_COOKIE,             // HTTP Cookies: "l, d-M-Y H:i:s T" (e.g., "Monday, 15-Aug-2005 15:52:01 UTC").
				'iso8601'           => DATE_ISO8601,           // ISO-8601: "Y-m-d\TH:i:sO" (legacy).
				'iso8601Expanded'   => DATE_ISO8601_EXPANDED,  // ISO-8601 Expanded for large year ranges.
				'rfc822'            => DATE_RFC822,            // RFC 822: "D, d M y H:i:s O" (e.g., "Mon, 15 Aug 05 15:52:01 +0000").
				'rfc850'            => DATE_RFC850,            // RFC 850: "l, d-M-y H:i:s T" (e.g., "Monday, 15-Aug-05 15:52:01 UTC").
				'rfc1036'           => DATE_RFC1036,           // RFC 1036: "D, d M y H:i:s O".
				'rfc1123'           => DATE_RFC1123,           // RFC 1123: "D, d M Y H:i:s O".
				'rfc7231'           => DATE_RFC7231,           // RFC 7231 (HTTP): "D, d M Y H:i:s GMT".
				'rfc2822'           => DATE_RFC2822,           // RFC 2822: "D, d M Y H:i:s O".
				'rfc3339'           => DATE_RFC3339,           // RFC 3339: "Y-m-d\TH:i:sP".
				'rfc3339Extended'   => DATE_RFC3339_EXTENDED,  // RFC 3339 Extended: "Y-m-d\TH:i:s.vP".
				'rss'               => DATE_RSS,               // RSS: "D, d M Y H:i:s O" (Alias of DATE_RFC1123).
				'w3c'               => DATE_W3C,               // W3C: "Y-m-d\TH:i:sP" (Alias of DATE_RFC3339).
			],
		];
	}

	/**
	 * Validate a Gregorian date.
	 *
	 * @param int $month
	 * @param int $day
	 * @param int $year
	 * @return bool
	 */
	public function isValidDate(int $month, int $day, int $year): bool
	{
		return checkdate($month, $day, $year);
	}

	/**
	 * Format a Unix timestamp.
	 *
	 * @param string $format
	 * @param int|null $timestamp
	 * @return string
	 */
	public function formatTimestamp(string $format, ?int $timestamp = null): string
	{
		return date($format, $timestamp ?? time());
	}

	/**
	 * Get the default timezone used by all date/time functions.
	 *
	 * @return string
	 */
	public function getDefaultTimezone(): string
	{
		return date_default_timezone_get();
	}

	/**
	 * Set the default timezone used by all date/time functions.
	 *
	 * @param string $timezone
	 * @return bool
	 */
	public function setDefaultTimezone(string $timezone): bool
	{
		return date_default_timezone_set($timezone);
	}

	/**
	 * Parse a date/time string into detailed information.
	 *
	 * @param string $datetime
	 * @return array
	 */
	public function parseDate(string $datetime): array
	{
		return date_parse($datetime);
	}

	/**
	 * Parse a formatted date string into detailed information.
	 *
	 * @param string $format
	 * @param string $datetime
	 * @return array
	 */
	public function parseDateFromFormat(string $format, string $datetime): array
	{
		return date_parse_from_format($format, $datetime);
	}

	/**
	 * Get information about sunset, sunrise, and twilight times.
	 *
	 * @param float $latitude
	 * @param float $longitude
	 * @param int $timestamp
	 * @return array
	 */
	public function getSunInfo(float $latitude, float $longitude, int $timestamp): array
	{
		return date_sun_info($timestamp, $latitude, $longitude);
	}

	/**
	 * Get the time of sunrise for a specific location and day.
	 *
	 * @param float $latitude
	 * @param float $longitude
	 * @param int $timestamp
	 * @param int $returnFormat
	 * @return string|int|float
	 */
	public function getSunrise(float $latitude, float $longitude, int $timestamp, int $returnFormat = SUNFUNCS_RET_STRING): string|int|float
	{
		return date_sunrise($timestamp, $returnFormat, $latitude, $longitude);
	}

	/**
	 * Get the time of sunset for a specific location and day.
	 *
	 * @param float $latitude
	 * @param float $longitude
	 * @param int $timestamp
	 * @param int $returnFormat
	 * @return string|int|float
	 */
	public function getSunset(float $latitude, float $longitude, int $timestamp, int $returnFormat = SUNFUNCS_RET_STRING): string|int|float
	{
		return date_sunset($timestamp, $returnFormat, $latitude, $longitude);
	}

	/**
	 * Get date/time information for the current timestamp or a given timestamp.
	 *
	 * @param int|null $timestamp
	 * @return array
	 */
	public function getDateInfo(?int $timestamp = null): array
	{
		return getdate($timestamp ?? time());
	}

	/**
	 * Get the current time with microseconds.
	 *
	 * @param bool $asFloat
	 * @return string|float
	 */
	public function getMicroTime(bool $asFloat = false): string|float
	{
		return microtime($asFloat);
	}

	/**
	 * Get the current Unix timestamp.
	 *
	 * @return int
	 */
	public function getCurrentTimestamp(): int
	{
		return time();
	}

	/**
	 * Parse a textual date/time description into a Unix timestamp.
	 *
	 * @param string $datetime
	 * @param int|null $baseTimestamp
	 * @return int|false
	 */
	public function parseToTimestamp(string $datetime, ?int $baseTimestamp = null): int|false
	{
		return strtotime($datetime, $baseTimestamp ?? time());
	}

	/**
	 * Format a GMT/UTC date/time.
	 *
	 * @param string $format
	 * @param int|null $timestamp
	 * @return string
	 */
	public function formatGmtDate(string $format, ?int $timestamp = null): string
	{
		return gmdate($format, $timestamp ?? time());
	}

	/**
	 * Get current local time.
	 *
	 * @param bool $asAssociativeArray
	 * @return array|int[]
	 */
	public function getLocalTime(bool $asAssociativeArray = true): array|int
	{
		return localtime(time(), $asAssociativeArray);
	}

	/**
	 * Get timezone abbreviations.
	 *
	 * @return array
	 */
	public function listTimeZoneAbbreviations(): array
	{
		return timezone_abbreviations_list();
	}

	/**
	 * Get timezone identifiers.
	 *
	 * @param int $group
	 * @param string|null $country
	 * @return array
	 */
	public function listTimeZoneIdentifiers(int $group = DateTimeZone::ALL, ?string $country = null): array
	{
		return timezone_identifiers_list($group, $country);
	}

	/**
	 * Get timezone name from abbreviation.
	 *
	 * @param string $abbr
	 * @param int $offset
	 * @param int $isDST
	 * @return string|false
	 */
	public function getTimeZoneNameFromAbbr(string $abbr, int $offset = 0, int $isDST = 0): string|false
	{
		return timezone_name_from_abbr($abbr, $offset, $isDST);
	}

	/**
	 * Get the version of the timezonedb.
	 *
	 * @return string
	 */
	public function getTimeZoneDbVersion(): string
	{
		return timezone_version_get();
	}
}
