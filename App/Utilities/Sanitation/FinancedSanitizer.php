<?php

namespace App\Utilities\Sanitation;

use App\Abstracts\Data\Sanitizer;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Traits\Patterns\Sanitation\FinanceSanitationPatternsTrait;

/**
 * Class FinanceSanitizer
 *
 * Provides sanitation methods for finance-related fields using regex patterns.
 */
class FinanceSanitizer extends Sanitizer
{
	use PatternTrait, FinanceSanitationPatternsTrait;

	/**
	 * === ENTRY POINT: sanitize method (Do not modify) ===
	 *
	 * @param mixed $data The data to be sanitized.
	 * @return array The sanitized data array.
	 */
	protected function clean(mixed $data): array
	{
		return $this->handle($data);
	}

	// === Basic Sanitation Using Patterns ===

	/**
	 * Sanitize a credit card number by removing invalid characters.
	 *
	 * @param string $input The input credit card number.
	 * @return string The sanitized credit card number.
	 */
	public function sanitizeCreditCardNumber(string $input): string
	{
		return $this->replace($this->getPattern('credit_card_number'), '', $input);
	}

	/**
	 * Sanitize an ISBN-10 number by removing invalid characters.
	 *
	 * @param string $input The input ISBN-10 number.
	 * @return string The sanitized ISBN-10 number.
	 */
	public function sanitizeIsbn10(string $input): string
	{
		return $this->replace($this->getPattern('isbn_10'), '', $input);
	}

	/**
	 * Sanitize an IBAN (International Bank Account Number) by removing invalid characters.
	 *
	 * @param string $input The input IBAN.
	 * @return string The sanitized IBAN.
	 */
	public function sanitizeIban(string $input): string
	{
		return $this->replace($this->getPattern('iban'), '', $input);
	}

	/**
	 * Sanitize a BIC (Bank Identifier Code) by removing invalid characters.
	 *
	 * @param string $input The input BIC.
	 * @return string The sanitized BIC.
	 */
	public function sanitizeBic(string $input): string
	{
		return $this->replace($this->getPattern('bic'), '', $input);
	}

	/**
	 * Sanitize an Ethereum wallet address by removing invalid characters.
	 *
	 * @param string $input The input Ethereum wallet address.
	 * @return string The sanitized Ethereum address.
	 */
	public function sanitizeEthereumAddress(string $input): string
	{
		return $this->replace($this->getPattern('ethereum_address'), '', $input);
	}

	/**
	 * Sanitize a Bitcoin wallet address by removing invalid characters.
	 *
	 * @param string $input The input Bitcoin wallet address.
	 * @return string The sanitized Bitcoin address.
	 */
	public function sanitizeBitcoinAddress(string $input): string
	{
		return $this->replace($this->getPattern('bitcoin_address'), '', $input);
	}

	/**
	 * Sanitize a currency format in USD by removing invalid characters.
	 *
	 * @param string $input The input currency in USD format.
	 * @return string The sanitized USD currency.
	 */
	public function sanitizeCurrencyUsd(string $input): string
	{
		return $this->replace($this->getPattern('currency_usd'), '', $input);
	}

	/**
	 * Sanitize a currency format in Euro by removing invalid characters.
	 *
	 * @param string $input The input currency in Euro format.
	 * @return string The sanitized Euro currency.
	 */
	public function sanitizeCurrencyEuro(string $input): string
	{
		return $this->replace($this->getPattern('currency_euro'), '', $input);
	}

	/**
	 * Sanitize a currency format with no decimals by removing invalid characters.
	 *
	 * @param string $input The input currency with no decimals.
	 * @return string The sanitized currency.
	 */
	public function sanitizeCurrencyNoDecimals(string $input): string
	{
		return $this->replace($this->getPattern('currency_no_decimals'), '', $input);
	}
}
