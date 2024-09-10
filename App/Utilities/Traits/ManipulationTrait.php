<?php

namespace App\Utilities\Traits;

/**
 * Trait ManipulationTrait
 *
 * Provides utility functions for string manipulation and array joining operations.
 */
trait ManipulationTrait
{
	/**
	 * Splits a string by a delimiter into an array.
	 *
	 * @param string $delimiter The delimiter to split the string by.
	 * @param string $string The string to split.
	 * @return array The split string as an array.
	 */
	public function split(string $delimiter, string $string): array
	{
		return explode($delimiter, $string);
	}

	/**
	 * Joins an array of strings into a single string with a glue.
	 *
	 * @param string $glue The string to join array elements.
	 * @param array $pieces The array of pieces to join.
	 * @return string The joined string.
	 */
	public function join(string $glue, array $pieces): string
	{
		return implode($glue, $pieces);
	}

	/**
	 * Pads a string to a certain length with another string.
	 *
	 * @param string $input The input string.
	 * @param int $length The length to pad the string to.
	 * @param string $padStr The string to pad with (default is a space).
	 * @param int $padType The padding type (default is STR_PAD_RIGHT).
	 * @return string The padded string.
	 */
	public function pad(string $input, int $length, string $padStr = ' ', int $padType = STR_PAD_RIGHT): string
	{
		return str_pad($input, $length, $padStr, $padType);
	}

	/**
	 * Replaces all occurrences of the search string with the replacement string.
	 *
	 * @param string|array $search The string or array of strings to search for.
	 * @param string|array $replace The replacement string or array.
	 * @param string|array $subject The subject string or array.
	 * @param int|null $count (Optional) If provided, this will be filled with the number of replacements made.
	 * @return string|array The resulting string or array with replacements.
	 */
	public function replace(string|array $search, string|array $replace, string|array $subject, ?int &$count = null): string|array
	{
		return str_replace($search, $replace, $subject, $count);
	}

	/**
	 * Repeats a string a specified number of times.
	 *
	 * @param string $input The string to repeat.
	 * @param int $multiplier The number of times to repeat the string.
	 * @return string The repeated string.
	 */
	public function repeat(string $input, int $multiplier): string
	{
		return str_repeat($input, $multiplier);
	}

	/**
	 * Compares two strings case-insensitively.
	 *
	 * @param string $str1 The first string.
	 * @param string $str2 The second string.
	 * @return int Returns < 0 if str1 is less than str2, > 0 if str1 is greater than str2, and 0 if they are equal.
	 */
	public function compareIgnoreCase(string $str1, string $str2): int
	{
		return strcasecmp($str1, $str2);
	}

	/**
	 * Finds the first occurrence of a substring in a string, case-insensitively.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle The substring to search for.
	 * @return string|false The portion of the haystack starting from the first occurrence, or false if not found.
	 */
	public function findIgnoreCase(string $haystack, string $needle): string|false
	{
		return stristr($haystack, $needle);
	}

	/**
	 * Gets the length of a string.
	 *
	 * @param string $string The input string.
	 * @return int The length of the string.
	 */
	public function length(string $string): int
	{
		return strlen($string);
	}

	/**
	 * Finds the first occurrence of a substring in a string.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle The substring to search for.
	 * @return int|false The position of the first occurrence, or false if not found.
	 */
	public function findFirst(string $haystack, string $needle): int|false
	{
		return strpos($haystack, $needle);
	}

	/**
	 * Reverses a string.
	 *
	 * @param string $string The string to reverse.
	 * @return string The reversed string.
	 */
	public function reverse(string $string): string
	{
		return strrev($string);
	}

	/**
	 * Finds the last occurrence of a substring in a string.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle The substring to search for.
	 * @return int|false The position of the last occurrence, or false if not found.
	 */
	public function findLast(string $haystack, string $needle): int|false
	{
		return strrpos($haystack, $needle);
	}

	/**
	 * Finds the first occurrence of a substring in a string and returns the rest of the string.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle The substring to search for.
	 * @return string|false The portion of the string starting from the first occurrence, or false if not found.
	 */
	public function findSubstring(string $haystack, string $needle): string|false
	{
		return strstr($haystack, $needle);
	}

	/**
	 * Converts a string to lowercase.
	 *
	 * @param string $string The input string.
	 * @return string The string in lowercase.
	 */
	public function toLower(string $string): string
	{
		return strtolower($string);
	}

	/**
	 * Converts a string to uppercase.
	 *
	 * @param string $string The input string.
	 * @return string The string in uppercase.
	 */
	public function toUpper(string $string): string
	{
		return strtoupper($string);
	}

	/**
	 * Splits a string into an array of characters.
	 *
	 * @param string $string The input string.
	 * @param int $length The length of each segment (default is 1).
	 * @return array The split string as an array.
	 */
	public function splitToArray(string $string, int $length = 1): array
	{
		return str_split($string, $length);
	}

	/**
	 * Extracts a substring from a string.
	 *
	 * @param string $string The input string.
	 * @param int $start The starting position.
	 * @param int|null $length The length of the substring (optional).
	 * @return string The extracted substring.
	 */
	public function substring(string $string, int $start, ?int $length = null): string
	{
		return substr($string, $start, $length);
	}

	/**
	 * Trims whitespace or specified characters from the beginning and end of a string.
	 *
	 * @param string $string The input string.
	 * @param string $characters The characters to trim (default is whitespace).
	 * @return string The trimmed string.
	 */
	public function trim(string $string, string $characters = " \t\n\r\0\x0B"): string
	{
		return trim($string, $characters);
	}

	/**
	 * Capitalizes the first letter of each word in a string.
	 *
	 * @param string $string The input string.
	 * @return string The capitalized string.
	 */
	public function capitalizeWords(string $string): string
	{
		return ucwords($string);
	}
}
