<?php

namespace App\Utilities\Sanitation;

use App\Abstracts\Data\Sanitizer;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Traits\Patterns\Sanitation\CodeSanitationPatternsTrait;

/**
 * Class CodeSanitizer
 *
 * Provides sanitation methods for code-related fields using regex patterns.
 */
class CodeSanitizer extends Sanitizer
{
	use PatternTrait, CodeSanitationPatternsTrait;

	/**
	 * === ENTRY POINT: sanitize method (Do not modify) ===
	 *
	 * @param mixed $data The data to be sanitized.
	 * @return array The sanitized data array.
	 */
	protected function clean(mixed $data): array
	{
		return $this->handle($data);
	}

	// === Basic Sanitation Using Patterns ===

	/**
	 * Sanitize a hexadecimal string by removing invalid characters.
	 *
	 * @param string $input The input hexadecimal string.
	 * @return string The sanitized hexadecimal string.
	 */
	public function sanitizeHexadecimal(string $input): string
	{
		return $this->replace($this->getPattern('hexadecimal'), '', $input);
	}

	/**
	 * Sanitize a hexadecimal string (without "0x" prefix) by removing invalid characters.
	 *
	 * @param string $input The input hexadecimal string.
	 * @return string The sanitized hex-only string.
	 */
	public function sanitizeHexOnly(string $input): string
	{
		return $this->replace($this->getPattern('hex_only'), '', $input);
	}

	/**
	 * Sanitize a binary string by removing invalid characters.
	 *
	 * @param string $input The input binary string.
	 * @return string The sanitized binary string.
	 */
	public function sanitizeBinary(string $input): string
	{
		return $this->replace($this->getPattern('binary'), '', $input);
	}

	/**
	 * Sanitize an octal string by removing invalid characters.
	 *
	 * @param string $input The input octal string.
	 * @return string The sanitized octal string.
	 */
	public function sanitizeOctal(string $input): string
	{
		return $this->replace($this->getPattern('octal'), '', $input);
	}

	/**
	 * Remove HTML comments from a string.
	 *
	 * @param string $input The input string containing HTML comments.
	 * @return string The sanitized string without HTML comments.
	 */
	public function sanitizeHtmlComment(string $input): string
	{
		return $this->replace($this->getPattern('html_comment'), '', $input);
	}
}
