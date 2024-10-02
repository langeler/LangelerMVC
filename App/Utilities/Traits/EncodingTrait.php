<?php

namespace App\Utilities\Traits;

/**
 * Trait EncodingTrait
 *
 * Provides a set of utility functions for encoding, decoding, and string manipulation operations.
 */
trait EncodingTrait
{
	// String Escaping and Slashes

	/**
	 * Adds slashes to a string.
	 *
	 * @param string $input The input string.
	 * @return string The string with added slashes.
	 */
	public function addSlashesToString(string $input): string
	{
		return addslashes($input);
	}

	/**
	 * Strips slashes from a string.
	 *
	 * @param string $input The input string.
	 * @return string The string with slashes removed.
	 */
	public function stripSlashesFromString(string $input): string
	{
		return stripslashes($input);
	}

	// Base64 Encoding/Decoding

	/**
	 * Encodes a string in base64.
	 *
	 * @param string $data The input string.
	 * @return string The base64 encoded string.
	 */
	public function base64EncodeString(string $data): string
	{
		return base64_encode($data);
	}

	/**
	 * Decodes a base64 encoded string.
	 *
	 * @param string $data The encoded string.
	 * @param bool $strict Whether to apply strict decoding.
	 * @return string|false The decoded string or false on failure.
	 */
	public function base64DecodeString(string $data, bool $strict = false): string|false
	{
		return base64_decode($data, $strict);
	}

	// URL Encoding/Decoding

	/**
	 * Encodes a string for use in a URL.
	 *
	 * @param string $input The input string.
	 * @return string The URL-encoded string.
	 */
	public function encodeStringForUrl(string $input): string
	{
		return urlencode($input);
	}

	/**
	 * Decodes a URL-encoded string.
	 *
	 * @param string $input The URL-encoded string.
	 * @return string The decoded string.
	 */
	public function decodeStringFromUrl(string $input): string
	{
		return urldecode($input);
	}

	/**
	 * Encodes a string for use in a URL, encoding spaces as `%20`.
	 *
	 * @param string $input The input string.
	 * @return string The raw URL-encoded string.
	 */
	public function encodeStringForRawUrl(string $input): string
	{
		return rawurlencode($input);
	}

	/**
	 * Decodes a raw URL-encoded string.
	 *
	 * @param string $input The raw URL-encoded string.
	 * @return string The decoded string.
	 */
	public function decodeStringFromRawUrl(string $input): string
	{
		return rawurldecode($input);
	}

	// HTML Entity Encoding/Decoding

	/**
	 * Encodes special characters into HTML entities.
	 *
	 * @param string $input The input string.
	 * @param int $flags Flags for encoding.
	 * @param string $encoding The character encoding.
	 * @param bool $doubleEncode Whether to double encode.
	 * @return string The encoded string.
	 */
	public function encodeHtmlEntitiesString(string $input, int $flags = ENT_COMPAT | ENT_HTML401, string $encoding = 'UTF-8', bool $doubleEncode = true): string
	{
		return htmlentities($input, $flags, $encoding, $doubleEncode);
	}

	/**
	 * Encodes special characters into HTML special characters.
	 *
	 * @param string $input The input string.
	 * @param int $flags Flags for encoding.
	 * @param string $encoding The character encoding.
	 * @param bool $doubleEncode Whether to double encode.
	 * @return string The encoded string.
	 */
	public function encodeSpecialCharsString(string $input, int $flags = ENT_COMPAT | ENT_HTML401, string $encoding = 'UTF-8', bool $doubleEncode = true): string
	{
		return htmlspecialchars($input, $flags, $encoding, $doubleEncode);
	}

	/**
	 * Decodes HTML entities back to their corresponding characters.
	 *
	 * @param string $input The HTML-encoded string.
	 * @param int $flags Flags for decoding.
	 * @param string $encoding The character encoding.
	 * @return string The decoded string.
	 */
	public function decodeHtmlEntitiesString(string $input, int $flags = ENT_COMPAT | ENT_HTML401, string $encoding = 'UTF-8'): string
	{
		return html_entity_decode($input, $flags, $encoding);
	}

	// Quoted-Printable Encoding/Decoding

	/**
	 * Encodes a string in quoted-printable format.
	 *
	 * @param string $input The input string.
	 * @return string The quoted-printable encoded string.
	 */
	public function quotedPrintableEncodeString(string $input): string
	{
		return quoted_printable_encode($input);
	}

	/**
	 * Decodes a quoted-printable encoded string.
	 *
	 * @param string $input The quoted-printable encoded string.
	 * @return string The decoded string.
	 */
	public function quotedPrintableDecodeString(string $input): string
	{
		return quoted_printable_decode($input);
	}

	// Unix-to-Unix Encoding/Decoding

