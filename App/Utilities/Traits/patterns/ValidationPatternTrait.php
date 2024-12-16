<?php

namespace App\Utilities\Traits\Patterns\Validation;

use App\Utilities\Traits\Patterns\PatternTrait;

/**
 * Trait ValidationPatternTrait
 *
 * Provides a collection of regular expression patterns for validating various types of input data.
 * Includes methods to validate inputs based on these patterns, ensuring strict compliance with predefined formats.
 */
trait ValidationPatternTrait
{
	use PatternTrait;

	/**
	 * Regular expression patterns for validating different input data types.
	 *
	 * @var array<string, string>
	 */
	public readonly array $patterns;

	/**
	 * Initializes the validation patterns.
	 *
	 * The patterns include formats for names, social security numbers, phone numbers, file paths, numeric formats,
	 * network-related data (e.g., IP addresses, URLs), and more.
	 */
	public function __construct()
	{
		$this->patterns = [
			// **Name and ID Patterns**
			'name' => "/^[a-zA-Z]+(?:\s+[-a-zA-Z.'\s]+)*$/", // Full name: letters, spaces, periods, apostrophes, hyphens
			'ssn' => "/^\d{3}-\d{2}-\d{4}$/", // US Social Security Number
			'phoneUs' => "/^(\+1\s?)?\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}$/", // US phone numbers
			'phoneIntl' => "/^\+\d{1,3}\s?\d{1,14}(\s?\d{1,13})?$/", // International phone numbers

			// **Hex and Binary Patterns**
			'hexadecimal' => "/^0x[0-9a-fA-F]+$/", // Hexadecimal with prefix
			'hexOnly' => "/^[0-9a-fA-F]+$/", // Hexadecimal without prefix
			'binary' => "/^[01]+$/", // Binary numbers (0s and 1s)
			'octal' => "/^[0-7]+$/", // Octal numbers (0-7)

			// **Finance Patterns**
			'creditCard' => "/^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|3[47][0-9]{13}|6(?:011|5[0-9]{2})[0-9]{12})$/", // Credit card
			'isbn10' => "/^\d{9}(\d|X)$/", // ISBN-10 format
			'iban' => "/^[A-Z]{2}\d{2}[A-Z0-9]{1,30}$/", // IBAN
			'bic' => "/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/", // BIC/SWIFT code
			'ethereumAddress' => "/^0x[a-fA-F0-9]{40}$/", // Ethereum address
			'bitcoinAddress' => "/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$/", // Bitcoin address

			// **File and Path Patterns**
			'fileName' => "/^[^\/:*?\"<>|]+\.[a-zA-Z0-9]+$/", // File name with extension
			'directory' => "/^[^\/:*?\"<>|]+$/", // Directory names
			'pathUnix' => "/^(\/[^\/ ]*)+\/?$/", // Unix file paths
			'pathWindows' => "/^[a-zA-Z]:\\[\\\S|*\S]?.*$/", // Windows file paths
			'fileExt' => "/\.[a-zA-Z0-9]+$/", // File extensions
			'imageExt' => "/\.(jpg|jpeg|png|gif|bmp|webp)$/i", // Image file extensions
			'audioExt' => "/\.(mp3|wav|flac|aac|ogg)$/i", // Audio file extensions
			'videoExt' => "/\.(mp4|avi|mkv|mov)$/i", // Video file extensions

			// **Network Patterns**
			'slug' => "/^[a-z0-9]+(?:-[a-z0-9]+)*$/", // URL slugs
			'url' => "/^(https?:\/\/)?([\w\-]+\.)+[\w\-]+(\/[\w\-._~:\/?#\[\]@!$&'()*+,;=]*)?$/i", // HTTP/HTTPS URL
			'urlPort' => "/^(https?:\/\/)?([\w\-]+\.)+[\w\-]+(:\d+)?(\/[\w\-._~:\/?#\[\]@!$&'()*+,;=]*)?$/i", // URL with port
			'ipv4' => "/^(\d{1,3}\.){3}\d{1,3}$/", // IPv4 addresses
			'ipv6' => "/^([0-9a-fA-F]{1,4}:){7}([0-9a-fA-F]{1,4}|:)$/", // IPv6 addresses
			'zipUs' => "/^\d{5}(-\d{4})?$/", // US ZIP code
			'zipUk' => "/^([A-Z]{1,2}\d[A-Z\d]? \d[A-Z]{2})$/i", // UK postal code

			// **Numeric Patterns**
			'intPos' => "/^\d+$/", // Positive integers
			'intNeg' => "/^-\d+$/", // Negative integers
			'int' => "/^-?\d+$/", // Integers
			'floatPos' => "/^\d*\.?\d+$/", // Positive floating-point numbers
			'floatNeg' => "/^-?\d*\.\d+$/", // Negative floating-point numbers
			'float' => "/^-?\d*(\.\d+)?$/", // Floating-point numbers
			'scientific' => "/^[+-]?\d+(\.\d+)?[eE][+-]?\d+$/", // Scientific notation

			// **Alphabetic Patterns**
			'alpha' => "/^[a-zA-Z]+$/", // Alphabetic characters
			'alphaSpace' => "/^[a-zA-Z\s]+$/", // Alphabetic characters with spaces
			'alphaDash' => "/^[a-zA-Z-_]+$/", // Alphabetic characters with hyphens and underscores
			'alphaNum' => "/^[a-zA-Z0-9]+$/", // Alphanumeric characters
			'alphaNumSpace' => "/^[a-zA-Z0-9\s]+$/", // Alphanumeric characters with spaces
			'hashtag' => "/^#[a-zA-Z0-9_]+$/", // Hashtags
			'twitterHandle' => "/^@?([a-zA-Z0-9_]{1,15})$/", // Twitter handle
		];
	}

