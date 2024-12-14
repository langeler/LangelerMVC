<?php

namespace App\Utilities\Traits\Rules;

use DateTime;
use DateTimeZone;

trait RulesTrait
{
	// Rule: Ensure a value is not null
	public function ruleRequire(mixed $input): bool
	{
		return isset($input);
	}

	// Rule: Ensure a numeric value meets a minimum threshold
	public function ruleMin(float|int $input, float|int $min): bool
	{
		return $input >= $min;
	}

	// Rule: Ensure a numeric value does not exceed a maximum threshold
	public function ruleMax(float|int $input, float|int $max): bool
	{
		return $input <= $max;
	}

	// Rule: Ensure a numeric value is between a minimum and maximum (inclusive)
	public function ruleBetween(float|int $input, float|int $min, float|int $max): bool
	{
		return $input >= $min && $input <= $max;
	}

	// Rule: Ensure a numeric value is less than a given threshold
	public function ruleLess(float|int $input, float|int $threshold): bool
	{
		return $input < $threshold;
	}

	// Rule: Ensure a numeric value is greater than a given threshold
	public function ruleGreater(float|int $input, float|int $threshold): bool
	{
		return $input > $threshold;
	}

	// Rule: Ensure string length is at least a minimum
	public function ruleMinLength(string $input, int $min): bool
	{
		return mb_strlen($input) >= $min;
	}

	// Rule: Ensure string length does not exceed a maximum
	public function ruleMaxLength(string $input, int $max): bool
	{
		return mb_strlen($input) <= $max;
	}

	// Rule: Ensure string length is between a minimum and maximum
	public function ruleLengthBetween(string $input, int $min, int $max): bool
	{
		return mb_strlen($input) >= $min && mb_strlen($input) <= $max;
	}

	// Rule: Ensure a value exists in an array (whitelist)
	public function ruleInArray(mixed $input, array $array): bool
	{
		return in_array($input, $array, true);
	}

	// Rule: Ensure a value does not exist in an array (blacklist)
	public function ruleNotInArray(mixed $input, array $array): bool
	{
		return !in_array($input, $array, true);
	}

	// Rule: Ensure a date is before another date
	public function ruleDateBefore(string $date, string $referenceDate, string $format = 'Y-m-d'): bool
	{
		return ($d1 = DateTime::createFromFormat($format, $date)) &&
			   ($d2 = DateTime::createFromFormat($format, $referenceDate)) &&
			   $d1 < $d2;
	}

	// Rule: Ensure a date is after another date
	public function ruleDateAfter(string $date, string $referenceDate, string $format = 'Y-m-d'): bool
	{
		return ($d1 = DateTime::createFromFormat($format, $date)) &&
			   ($d2 = DateTime::createFromFormat($format, $referenceDate)) &&
			   $d1 > $d2;
	}

	// Rule: Ensure a value is an integer
	public function ruleIsInt(mixed $input): bool
	{
		return is_int($input);
	}

	// Rule: Ensure a value is a float
	public function ruleIsFloat(mixed $input): bool
	{
		return is_float($input);
	}

	// Rule: Ensure a value is a string
	public function ruleIsString(mixed $input): bool
	{
		return is_string($input);
	}

	// Rule: Ensure a value is a boolean
	public function ruleIsBoolean(mixed $input): bool
	{
		return is_bool($input);
	}

	// Rule: Ensure an array is associative
	public function ruleIsAssociativeArray(array $input): bool
	{
		return array_keys($input) !== range(0, count($input) - 1);
	}

	// Rule: Ensure a value is a valid date in a given format
	public function ruleIsValidDate(string $date, string $format = 'Y-m-d'): bool
	{
		return ($d = DateTime::createFromFormat($format, $date)) && $d->format($format) === $date;
	}

	// Rule: Ensure all elements in an array are unique
	public function ruleArrayUnique(array $input): bool
	{
		return count($input) === count(array_unique($input));
	}

	// Rule: Ensure a value is divisible by another value
	public function ruleDivisibleBy(int $input, int $divisor): bool
	{
		return $divisor !== 0 && $input % $divisor === 0;
	}

	// Rule: Ensure a trimmed string is not empty
	public function ruleNotEmpty(string $input): bool
	{
		return trim($input) !== '';
	}

	// Rule: Ensure a numeric value matches a step
	public function ruleStep(float|int $input, float|int $step, float|int $base = 0): bool
	{
		return fmod($input - $base, $step) === 0.0;
	}

	// Rule: Ensure an array meets size constraints
	public function ruleArraySize(array $input, int $min, int $max): bool
	{
		return count($input) >= $min && count($input) <= $max;
	}

	// Rule: Ensure numbers in an array are sequential
	public function ruleSequential(array $numbers, bool $allowGaps = false): bool
	{
		sort($numbers);
		return $allowGaps ? count($numbers) === count(array_unique($numbers))
						  : $numbers === range(min($numbers), max($numbers));
	}

	// Rule: Ensure a string starts with a specific prefix
	public function ruleStartsWith(string $input, string $prefix): bool
	{
		return str_starts_with($input, $prefix);
	}

	// Rule: Ensure a string ends with a specific suffix
	public function ruleEndsWith(string $input, string $suffix): bool
	{
		return str_ends_with($input, $suffix);
	}

	// Rule: Ensure a value is a positive number
	public function rulePositive(float|int $input): bool
	{
		return $input > 0;
	}

	// Rule: Ensure a value is a negative number
	public function ruleNegative(float|int $input): bool
	{
		return $input < 0;
	}

	// Rule: Ensure an array has at least one element
	public function ruleArrayNotEmpty(array $input): bool
	{
		return count($input) > 0;
	}
}
