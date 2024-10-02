<?php

namespace App\Utilities\Traits\Patterns\Validation;

trait TextValidationPatternsTrait
{
	public array $patterns = [
		'alpha' => "/^[a-zA-Z]+$/",
		'alpha_space' => "/^[a-zA-Z\s]+$/",
		'alpha_dash' => "/^[a-zA-Z-_]+$/",
		'alpha_numeric' => "/^[a-zA-Z0-9]+$/",
		'alpha_numeric_space' => "/^[a-zA-Z0-9\s]+$/",
		'alpha_numeric_dash' => "/^[a-zA-Z0-9-_]+$/",
		'full_name' => "/^[a-zA-Z]+(?:\s+[-a-zA-Z.'\s]+)*$/",
		'unicode_letters' => "/^\p{L}+$/u",
		'text_with_punctuation' => "/^[\w\s.,'!?-]+$/",
		'contains_special_characters' => "/[!@#$%^&*(),.?\":{}|<>]/",
		'ascii_only' => "/^[\x00-\x7F]+$/",
		'cyrillic_text' => "/^[\p{Cyrillic}]+$/u",
		'arabic_text' => "/^[\p{Arabic}]+$/u",
		'hashtag' => "/^#[a-zA-Z0-9_]+$/",
		'twitter_handle' => "/^@?([a-zA-Z0-9_]{1,15})$/",
	];

		/**
		 * Retrieve the sanitization pattern by name.
		 */
		public function getPatterns(string $name): ?string
		{
			return $this->patterns[$name] ?? null;
		}
	}
