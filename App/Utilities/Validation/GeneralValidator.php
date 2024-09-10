<?php

namespace App\Utilities\Validation;

use App\Abstracts\Validator;
use App\Utilities\Traits\Filters\FiltrationTrait;
use App\Utilities\Traits\Filters\FilterFlagTrait;
use App\Utilities\Traits\Filters\ValidationFilterTrait;

/**
 * Class GeneralValidator
 *
 * Provides general validation methods using traits for filtration, validation, and filter flags.
 */
class GeneralValidator extends Validator
{
	use FiltrationTrait, ValidationFilterTrait, FilterFlagTrait;

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

	// === Basic Validation Functions ===

	/**
	 * Validates if a value is a boolean.
	 *
	 * @param mixed $input The input to validate.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateBoolean(mixed $input): bool
	{
		return $this->var($input, $this->getFilter('boolean'));
	}

	/**
	 * Validates if a value is a valid email.
	 *
	 * @param string $input The input email.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateEmail(string $input): bool
	{
		return $this->var($input, $this->getFilter('email'));
	}

	/**
	 * Validates if a value is a valid float.
	 *
	 * @param string|float $input The input value.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateFloat(string|float $input): bool
	{
		return $this->var($input, $this->getFilter('float'));
	}

	/**
	 * Validates if a value is a valid integer.
	 *
	 * @param string|int $input The input value.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateInt(string|int $input): bool
	{
		return $this->var($input, $this->getFilter('int'));
	}

	/**
	 * Validates if a value is a valid IP address.
	 *
	 * @param string $input The input value.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateIp(string $input): bool
	{
		return $this->var($input, $this->getFilter('ip'));
	}

	/**
	 * Validates if a value is a valid MAC address.
	 *
	 * @param string $input The input MAC address.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateMac(string $input): bool
	{
		return $this->var($input, $this->getFilter('mac'));
	}

	/**
	 * Validates if a value matches a regular expression.
	 *
	 * @param string $input The input string.
	 * @param string $pattern The regex pattern.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateRegexp(string $input, string $pattern): bool
	{
		return $this->var($input, $this->getFilter('regexp'), ['options' => ['regexp' => $pattern]]);
	}

	/**
	 * Validates if a value is a valid URL.
	 *
	 * @param string $input The input URL.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateUrl(string $input): bool
	{
		return $this->var($input, $this->getFilter('url'));
	}

	/**
	 * Validates if a value is a valid domain.
	 *
	 * @param string $input The input domain.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateDomain(string $input): bool
	{
		return $this->var($input, $this->getFilter('domain'));
	}

	// === Separate Flag Functions (Optional Add-ons) ===

	/**
	 * Applies the allow_fraction flag to a float validation.
	 *
	 * @param string|float $input The input string or float.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function applyAllowFraction(string|float $input): bool
	{
		return $this->var($input, $this->getFilter('float'), $this->getFlag('allow_fraction'));
	}

	/**
	 * Applies the allow_scientific flag to a float validation.
	 *
	 * @param string|float $input The input string or float.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function applyAllowScientific(string|float $input): bool
	{
		return $this->var($input, $this->getFilter('float'), $this->getFlag('allow_scientific'));
	}

	/**
	 * Applies the allow_thousand flag to a float validation.
	 *
	 * @param string|float $input The input string or float.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function applyAllowThousand(string|float $input): bool
	{
		return $this->var($input, $this->getFilter('float'), $this->getFlag('allow_thousand'));
	}

	/**
	 * Validates an IP address with the IPv4 flag.
	 *
	 * @param string $input The input IP address.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function applyIpv4(string $input): bool
	{
		return $this->var($input, $this->getFilter('ip'), $this->getFlag('ipv4'));
	}

	/**
	 * Validates an IP address with the IPv6 flag.
	 *
	 * @param string $input The input IP address.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function applyIpv6(string $input): bool
	{
		return $this->var($input, $this->getFilter('ip'), $this->getFlag('ipv6'));
	}

	/**
	 * Validates an IP address excluding reserved IP ranges.
	 *
	 * @param string $input The input IP address.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function applyNoResRange(string $input): bool
	{
		return $this->var($input, $this->getFilter('ip'), $this->getFlag('no_res_range'));
	}

	/**
	 * Validates an IP address excluding private IP ranges.
	 *
	 * @param string $input The input IP address.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function applyNoPrivRange(string $input): bool
	{
		return $this->var($input, $this->getFilter('ip'), $this->getFlag('no_priv_range'));
	}

	/**
	 * Validates a URL with the path_required flag.
	 *
	 * @param string $input The input URL.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function applyPathRequired(string $input): bool
	{
		return $this->var($input, $this->getFilter('url'), $this->getFlag('path_required'));
	}

	/**
	 * Validates a URL with the query_required flag.
	 *
	 * @param string $input The input URL.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function applyQueryRequired(string $input): bool
	{
		return $this->var($input, $this->getFilter('url'), $this->getFlag('query_required'));
	}
}
