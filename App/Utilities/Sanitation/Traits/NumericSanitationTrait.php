<?php

namespace App\Utilities\Sanitation\Traits;

trait NumericSanitationTrait
{
	use BaseSanitationTrait;

	/**
	 * Normalize numeric inputs for consistent decimal and thousand separators.
	 *
	 * @param string $numeric
	 * @return string
	 */
	public function normalizeNumeric(string $numeric): string
	{
		// Convert commas to periods for decimal points
		$numeric = str_replace(',', '.', $numeric);
		// Remove thousand separators (assuming European format with periods)
		return str_replace('.', '', $numeric);
	}

	/**
	 * Sanitize currency values by removing symbols and ensuring correct formatting.
	 *
	 * @param string $currency
	 * @return string
	 */
	public function sanitizeCurrency(string $currency): string
	{
		return $this->sanitizeNumeric($currency);
	}

	/**
	 * Round numeric values to specified precision.
	 *
	 * @param float $value
	 * @param int $precision
	 * @return float
	 */
	public function roundNumeric(float $value, int $precision = 2): float
	{
		return round($value, $precision);
	}

	/**
	 * Sanitize and normalize scientific notation.
	 *
	 * @param string $value
	 * @return string
	 */
	public function sanitizeScientificNotation(string $value): string
	{
		if (preg_match('/^[+-]?[0-9]*\.?[0-9]+([eE][+-]?[0-9]+)?$/', $value)) {
			return $value;
		}
		return '';
	}
}
