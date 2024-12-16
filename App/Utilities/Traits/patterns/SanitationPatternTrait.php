<?php

namespace App\Utilities\Traits\Patterns\Sanitation;

use App\Utilities\Traits\Patterns\PatternTrait;

/**
 * Trait SanitationPatternTrait
 *
 * Provides a predefined collection of regular expression patterns for sanitizing various input types.
 * Includes sanitization methods that use these patterns to remove unwanted characters.
 */
trait SanitationPatternTrait
{
	use PatternTrait;

	/**
	 * A collection of regular expression patterns for sanitizing different types of input data.
	 *
	 * @var array<string, string> Patterns categorized by input type.
	 */
	public readonly array $patterns;

	/**
	 * Initializes the predefined patterns for sanitization.
	 *
	 * Patterns cover common use cases such as names, phone numbers, file paths, URLs, numeric formats, and more.
	 */
	public function __construct()
	{
		$this->patterns = [
			// **Name and ID Patterns**
			'name' => '/[^a-zA-Z\s.\'\-]/', // Full name: letters, spaces, periods, apostrophes, hyphens
			'ssn' => '/[^\d\-]/', // Generic SSN: digits and hyphens
			'phoneUs' => '/[^\d\s\(\).\-\+]/', // US phone numbers: digits, spaces, parentheses, hyphens, plus
			'phoneIntl' => '/[^\d\s\+]/', // International phone numbers: digits, spaces, plus
			'zipUs' => '/[^\d\-]/', // US ZIP codes: digits and hyphens
			'zipUk' => '/[^A-Z0-9\s]/i', // UK postal codes: alphanumeric and spaces (case-insensitive)

			// **Hex and Binary Patterns**
			'hex' => '/[^0-9a-fA-Fx]/', // Hexadecimal: digits, "a-f/A-F", optional "x" prefix
			'binary' => '/[^01]/', // Binary numbers: 0 and 1
			'octal' => '/[^0-7]/', // Octal numbers: digits 0-7

			// **Finance Patterns**
			'creditCard' => '/[^\d]/', // Credit card numbers: digits only
			'isbn' => '/[^\dX]/', // ISBN-10: digits or "X" as the check digit
			'currencyUsd' => '/[^\d,.\$]/', // USD currency: digits, comma, period, "$"

			// **File and Path Patterns**
			'fileName' => '/[^a-zA-Z0-9\-_\.]/', // File names: alphanumeric, hyphens, underscores, dots
			'directory' => '/[^a-zA-Z0-9\-_]/', // Directory names: alphanumeric, hyphens, underscores
			'pathUnix' => '/[^a-zA-Z0-9_\/\-\.]/', // Unix paths: alphanumeric, slashes, hyphens, underscores, dots
			'fileExt' => '/[^a-zA-Z0-9]/', // File extensions: alphanumeric only

			// **Network Patterns**
			'slug' => '/[^a-z0-9\-]/', // URL slugs: lowercase letters, numbers, hyphens
			'url' => '/[^a-zA-Z0-9\-_\.~:\/?#\[\]@!$&\'()*+,;=%]/', // URLs: common URL characters
			'ipv4' => '/[^0-9.]/', // IPv4 addresses: digits, dots
			'ipv6' => '/[^0-9a-fA-F:]/', // IPv6 addresses: hexadecimal, colons

			// **Numeric Patterns**
			'intPos' => '/[^\d]/', // Positive integers: digits only
			'float' => '/[^\d.\-]/', // Floating-point: digits, period, optional negative sign
			'percent' => '/[^\d\.\%]/', // Percentages: digits, period, "%"

			// **Alphabetic Patterns**
			'alpha' => '/[^a-zA-Z]/', // Alphabetic characters only
			'alphaNum' => '/[^a-zA-Z0-9]/', // Alphanumeric characters only
			'hashtag' => '/[^a-zA-Z0-9_#]/', // Hashtags: letters, numbers, underscores, "#"
		];
	}

