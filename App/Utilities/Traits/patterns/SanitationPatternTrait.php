<?php

namespace App\Utilities\Traits\Patterns\Sanitation;

use App\Utilities\Traits\Patterns\PatternTrait;

trait SanitationPatternTrait
{
	use PatternTrait;

	// Read-only property for patterns
	public readonly array $patterns;

	public function __construct()
	{
		$this->patterns = [
			// **Name and ID Patterns**
			'name' => '/[^a-zA-Z\s.\'\-]/', // Full name with letters, spaces, periods, apostrophes, hyphens
			'ssn' => '/[^\d\-]/', // Generic SSN format with digits and hyphens
			'phoneUs' => '/[^\d\s\(\).\-\+]/', // US phone numbers
			'phoneIntl' => '/[^\d\s\+]/', // International phone numbers
			'zipUs' => '/[^\d\-]/', // US zip codes with digits and hyphens
			'zipUk' => '/[^A-Z0-9\s]/i', // UK postal codes, case-insensitive

			// **Hex and Binary Patterns**
			'hex' => '/[^0-9a-fA-Fx]/', // Hexadecimal format, allowing "x" for hex prefix
			'binary' => '/[^01]/', // Binary numbers (0s and 1s)
			'octal' => '/[^0-7]/', // Octal numbers (0-7)

			// **Finance Patterns**
			'creditCard' => '/[^\d]/', // Credit card number (digits only)
			'isbn' => '/[^\dX]/', // ISBN-10 (digits or 'X')
			'currencyUsd' => '/[^\d,.\$]/', // USD currency with digits, comma, period, "$" symbol

			// **File and Path Patterns**
			'fileName' => '/[^a-zA-Z0-9\-_\.]/', // File names
			'directory' => '/[^a-zA-Z0-9\-_]/', // Directory names
			'pathUnix' => '/[^a-zA-Z0-9_\/\-\.]/', // Unix file paths
			'fileExt' => '/[^a-zA-Z0-9]/', // File extensions

			// **Network Patterns**
			'slug' => '/[^a-z0-9\-]/', // URL slugs (lowercase letters, numbers, hyphens)
			'url' => '/[^a-zA-Z0-9\-_\.~:\/?#\[\]@!$&\'()*+,;=%]/', // URLs with common characters
			'ipv4' => '/[^0-9.]/', // IPv4 addresses
			'ipv6' => '/[^0-9a-fA-F:]/', // IPv6 addresses

			// **Numeric Patterns**
			'intPos' => '/[^\d]/', // Positive integers (digits only)
			'float' => '/[^\d.\-]/', // Floating-point numbers with optional negative sign
			'percent' => '/[^\d\.\%]/', // Percentages with digits, period, "%" symbol

			// **Alphabetic Patterns**
			'alpha' => '/[^a-zA-Z]/', // Alphabetic characters only
			'alphaNum' => '/[^a-zA-Z0-9]/', // Alphanumeric characters only
			'hashtag' => '/[^a-zA-Z0-9_#]/', // Hashtags with letters, numbers, underscores, "#"
		];
	}

	// **Sanitize Methods**

	public function sanitizeName(string $input): ?string
	{
		return $this->replace($this->patterns['name'], '', $input);
	}

	public function sanitizeSsn(string $input): ?string
	{
		return $this->replace($this->patterns['ssn'], '', $input);
	}

	public function sanitizePhoneUs(string $input): ?string
	{
		return $this->replace($this->patterns['phoneUs'], '', $input);
	}

	public function sanitizePhoneIntl(string $input): ?string
	{
		return $this->replace($this->patterns['phoneIntl'], '', $input);
	}

	public function sanitizeZipUs(string $input): ?string
	{
		return $this->replace($this->patterns['zipUs'], '', $input);
	}

	public function sanitizeZipUk(string $input): ?string
	{
		return $this->replace($this->patterns['zipUk'], '', $input);
	}

	public function sanitizeHex(string $input): ?string
	{
		return $this->replace($this->patterns['hex'], '', $input);
	}

	public function sanitizeBinary(string $input): ?string
	{
		return $this->replace($this->patterns['binary'], '', $input);
	}

	public function sanitizeOctal(string $input): ?string
	{
		return $this->replace($this->patterns['octal'], '', $input);
	}

	public function sanitizeCreditCard(string $input): ?string
	{
		return $this->replace($this->patterns['creditCard'], '', $input);
	}

	public function sanitizeIsbn(string $input): ?string
	{
		return $this->replace($this->patterns['isbn'], '', $input);
	}

	public function sanitizeCurrencyUsd(string $input): ?string
	{
		return $this->replace($this->patterns['currencyUsd'], '', $input);
	}

	public function sanitizeFileName(string $input): ?string
	{
		return $this->replace($this->patterns['fileName'], '', $input);
	}

	public function sanitizeDirectory(string $input): ?string
	{
		return $this->replace($this->patterns['directory'], '', $input);
	}

	public function sanitizePathUnix(string $input): ?string
	{
		return $this->replace($this->patterns['pathUnix'], '', $input);
	}

	public function sanitizeFileExt(string $input): ?string
	{
		return $this->replace($this->patterns['fileExt'], '', $input);
	}

	public function sanitizeSlug(string $input): ?string
	{
		return $this->replace($this->patterns['slug'], '', $input);
	}

	public function sanitizeUrl(string $input): ?string
	{
		return $this->replace($this->patterns['url'], '', $input);
	}

	public function sanitizeIpv4(string $input): ?string
	{
		return $this->replace($this->patterns['ipv4'], '', $input);
	}

	public function sanitizeIpv6(string $input): ?string
	{
		return $this->replace($this->patterns['ipv6'], '', $input);
	}

	public function sanitizeIntPos(string $input): ?string
	{
		return $this->replace($this->patterns['intPos'], '', $input);
	}

	public function sanitizeFloat(string $input): ?string
	{
		return $this->replace($this->patterns['float'], '', $input);
	}

	public function sanitizePercent(string $input): ?string
	{
		return $this->replace($this->patterns['percent'], '', $input);
	}

	public function sanitizeAlpha(string $input): ?string
	{
		return $this->replace($this->patterns['alpha'], '', $input);
	}

	public function sanitizeAlphaNum(string $input): ?string
	{
		return $this->replace($this->patterns['alphaNum'], '', $input);
	}

	public function sanitizeHashtag(string $input): ?string
	{
		return $this->replace($this->patterns['hashtag'], '', $input);
	}
}
