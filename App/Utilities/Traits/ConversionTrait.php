<?php

namespace App\Utilities\Traits;

/**
 * Trait ConversionTrait
 *
 * Provides utility functions for converting data types in PHP.
 */
trait ConversionTrait
{
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
}
