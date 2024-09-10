<?php

namespace App\Utilities\Sanitation;

use App\Abstracts\Sanitizer;
use App\Utilities\Traits\Filters\FiltrationTrait;
use App\Utilities\Traits\Filters\FilterFlagTrait;
use App\Utilities\Traits\Filters\SanitationFilterTrait;

/**
 * Class GeneralSanitizer
 *
 * Provides general sanitization methods using traits for filtration, sanitation, and filter flags.
 */
class GeneralSanitizer extends Sanitizer
{
	use FiltrationTrait, SanitationFilterTrait, FilterFlagTrait;

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

	// === Basic Sanitization Functions ===

	/**
	 * Sanitizes a string by escaping HTML special characters.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeString(string $input): string
	{
		return $this->var($input, $this->getFilter('string'));
	}

	/**
	 * Sanitizes an email by removing invalid characters.
	 *
	 * @param string $input The input email string.
	 * @return string The sanitized email.
	 */
	public function sanitizeEmail(string $input): string
	{
		return $this->var($input, $this->getFilter('email'));
	}

	/**
	 * Sanitizes a URL by removing invalid characters.
	 *
	 * @param string $input The input URL string.
	 * @return string The sanitized URL.
	 */
	public function sanitizeUrl(string $input): string
	{
		return $this->var($input, $this->getFilter('url'));
	}

	/**
	 * Sanitizes an integer by removing all non-digit characters.
	 *
	 * @param string|int $input The input string or integer.
	 * @return int The sanitized integer.
	 */
	public function sanitizeInt(string|int $input): int
	{
		return $this->var($input, $this->getFilter('number_int'));
	}

	/**
	 * Sanitizes a float number (no flags).
	 *
	 * @param string|float $input The input string or float.
	 * @return float The sanitized float.
	 */
	public function sanitizeFloat(string|float $input): float
	{
		return $this->var($input, $this->getFilter('number_float'));
	}

	/**
	 * Sanitizes input by adding slashes to escape certain characters.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string with slashes.
	 */
	public function sanitizeWithSlashes(string $input): string
	{
		return $this->var($input, $this->getFilter('add_slashes'));
	}

	/**
	 * Sanitizes a string by escaping HTML special characters (alternative using full_special_chars).
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeFullSpecialChars(string $input): string
	{
		return $this->var($input, $this->getFilter('full_special_chars'));
	}

	/**
	 * Sanitizes an encoded string by URL-encoding it.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized encoded string.
	 */
	public function sanitizeEncoded(string $input): string
	{
		return $this->var($input, $this->getFilter('encoded'));
	}

	// === Separate Flag Functions (Optional Add-ons) ===

	/**
	 * Applies the allow_fraction flag to a float sanitization.
	 *
	 * @param string|float $input The input string or float.
	 * @return float The sanitized float allowing fractions.
	 */
	public function applyAllowFraction(string|float $input): float
	{
		return $this->var($input, $this->getFilter('number_float'), $this->getFlag('allow_fraction'));
	}

	/**
	 * Applies the allow_scientific flag to a float sanitization.
	 *
	 * @param string|float $input The input string or float.
	 * @return float The sanitized float allowing scientific notation.
	 */
	public function applyAllowScientific(string|float $input): float
	{
		return $this->var($input, $this->getFilter('number_float'), $this->getFlag('allow_scientific'));
	}

	/**
	 * Applies the allow_thousand flag to a float sanitization.
	 *
	 * @param string|float $input The input string or float.
	 * @return float The sanitized float allowing thousand separators.
	 */
	public function applyAllowThousand(string|float $input): float
	{
		return $this->var($input, $this->getFilter('number_float'), $this->getFlag('allow_thousand'));
	}

	/**
	 * Sanitizes a string with the strip_low flag (removes ASCII < 32).
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string with low ASCII characters removed.
	 */
	public function applyStripLow(string $input): string
	{
		return $this->var($input, $this->getFilter('string'), $this->getFlag('strip_low'));
	}

	/**
	 * Sanitizes a string with the strip_high flag (removes ASCII > 127).
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string with high ASCII characters removed.
	 */
	public function applyStripHigh(string $input): string
	{
		return $this->var($input, $this->getFilter('string'), $this->getFlag('strip_high'));
	}

	/**
	 * Sanitizes a string with the encode_amp flag (encodes ampersands).
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string with ampersands encoded.
	 */
	public function applyEncodeAmp(string $input): string
	{
		return $this->var($input, $this->getFilter('string'), $this->getFlag('encode_amp'));
	}

	/**
	 * Sanitizes an encoded string without encoding quotes (no_encode_quotes flag).
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string without encoding quotes.
	 */
	public function applyNoEncodeQuotes(string $input): string
	{
		return $this->var($input, $this->getFilter('encoded'), $this->getFlag('no_encode_quotes'));
	}

	/**
	 * Sanitizes a string by stripping backticks (strip_backtick flag).
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string without backticks.
	 */
	public function applyStripBacktick(string $input): string
	{
		return $this->var($input, $this->getFilter('string'), $this->getFlag('strip_backtick'));
	}
}
