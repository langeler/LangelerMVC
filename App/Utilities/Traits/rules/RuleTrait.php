<?php

namespace App\Utilities\Traits\Rules;

/**
 * Trait RulesTrait
 *
 * Provides utility methods for validating input values against common rules.
 * These rules can be used to validate strings, numbers, and arrays, ensuring
 * that the input meets specified criteria.
 */
trait RulesTrait
{
	/**
	 * Ensure a value is not null.
	 *
	 * @param mixed $input The value to check.
	 * @return bool True if the value is not null, otherwise false.
	 */
	public function ruleRequire(mixed $input): bool
	{
		return isset($input);
	}

	/**
	 * Ensure a numeric value meets a minimum threshold.
	 *
	 * @param float|int $input The value to check.
	 * @param float|int $min The minimum threshold.
	 * @return bool True if the value is greater than or equal to the minimum, otherwise false.
	 */
	public function ruleMin(float|int $input, float|int $min): bool
	{
		return $input >= $min;
	}

	/**
	 * Ensure a numeric value does not exceed a maximum threshold.
	 *
	 * @param float|int $input The value to check.
	 * @param float|int $max The maximum threshold.
	 * @return bool True if the value is less than or equal to the maximum, otherwise false.
	 */
	public function ruleMax(float|int $input, float|int $max): bool
	{
		return $input <= $max;
	}

	/**
	 * Ensure a numeric value is between a minimum and maximum (inclusive).
	 *
	 * @param float|int $input The value to check.
	 * @param float|int $min The minimum threshold.
	 * @param float|int $max The maximum threshold.
	 * @return bool True if the value is within the range, otherwise false.
	 */
	public function ruleBetween(float|int $input, float|int $min, float|int $max): bool
	{
		return $input >= $min && $input <= $max;
	}

	/**
	 * Ensure a numeric value is less than a given threshold.
	 *
	 * @param float|int $input The value to check.
	 * @param float|int $threshold The threshold to compare against.
	 * @return bool True if the value is less than the threshold, otherwise false.
	 */
	public function ruleLess(float|int $input, float|int $threshold): bool
	{
		return $input < $threshold;
	}

	/**
	 * Ensure a numeric value is greater than a given threshold.
	 *
	 * @param float|int $input The value to check.
	 * @param float|int $threshold The threshold to compare against.
	 * @return bool True if the value is greater than the threshold, otherwise false.
	 */
	public function ruleGreater(float|int $input, float|int $threshold): bool
	{
		return $input > $threshold;
	}

	/**
	 * Ensure string length is at least a minimum value.
	 *
	 * @param string $input The string to check.
	 * @param int $min The minimum length.
	 * @return bool True if the string length is greater than or equal to the minimum, otherwise false.
	 */
	public function ruleMinLength(string $input, int $min): bool
	{
		return mb_strlen($input) >= $min;
	}

	/**
	 * Ensure string length does not exceed a maximum value.
	 *
	 * @param string $input The string to check.
	 * @param int $max The maximum length.
	 * @return bool True if the string length is less than or equal to the maximum, otherwise false.
	 */
	public function ruleMaxLength(string $input, int $max): bool
	{
		return mb_strlen($input) <= $max;
	}

	/**
	 * Ensure string length is between a minimum and maximum value.
	 *
	 * @param string $input The string to check.
	 * @param int $min The minimum length.
	 * @param int $max The maximum length.
	 * @return bool True if the string length is within the range, otherwise false.
	 */
	public function ruleLengthBetween(string $input, int $min, int $max): bool
	{
		return mb_strlen($input) >= $min && mb_strlen($input) <= $max;
	}

	/**
	 * Ensure a value exists in an array (whitelist).
	 *
	 * @param mixed $input The value to check.
	 * @param array $array The array to check against.
	 * @return bool True if the value exists in the array, otherwise false.
	 */
	public function ruleInArray(mixed $input, array $array): bool
	{
		return in_array($input, $array, true);
	}

	/**
	 * Ensure a value does not exist in an array (blacklist).
	 *
	 * @param mixed $input The value to check.
	 * @param array $array The array to check against.
	 * @return bool True if the value does not exist in the array, otherwise false.
	 */
	public function ruleNotInArray(mixed $input, array $array): bool
	{
		return !in_array($input, $array, true);
	}

	/**
	 * Ensure a value is an integer.
	 *
	 * @param mixed $input The value to check.
	 * @return bool True if the value is an integer, otherwise false.
	 */
	public function ruleIsInt(mixed $input): bool
	{
		return is_int($input);
	}

