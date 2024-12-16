<?php

namespace App\Utilities\Traits\Filters;

use InvalidArgumentException;
use App\Utilities\Traits\ArrayTrait;

/**
 * Trait SanitationTrait
 *
 * Provides methods to sanitize various data types using PHP filters with flexible flag handling.
 * Preserves the original filters and flags properties while leveraging ArrayTrait methods to avoid native array operations.
 */
trait SanitationTrait
{
	use FiltrationTrait, ArrayTrait;

	public readonly array $filters;
	public readonly array $flags;

	/**
	 * Constructor to initialize filters and flags.
	 */
	public function __construct()
	{
		$this->filters = [
			'encoded' => FILTER_SANITIZE_ENCODED,            // URL-encodes a string
			'string' => FILTER_SANITIZE_SPECIAL_CHARS,       // Escapes HTML special characters
			'email' => FILTER_SANITIZE_EMAIL,                // Removes invalid characters from an email
			'url' => FILTER_SANITIZE_URL,                    // Removes invalid characters from a URL
			'int' => FILTER_SANITIZE_NUMBER_INT,             // Removes all characters except digits, plus and minus signs
			'float' => FILTER_SANITIZE_NUMBER_FLOAT,         // Removes all characters except digits, +-., and optionally eE for floats
			'addSlashes' => FILTER_SANITIZE_ADD_SLASHES,     // Adds backslashes before special characters
			'fullSpecialChars' => FILTER_SANITIZE_FULL_SPECIAL_CHARS // Escapes HTML special characters
		];

		$this->flags = [
			'allowFraction' => FILTER_FLAG_ALLOW_FRACTION,      // Allows decimal fractions in numbers
			'allowScientific' => FILTER_FLAG_ALLOW_SCIENTIFIC,  // Allows scientific notation in numbers
			'allowThousand' => FILTER_FLAG_ALLOW_THOUSAND,      // Allows thousand separators in numbers
			'noEncodeQuotes' => FILTER_FLAG_NO_ENCODE_QUOTES,   // Prevents encoding of quotes
			'stripLow' => FILTER_FLAG_STRIP_LOW,                // Strips ASCII < 32 characters
			'stripHigh' => FILTER_FLAG_STRIP_HIGH,              // Strips ASCII > 127 characters
			'encodeAmp' => FILTER_FLAG_ENCODE_AMP,              // Encodes ampersands
			'stripBacktick' => FILTER_FLAG_STRIP_BACKTICK       // Strips backticks
		];
	}

	/**
	 * Sanitize input using the encoded filter.
	 *
	 * @param string $input The input to sanitize.
	 * @param array $flags Optional array of flag keys to apply.
	 * @return string The sanitized input.
	 */
	public function sanitizeEncoded(string $input, array $flags = []): string
	{
		return $this->var($input, $this->filters['encoded'], $this->getFilterOptions('encoded', $flags));
	}

	/**
	 * Sanitize input using the string filter.
	 *
	 * @param string $input The input to sanitize.
	 * @param array $flags Optional array of flag keys to apply.
	 * @return string The sanitized input.
	 */
	public function sanitizeString(string $input, array $flags = []): string
	{
		return $this->var($input, $this->filters['string'], $this->getFilterOptions('string', $flags));
	}

	/**
	 * Sanitize an email address.
	 *
	 * @param string $input The input to sanitize.
	 * @return string The sanitized input.
	 */
	public function sanitizeEmail(string $input): string
	{
		return $this->var($input, $this->filters['email']);
	}

	/**
	 * Sanitize a URL.
	 *
	 * @param string $input The input to sanitize.
	 * @return string The sanitized input.
	 */
	public function sanitizeUrl(string $input): string
	{
		return $this->var($input, $this->filters['url']);
	}

	/**
	 * Sanitize an integer.
	 *
	 * @param string $input The input to sanitize.
	 * @param array $flags Optional array of flag keys to apply.
	 * @return string The sanitized input.
	 */
	public function sanitizeInt(string $input, array $flags = []): string
	{
		return $this->var($input, $this->filters['int'], $this->getFilterOptions('int', $flags));
	}

	/**
	 * Sanitize a floating-point number.
	 *
	 * @param string $input The input to sanitize.
	 * @param array $flags Optional array of flag keys to apply.
	 * @return string The sanitized input.
	 */
	public function sanitizeFloat(string $input, array $flags = []): string
	{
		return $this->var($input, $this->filters['float'], $this->getFilterOptions('float', $flags));
	}

	/**
	 * Sanitize input by adding slashes before special characters.
	 *
	 * @param string $input The input to sanitize.
	 * @return string The sanitized input.
	 */
	public function sanitizeAddSlashes(string $input): string
	{
		return $this->var($input, $this->filters['addSlashes']);
	}

	/**
	 * Sanitize input using the fullSpecialChars filter.
	 *
	 * @param string $input The input to sanitize.
	 * @param array $flags Optional array of flag keys to apply.
	 * @return string The sanitized input.
	 */
	public function sanitizeFullSpecialChars(string $input, array $flags = []): string
	{
		return $this->var($input, $this->filters['fullSpecialChars'], $this->getFilterOptions('fullSpecialChars', $flags));
	}

	/**
	 * Get filter options for a given filter and flags.
	 *
	 * @param string $filter The filter key.
	 * @param array $flagKeys Array of flag keys to apply.
	 * @return array An array of options with the combined flags.
	 */
	private function getFilterOptions(string $filter, array $flagKeys): array
	{
		return [
			'flags' => $this->reduce(
				$flagKeys,
				fn($carry, $key) => $carry | ($this->flags[$key] ?? 0),
				0
			)
		];
	}
}