	/**
	 * Validates a full name format (letters, spaces, periods, apostrophes, hyphens).
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateName(string $input): bool
	{
		return $this->match($this->patterns['name'], $input) === 1;
	}

	/**
	 * Validates a US Social Security Number (SSN) format (e.g., 123-45-6789).
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateSsn(string $input): bool
	{
		return $this->match($this->patterns['ssn'], $input) === 1;
	}

	/**
	 * Validates a US phone number format (e.g., (123) 456-7890 or +1 123-456-7890).
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validatePhoneUs(string $input): bool
	{
		return $this->match($this->patterns['phoneUs'], $input) === 1;
	}

	/**
	 * Validates an international phone number format (e.g., +44 1234 567890).
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validatePhoneIntl(string $input): bool
	{
		return $this->match($this->patterns['phoneIntl'], $input) === 1;
	}

	/**
	 * Validates a hexadecimal number with a "0x" prefix.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateHexadecimal(string $input): bool
	{
		return $this->match($this->patterns['hexadecimal'], $input) === 1;
	}

	/**
	 * Validates a hexadecimal number without a prefix.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateHexOnly(string $input): bool
	{
		return $this->match($this->patterns['hexOnly'], $input) === 1;
	}

	/**
	 * Validates a binary number (0s and 1s).
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateBinary(string $input): bool
	{
		return $this->match($this->patterns['binary'], $input) === 1;
	}

	/**
	 * Validates an octal number (digits 0-7).
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateOctal(string $input): bool
	{
		return $this->match($this->patterns['octal'], $input) === 1;
	}

	/**
	 * Validates a credit card number format.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateCreditCard(string $input): bool
	{
		return $this->match($this->patterns['creditCard'], $input) === 1;
	}

	/**
	 * Validates an ISBN-10 format.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateIsbn10(string $input): bool
	{
		return $this->match($this->patterns['isbn10'], $input) === 1;
	}

	/**
	 * Validates an International Bank Account Number (IBAN).
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateIban(string $input): bool
	{
		return $this->match($this->patterns['iban'], $input) === 1;
	}

	/**
	 * Validates a BIC/SWIFT code format.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateBic(string $input): bool
	{
		return $this->match($this->patterns['bic'], $input) === 1;
	}

	/**
	 * Validates an Ethereum address format.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateEthereumAddress(string $input): bool
	{
		return $this->match($this->patterns['ethereumAddress'], $input) === 1;
	}

	/**
	 * Validates a Bitcoin address format.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateBitcoinAddress(string $input): bool
	{
		return $this->match($this->patterns['bitcoinAddress'], $input) === 1;
	}

	/**
	 * Validates a file name format with an extension.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateFileName(string $input): bool
	{
		return $this->match($this->patterns['fileName'], $input) === 1;
	}

	/**
	 * Validates a directory name format.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateDirectory(string $input): bool
	{
		return $this->match($this->patterns['directory'], $input) === 1;
	}

	/**
	 * Validates a Unix file path format.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validatePathUnix(string $input): bool
	{
		return $this->match($this->patterns['pathUnix'], $input) === 1;
	}

	/**
	 * Validates a Windows file path format.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validatePathWindows(string $input): bool
	{
		return $this->match($this->patterns['pathWindows'], $input) === 1;
	}

	/**
	 * Validates a file extension format.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateFileExt(string $input): bool
	{
		return $this->match($this->patterns['fileExt'], $input) === 1;
	}

	/**
	 * Validates an image file extension format (e.g., .jpg, .png).
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateImageExt(string $input): bool
	{
		return $this->match($this->patterns['imageExt'], $input) === 1;
	}

	/**
	 * Validates an audio file extension format (e.g., .mp3, .wav).
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateAudioExt(string $input): bool
	{
		return $this->match($this->patterns['audioExt'], $input) === 1;
	}

	/**
	 * Validates a video file extension format (e.g., .mp4, .avi).
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateVideoExt(string $input): bool
	{
		return $this->match($this->patterns['videoExt'], $input) === 1;
	}

	/**
	 * Validates a URL slug format (lowercase letters, numbers, hyphens).
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateSlug(string $input): bool
	{
		return $this->match($this->patterns['slug'], $input) === 1;
	}

	/**
	 * Validates a URL format.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateUrl(string $input): bool
	{
		return $this->match($this->patterns['url'], $input) === 1;
	}

	/**
	 * Validates a URL with port format.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateUrlPort(string $input): bool
	{
		return $this->match($this->patterns['urlPort'], $input) === 1;
	}

	/**
	 * Validates an IPv4 address format.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateIpv4(string $input): bool
	{
		return $this->match($this->patterns['ipv4'], $input) === 1;
	}

	/**
	 * Validates an IPv6 address format.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateIpv6(string $input): bool
	{
		return $this->match($this->patterns['ipv6'], $input) === 1;
	}

	/**
	 * Validates a US ZIP code format.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateZipUs(string $input): bool
	{
		return $this->match($this->patterns['zipUs'], $input) === 1;
	}

	/**
	 * Validates a UK postal code format.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateZipUk(string $input): bool
	{
		return $this->match($this->patterns['zipUk'], $input) === 1;
	}

	/**
	 * Validates a positive integer.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateIntPos(string $input): bool
	{
		return $this->match($this->patterns['intPos'], $input) === 1;
	}

	/**
	 * Validates a negative integer.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateIntNeg(string $input): bool
	{
		return $this->match($this->patterns['intNeg'], $input) === 1;
	}

	/**
	 * Validates an integer (positive or negative).
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateInt(string $input): bool
	{
		return $this->match($this->patterns['int'], $input) === 1;
	}

	/**
	 * Validates a positive floating-point number.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateFloatPos(string $input): bool
	{
		return $this->match($this->patterns['floatPos'], $input) === 1;
	}

	/**
	 * Validates a negative floating-point number.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateFloatNeg(string $input): bool
	{
		return $this->match($this->patterns['floatNeg'], $input) === 1;
	}

	/**
	 * Validates a floating-point number (positive or negative).
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateFloat(string $input): bool
	{
		return $this->match($this->patterns['float'], $input) === 1;
	}

	/**
	 * Validates a scientific notation format.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateScientific(string $input): bool
	{
		return $this->match($this->patterns['scientific'], $input) === 1;
	}

	/**
	 * Validates alphabetic characters only.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateAlpha(string $input): bool
	{
		return $this->match($this->patterns['alpha'], $input) === 1;
	}

	/**
	 * Validates alphabetic characters with spaces.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateAlphaSpace(string $input): bool
	{
		return $this->match($this->patterns['alphaSpace'], $input) === 1;
	}

	/**
	 * Validates alphabetic characters with hyphens and underscores.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateAlphaDash(string $input): bool
	{
		return $this->match($this->patterns['alphaDash'], $input) === 1;
	}

	/**
	 * Validates alphanumeric characters only.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateAlphaNum(string $input): bool
	{
		return $this->match($this->patterns['alphaNum'], $input) === 1;
	}

	/**
	 * Validates alphanumeric characters with spaces.
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateAlphaNumSpace(string $input): bool
	{
		return $this->match($this->patterns['alphaNumSpace'], $input) === 1;
	}

	/**
	 * Validates a hashtag format (e.g., #example).
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateHashtag(string $input): bool
	{
		return $this->match($this->patterns['hashtag'], $input) === 1;
	}

	/**
	 * Validates a Twitter handle format (e.g., @username).
	 *
	 * @param string $input The input string to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validateTwitterHandle(string $input): bool
	{
		return $this->match($this->patterns['twitterHandle'], $input) === 1;
	}
}
