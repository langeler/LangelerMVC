<?php

namespace App\Utilities\Traits\Patterns\Sanitation;

trait AlphabeticTextPatternsTrait
{
	public array $patterns = [
		'alpha' => '/[^a-zA-Z]/',
		'alpha_space' => '/[^a-zA-Z\s]/',
		'alpha_dash' => '/[^a-zA-Z-_]/',
		'alpha_numeric' => '/[^a-zA-Z0-9]/',
		'alpha_numeric_space' => '/[^a-zA-Z0-9\s]/',
		'alpha_numeric_dash' => '/[^a-zA-Z0-9-_]/',
		'unicode_letters' => '/[^\p{L}]/u',
		'cyrillic_text' => '/[^\p{Cyrillic}]/u',
		'arabic_text' => '/[^\p{Arabic}]/u',
		'text_with_punctuation' => '/[^\w\s.,\'!?-]/',
		'contains_special_characters' => '/[^!@#$%^&*(),.?":{}|<>]/',
		'ascii_only' => '/[^\x00-\x7F]/',
		'hashtag' => '/[^a-zA-Z0-9_#]/',
		'twitter_handle' => '/[^a-zA-Z0-9_]/',
	];

	/**
		 * Retrieve the sanitization pattern by name.
		 */
		public function getPattern(string $name): ?string
		{
			return $this->patterns[$name] ?? null;
		}
	}

