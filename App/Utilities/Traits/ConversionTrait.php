<?php

namespace App\Utilities\Traits;

/**
 * Trait ConversionTrait
 *
 * Provides utility functions for converting data types in PHP.
 */
trait ConversionTrait
{
	// Basic Type Conversions

	/**
	 * Converts a variable to a boolean.
	 *
	 * @param mixed $input The input variable to convert.
	 * @return bool The boolean value of the input.
	 */
	public function toBool(mixed $input): bool
	{
		return boolval($input);
	}

	/**
	 * Converts a variable to a float.
	 *
	 * @param mixed $input The input variable to convert.
	 * @return float The float value of the input.
	 */
	public function toFloat(mixed $input): float
	{
		return floatval($input);
	}

	/**
	 * Converts a variable to an integer.
	 *
	 * @param mixed $input The input variable to convert.
	 * @return int The integer value of the input.
	 */
	public function toInt(mixed $input): int
	{
		return intval($input);
	}

	/**
	 * Changes the type of a variable.
	 *
	 * @param mixed $input The variable to change.
	 * @param string $type The target type (e.g., 'bool', 'int', 'float', 'string').
	 * @return bool True on success, false on failure.
	 */
	public function changeType(mixed &$input, string $type): bool
	{
		return settype($input, $type);
	}

	/**
	 * Converts a variable to a string.
	 *
	 * @param mixed $input The input variable to convert.
	 * @return string The string value of the input.
	 */
	public function toString(mixed $input): string
	{
		return strval($input);
	}

	// JSON Conversion

	/**
	 * Converts a variable to its JSON representation.
	 *
	 * @param mixed $input The input variable to convert.
	 * @param int $flags Optional flags for JSON encoding.
	 * @return string The JSON encoded string.
	 */
	public function toJson(mixed $input, int $flags = 0): string
	{
		return json_encode($input, $flags);
	}

	/**
	 * Decodes a JSON string to an associative array or object.
	 *
	 * @param string $json The JSON string to decode.
	 * @param bool $assoc Whether to return an associative array.
	 * @param int $depth Maximum depth of the decoding process.
	 * @param int $flags Optional flags for JSON decoding.
	 * @return mixed The decoded value, typically an array or object.
	 */
	public function fromJson(string $json, bool $assoc = true, int $depth = 512, int $flags = 0): mixed
	{
		return json_decode($json, $assoc, $depth, $flags);
	}

	// Date and Time Conversions

	/**
	 * Converts a string to a DateTime object.
	 *
	 * @param string $input The date string to convert.
	 * @param string $format The format of the date string.
	 * @return \DateTime|false The DateTime object, or false on failure.
	 */
	public function toDateTime(string $input, string $format = 'Y-m-d H:i:s'): \DateTime|false
	{
		return \DateTime::createFromFormat($format, $input);
	}

	/**
	 * Converts a DateTime object to a string.
	 *
	 * @param \DateTime $date The DateTime object to convert.
	 * @param string $format The format to convert to.
	 * @return string The formatted date string.
	 */
	public function fromDateTime(\DateTime $date, string $format = 'Y-m-d H:i:s'): string
	{
		return $date->format($format);
	}

	// Array Conversion

	/**
	 * Converts an object or array to a serialized string.
	 *
	 * @param mixed $input The object or array to serialize.
	 * @return string The serialized string.
	 */
	public function serializeData(mixed $input): string
	{
		return serialize($input);
	}

	/**
	 * Unserializes a serialized string back to its original value.
	 *
	 * @param string $input The serialized string.
	 * @return mixed The unserialized value.
	 */
	public function unserializeData(string $input): mixed
	{
		return unserialize($input);
	}

	// Special Formats

	/**
	 * Converts a binary string to hexadecimal representation.
	 *
	 * @param string $input The binary string to convert.
	 * @return string The hexadecimal representation.
	 */
	public function binToHex(string $input): string
	{
		return bin2hex($input);
	}

	/**
	 * Converts a hexadecimal string to binary representation.
	 *
	 * @param string $input The hexadecimal string to convert.
	 * @return string The binary string.
	 */
	public function hexToBin(string $input): string
	{
		return hex2bin($input);
	}

	/**
	 * Converts a string to an array of characters.
	 *
	 * @param string $input The input string.
	 * @return array The array of characters.
	 */
	public function stringToArray(string $input): array
	{
		return str_split($input);
	}

	/**
	 * Converts an array of characters back to a string.
	 *
	 * @param array $input The array of characters.
	 * @return string The resulting string.
	 */
	public function arrayToString(array $input): string
	{
		return implode('', $input);
	}
}
