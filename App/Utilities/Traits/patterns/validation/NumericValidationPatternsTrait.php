<?php

namespace App\Utilities\Traits\Patterns\Validation;

trait NumericValidationPatternsTrait
{
	public array $patterns = [
		'int_positive' => "/^\d+$/",
		'int_negative' => "/^-\d+$/",
		'int' => "/^-?\d+$/",
		'float_positive' => "/^\d*\.?\d+$/",
		'float_negative' => "/^-?\d*\.\d+$/",
		'float' => "/^-?\d*(\.\d+)?$/",
		'scientific' => "/^[+-]?\d+(\.\d+)?[eE][+-]?\d+$/",
		'currency_usd' => "/^\$?\d+(,\d{3})*(\.\d{2})?$/",
		'currency_euro' => "/^\€?\d+(,\d{3})*(\.\d{2})?$/",
		'currency_no_decimals' => "/^\d+(,\d{3})*$/",
		'percentage' => "/^100(\.0{1,2})?$|^\d{1,2}(\.\d{1,2})?%$/",
	];

		/**
		 * Retrieve the sanitization pattern by name.
		 */
		public function getPatterns(string $name): ?string
		{
			return $this->patterns[$name] ?? null;
		}
	}