	/**
	 * Ensure a value is a float.
	 *
	 * @param mixed $input The value to check.
	 * @return bool True if the value is a float, otherwise false.
	 */
	public function ruleIsFloat(mixed $input): bool
	{
		return is_float($input);
	}

	/**
	 * Ensure a value is a string.
	 *
	 * @param mixed $input The value to check.
	 * @return bool True if the value is a string, otherwise false.
	 */
	public function ruleIsString(mixed $input): bool
	{
		return is_string($input);
	}

	/**
	 * Ensure a value is a boolean.
	 *
	 * @param mixed $input The value to check.
	 * @return bool True if the value is a boolean, otherwise false.
	 */
	public function ruleIsBoolean(mixed $input): bool
	{
		return is_bool($input);
	}

	/**
	 * Ensure an array is associative.
	 *
	 * @param array $input The array to check.
	 * @return bool True if the array is associative, otherwise false.
	 */
	public function ruleIsAssociativeArray(array $input): bool
	{
		return array_keys($input) !== range(0, count($input) - 1);
	}

	/**
	 * Ensure all elements in an array are unique.
	 *
	 * @param array $input The array to check.
	 * @return bool True if all elements in the array are unique, otherwise false.
	 */
	public function ruleArrayUnique(array $input): bool
	{
		return count($input) === count(array_unique($input));
	}

	/**
	 * Ensure a value is divisible by another value.
	 *
	 * @param int $input The value to check.
	 * @param int $divisor The divisor to check against.
	 * @return bool True if the value is divisible by the divisor, otherwise false.
	 */
	public function ruleDivisibleBy(int $input, int $divisor): bool
	{
		return $divisor !== 0 && $input % $divisor === 0;
	}

	/**
	 * Ensure a trimmed string is not empty.
	 *
	 * @param string $input The string to check.
	 * @return bool True if the string is not empty after trimming, otherwise false.
	 */
	public function ruleNotEmpty(string $input): bool
	{
		return trim($input) !== '';
	}

	/**
	 * Ensure a numeric value matches a step.
	 *
	 * @param float|int $input The value to check.
	 * @param float|int $step The step value.
	 * @param float|int $base The base value for the step calculation.
	 * @return bool True if the value matches the step, otherwise false.
	 */
	public function ruleStep(float|int $input, float|int $step, float|int $base = 0): bool
	{
		return fmod($input - $base, $step) === 0.0;
	}

	/**
	 * Ensure an array meets size constraints.
	 *
	 * @param array $input The array to check.
	 * @param int $min The minimum size.
	 * @param int $max The maximum size.
	 * @return bool True if the array size is within the range, otherwise false.
	 */
	public function ruleArraySize(array $input, int $min, int $max): bool
	{
		return count($input) >= $min && count($input) <= $max;
	}

	/**
	 * Ensure numbers in an array are sequential.
	 *
	 * @param array $numbers The array of numbers to check.
	 * @param bool $allowGaps Whether to allow gaps in the sequence.
	 * @return bool True if the numbers are sequential, otherwise false.
	 */
	public function ruleSequential(array $numbers, bool $allowGaps = false): bool
	{
		sort($numbers);
		return $allowGaps
			? count($numbers) === count(array_unique($numbers))
			: $numbers === range(min($numbers), max($numbers));
	}

	/**
	 * Ensure a string starts with a specific prefix.
	 *
	 * @param string $input The string to check.
	 * @param string $prefix The prefix to match.
	 * @return bool True if the string starts with the prefix, otherwise false.
	 */
	public function ruleStartsWith(string $input, string $prefix): bool
	{
		return str_starts_with($input, $prefix);
	}

	/**
	 * Ensure a string ends with a specific suffix.
	 *
	 * @param string $input The string to check.
	 * @param string $suffix The suffix to match.
	 * @return bool True if the string ends with the suffix, otherwise false.
	 */
	public function ruleEndsWith(string $input, string $suffix): bool
	{
		return str_ends_with($input, $suffix);
	}

	/**
	 * Ensure a value is a positive number.
	 *
	 * @param float|int $input The value to check.
	 * @return bool True if the value is positive, otherwise false.
	 */
	public function rulePositive(float|int $input): bool
	{
		return $input > 0;
	}

	/**
	 * Ensure a value is a negative number.
	 *
	 * @param float|int $input The value to check.
	 * @return bool True if the value is negative, otherwise false.
	 */
	public function ruleNegative(float|int $input): bool
	{
		return $input < 0;
	}

	/**
	 * Ensure an array has at least one element.
	 *
	 * @param array $input The array to check.
	 * @return bool True if the array is not empty, otherwise false.
	 */
	public function ruleArrayNotEmpty(array $input): bool
	{
		return count($input) > 0;
	}
}
