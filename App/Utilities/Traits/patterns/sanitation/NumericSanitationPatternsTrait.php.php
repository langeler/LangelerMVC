<?php

namespace App\Utilities\Traits\Patterns\Sanitation;

trait NumericSanitationPatternsTrait
{
	public array $patterns = [
		'int_positive' => '/[^\d]/',
		'int_negative' => '/[^\-\d]/',
		'int' => '/[^\-\d]/',
		'float_positive' => '/[^\d.]/',
		'float_negative' => '/[^\d.\-]/',
		'float' => '/[^\d.\-]/',
		'scientific' => '/[^0-9eE\.\+\-]/',
		'percentage' => '/[^\d\.\%]/',
		'range_1_to_100' => '/[^\d]/',
		'negative_range_1_to_100' => '/[^\-\d]/',
	];


	/**
		 * Retrieve the sanitization pattern by name.
		 */
		public function getPattern(string $name): ?string
		{
			return $this->patterns[$name] ?? null;
		}
	}

