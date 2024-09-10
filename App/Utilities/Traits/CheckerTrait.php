<?php

namespace App\Utilities\Traits;

/**
 * Trait CheckerTrait
 *
 * Provides utility functions for checking various properties of strings.
 */
trait CheckerTrait
{
	/**
	 * Checks if a string contains only alphanumeric characters.
	 *
	 * @param string $input The input string to check.
	 * @return bool True if the string is alphanumeric, false otherwise.
	 */
	public function isAlphanumeric(string $input): bool
	{
		return ctype_alnum($input);
	}

	/**
	 * Checks if a string contains only alphabetic characters.
	 *
	 * @param string $input The input string to check.
	 * @return bool True if the string contains only letters, false otherwise.
	 */
	public function isAlphabetic(string $input): bool
	{
		return ctype_alpha($input);
	}

	/**
	 * Checks if a string contains only numeric digits.
	 *
	 * @param string $input The input string to check.
	 * @return bool True if the string contains only digits, false otherwise.
	 */
	public function isNumeric(string $input): bool
	{
		return ctype_digit($input);
	}

	/**
	 * Checks if a string contains only lowercase alphabetic characters.
	 *
	 * @param string $input The input string to check.
	 * @return bool True if the string contains only lowercase letters, false otherwise.
	 */
	public function isLowercase(string $input): bool
	{
		return ctype_lower($input);
	}

	/**
	 * Checks if a string contains only whitespace characters.
	 *
	 * @param string $input The input string to check.
	 * @return bool True if the string contains only whitespace, false otherwise.
	 */
	public function isWhitespace(string $input): bool
	{
		return ctype_space($input);
	}

	/**
	 * Checks if a string contains only uppercase alphabetic characters.
	 *
	 * @param string $input The input string to check.
	 * @return bool True if the string contains only uppercase letters, false otherwise.
	 */
	public function isUppercase(string $input): bool
	{
		return ctype_upper($input);
	}
}
