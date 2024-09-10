<?php

namespace App\Utilities\Traits\Filters;

use InvalidArgumentException;

/**
 * Trait FilterFlagTrait
 *
 * Provides access to PHP's non-deprecated filter flags.
 */
trait FilterFlagTrait
{
	/**
	 * Available non-deprecated filter flags.
	 *
	 * Key: Descriptive name of the flag for easy reference.
	 * Value: The actual PHP FILTER_FLAG_* constant.
	 */
	private array $flags = [
		// Applicable to both sanitization and validation
		'allow_fraction' => FILTER_FLAG_ALLOW_FRACTION,            // Allows decimal fractions in numbers
		'allow_scientific' => FILTER_FLAG_ALLOW_SCIENTIFIC,        // Allows scientific notation in numbers
		'allow_thousand' => FILTER_FLAG_ALLOW_THOUSAND,            // Allows thousand separators in numbers

		// Sanitization-specific
		'no_encode_quotes' => FILTER_FLAG_NO_ENCODE_QUOTES,        // Prevents the encoding of quotes in strings (FILTER_SANITIZE_ENCODED)
		'strip_low' => FILTER_FLAG_STRIP_LOW,                      // Strips characters with ASCII value < 32 (FILTER_SANITIZE_STRING)
		'strip_high' => FILTER_FLAG_STRIP_HIGH,                    // Strips characters with ASCII value > 127 (FILTER_SANITIZE_STRING)
		'encode_amp' => FILTER_FLAG_ENCODE_AMP,                    // Encodes ampersands (&) (FILTER_SANITIZE_STRING)
		'strip_backtick' => FILTER_FLAG_STRIP_BACKTICK,            // Strips backticks from strings (FILTER_SANITIZE_STRING)

		// Validation-specific
		'ipv4' => FILTER_FLAG_IPV4,                                // Limits IP validation to IPv4 addresses (FILTER_VALIDATE_IP)
		'ipv6' => FILTER_FLAG_IPV6,                                // Limits IP validation to IPv6 addresses (FILTER_VALIDATE_IP)
		'no_res_range' => FILTER_FLAG_NO_RES_RANGE,                // Excludes reserved IP ranges (FILTER_VALIDATE_IP)
		'no_priv_range' => FILTER_FLAG_NO_PRIV_RANGE,              // Excludes private IP ranges (FILTER_VALIDATE_IP)
		'path_required' => FILTER_FLAG_PATH_REQUIRED,              // Requires a path in the URL (FILTER_VALIDATE_URL)
		'query_required' => FILTER_FLAG_QUERY_REQUIRED,            // Requires a query string in the URL (FILTER_VALIDATE_URL)
	];

	/**
	 * Retrieves the corresponding filter flag constant for a given flag key.
	 *
	 * @param string $key The descriptive key name of the flag.
	 * @return int The corresponding FILTER_FLAG_* constant.
	 * @throws InvalidArgumentException If the flag key is invalid.
	 */
	public function getFlag(string $key): int
	{
		return $this->flags[$key] ?? throw new InvalidArgumentException("Invalid filter flag key: $key");
	}

	/**
	 * Retrieves all flags applicable to validation.
	 *
	 * @return array The list of FILTER_FLAG_* constants related to validation.
	 */
	public function getValidationFlags(): array
	{
		return array_intersect_key($this->flags, array_flip([
			'allow_fraction',
			'allow_scientific',
			'allow_thousand',
			'ipv4',
			'ipv6',
			'no_res_range',
			'no_priv_range',
			'path_required',
			'query_required'
		]));
	}

	/**
	 * Retrieves all flags applicable to sanitization.
	 *
	 * @return array The list of FILTER_FLAG_* constants related to sanitization.
	 */
	public function getSanitizationFlags(): array
	{
		return array_intersect_key($this->flags, array_flip([
			'allow_fraction',
			'allow_scientific',
			'allow_thousand',
			'no_encode_quotes',
			'strip_low',
			'strip_high',
			'encode_amp',
			'strip_backtick'
		]));
	}

	/**
	 * Checks if a given flag key exists in the available flags.
	 *
	 * @param string $key The descriptive key name of the flag.
	 * @return bool True if the flag exists, false otherwise.
	 */
	public function hasFlag(string $key): bool
	{
		return isset($this->flags[$key]);
	}
}
