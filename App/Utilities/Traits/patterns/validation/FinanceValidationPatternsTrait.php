<?php

namespace App\Utilities\Traits\Patterns\Validation;

trait FinanceValidationPatternsTrait
{
	public array $patterns = [
		'credit_card_number' => "/^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|3[47][0-9]{13}|6(?:011|5[0-9]{2})[0-9]{12})$/",
		'isbn_10' => "/^\d{9}(\d|X)$/",
		'ssn_us' => "/^\d{3}-\d{2}-\d{4}$/",
		'iban' => "/^[A-Z]{2}\d{2}[A-Z0-9]{1,30}$/",
		'bic' => "/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/",
		'ethereum_address' => "/^0x[a-fA-F0-9]{40}$/",
		'bitcoin_address' => "/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$/",
	];

		/**
		 * Retrieve the sanitization pattern by name.
		 */
		public function getPatterns(string $name): ?string
		{
			return $this->patterns[$name] ?? null;
		}
	}
