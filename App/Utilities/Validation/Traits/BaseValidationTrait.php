<?php

namespace App\Utilities\Validation\Traits;

trait BaseValidationTrait
{
	/**
	 * Validate that a value is not empty.
	 *
	 * @param mixed $value
	 * @return bool
	 */
	public function validateNotEmpty($value): bool
	{
		return !empty(trim((string)$value));
	}

	/**
	 * Validate the length of a string or array.
	 *
	 * @param mixed $value
	 * @param int $min
	 * @param int $max
	 * @return bool
	 */
	public function validateLength($value, int $min = 0, int $max = PHP_INT_MAX): bool
	{
		$length = is_array($value) ? count($value) : strlen((string)$value);
		return $length >= $min && $length <= $max;
	}

	/**
	 * Validate that a value matches a specific pattern.
	 *
	 * @param string $value
	 * @param string $pattern
	 * @return bool
	 */
	public function validatePattern(string $value, string $pattern): bool
	{
		return preg_match($pattern, $value) === 1;
	}

	/**
	 * Validate that a resource exists (file, key, etc.).
	 *
	 * @param mixed $resource
	 * @return bool
	 */
	public function validateExists($resource): bool
	{
		if (is_string($resource)) {
			return file_exists($resource);
		}
		if (is_array($resource)) {
			return !empty($resource);
		}
		return false;
	}

	/**
	 * Validate that a number is within a specified range.
	 *
	 * @param float|int $value
	 * @param float|int $min
	 * @param float|int $max
	 * @return bool
	 */
	public function validateRange($value, $min = PHP_FLOAT_MIN, $max = PHP_FLOAT_MAX): bool
	{
		return $value >= $min && $value <= $max;
	}
}