	/**
	 * Sanitizes a full name by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeName(string $input): ?string
	{
		return $this->replace($this->patterns['name'], '', $input);
	}

	/**
	 * Sanitizes a Social Security Number (SSN) by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeSsn(string $input): ?string
	{
		return $this->replace($this->patterns['ssn'], '', $input);
	}

	/**
	 * Sanitizes a US phone number by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizePhoneUs(string $input): ?string
	{
		return $this->replace($this->patterns['phoneUs'], '', $input);
	}

	/**
	 * Sanitizes an international phone number by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizePhoneIntl(string $input): ?string
	{
		return $this->replace($this->patterns['phoneIntl'], '', $input);
	}

	/**
	 * Sanitizes a US ZIP code by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeZipUs(string $input): ?string
	{
		return $this->replace($this->patterns['zipUs'], '', $input);
	}

	/**
	 * Sanitizes a UK postal code by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeZipUk(string $input): ?string
	{
		return $this->replace($this->patterns['zipUk'], '', $input);
	}

	/**
	 * Sanitizes a hexadecimal number by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeHex(string $input): ?string
	{
		return $this->replace($this->patterns['hex'], '', $input);
	}

	/**
	 * Sanitizes a binary number by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeBinary(string $input): ?string
	{
		return $this->replace($this->patterns['binary'], '', $input);
	}

	/**
	 * Sanitizes an octal number by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeOctal(string $input): ?string
	{
		return $this->replace($this->patterns['octal'], '', $input);
	}

	/**
	 * Sanitizes a credit card number by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeCreditCard(string $input): ?string
	{
		return $this->replace($this->patterns['creditCard'], '', $input);
	}

	/**
	 * Sanitizes an ISBN-10 number by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeIsbn(string $input): ?string
	{
		return $this->replace($this->patterns['isbn'], '', $input);
	}

	/**
	 * Sanitizes a USD currency value by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeCurrencyUsd(string $input): ?string
	{
		return $this->replace($this->patterns['currencyUsd'], '', $input);
	}

	/**
	 * Sanitizes a file name by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeFileName(string $input): ?string
	{
		return $this->replace($this->patterns['fileName'], '', $input);
	}

	/**
	 * Sanitizes a directory name by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeDirectory(string $input): ?string
	{
		return $this->replace($this->patterns['directory'], '', $input);
	}

	/**
	 * Sanitizes a Unix file path by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizePathUnix(string $input): ?string
	{
		return $this->replace($this->patterns['pathUnix'], '', $input);
	}

	/**
	 * Sanitizes a file extension by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeFileExt(string $input): ?string
	{
		return $this->replace($this->patterns['fileExt'], '', $input);
	}

	/**
	 * Sanitizes a URL slug by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeSlug(string $input): ?string
	{
		return $this->replace($this->patterns['slug'], '', $input);
	}

	/**
	 * Sanitizes a URL by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeUrl(string $input): ?string
	{
		return $this->replace($this->patterns['url'], '', $input);
	}

	/**
	 * Sanitizes an IPv4 address by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeIpv4(string $input): ?string
	{
		return $this->replace($this->patterns['ipv4'], '', $input);
	}

	/**
	 * Sanitizes an IPv6 address by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeIpv6(string $input): ?string
	{
		return $this->replace($this->patterns['ipv6'], '', $input);
	}

	/**
	 * Sanitizes a positive integer by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeIntPos(string $input): ?string
	{
		return $this->replace($this->patterns['intPos'], '', $input);
	}

	/**
	 * Sanitizes a floating-point number by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeFloat(string $input): ?string
	{
		return $this->replace($this->patterns['float'], '', $input);
	}

	/**
	 * Sanitizes a percentage value by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizePercent(string $input): ?string
	{
		return $this->replace($this->patterns['percent'], '', $input);
	}

	/**
	 * Sanitizes alphabetic input by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeAlpha(string $input): ?string
	{
		return $this->replace($this->patterns['alpha'], '', $input);
	}

	/**
	 * Sanitizes alphanumeric input by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeAlphaNum(string $input): ?string
	{
		return $this->replace($this->patterns['alphaNum'], '', $input);
	}

	/**
	 * Sanitizes a hashtag by removing unwanted characters.
	 *
	 * @param string $input The input string to sanitize.
	 * @return string|null The sanitized string.
	 */
	public function sanitizeHashtag(string $input): ?string
	{
		return $this->replace($this->patterns['hashtag'], '', $input);
	}
}
