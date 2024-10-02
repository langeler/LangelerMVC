<?php

namespace App\Utilities\Validation;

use App\Abstracts\Data\Validator;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Traits\Patterns\Validation\NumericValidationPatternsTrait;

/**
 * Class NumericValidator
 *
 * Provides validation methods for various numeric formats using regex patterns.
 */
class NumericValidator extends Validator
{
	use PatternTrait, NumericValidationPatternsTrait;

	/**
	 * === ENTRY POINT: validate method (Do not modify) ===
	 *
	 * @param mixed $data The data to be validated.
	 * @return array The validated data array.
	 */
	protected function verify(mixed $data): array
	{
		return $this->handle($data);
	}

	// === Basic Validation Using Patterns ===

	/**
	 * Validate if the input is a positive integer.
	 *
	 * @param string $input The input value.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateIntPositive(string $input): bool
	{
		return $this->match($this->getPatterns('int_positive'), $input) === 1;
	}

	/**
	 * Validate if the input is a negative integer.
	 *
	 * @param string $input The input value.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateIntNegative(string $input): bool
	{
		return $this->match($this->getPatterns('int_negative'), $input) === 1;
	}

	/**
	 * Validate if the input is an integer (positive or negative).
	 *
	 * @param string $input The input value.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateInt(string $input): bool
	{
		return $this->match($this->getPatterns('int'), $input) === 1;
	}

	/**
	 * Validate if the input is a positive float.
	 *
	 * @param string $input The input value.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateFloatPositive(string $input): bool
	{
		return $this->match($this->getPatterns('float_positive'), $input) === 1;
	}

	/**
	 * Validate if the input is a negative float.
	 *
	 * @param string $input The input value.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateFloatNegative(string $input): bool
	{
		return $this->match($this->getPatterns('float_negative'), $input) === 1;
	}

	/**
	 * Validate if the input is a float (positive or negative).
	 *
	 * @param string $input The input value.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateFloat(string $input): bool
	{
		return $this->match($this->getPatterns('float'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid scientific notation.
	 *
	 * @param string $input The input value.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateScientific(string $input): bool
	{
		return $this->match($this->getPatterns('scientific'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid USD currency format.
	 *
	 * @param string $input The input value.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateCurrencyUsd(string $input): bool
	{
		return $this->match($this->getPatterns('currency_usd'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid Euro currency format.
	 *
	 * @param string $input The input value.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateCurrencyEuro(string $input): bool
	{
		return $this->match($this->getPatterns('currency_euro'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid currency format with no decimals.
	 *
	 * @param string $input The input value.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateCurrencyNoDecimals(string $input): bool
	{
		return $this->match($this->getPatterns('currency_no_decimals'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid percentage.
	 *
	 * @param string $input The input value.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validatePercentage(string $input): bool
	{
		return $this->match($this->getPatterns('percentage'), $input) === 1;
	}
}
