<?php

namespace App\Utilities\Validation\Traits;

trait NumericValidationTrait
{
	use BaseValidationTrait;

	/**
	 * Validate that a number is an integer.
	 *
	 * @param float|int $number
	 * @return bool
	 */
	public function validateInteger($number): bool
	{
		return filter_var($number, FILTER_VALIDATE_INT) !== false;
	}

	/**
	 * Validate that a number is a float.
	 *
	 * @param float|int $number
	 * @return bool
	 */
	public function validateFloat($number): bool
	{
		return filter_var($number, FILTER_VALIDATE_FLOAT) !== false;
	}

	/**
	 * Validate that a number is positive.
	 *
	 * @param float|int $number
	 * @return bool
	 */
	public function validatePositive($number): bool
	{
		return $number > 0;
	}

	/**
	 * Validate that a number has a specific precision (number of decimal places).
	 *
	 * @param float $number
	 * @param int $precision
	 * @return bool
	 */
	public function validatePrecision(float $number, int $precision): bool
	{
		return preg_match('/^-?\d+(\.\d{1,' . $precision . '})?$/', (string)$number) === 1;
	}
}
