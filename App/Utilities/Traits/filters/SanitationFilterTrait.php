<?php

namespace App\Utilities\Traits\Filters;

use InvalidArgumentException;

trait SanitationTrait
{
	use FiltrationTrait;

	public readonly array $filters;
	public readonly array $flags;

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
			'fullSpecialChars' => FILTER_SANITIZE_FULL_SPECIAL_CHARS, // Escapes HTML special characters
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

	public function sanitizeEncoded(string $input, array $flags = []): string
	{
		$options = $this->getFilterOptions('encoded', $flags);
		return $this->var($input, $this->filters['encoded'], $options);
	}

	public function sanitizeString(string $input, array $flags = []): string
	{
		$options = $this->getFilterOptions('string', $flags);
		return $this->var($input, $this->filters['string'], $options);
	}

	public function sanitizeEmail(string $input): string
	{
		return $this->var($input, $this->filters['email']);
	}

	public function sanitizeUrl(string $input): string
	{
		return $this->var($input, $this->filters['url']);
	}

	public function sanitizeInt(string $input, array $flags = []): string
	{
		$options = $this->getFilterOptions('int', $flags);
		return $this->var($input, $this->filters['int'], $options);
	}

	public function sanitizeFloat(string $input, array $flags = []): string
	{
		$options = $this->getFilterOptions('float', $flags);
		return $this->var($input, $this->filters['float'], $options);
	}

	public function sanitizeAddSlashes(string $input): string
	{
		return $this->var($input, $this->filters['addSlashes']);
	}

	public function sanitizeFullSpecialChars(string $input, array $flags = []): string
	{
		$options = $this->getFilterOptions('fullSpecialChars', $flags);
		return $this->var($input, $this->filters['fullSpecialChars'], $options);
	}

	private function getFilterOptions(string $filter, array $flagKeys): array
	{
		$flagValues = array_reduce($flagKeys, function ($carry, $flagKey) {
			$carry |= $this->flags[$flagKey] ?? 0;
			return $carry;
		}, 0);

		return ['flags' => $flagValues];
	}
}
