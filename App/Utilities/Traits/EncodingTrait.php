<?php

namespace App\Utilities\Traits;

/**
 * Trait EncodingTrait
 *
 * Provides a set of utility functions for encoding, decoding, and string manipulation operations.
 */
trait EncodingTrait
{
	/**
	 * Adds backslashes before certain characters in a string.
	 *
	 * @param string $input The input string.
	 * @return string The string with added slashes.
	 */
	public function addSlashes(string $input): string
	{
		return addslashes($input);
	}

	/**
	 * Encodes data in base64 format.
	 *
	 * @param string $data The data to encode.
	 * @return string The base64 encoded string.
	 */
	public function base64Encode(string $data): string
	{
		return base64_encode($data);
	}

	/**
	 * Decodes a base64 encoded string.
	 *
	 * @param string $data The base64 encoded string.
	 * @param bool $strict Whether to enforce strict decoding (default is false).
	 * @return string|false The decoded string or false on failure.
	 */
	public function base64Decode(string $data, bool $strict = false): string|false
	{
		return base64_decode($data, $strict);
	}

	/**
	 * Converts special characters to HTML entities.
	 *
	 * @param string $input The input string.
	 * @param int $flags A bitmask of flags (default is ENT_COMPAT | ENT_HTML401).
	 * @param string $encoding The character encoding (default is UTF-8).
	 * @param bool $doubleEncode Whether to double encode (default is true).
	 * @return string The encoded string with HTML entities.
	 */
	public function encodeHtmlEntities(string $input, int $flags = ENT_COMPAT | ENT_HTML401, string $encoding = 'UTF-8', bool $doubleEncode = true): string
	{
		return htmlentities($input, $flags, $encoding, $doubleEncode);
	}

	/**
	 * Encodes special HTML characters.
	 *
	 * @param string $input The input string.
	 * @param int $flags A bitmask of flags (default is ENT_COMPAT | ENT_HTML401).
	 * @param string $encoding The character encoding (default is UTF-8).
	 * @param bool $doubleEncode Whether to double encode (default is true).
	 * @return string The encoded string with special HTML characters.
	 */
	public function encodeSpecialChars(string $input, int $flags = ENT_COMPAT | ENT_HTML401, string $encoding = 'UTF-8', bool $doubleEncode = true): string
	{
		return htmlspecialchars($input, $flags, $encoding, $doubleEncode);
	}

	/**
	 * Checks if a string is valid for the specified encoding.
	 *
	 * @param string $input The input string.
	 * @param string|null $encoding The expected encoding (optional).
	 * @return bool True if valid, false otherwise.
	 */
	public function checkEncoding(string $input, ?string $encoding = null): bool
	{
		return mb_check_encoding($input, $encoding);
	}

	/**
	 * Converts a string's case based on the specified mode.
	 *
	 * @param string $input The input string.
	 * @param int $mode The mode for case conversion (e.g., MB_CASE_UPPER, MB_CASE_LOWER).
	 * @param string|null $encoding The character encoding (optional).
	 * @return string The string with converted case.
	 */
	public function convertCase(string $input, int $mode, ?string $encoding = null): string
	{
		return mb_convert_case($input, $mode, $encoding);
	}

	/**
	 * Converts a string from one encoding to another.
	 *
	 * @param string $input The input string.
	 * @param string $toEncoding The target encoding.
	 * @param string|null $fromEncoding The source encoding (optional).
	 * @return string The converted string.
	 */
	public function convertEncoding(string $input, string $toEncoding, ?string $fromEncoding = null): string
	{
		return mb_convert_encoding($input, $toEncoding, $fromEncoding);
	}

	/**
	 * Detects the encoding of a string.
	 *
	 * @param string $input The input string.
	 * @param array|string|null $encodings List of encodings to detect (optional).
	 * @param bool $strict Whether to use strict encoding detection (default is false).
	 * @return string|false The detected encoding or false if not found.
	 */
	public function detectEncoding(string $input, array|string|null $encodings = null, bool $strict = false): string|false
	{
		return mb_detect_encoding($input, $encodings, $strict);
	}

	/**
	 * Sets or retrieves the internal encoding.
	 *
	 * @param string|null $encoding The character encoding to set (optional).
	 * @return string The current internal encoding.
	 */
	public function setInternalEncoding(?string $encoding = null): string
	{
		return mb_internal_encoding($encoding);
	}

	/**
	 * Lists all supported encodings.
	 *
	 * @return array The list of supported encodings.
	 */
	public function listEncodings(): array
	{
		return mb_list_encodings();
	}

	/**
	 * Gets the length of a string in a specified encoding.
	 *
	 * @param string $input The input string.
	 * @param string|null $encoding The character encoding (optional).
	 * @return int The length of the string.
	 */
	public function getLength(string $input, ?string $encoding = null): int
	{
		return mb_strlen($input, $encoding);
	}

	/**
	 * Finds the position of the first occurrence of a substring in a string.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle The substring to search for.
	 * @param int $offset The offset to start searching from (default is 0).
	 * @param string|null $encoding The character encoding (optional).
	 * @return int|false The position of the first occurrence or false if not found.
	 */
	public function findSubstring(string $haystack, string $needle, int $offset = 0, ?string $encoding = null): int|false
	{
		return mb_strpos($haystack, $needle, $offset, $encoding);
	}

	/**
	 * Finds the position of the last occurrence of a substring in a string.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle The substring to search for.
	 * @param string|null $encoding The character encoding (optional).
	 * @return int|false The position of the last occurrence or false if not found.
	 */
	public function findLastSubstring(string $haystack, string $needle, ?string $encoding = null): int|false
	{
		return mb_strrpos($haystack, $needle, $encoding);
	}

	/**
	 * Converts a string to lowercase using a specified encoding.
	 *
	 * @param string $input The input string.
	 * @param string|null $encoding The character encoding (optional).
	 * @return string The string in lowercase.
	 */
	public function toLower(string $input, ?string $encoding = null): string
	{
		return mb_strtolower($input, $encoding);
	}

	/**
	 * Converts a string to uppercase using a specified encoding.
	 *
	 * @param string $input The input string.
	 * @param string|null $encoding The character encoding (optional).
	 * @return string The string in uppercase.
	 */
	public function toUpper(string $input, ?string $encoding = null): string
	{
		return mb_strtoupper($input, $encoding);
	}

	/**
	 * Extracts a substring from a string.
	 *
	 * @param string $input The input string.
	 * @param int $start The starting position.
	 * @param int|null $length The length of the substring (optional).
	 * @param string|null $encoding The character encoding (optional).
	 * @return string The extracted substring.
	 */
	public function getSubstring(string $input, int $start, ?int $length = null, ?string $encoding = null): string
	{
		return mb_substr($input, $start, $length, $encoding);
	}

	/**
	 * Strips slashes from a string.
	 *
	 * @param string $input The input string.
	 * @return string The string with slashes removed.
	 */
	public function stripSlashes(string $input): string
	{
		return stripslashes($input);
	}

	/**
	 * Encodes a string for use in a URL.
	 *
	 * @param string $input The input string.
	 * @return string The URL-encoded string.
	 */
	public function urlEncode(string $input): string
	{
		return urlencode($input);
	}

	/**
	 * Decodes a URL-encoded string.
	 *
	 * @param string $input The URL-encoded string.
	 * @return string The decoded string.
	 */
	public function urlDecode(string $input): string
	{
		return urldecode($input);
	}
}
