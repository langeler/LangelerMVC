<?php

namespace App\Utilities\Sanitation;

use App\Abstracts\Sanitizer;
use App\Utilities\Traits\Filters\sanitizationFilterTrait;
use App\Utilities\Traits\Patterns\sanitationPatternTrait;
use App\Utilities\Traits\generalTrait;

class FullSanitizer extends Sanitizer
{
    use sanitizationFilterTrait, sanitationPatternTrait, generalTrait;

    // === ENTRY POINT: sanitize method (Do not modify) ===
    protected function sanitize(mixed $data): array
    {
        return $this->handle($data);
    }

    // === FILTER FUNCTIONS WITHOUT FLAGS ===
    public function sanitizeEmail(string $input): string
    {
        return $this->filter($input, $this->sanitizeFilters['email']);
    }

    public function sanitizeUrl(string $input): string
    {
        return $this->filter($input, $this->sanitizeFilters['url']);
    }

    public function sanitizeString(string $input): string
    {
        return $this->filter($input, $this->sanitizeFilters['string']);
    }

    // === FILTER FUNCTIONS WITH FLAGS ===
    public function sanitizeFloatWithFlags(string $input, array $flags): string
    {
        $flagValue = $this->applySanitizationFlags($flags);
        return $this->filter($input, $this->sanitizeFilters['float'], $flagValue);
    }

    public function sanitizeStringWithFlags(string $input, array $flags): string
    {
        $flagValue = $this->applySanitizationFlags($flags);
        return $this->filter($input, $this->sanitizeFilters['string'], $flagValue);
    }

    // === COMBINATION: FILTER + PATTERN ===
    public function sanitizeUrlWithPattern(string $input, array $flags = []): string
    {
        $filtered = $this->filter($input, $this->sanitizeFilters['url'], $this->applySanitizationFlags($flags));
        return $this->sanitizeByPattern('url', $filtered);
    }

    public function sanitizeAlpha(string $input): string
    {
        return $this->sanitizeByPattern('alpha', $input);
    }

    public function sanitizeAlphanumeric(string $input): string
    {
        return $this->sanitizeByPattern('alphanumeric', $input);
    }

    // === ALL OTHER PATTERNS AND FILTERS ===
    public function sanitizeSessionId(string $input): string
    {
        return $this->sanitizeByPattern('session_id', $input);
    }

    public function sanitizePhoneNumber(string $input): string
    {
        return $this->sanitizeByPattern('phone_number', $input);
    }

    public function sanitizeJwtToken(string $input): string
    {
        return $this->sanitizeByPattern('jwt_token', $input);
    }

    public function sanitizeSqlInjection(string $input): string
    {
        return $this->sanitizeByPattern('sql_sanitize', $input);
    }

    public function sanitizeJson(string $input): string
    {
        return $this->sanitizeByPattern('json', $input);
    }

    // === NEW SANITIZATION FUNCTIONS ===

    // Sanitize alpha with spaces
    public function sanitizeAlphaSpace(string $input): string
    {
        return $this->sanitizeByPattern('alpha_space', $input);
    }

    // Sanitize alpha with dashes and underscores
    public function sanitizeAlphaDash(string $input): string
    {
        return $this->sanitizeByPattern('alpha_dash', $input);
    }

    // Sanitize alphanumeric with spaces
    public function sanitizeAlphanumericSpace(string $input): string
    {
        return $this->sanitizeByPattern('alpha_numeric_space', $input);
    }

    // Sanitize alphanumeric with dashes
    public function sanitizeAlphanumericDash(string $input): string
    {
        return $this->sanitizeByPattern('alpha_numeric_dash', $input);
    }

    // Sanitize full name
    public function sanitizeFullName(string $input): string
    {
        return $this->sanitizeByPattern('full_name', $input);
    }

    // Sanitize password (simple)
    public function sanitizePasswordSimple(string $input): string
    {
        return $this->sanitizeByPattern('password_simple', $input);
    }

    // Sanitize password (complex)
    public function sanitizePasswordComplex(string $input): string
    {
        return $this->sanitizeByPattern('password_complex', $input);
    }

    // Sanitize password without spaces
    public function sanitizePasswordNoSpaces(string $input): string
    {
        return $this->sanitizeByPattern('password_no_spaces', $input);
    }

    // Sanitize slug
    public function sanitizeSlug(string $input): string
    {
        return $this->sanitizeByPattern('slug', $input);
    }

    // Sanitize currency (USD)
    public function sanitizeCurrencyUSD(string $input): string
    {
        return $this->sanitizeByPattern('currency_usd', $input);
    }

    // Sanitize ethereum address
    public function sanitizeEthereumAddress(string $input): string
    {
        return $this->sanitizeByPattern('ethereum_address', $input);
    }

    // Sanitize bitcoin address
    public function sanitizeBitcoinAddress(string $input): string
    {
        return $this->sanitizeByPattern('bitcoin_address', $input);
    }

    // Sanitize percentage (0-100%)
    public function sanitizePercentage(string $input): string
    {
        return $this->sanitizeByPattern('percentage', $input);
    }

    // Sanitize IBAN
    public function sanitizeIban(string $input): string
    {
        return $this->sanitizeByPattern('iban', $input);
    }

    // Sanitize BIC
    public function sanitizeBic(string $input): string
    {
        return $this->sanitizeByPattern('bic', $input);
    }

    // Sanitize file extension
    public function sanitizeFileExtension(string $input): string
    {
        return $this->sanitizeByPattern('file_extension', $input);
    }

    // Sanitize IPv4 address
    public function sanitizeIPv4(string $input): string
    {
        return $this->sanitizeByPattern('ipv4_address', $input);
    }

    // Sanitize IPv6 address
    public function sanitizeIPv6(string $input): string
    {
        return $this->sanitizeByPattern('ipv6_address', $input);
    }

    // Sanitize URL slug
    public function sanitizeUrlSlug(string $input): string
    {
        return $this->sanitizeByPattern('url_slug', $input);
    }

    // Sanitize MIME type for images
    public function sanitizeImageMimeType(string $input): string
    {
        return $this->sanitizeByPattern('image_mime_type', $input);
    }

    // Sanitize Twitter handle
    public function sanitizeTwitterHandle(string $input): string
    {
        return $this->sanitizeByPattern('twitter_handle', $input);
    }

    // Sanitize hashtag
    public function sanitizeHashtag(string $input): string
    {
        return $this->sanitizeByPattern('hashtag', $input);
    }

    // Sanitize hexadecimals
    public function sanitizeHexadecimal(string $input): string
    {
        return $this->sanitizeByPattern('hexadecimal', $input);
    }

    // Add more sanitization patterns...
}
