<?php

namespace App\Utilities\Rules;

trait BaseRuleTrait
{
	/**
	 * Rule for checking the length of a string.
	 *
	 * @param string $input
	 * @param int $min
	 * @param int $max
	 * @return bool
	 */
	public function ruleStringLength(string $input, int $min = 0, int $max = PHP_INT_MAX): bool
	{
		$length = strlen($input);
		return $length >= $min && $length <= $max;
	}

	/**
	 * Rule for pattern matching using regular expressions.
	 *
	 * @param string $input
	 * @param string $pattern
	 * @return bool
	 */
	public function rulePatternMatch(string $input, string $pattern): bool
	{
		return preg_match($pattern, $input) === 1;
	}

	/**
	 * Rule for required fields (non-empty).
	 *
	 * @param string $input
	 * @return bool
	 */
	public function ruleRequiredField(string $input): bool
	{
		return !empty(trim($input));
	}

	/**
	 * Rule for checking if a resource exists (file, key, etc.).
	 *
	 * @param mixed $resource
	 * @return bool
	 */
	public function ruleExists($resource): bool
	{
		if (is_string($resource)) {
			return file_exists($resource);
		}
		if (is_array($resource)) {
			return !empty($resource);
		}
		return false;
	}

	/**
	 * Rule for validating a range (e.g., numeric, date).
	 *
	 * @param float|int $value
	 * @param float|int $min
	 * @param float|int $max
	 * @return bool
	 */
	public function ruleRangeValidation($value, $min = PHP_FLOAT_MIN, $max = PHP_FLOAT_MAX): bool
	{
		return $value >= $min && $value <= $max;
	}

	/**
	 * Rule for validating IP addresses and domain names.
	 *
	 * @param string $input
	 * @param string $type
	 * @return bool
	 */
	public function ruleIpOrDomain(string $input, string $type = 'both'): bool
	{
		if ($type === 'ipv4') {
			return filter_var($input, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
		} elseif ($type === 'ipv6') {
			return filter_var($input, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
		}
		return filter_var($input, FILTER_VALIDATE_IP) !== false || filter_var($input, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
	}
}