	/**
	 * Encodes a string using UUEncode.
	 *
	 * @param string $input The input string.
	 * @return string The UUEncoded string.
	 */
	public function uuencodeString(string $input): string
	{
		return convert_uuencode($input);
	}

	/**
	 * Decodes a UUEncoded string.
	 *
	 * @param string $input The UUEncoded string.
	 * @return string The decoded string.
	 */
	public function uudecodeString(string $input): string
	{
		return convert_uudecode($input);
	}

	// Multibyte Encoding/Decoding

	/**
	 * Checks if the string is valid for a given encoding.
	 *
	 * @param string $input The input string.
	 * @param string|null $encoding The character encoding.
	 * @return bool True if the string is valid for the encoding, false otherwise.
	 */
	public function isValidEncoding(string $input, ?string $encoding = null): bool
	{
		return mb_check_encoding($input, $encoding);
	}

	/**
	 * Converts the case of a string based on the mode.
	 *
	 * @param string $input The input string.
	 * @param int $mode The case mode (e.g. MB_CASE_UPPER, MB_CASE_LOWER).
	 * @param string|null $encoding The character encoding.
	 * @return string The string with case converted.
	 */
	public function convertStringCase(string $input, int $mode, ?string $encoding = null): string
	{
		return mb_convert_case($input, $mode, $encoding);
	}

	/**
	 * Converts the character encoding of a string.
	 *
	 * @param string $input The input string.
	 * @param string $toEncoding The target encoding.
	 * @param string|null $fromEncoding The source encoding.
	 * @return string The string with the new encoding.
	 */
	public function convertStringEncoding(string $input, string $toEncoding, ?string $fromEncoding = null): string
	{
		return mb_convert_encoding($input, $toEncoding, $fromEncoding);
	}

	/**
	 * Detects the character encoding of a string.
	 *
	 * @param string $input The input string.
	 * @param array|string|null $encodings List of possible encodings.
	 * @param bool $strict Whether to use strict mode.
	 * @return string|false The detected encoding or false on failure.
	 */
	public function detectStringEncoding(string $input, array|string|null $encodings = null, bool $strict = false): string|false
	{
		return mb_detect_encoding($input, $encodings, $strict);
	}

	/**
	 * Sets or gets the internal character encoding.
	 *
	 * @param string|null $encoding The character encoding to set.
	 * @return string The current internal encoding.
	 */
	public function setInternalStringEncoding(?string $encoding = null): string
	{
		return mb_internal_encoding($encoding);
	}

	/**
	 * Lists all supported character encodings.
	 *
	 * @return array The list of supported encodings.
	 */
	public function listSupportedEncodings(): array
	{
		return mb_list_encodings();
	}

	// Multibyte String Operations

	/**
	 * Gets the length of a string in characters.
	 *
	 * @param string $input The input string.
	 * @param string|null $encoding The character encoding.
	 * @return int The length of the string.
	 */
	public function getStringLength(string $input, ?string $encoding = null): int
	{
		return mb_strlen($input, $encoding);
	}

	/**
	 * Finds the first occurrence of a substring in a string.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle The substring to search for.
	 * @param int $offset The position to start searching.
	 * @param string|null $encoding The character encoding.
	 * @return int|false The position of the substring or false if not found.
	 */
	public function findSubstringInString(string $haystack, string $needle, int $offset = 0, ?string $encoding = null): int|false
	{
		return mb_strpos($haystack, $needle, $offset, $encoding);
	}

	/**
	 * Finds the last occurrence of a substring in a string.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle The substring to search for.
	 * @param string|null $encoding The character encoding.
	 * @return int|false The position of the substring or false if not found.
	 */
	public function findLastSubstringInString(string $haystack, string $needle, ?string $encoding = null): int|false
	{
		return mb_strrpos($haystack, $needle, $encoding);
	}

	/**
	 * Converts a string to lowercase.
	 *
	 * @param string $input The input string.
	 * @param string|null $encoding The character encoding.
	 * @return string The string in lowercase.
	 */
	public function convertStringToLower(string $input, ?string $encoding = null): string
	{
		return mb_strtolower($input, $encoding);
	}

	/**
	 * Converts a string to uppercase.
	 *
	 * @param string $input The input string.
	 * @param string|null $encoding The character encoding.
	 * @return string The string in uppercase.
	 */
	public function convertStringToUpper(string $input, ?string $encoding = null): string
	{
		return mb_strtoupper($input, $encoding);
	}

	/**
	 * Gets a substring of a string.
	 *
	 * @param string $input The input string.
	 * @param int $start The starting position.
	 * @param int|null $length The length of the substring.
	 * @param string|null $encoding The character encoding.
	 * @return string The substring.
	 */
	public function getSubstringOfString(string $input, int $start, ?int $length = null, ?string $encoding = null): string
	{
		return mb_substr($input, $start, $length, $encoding);
	}
}
