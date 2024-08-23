<?php

namespace App\Utilities\Validation\Traits;

trait NetworkValidationTrait
{
	use BaseValidationTrait;

	/**
	 * Validate that an IP address is valid (IPv4/IPv6).
	 *
	 * @param string $ip
	 * @param string $type
	 * @return bool
	 */
	public function validateIpAddress(string $ip, string $type = 'both'): bool
	{
		if ($type === 'ipv4') {
			return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
		} elseif ($type === 'ipv6') {
			return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
		}
		return filter_var($ip, FILTER_VALIDATE_IP) !== false;
	}

	/**
	 * Validate that a domain name is valid.
	 *
	 * @param string $domain
	 * @return bool
	 */
	public function validateDomainName(string $domain): bool
	{
		return filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
	}
}
