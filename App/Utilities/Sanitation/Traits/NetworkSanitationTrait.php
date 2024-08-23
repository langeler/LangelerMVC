<?php

namespace App\Utilities\Sanitation\Traits;

trait NetworkSanitationTrait
{
	use BaseSanitationTrait;

	/**
	 * Normalize and sanitize IP addresses.
	 *
	 * @param string $ip
	 * @return string
	 */
	public function sanitizeIpAddress(string $ip): string
	{
		return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
	}

	/**
	 * Sanitize and validate domain names.
	 *
	 * @param string $domain
	 * @return string
	 */
	public function sanitizeDomain(string $domain): string
	{
		return filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) ? $domain : '';
	}

	/**
	 * Sanitize DNS records, ensuring safe handling.
	 *
	 * @param string $dnsRecord
	 * @return string
	 */
	public function sanitizeDnsRecord(string $dnsRecord): string
	{
		return $this->sanitizeText($dnsRecord);
	}
}
