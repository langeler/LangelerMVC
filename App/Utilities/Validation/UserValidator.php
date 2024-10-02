<?php

namespace App\Utilities\Validation;

use App\Abstracts\Data\Validator;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Traits\Patterns\Validation\UserValidationPatternsTrait;

/**
 * Class UserValidator
 *
 * Provides validation methods for user-related data using regex patterns.
 */
class UserValidator extends Validator
{
	use PatternTrait, UserValidationPatternsTrait;

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
	 * Validate if the input matches a full name pattern.
	 *
	 * @param string $input The input full name.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateFullName(string $input): bool
	{
		return $this->match($this->getPatterns('full_name'), $input) === 1;
	}

	/**
	 * Validate if the input matches a US Social Security Number (SSN) pattern.
	 *
	 * @param string $input The input SSN.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateSsnUs(string $input): bool
	{
		return $this->match($this->getPatterns('ssn_us'), $input) === 1;
	}

	/**
	 * Validate if the input matches a US phone number pattern.
	 *
	 * @param string $input The input phone number.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validatePhoneUs(string $input): bool
	{
		return $this->match($this->getPatterns('phone_us'), $input) === 1;
	}

	/**
	 * Validate if the input matches an international phone number pattern.
	 *
	 * @param string $input The input international phone number.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateInternationalPhone(string $input): bool
	{
		return $this->match($this->getPatterns('international_phone'), $input) === 1;
	}
}
