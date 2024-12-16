<?php

namespace App\Utilities\Traits\Filters;

use InvalidArgumentException;
use App\Utilities\Traits\ArrayTrait;

/**
 * Trait ValidationTrait
 *
 * Provides robust methods for validating various data types using PHP's filter extensions.
 * Supports optional flags and fallback to default property values when flags are omitted.
 */
trait ValidationTrait
{
	use FiltrationTrait, ArrayTrait;

	/**
	 * Validation filters mapped to their corresponding PHP filter constants.
	 *
	 * @var array
	 */
	public readonly array $filters;

	/**
	 * Validation flags mapped to their corresponding PHP flag constants.
	 *
	 * @var array
	 */
	public readonly array $flags;

	/**
	 * Constructor to initialize validation filters and flags.
	 */
	public function __construct()
	{
		$this->filters = [
			'boolean' => FILTER_VALIDATE_BOOLEAN,        // Validates boolean values
			'email' => FILTER_VALIDATE_EMAIL,            // Validates an email address
			'float' => FILTER_VALIDATE_FLOAT,            // Validates a floating-point number
			'int' => FILTER_VALIDATE_INT,                // Validates an integer
			'ip' => FILTER_VALIDATE_IP,                  // Validates an IP address
			'mac' => FILTER_VALIDATE_MAC,                // Validates a MAC address
			'regexp' => FILTER_VALIDATE_REGEXP,          // Validates against a regular expression
			'url' => FILTER_VALIDATE_URL,                // Validates a URL
			'domain' => FILTER_VALIDATE_DOMAIN,          // Validates a domain name
		];

		$this->flags = [
			'allowFraction' => FILTER_FLAG_ALLOW_FRACTION,      // Allows decimal fractions in numbers
			'allowScientific' => FILTER_FLAG_ALLOW_SCIENTIFIC,  // Allows scientific notation in numbers
			'allowThousand' => FILTER_FLAG_ALLOW_THOUSAND,      // Allows thousand separators in numbers
			'ipv4' => FILTER_FLAG_IPV4,                         // Restricts validation to IPv4
			'ipv6' => FILTER_FLAG_IPV6,                         // Restricts validation to IPv6
			'noResRange' => FILTER_FLAG_NO_RES_RANGE,           // Excludes reserved IP ranges
			'noPrivRange' => FILTER_FLAG_NO_PRIV_RANGE,         // Excludes private IP ranges
			'pathRequired' => FILTER_FLAG_PATH_REQUIRED,        // Requires path in URLs
			'queryRequired' => FILTER_FLAG_QUERY_REQUIRED       // Requires query in URLs
		];
	}

	/**
	 * Validates a boolean input.
	 *
	 * @param mixed $input Input to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateBoolean($input): bool
	{
		return $this->var($input, $this->filters['boolean']) !== false;
	}

	/**
	 * Validates an email address.
	 *
	 * @param string $input Input to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateEmail(string $input): bool
	{
		return $this->var($input, $this->filters['email']) !== false;
	}

	/**
	 * Validates a floating-point number with optional flags.
	 *
	 * @param string $input Input to validate.
	 * @param array $flags Optional flags to use.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateFloat(string $input, array $flags = []): bool
	{
		return $this->var($input, $this->filters['float'], $this->getFilterOptions($flags)) !== false;
	}

	/**
	 * Validates an integer with optional flags.
	 *
	 * @param string $input Input to validate.
	 * @param array $flags Optional flags to use.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateInt(string $input, array $flags = []): bool
	{
		return $this->var($input, $this->filters['int'], $this->getFilterOptions($flags)) !== false;
	}

	/**
	 * Validates an IP address with optional flags.
	 *
	 * @param string $input Input to validate.
	 * @param array $flags Optional flags to use.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateIp(string $input, array $flags = []): bool
	{
		return $this->var($input, $this->filters['ip'], $this->getFilterOptions($flags)) !== false;
	}

	/**
	 * Validates a MAC address.
	 *
	 * @param string $input Input to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateMac(string $input): bool
	{
		return $this->var($input, $this->filters['mac']) !== false;
	}

	/**
	 * Validates a string against a regular expression.
	 *
	 * @param string $input Input to validate.
	 * @param string $pattern Regular expression pattern.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateRegexp(string $input, string $pattern): bool
	{
		return $this->var($input, $this->filters['regexp'], ['options' => ['regexp' => $pattern]]) !== false;
	}

	/**
	 * Validates a URL with optional flags.
	 *
	 * @param string $input Input to validate.
	 * @param array $flags Optional flags to use.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateUrl(string $input, array $flags = []): bool
	{
		return $this->var($input, $this->filters['url'], $this->getFilterOptions($flags)) !== false;
	}

	/**
	 * Validates a domain name.
	 *
	 * @param string $input Input to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateDomain(string $input): bool
	{
		return $this->var($input, $this->filters['domain']) !== false;
	}

	/**
	 * Generates filter options from flags or defaults to class-level properties if no flags are provided.
	 *
	 * @param array $flagKeys List of flag keys to combine.
	 * @return array Filter options with combined flags.
	 */
	private function getFilterOptions(array $flagKeys = []): array
	{
		return [
			'flags' => $this->reduce(
				$flagKeys ?: $this->getKeys($this->flags),
				fn($carry, $key) => $carry | ($this->flags[$key] ?? 0),
				0
			)
		];
	}
}
