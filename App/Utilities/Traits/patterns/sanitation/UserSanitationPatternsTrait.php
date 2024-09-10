<?php

namespace App\Utilities\Traits\Patterns\Sanitation;

trait TextSanitationPatternsTrait
{
	public array $patterns = [
		'full_name' => '/[^a-zA-Z\s\.\'\-]/',
		'ssn_generic' => '/[^\d\-]/',
		'ssn_us' => '/[^\d\-]/',
		'phone_us' => '/[^\d\s\(\).\-\+]/',
		'international_phone' => '/[^\d\s\+]/',
		'zip_code_us' => '/[^\d\-]/',
		'zip_code_uk' => '/[^A-Z0-9\s]/i',
	];


	/**
		 * Retrieve the sanitization pattern by name.
		 */
		public function getPattern(string $name): ?string
		{
			return $this->patterns[$name] ?? null;
		}
	}

