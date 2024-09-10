<?php

namespace App\Utilities\Traits\Patterns\Validation;

trait UserValidationPatternsTrait
{
	public array $patterns = [
		'full_name' => "/^[a-zA-Z]+(?:\s+[-a-zA-Z.'\s]+)*$/",
		'ssn_us' => "/^\d{3}-\d{2}-\d{4}$/",
		'phone_us' => "/^(\+1\s?)?\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}$/",
		'international_phone' => "/^\+\d{1,3}\s?\d{1,14}(\s?\d{1,13})?$/",
	];

		/**
		 * Retrieve the sanitization pattern by name.
		 */
		public function getPatterns(string $name): ?string
		{
			return $this->patterns[$name] ?? null;
		}
	}
