<?php

namespace App\Utilities\Sanitation;

use App\Abstracts\Data\Sanitizer;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Traits\Patterns\Sanitation\NumericSanitationPatternsTrait;

/**
 * Class NumericSanitizer
 *
 * Provides sanitation methods for numeric-related fields using regex patterns.
 */
class NumericSanitizer extends Sanitizer
{
	use PatternTrait, NumericSanitationPatternsTrait;

	/**
	 * === ENTRY POINT: sanitize method (Do not modify) ===
	 *
	 * @param mixed $data The data to be sanitized.
	 * @return array The sanitized data array.
	 */
	protected function clean(mixed $data): array
	{
		return $this->handle($data);
	}

	// === Basic Sanitation Using Patterns ===

	/**
	 * Sanitize a string to allow only positive integers.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeIntPositive(string $input): string
	{
		return $this->replace($this->getPattern('int_positive'), '', $input);
	}

	/**
	 * Sanitize a string to allow negative integers.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeIntNegative(string $input): string
	{
		return $this->replace($this->getPattern('int_negative'), '', $input);
	}

	/**
	 * Sanitize a string to allow both positive and negative integers.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeInt(string $input): string
	{
		return $this->replace($this->getPattern('int'), '', $input);
	}

	/**
	 * Sanitize a string to allow only positive float values.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeFloatPositive(string $input): string
	{
		return $this->replace($this->getPattern('float_positive'), '', $input);
	}

	/**
	 * Sanitize a string to allow negative float values.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeFloatNegative(string $input): string
	{
		return $this->replace($this->getPattern('float_negative'), '', $input);
	}

	/**
	 * Sanitize a string to allow both positive and negative float values.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeFloat(string $input): string
	{
		return $this->replace($this->getPattern('float'), '', $input);
	}

	/**
	 * Sanitize a string to allow only scientific notation values.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeScientific(string $input): string
	{
		return $this->replace($this->getPattern('scientific'), '', $input);
	}

	/**
	 * Sanitize a string to allow percentage values.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizePercentage(string $input): string
	{
		return $this->replace($this->getPattern('percentage'), '', $input);
	}

	/**
	 * Sanitize a string to allow values in the range 1 to 100.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeRange1To100(string $input): string
	{
		return $this->replace($this->getPattern('range_1_to_100'), '', $input);
	}

	/**
	 * Sanitize a string to allow negative values in the range -1 to -100.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeNegativeRange1To100(string $input): string
	{
		return $this->replace($this->getPattern('negative_range_1_to_100'), '', $input);
	}
}
