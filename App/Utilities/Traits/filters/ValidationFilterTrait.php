<?php

namespace App\Utilities\Traits\Filters;

use InvalidArgumentException;

trait ValidationTrait
{
	use FiltrationTrait;

	public readonly array $filters;
	public readonly array $flags;

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

	public function validateBoolean($input): bool
	{
		return $this->var($input, $this->filters['boolean']) !== false;
	}

	public function validateEmail(string $input): bool
	{
		return $this->var($input, $this->filters['email']) !== false;
	}

	public function validateFloat(string $input, array $flags = []): bool
	{
		$options = $this->getFilterOptions('float', $flags);
		return $this->var($input, $this->filters['float'], $options) !== false;
	}

	public function validateInt(string $input, array $flags = []): bool
	{
		$options = $this->getFilterOptions('int', $flags);
		return $this->var($input, $this->filters['int'], $options) !== false;
	}

	public function validateIp(string $input, array $flags = []): bool
	{
		$options = $this->getFilterOptions('ip', $flags);
		return $this->var($input, $this->filters['ip'], $options) !== false;
	}

	public function validateMac(string $input): bool
	{
		return $this->var($input, $this->filters['mac']) !== false;
	}

	public function validateRegexp(string $input, string $pattern): bool
	{
		return $this->var($input, $this->filters['regexp'], ['options' => ['regexp' => $pattern]]) !== false;
	}

	public function validateUrl(string $input, array $flags = []): bool
	{
		$options = $this->getFilterOptions('url', $flags);
		return $this->var($input, $this->filters['url'], $options) !== false;
	}

	public function validateDomain(string $input): bool
	{
		return $this->var($input, $this->filters['domain']) !== false;
	}

	private function getFilterOptions(string $filter, array $flagKeys): array
	{
		$flagValues = array_reduce($flagKeys, function ($carry, $flagKey) {
			$carry |= $this->flags[$flagKey] ?? 0;
			return $carry;
		}, 0);

		return ['flags' => $flagValues];
	}
}
