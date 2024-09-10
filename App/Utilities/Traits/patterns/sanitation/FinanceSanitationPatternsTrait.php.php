<?php

namespace App\Utilities\Traits\Patterns\Sanitation;

trait FinanceSanitationPatternsTrait
{
	public array $patterns = [
		'credit_card_number' => '/[^\d]/',
		'isbn_10' => '/[^\dX]/',
		'iban' => '/[^A-Z0-9]/',
		'bic' => '/[^A-Z0-9]/',
		'ethereum_address' => '/[^a-fA-F0-9]/',
		'bitcoin_address' => '/[^13a-km-zA-HJ-NP-Z1-9]/',
		'currency_usd' => '/[^\d,.\$]/',
		'currency_euro' => '/[^\d,.\€]/',
		'currency_no_decimals' => '/[^\d,]/',
	];

	/**
		 * Retrieve the sanitization pattern by name.
		 */
		public function getPattern(string $name): ?string
		{
			return $this->patterns[$name] ?? null;
		}
	}

