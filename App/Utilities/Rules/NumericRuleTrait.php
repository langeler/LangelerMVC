<?php

namespace App\Utilities\Rules;

trait NumericRuleTrait
{
	use BaseRuleTrait;

	// Rule to enforce specific precision (number of decimal places).
	public function rulePrecision(float $value, int $precision): bool
	{
		$pattern = '/^-?\d+(\.\d{1,' . $precision . '})?$/';
		return $this->rulePatternMatch((string)$value, $pattern);
	}

	// Rule to check if the number is positive.
	public function rulePositiveNumber(float $value): bool
	{
		return $value > 0;
	}

	// Rule for integer validation.
	public function ruleInteger(float $value): bool
	{
		return floor($value) == $value;
	}
}
