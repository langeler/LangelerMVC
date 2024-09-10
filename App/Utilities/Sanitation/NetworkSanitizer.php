<?php

namespace App\Utilities\Sanitation;

use App\Abstracts\Sanitizer;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Traits\Patterns\Sanitation\NetworkSanitationPatternsTrait;

/**
 * Class NetworkSanitizer
 *
 * Provides sanitation methods for network-related fields using regex patterns.
 */
class NetworkSanitizer extends Sanitizer
{
	use PatternTrait, NetworkSanitationPatternsTrait;

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
	 * Sanitize a slug by removing invalid characters.
	 *
	 * @param string $input The input slug.
	 * @return string The sanitized slug.
	 */
	public function sanitizeSlug(string $input): string
	{
		return $this->replace($this->getPattern('slug'), '', $input);
	}

	/**
	 * Sanitize an HTTP/HTTPS URL by removing invalid characters.
	 *
	 * @param string $input The input URL.
	 * @return string The sanitized URL.
	 */
	public function sanitizeUrlHttpHttps(string $input): string
	{
		return $this->replace($this->getPattern('url_http_https'), '', $input);
	}

	/**
	 * Sanitize a URL with a port by removing invalid characters.
	 *
	 * @param string $input The input URL with port.
	 * @return string The sanitized URL.
	 */
	public function sanitizeUrlWithPort(string $input): string
	{
		return $this->replace($this->getPattern('url_with_port'), '', $input);
	}

	/**
	 * Sanitize a URL with a query string by removing invalid characters.
	 *
	 * @param string $input The input URL with query string.
	 * @return string The sanitized URL.
	 */
	public function sanitizeUrlWithQuery(string $input): string
	{
		return $this->replace($this->getPattern('url_with_query'), '', $input);
	}

	/**
	 * Sanitize an FTP URL by removing invalid characters.
	 *
	 * @param string $input The input FTP URL.
	 * @return string The sanitized FTP URL.
	 */
	public function sanitizeFtpUrl(string $input): string
	{
		return $this->replace($this->getPattern('ftp_url'), '', $input);
	}

	/**
	 * Sanitize a Google Drive URL by removing invalid characters.
	 *
	 * @param string $input The input Google Drive URL.
	 * @return string The sanitized Google Drive URL.
	 */
	public function sanitizeGoogleDriveUrl(string $input): string
	{
		return $this->replace($this->getPattern('google_drive_url'), '', $input);
	}

	/**
	 * Sanitize a Dropbox URL by removing invalid characters.
	 *
	 * @param string $input The input Dropbox URL.
	 * @return string The sanitized Dropbox URL.
	 */
	public function sanitizeDropboxUrl(string $input): string
	{
		return $this->replace($this->getPattern('dropbox_url'), '', $input);
	}

	/**
	 * Sanitize an IPv4 address by removing invalid characters.
	 *
	 * @param string $input The input IPv4 address.
	 * @return string The sanitized IPv4 address.
	 */
	public function sanitizeIpv4Address(string $input): string
	{
		return $this->replace($this->getPattern('ipv4_address'), '', $input);
	}

	/**
	 * Sanitize an IPv6 address by removing invalid characters.
	 *
	 * @param string $input The input IPv6 address.
	 * @return string The sanitized IPv6 address.
	 */
	public function sanitizeIpv6Address(string $input): string
	{
		return $this->replace($this->getPattern('ipv6_address'), '', $input);
	}
}
