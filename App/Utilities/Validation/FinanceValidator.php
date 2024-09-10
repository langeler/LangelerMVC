<?php

namespace App\Utilities\Validation;

use App\Abstracts\Validator;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Traits\Patterns\Validation\FinanceValidationPatternsTrait;

/**
 * Class FinanceValidator
 *
 * Provides validation methods for finance-related fields using regex patterns.
 */
class FinanceValidator extends Validator
{
	use PatternTrait, FinanceValidationPatternsTrait;

	/**
	 * === ENTRY POINT: validate method (Do not modify) ===
	 *
	 * @param mixed $data The data to be validated.
	 * @return array The validated data array.
	 */
	protected function validate(mixed $data): array
	{
		return $this->handle($data);
	}

	// === Basic Validation Using Patterns ===

	/**
	 * Validate if the input is a valid credit card number.
	 *
	 * @param string $input The input credit card number.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateCreditCardNumber(string $input): bool
	{
		return $this->match($this->getPatterns('credit_card_number'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid ISBN-10 number.
	 *
	 * @param string $input The input ISBN-10 number.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateIsbn10(string $input): bool
	{
		return $this->match($this->getPatterns('isbn_10'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid US Social Security Number (SSN).
	 *
	 * @param string $input The input SSN.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateSsnUs(string $input): bool
	{
		return $this->match($this->getPatterns('ssn_us'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid IBAN (International Bank Account Number).
	 *
	 * @param string $input The input IBAN.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateIban(string $input): bool
	{
		return $this->match($this->getPatterns('iban'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid BIC (Business Identifier Code).
	 *
	 * @param string $input The input BIC.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateBic(string $input): bool
	{
		return $this->match($this->getPatterns('bic'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid Ethereum wallet address.
	 *
	 * @param string $input The input Ethereum address.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateEthereumAddress(string $input): bool
	{
		return $this->match($this->getPatterns('ethereum_address'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid Bitcoin wallet address.
	 *
	 * @param string $input The input Bitcoin address.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateBitcoinAddress(string $input): bool
	{
		return $this->match($this->getPatterns('bitcoin_address'), $input) === 1;
	}
}
