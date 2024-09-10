<?php

namespace App\Utilities\Sanitation;

use App\Abstracts\Sanitizer;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Traits\Patterns\Sanitation\AlphabeticTextPatternsTrait;

/**
 * Class TextSanitizer
 *
 * Provides sanitation methods for text-related fields using regex patterns.
 */
class TextSanitizer extends Sanitizer
{
	use PatternTrait, AlphabeticTextPatternsTrait;

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
	 * Sanitize a string to allow only alphabetic characters.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeAlpha(string $input): string
	{
		return $this->replace($this->getPattern('alpha'), '', $input);
	}

	/**
	 * Sanitize a string to allow alphabetic characters and spaces.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeAlphaSpace(string $input): string
	{
		return $this->replace($this->getPattern('alpha_space'), '', $input);
	}

	/**
	 * Sanitize a string to allow alphabetic characters, dashes, and underscores.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeAlphaDash(string $input): string
	{
		return $this->replace($this->getPattern('alpha_dash'), '', $input);
	}

	/**
	 * Sanitize a string to allow alphanumeric characters.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeAlphaNumeric(string $input): string
	{
		return $this->replace($this->getPattern('alpha_numeric'), '', $input);
	}

	/**
	 * Sanitize a string to allow alphanumeric characters and spaces.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeAlphaNumericSpace(string $input): string
	{
		return $this->replace($this->getPattern('alpha_numeric_space'), '', $input);
	}

	/**
	 * Sanitize a string to allow alphanumeric characters, dashes, and underscores.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeAlphaNumericDash(string $input): string
	{
		return $this->replace($this->getPattern('alpha_numeric_dash'), '', $input);
	}

	/**
	 * Sanitize a string to allow only Unicode letters.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeUnicodeLetters(string $input): string
	{
		return $this->replace($this->getPattern('unicode_letters'), '', $input);
	}

	/**
	 * Sanitize a string to allow only Cyrillic characters.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeCyrillicText(string $input): string
	{
		return $this->replace($this->getPattern('cyrillic_text'), '', $input);
	}

	/**
	 * Sanitize a string to allow only Arabic characters.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeArabicText(string $input): string
	{
		return $this->replace($this->getPattern('arabic_text'), '', $input);
	}

	/**
	 * Sanitize a string to allow text with basic punctuation.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeTextWithPunctuation(string $input): string
	{
		return $this->replace($this->getPattern('text_with_punctuation'), '', $input);
	}

	/**
	 * Sanitize a string to allow specific special characters.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeContainsSpecialCharacters(string $input): string
	{
		return $this->replace($this->getPattern('contains_special_characters'), '', $input);
	}

	/**
	 * Sanitize a string to allow only ASCII characters.
	 *
	 * @param string $input The input string.
	 * @return string The sanitized string.
	 */
	public function sanitizeAsciiOnly(string $input): string
	{
		return $this->replace($this->getPattern('ascii_only'), '', $input);
	}

	/**
	 * Sanitize a string to allow valid hashtag characters.
	 *
	 * @param string $input The input hashtag.
	 * @return string The sanitized hashtag.
	 */
	public function sanitizeHashtag(string $input): string
	{
		return $this->replace($this->getPattern('hashtag'), '', $input);
	}

	/**
	 * Sanitize a string to allow valid Twitter handle characters.
	 *
	 * @param string $input The input Twitter handle.
	 * @return string The sanitized Twitter handle.
	 */
	public function sanitizeTwitterHandle(string $input): string
	{
		return $this->replace($this->getPattern('twitter_handle'), '', $input);
	}
}
