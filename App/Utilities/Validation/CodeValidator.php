<?php

namespace App\Utilities\Validation;

use App\Abstracts\Data\Validator;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Traits\Patterns\Validation\CodeValidationPatternsTrait;

/**
 * Class CodeValidator
 *
 * Provides validation methods for code-related fields using regex patterns.
 */
class CodeValidator extends Validator
{
	use PatternTrait, CodeValidationPatternsTrait;

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
	 * Validate if the input is a valid hexadecimal number with 0x prefix.
	 *
	 * @param string $input The input hexadecimal number.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateHexadecimal(string $input): bool
	{
		return $this->match($this->getPatterns('hexadecimal'), $input) === 1;
	}

	/**
	 * Validate if the input contains only hexadecimal digits.
	 *
	 * @param string $input The input hex string (no 0x prefix).
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateHexOnly(string $input): bool
	{
		return $this->match($this->getPatterns('hex_only'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid binary number.
	 *
	 * @param string $input The input binary number.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateBinary(string $input): bool
	{
		return $this->match($this->getPatterns('binary'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid octal number.
	 *
	 * @param string $input The input octal number.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateOctal(string $input): bool
	{
		return $this->match($this->getPatterns('octal'), $input) === 1;
	}

	/**
	 * Validate if the input contains an HTML comment.
	 *
	 * @param string $input The input string containing HTML.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateHtmlComment(string $input): bool
	{
		return $this->match($this->getPatterns('html_comment'), $input) === 1;
	}
}
