<?php

namespace App\Utilities\Validation;

use App\Abstracts\Validator;
use App\Utilities\Traits\Filters\validationFilterTrait;
use App\Utilities\Traits\Patterns\validationPatternTrait;
use App\Utilities\Traits\generalTrait;

class FullValidator extends Validator
{
    use validationFilterTrait, validationPatternTrait, generalTrait;

    // === ENTRY POINT: validate method (Do not modify) ===
    protected function validate(mixed $data): array
    {
        return $this->handle($data);
    }

    // === FILTER FUNCTIONS WITHOUT FLAGS ===
    public function validateEmail(string $input): bool
    {
        return $this->filter($input, $this->validateFilters['email']) !== false;
    }

    public function validateUrl(string $input): bool
    {
        return $this->filter($input, $this->validateFilters['url']) !== false;
    }

    public function validateBoolean(string $input): bool
    {
        return $this->filter($input, $this->validateFilters['boolean']) !== false;
    }

    // === FILTER FUNCTIONS WITH FLAGS ===
    public function validateIpv4WithFlags(string $input, array $flags): bool
    {
        $flagValue = $this->applyValidationFlags($flags);
        return $this->filter($input, $this->validateFilters['ipv4'], $flagValue) !== false;
    }

    public function validateUrlWithFlags(string $input, array $flags): bool
    {
        $flagValue = $this->applyValidationFlags($flags);
        return $this->filter($input, $this->validateFilters['url'], $flagValue) !== false;
    }

    // === COMBINATION: FILTER + PATTERN ===
    public function validateEmailWithPattern(string $input): bool
    {
        $filtered = $this->filter($input, $this->validateFilters['email']);
        return $filtered && $this->validateByPattern('email_strict', $input);
    }

    public function validateUsername(string $input): bool
    {
        return $this->validateByPattern('username', $input);
    }

    public function validateJwtToken(string $input): bool
    {
        return $this->validateByPattern('jwt_token', $input);
    }

    public function validatePhoneNumber(string $input): bool
    {
        return $this->validateByPattern('phone_us', $input);
    }

    public function validateUUID(string $input): bool
    {
        return $this->validateByPattern('uuid', $input);
    }

    public function validateFilePath(string $input): bool
    {
        return $this->validateByPattern('file_path_unix', $input);
    }

    // === ALL OTHER PATTERNS AND FILTERS ===
    public function validateCreditCardCVV(string $input): bool
    {
        return $this->validateByPattern('credit_card_cvv', $input);
    }

    public function validateIPv4(string $input): bool
    {
        return $this->validateByPattern('ipv4_address', $input);
    }

    public function validateMacAddress(string $input): bool
    {
        return $this->validateByPattern('mac_address', $input);
    }

    public function validateSSN(string $input): bool
    {
        return $this->validateByPattern('ssn_us', $input);
    }

    public function validateIsbn(string $input): bool
    {
        return $this->validateByPattern('isbn_10', $input);
    }

    // === NEW VALIDATION FUNCTIONS ===

    // Alphanumeric validation
    public function validateAlphaNumeric(string $input): bool
    {
        return $this->validateByPattern('alpha_numeric', $input);
    }

    // Alphanumeric with spaces validation
    public function validateAlphaNumericSpace(string $input): bool
    {
        return $this->validateByPattern('alpha_numeric_space', $input);
    }

    // Alphanumeric with dash validation
    public function validateAlphaNumericDash(string $input): bool
    {
        return $this->validateByPattern('alpha_numeric_dash', $input);
    }

    // Validate simple password
    public function validatePasswordSimple(string $input): bool
    {
        return $this->validateByPattern('password_simple', $input);
    }

    // Validate complex password
    public function validatePasswordComplex(string $input): bool
    {
        return $this->validateByPattern('password_complex', $input);
    }

    // Validate without spaces in password
    public function validatePasswordNoSpaces(string $input): bool
    {
        return $this->validateByPattern('password_no_spaces', $input);
    }

    // Validate full name
    public function validateFullName(string $input): bool
    {
        return $this->validateByPattern('full_name', $input);
    }

    // Validate IP Address (IPv6)
    public function validateIPv6(string $input): bool
    {
        return $this->validateByPattern('ipv6_address', $input);
    }

    // Validate currency (USD format)
    public function validateCurrencyUSD(string $input): bool
    {
        return $this->validateByPattern('currency_usd', $input);
    }

    // Validate Ethereum address
    public function validateEthereumAddress(string $input): bool
    {
        return $this->validateByPattern('ethereum_address', $input);
    }

    // Validate Bitcoin address
    public function validateBitcoinAddress(string $input): bool
    {
        return $this->validateByPattern('bitcoin_address', $input);
    }

    // Validate percentage (0-100%)
    public function validatePercentage(string $input): bool
    {
        return $this->validateByPattern('percentage', $input);
    }

    // Validate International Bank Account Number (IBAN)
    public function validateIban(string $input): bool
    {
        return $this->validateByPattern('iban', $input);
    }

    // Validate Bank Identifier Code (BIC)
    public function validateBic(string $input): bool
    {
        return $this->validateByPattern('bic', $input);
    }

    // Validate file extension
    public function validateFileExtension(string $input): bool
    {
        return $this->validateByPattern('file_extension', $input);
    }

    // Validate URL slug
    public function validateUrlSlug(string $input): bool
    {
        return $this->validateByPattern('url_slug', $input);
    }

    // Validate MIME type for images
    public function validateImageMimeType(string $input): bool
    {
        return $this->validateByPattern('image_mime_type', $input);
    }

    // Validate Twitter handle
    public function validateTwitterHandle(string $input): bool
    {
        return $this->validateByPattern('twitter_handle', $input);
    }

    // Validate social media hashtag
    public function validateHashtag(string $input): bool
    {
        return $this->validateByPattern('hashtag', $input);
    }

    // Validate hexadecimal
    public function validateHexadecimal(string $input): bool
    {
        return $this->validateByPattern('hexadecimal', $input);
    }

    // Add more validation functions based on patterns...
}
