<?php

namespace App\Utilities\Validation;

use App\Abstracts\Validator;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Traits\Patterns\Validation\TextValidationPatternsTrait;

/**
 * Class TextValidator
 *
 * Provides validation methods for various text formats using regex patterns.
 */
class TextValidator extends Validator
{
	use PatternTrait, TextValidationPatternsTrait;

	/**
	 * === ENTRY POINT: validate method (Do not modify) ===
	 *
	 * @param mixed $data The data to be validated.
	 * @return array The validated data array.
	 */
	protected function validate(mixed $data): array
	{
		return $this->handle($data);
	}

	// === Basic Validation Using Patterns ===

	/**
	 * Validate if the input contains only alphabetic characters.
	 *
	 * @param string $input The input string.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateAlpha(string $input): bool
	{
		return $this->match($this->getPatterns('alpha'), $input) === 1;
	}

	/**
	 * Validate if the input contains only alphabetic characters and spaces.
	 *
	 * @param string $input The input string.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateAlphaSpace(string $input): bool
	{
		return $this->match($this->getPatterns('alpha_space'), $input) === 1;
	}

	/**
	 * Validate if the input contains alphabetic characters, dashes, or underscores.
	 *
	 * @param string $input The input string.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateAlphaDash(string $input): bool
	{
		return $this->match($this->getPatterns('alpha_dash'), $input) === 1;
	}

	/**
	 * Validate if the input contains only alphanumeric characters.
	 *
	 * @param string $input The input string.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateAlphaNumeric(string $input): bool
	{
		return $this->match($this->getPatterns('alpha_numeric'), $input) === 1;
	}

	/**
	 * Validate if the input contains alphanumeric characters and spaces.
	 *
	 * @param string $input The input string.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateAlphaNumericSpace(string $input): bool
	{
		return $this->match($this->getPatterns('alpha_numeric_space'), $input) === 1;
	}

	/**
	 * Validate if the input contains alphanumeric characters, dashes, or underscores.
	 *
	 * @param string $input The input string.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateAlphaNumericDash(string $input): bool
	{
		return $this->match($this->getPatterns('alpha_numeric_dash'), $input) === 1;
	}

	/**
	 * Validate if the input matches a full name pattern (including spaces, hyphens, and apostrophes).
	 *
	 * @param string $input The input full name.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateFullName(string $input): bool
	{
		return $this->match($this->getPatterns('full_name'), $input) === 1;
	}

	/**
	 * Validate if the input contains only unicode letters.
	 *
	 * @param string $input The input string.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateUnicodeLetters(string $input): bool
	{
		return $this->match($this->getPatterns('unicode_letters'), $input) === 1;
	}

	/**
	 * Validate if the input contains text with basic punctuation.
	 *
	 * @param string $input The input string.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateTextWithPunctuation(string $input): bool
	{
		return $this->match($this->getPatterns('text_with_punctuation'), $input) === 1;
	}

	/**
	 * Validate if the input contains special characters.
	 *
	 * @param string $input The input string.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateContainsSpecialCharacters(string $input): bool
	{
		return $this->match($this->getPatterns('contains_special_characters'), $input) === 1;
	}

	/**
	 * Validate if the input contains only ASCII characters.
	 *
	 * @param string $input The input string.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateAsciiOnly(string $input): bool
	{
		return $this->match($this->getPatterns('ascii_only'), $input) === 1;
	}

	/**
	 * Validate if the input contains only Cyrillic characters.
	 *
	 * @param string $input The input string.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateCyrillicText(string $input): bool
	{
		return $this->match($this->getPatterns('cyrillic_text'), $input) === 1;
	}

	/**
	 * Validate if the input contains only Arabic characters.
	 *
	 * @param string $input The input string.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateArabicText(string $input): bool
	{
		return $this->match($this->getPatterns('arabic_text'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid hashtag.
	 *
	 * @param string $input The input string.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateHashtag(string $input): bool
	{
		return $this->match($this->getPatterns('hashtag'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid Twitter handle.
	 *
	 * @param string $input The input Twitter handle.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateTwitterHandle(string $input): bool
	{
		return $this->match($this->getPatterns('twitter_handle'), $input) === 1;
	}
}
