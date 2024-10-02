<?php

namespace App\Utilities\Traits;

/**
 * Trait CheckerTrait
 *
 * Provides utility functions for checking various properties of strings.
 */
trait CheckerTrait
{
	// Character Type Checks

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
	 * Checks if a string contains only uppercase alphabetic characters.
	 *
	 * @param string $input The input string to check.
	 * @return bool True if the string contains only uppercase letters, false otherwise.
	 */
	public function isUppercase(string $input): bool
	{
		return ctype_upper($input);
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

	// String Content Checks

	/**
	 * Checks if a string contains a given substring.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle The substring to search for.
	 * @return bool True if the haystack contains the needle, false otherwise.
	 */
	public function contains(string $haystack, string $needle): bool
	{
		return str_contains($haystack, $needle);
	}

	/**
	 * Checks if a string starts with a given substring.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle The substring to check.
	 * @return bool True if the string starts with the needle, false otherwise.
	 */
	public function startsWith(string $haystack, string $needle): bool
	{
		return str_starts_with($haystack, $needle);
	}

	/**
	 * Checks if a string ends with a given substring.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle The substring to check.
	 * @return bool True if the string ends with the needle, false otherwise.
	 */
	public function endsWith(string $haystack, string $needle): bool
	{
		return str_ends_with($haystack, $needle);
	}

	// Special Formats Checking

	/**
	 * Checks if a string is a valid JSON.
	 *
	 * @param string $input The input string to check.
	 * @return bool True if the string is a valid JSON, false otherwise.
	 */
	public function isJson(string $input): bool
	{
		json_decode($input);
		return (json_last_error() === JSON_ERROR_NONE);
	}

	/**
	 * Checks if a string is a valid hexadecimal number.
	 *
	 * @param string $input The input string to check.
	 * @return bool True if the string is hexadecimal, false otherwise.
	 */
	public function isHexadecimal(string $input): bool
	{
		return ctype_xdigit($input);
	}

	// General String Validity

	/**
	 * Checks if a string is empty.
	 *
	 * @param string $input The input string to check.
	 * @return bool True if the string is empty, false otherwise.
	 */
	public function isEmpty(string $input): bool
	{
		return empty($input);
	}
}
