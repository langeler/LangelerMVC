<?php

namespace App\Utilities\Traits\Rules;

use App\Utilities\Traits\{
	ArrayTrait,
	CheckerTrait,
	ConversionTrait,
	EncodingTrait,
	ManipulationTrait,
	TypeCheckerTrait
};

/**
 * Trait RulesTrait
 *
 * Provides utility methods for validating input values against common rules.
 */
trait RuleTrait
{
	use ArrayTrait, CheckerTrait, ConversionTrait, EncodingTrait, ManipulationTrait, TypeCheckerTrait;

	/**
	 * Ensure a value is not null.
	 *
	 * @param mixed $input The value to check.
	 * @return bool True if the value is not null, otherwise false.
	 */
	public function ruleRequire(mixed $input): bool
	{
		return !$this->isNull($input);
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
		return $this->getStringLength($input) >= $min;
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
		return $this->getStringLength($input) <= $max;
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
		$length = $this->getStringLength($input);

		return $length >= $min && $length <= $max;
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
		return $this->isInArray($input, $array, true);
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
		return !$this->isInArray($input, $array, true);
	}

	/**
	 * Ensure a value is an integer.
	 *
	 * @param mixed $input The value to check.
	 * @return bool True if the value is an integer, otherwise false.
	 */
	public function ruleIsInt(mixed $input): bool
	{
		return $this->isInt($input);
	}

	/**
	 * Ensure a value is a float.
	 *
	 * @param mixed $input The value to check.
	 * @return bool True if the value is a float, otherwise false.
	 */
	public function ruleIsFloat(mixed $input): bool
	{
		return $this->isFloat($input);
	}

	/**
	 * Ensure a value is a string.
	 *
	 * @param mixed $input The value to check.
	 * @return bool True if the value is a string, otherwise false.
	 */
	public function ruleIsString(mixed $input): bool
	{
		return $this->isString($input);
	}

	/**
	 * Ensure a value is a boolean.
	 *
	 * @param mixed $input The value to check.
	 * @return bool True if the value is a boolean, otherwise false.
	 */
	public function ruleIsBoolean(mixed $input): bool
	{
		return $this->isBool($input);
	}

	/**
	 * Ensure an array is associative.
	 *
	 * @param array $input The array to check.
	 * @return bool True if the array is associative, otherwise false.
	 */
	public function ruleIsAssociativeArray(array $input): bool
	{
		return !$this->isList($input);
	}

	/**
	 * Ensure all elements in an array are unique.
	 *
	 * @param array $input The array to check.
	 * @return bool True if all elements in the array are unique, otherwise false.
	 */
	public function ruleArrayUnique(array $input): bool
	{
		$seen = [];

		foreach ($input as $value) {
			if ($this->isInArray($value, $seen, true)) {
				return false;
			}

			$seen[] = $value;
		}

		return true;
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
		return $this->trimString($input) !== '';
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
		if ($step == 0.0) {
			return false;
		}

		$steps = ($input - $base) / $step;

		return abs($steps - round($steps)) < 1e-9;
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
		$count = $this->countElements($input);

		return $count >= $min && $count <= $max;
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
		if ($numbers === [] || !$this->ruleArrayUnique($numbers)) {
			return false;
		}

		$normalized = $this->map(
			function (mixed $number): float|int|null {
				if ($this->isInt($number) || $this->isFloat($number)) {
					return $number;
				}

				if ($this->isString($number) && $this->isNumeric($number)) {
					return str_contains($number, '.') || stripos($number, 'e') !== false
						? $this->toFloat($number)
						: $this->toInt($number);
				}

				return null;
			},
			$numbers
		);

		if ($this->any($normalized, fn(mixed $number): bool => $this->isNull($number))) {
			return false;
		}

		$previous = null;

		foreach ($normalized as $number) {
			if ($previous === null) {
				$previous = $number;
				continue;
			}

			if ($allowGaps) {
				if ($number <= $previous) {
					return false;
				}
			} elseif ($number !== $previous + 1) {
				return false;
			}

			$previous = $number;
		}

		return true;
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
		return $this->startsWith($input, $prefix);
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
		return $this->endsWith($input, $suffix);
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
		return $this->countElements($input) > 0;
	}
}
