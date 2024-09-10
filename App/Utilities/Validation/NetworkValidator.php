<?php

namespace App\Utilities\Validation;

use App\Abstracts\Validator;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Traits\Patterns\Validation\NetworkValidationPatternsTrait;

/**
 * Class NetworkValidator
 *
 * Provides validation methods for network and web-related data using regex patterns.
 */
class NetworkValidator extends Validator
{
	use PatternTrait, NetworkValidationPatternsTrait;

	/**
	 * === ENTRY POINT: validate method (Do not modify) ===
	 *
	 * @param mixed $data The data to be validated.
	 * @return array The validated data array.
	 */
	protected function validate(mixed $data): array
	{
		return $this->handle($data);
	}

	// === Basic Validation Using Patterns ===

	/**
	 * Validate if the input is a valid slug.
	 *
	 * @param string $input The input slug.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateSlug(string $input): bool
	{
		return $this->match($this->getPatterns('slug'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid HTTP/HTTPS URL.
	 *
	 * @param string $input The input URL.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateUrlHttpHttps(string $input): bool
	{
		return $this->match($this->getPatterns('url_http_https'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid URL with an optional port.
	 *
	 * @param string $input The input URL.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateUrlWithPort(string $input): bool
	{
		return $this->match($this->getPatterns('url_with_port'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid URL with an optional query string.
	 *
	 * @param string $input The input URL.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateUrlWithQuery(string $input): bool
	{
		return $this->match($this->getPatterns('url_with_query'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid FTP URL.
	 *
	 * @param string $input The input FTP URL.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateFtpUrl(string $input): bool
	{
		return $this->match($this->getPatterns('ftp_url'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid Google Drive URL.
	 *
	 * @param string $input The input Google Drive URL.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateGoogleDriveUrl(string $input): bool
	{
		return $this->match($this->getPatterns('google_drive_url'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid Dropbox URL.
	 *
	 * @param string $input The input Dropbox URL.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateDropboxUrl(string $input): bool
	{
		return $this->match($this->getPatterns('dropbox_url'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid IPv4 address.
	 *
	 * @param string $input The input IPv4 address.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateIpv4Address(string $input): bool
	{
		return $this->match($this->getPatterns('ipv4_address'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid IPv6 address.
	 *
	 * @param string $input The input IPv6 address.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateIpv6Address(string $input): bool
	{
		return $this->match($this->getPatterns('ipv6_address'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid US ZIP code.
	 *
	 * @param string $input The input ZIP code.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateZipCodeUs(string $input): bool
	{
		return $this->match($this->getPatterns('zip_code_us'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid UK ZIP code.
	 *
	 * @param string $input The input ZIP code.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateZipCodeUk(string $input): bool
	{
		return $this->match($this->getPatterns('zip_code_uk'), $input) === 1;
	}
}
