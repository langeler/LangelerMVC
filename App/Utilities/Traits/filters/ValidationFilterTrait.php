<?php

namespace App\Utilities\Traits\Filters;

use InvalidArgumentException;

/**
 * Trait ValidationFilterTrait
 *
 * Provides access to PHP's non-deprecated validation filters.
 *
 * This trait allows easy retrieval of all available validation filters (non-deprecated) using a descriptive key.
 */
trait ValidationFilterTrait
{
	/**
	 * Available non-deprecated validation filters.
	 *
	 * Key: Descriptive name of the filter for easy reference.
	 * Value: The actual PHP FILTER_VALIDATE_* constant.
	 */
	private array $filters = [
		'boolean' => FILTER_VALIDATE_BOOLEAN,              // Validates boolean values
		'email' => FILTER_VALIDATE_EMAIL,                  // Validates an email address
		'float' => FILTER_VALIDATE_FLOAT,                  // Validates a floating-point number
		'int' => FILTER_VALIDATE_INT,                      // Validates an integer
		'ip' => FILTER_VALIDATE_IP,                        // Validates an IP address (IPv4 or IPv6)
		'mac' => FILTER_VALIDATE_MAC,                      // Validates a MAC address
		'regexp' => FILTER_VALIDATE_REGEXP,                // Validates against a regular expression
		'url' => FILTER_VALIDATE_URL,                      // Validates a URL
		'domain' => FILTER_VALIDATE_DOMAIN,                // Validates a domain name (added in PHP 7.0)
	];

	/**
	 * Retrieves the corresponding validation filter constant for a given filter key.
	 *
	 * @param string $key The descriptive key name of the filter.
	 * @return int The corresponding FILTER_VALIDATE_* constant.
	 * @throws InvalidArgumentException If the filter key is invalid.
	 */
	public function getFilter(string $key): int
	{
		return $this->filters[$key] ?? throw new InvalidArgumentException("Invalid validation filter key: $key");
	}

	/**
	 * Retrieves all available validation filters.
	 *
	 * @return array The list of FILTER_VALIDATE_* constants.
	 */
	public function getAllFilters(): array
	{
		return $this->filters;
	}

	/**
	 * Checks if a given validation filter key exists in the available filters.
	 *
	 * @param string $key The descriptive key name of the filter.
	 * @return bool True if the filter exists, false otherwise.
	 */
	public function hasFilter(string $key): bool
	{
		return isset($this->filters[$key]);
	}
}
