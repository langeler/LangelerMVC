<?php

namespace App\Utilities\Sanitation;

use App\Abstracts\Sanitizer;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Traits\Patterns\Sanitation\TextSanitationPatternsTrait;

/**
 * Class UserSanitizer
 *
 * Provides sanitation methods for user-related fields using regex patterns.
 */
class UserSanitizer extends Sanitizer
{
	use PatternTrait, TextSanitationPatternsTrait;

	/**
	 * === ENTRY POINT: sanitize method (Do not modify) ===
	 *
	 * @param mixed $data The data to be sanitized.
	 * @return array The sanitized data array.
	 */
	protected function sanitize(mixed $data): array
	{
		return $this->handle($data);
	}

	// === Basic Sanitation Using Patterns ===

	/**
	 * Sanitize a full name by removing invalid characters.
	 *
	 * @param string $input The input full name.
	 * @return string The sanitized full name.
	 */
	public function sanitizeFullName(string $input): string
	{
		return $this->replace($this->getPattern('full_name'), '', $input);
	}

	/**
	 * Sanitize a generic SSN by removing invalid characters.
	 *
	 * @param string $input The input SSN.
	 * @return string The sanitized SSN.
	 */
	public function sanitizeSsnGeneric(string $input): string
	{
		return $this->replace($this->getPattern('ssn_generic'), '', $input);
	}

	/**
	 * Sanitize a US SSN by removing invalid characters.
	 *
	 * @param string $input The input SSN.
	 * @return string The sanitized US SSN.
	 */
	public function sanitizeSsnUs(string $input): string
	{
		return $this->replace($this->getPattern('ssn_us'), '', $input);
	}

	/**
	 * Sanitize a US phone number by removing invalid characters.
	 *
	 * @param string $input The input US phone number.
	 * @return string The sanitized US phone number.
	 */
	public function sanitizePhoneUs(string $input): string
	{
		return $this->replace($this->getPattern('phone_us'), '', $input);
	}

	/**
	 * Sanitize an international phone number by removing invalid characters.
	 *
	 * @param string $input The input international phone number.
	 * @return string The sanitized international phone number.
	 */
	public function sanitizeInternationalPhone(string $input): string
	{
		return $this->replace($this->getPattern('international_phone'), '', $input);
	}

	/**
	 * Sanitize a US ZIP code by removing invalid characters.
	 *
	 * @param string $input The input US ZIP code.
	 * @return string The sanitized US ZIP code.
	 */
	public function sanitizeZipCodeUs(string $input): string
	{
		return $this->replace($this->getPattern('zip_code_us'), '', $input);
	}

	/**
	 * Sanitize a UK ZIP code by removing invalid characters.
	 *
	 * @param string $input The input UK ZIP code.
	 * @return string The sanitized UK ZIP code.
	 */
	public function sanitizeZipCodeUk(string $input): string
	{
		return $this->replace($this->getPattern('zip_code_uk'), '', $input);
	}
}
