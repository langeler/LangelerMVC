<?php

namespace App\Utilities\Traits\Filters;

use InvalidArgumentException;

/**
 * Trait SanitationFilterTrait
 *
 * Provides access to PHP's non-deprecated sanitization filters.
 *
 * This trait allows easy retrieval of all available sanitization filters (non-deprecated) using a descriptive key.
 */
trait SanitationFilterTrait
{
	/**
	 * Available non-deprecated sanitization filters.
	 *
	 * Key: Descriptive name of the filter for easy reference.
	 * Value: The actual PHP FILTER_SANITIZE_* constant.
	 */
	private array $filters = [
		'encoded' => FILTER_SANITIZE_ENCODED,                 // URL-encodes a string
		'string' => FILTER_SANITIZE_SPECIAL_CHARS,            // Escapes HTML special characters
		'email' => FILTER_SANITIZE_EMAIL,                     // Removes invalid characters from an email
		'url' => FILTER_SANITIZE_URL,                         // Removes invalid characters from a URL
		'number_int' => FILTER_SANITIZE_NUMBER_INT,           // Removes all characters except digits, plus and minus signs
		'number_float' => FILTER_SANITIZE_NUMBER_FLOAT,       // Removes all characters except digits, +-., and optionally eE for floats
		'add_slashes' => FILTER_SANITIZE_ADD_SLASHES,         // Adds backslashes before characters like quotes, backslashes, and NUL
		'full_special_chars' => FILTER_SANITIZE_FULL_SPECIAL_CHARS, // Escapes HTML special characters (alternative to htmlspecialchars)
	];

	/**
	 * Retrieves the corresponding sanitization filter constant for a given filter key.
	 *
	 * @param string $key The descriptive key name of the filter.
	 * @return int The corresponding FILTER_SANITIZE_* constant.
	 * @throws InvalidArgumentException If the filter key is invalid.
	 */
	public function getFilter(string $key): int
	{
		return $this->filters[$key] ?? throw new InvalidArgumentException("Invalid sanitization filter key: $key");
	}

	/**
	 * Retrieves all available sanitization filters.
	 *
	 * @return array The list of FILTER_SANITIZE_* constants.
	 */
	public function getAllFilters(): array
	{
		return $this->filters;
	}

	/**
	 * Checks if a given sanitization filter key exists in the available filters.
	 *
	 * @param string $key The descriptive key name of the filter.
	 * @return bool True if the filter exists, false otherwise.
	 */
	public function hasFilter(string $key): bool
	{
		return isset($this->filters[$key]);
	}
}
