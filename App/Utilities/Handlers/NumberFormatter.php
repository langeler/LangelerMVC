<?php

namespace App\Utilities\Handlers;

use NumberFormatter;

/**
 * NumberFormatterHandler provides utilities for managing number formatting using PHP's NumberFormatter class.
 */
class NumberFormatterHandler
{
    /**
     * Constructs the handler and initializes the constants property.
     *
(     * @param NumberFormatter|null $formatter Optional existing NumberFormatter instance.
     * @param string $locale Locale for NumberFormatter, defaults to 'en_US'.
     * @param int $style Formatting style, defaults to DECIMAL.
     * @param string|null $pattern Optional custom pattern for formatting.
     * @param array $classConstants All NumberFormatter-related constants.
     */
	
/**
 * Constructs the handler and initializes the constants property.
 *
 * @param NumberFormatter|null $formatter Optional existing NumberFormatter instance.
 * @param string $locale Locale for NumberFormatter, defaults to 'en_US'.
 * @param int $style Formatting style, defaults to DECIMAL.
 * @param string|null $pattern Optional custom pattern for formatting.
 * @param array $classConstants Placeholder for NumberFormatter-related constants.
 */
public function __construct(
    protected ?NumberFormatter $formatter = null,
    protected string $locale = 'en_US',
    protected int $style = NumberFormatter::DECIMAL,
    protected ?string $pattern = null,
    protected readonly array $classConstants = [] // Placeholder for constants
) {
    $this->classConstants = [
        // Format types
        'patternDecimal' => NumberFormatter::PATTERN_DECIMAL, // Decimal format defined by pattern
        'decimal' => NumberFormatter::DECIMAL, // Decimal format
        'currency' => NumberFormatter::CURRENCY, // Currency format
        'percent' => NumberFormatter::PERCENT, // Percent format
        'scientific' => NumberFormatter::SCIENTIFIC, // Scientific format
        'spellout' => NumberFormatter::SPELLOUT, // Spellout rule-based format
        'ordinal' => NumberFormatter::ORDINAL, // Ordinal rule-based format
        'duration' => NumberFormatter::DURATION, // Duration rule-based format
        'patternRuleBased' => NumberFormatter::PATTERN_RULEBASED, // Rule-based format defined by pattern
        'ignore' => NumberFormatter::IGNORE, // Alias for PATTERN_DECIMAL
        'currencyAccounting' => NumberFormatter::CURRENCY_ACCOUNTING, // Currency format for accounting
        'defaultStyle' => NumberFormatter::DEFAULT_STYLE, // Default format for the locale
        
        // Rounding modes
        'roundCeiling' => NumberFormatter::ROUND_CEILING, // Round towards positive infinity
        'roundFloor' => NumberFormatter::ROUND_FLOOR, // Round towards negative infinity
        'roundDown' => NumberFormatter::ROUND_DOWN, // Round towards zero
        'roundUp' => NumberFormatter::ROUND_UP, // Round away from zero
        'roundTowardZero' => NumberFormatter::ROUND_TOWARD_ZERO, // Alias of ROUND_DOWN
        'roundAwayFromZero' => NumberFormatter::ROUND_AWAY_FROM_ZERO, // Alias of ROUND_UP
        'roundHalfEven' => NumberFormatter::ROUND_HALFEVEN, // Round towards nearest neighbor, favor even
        'roundHalfOdd' => NumberFormatter::ROUND_HALFODD, // Round towards nearest neighbor, favor odd
        'roundHalfDown' => NumberFormatter::ROUND_HALFDOWN, // Round towards nearest, favor down
        'roundHalfUp' => NumberFormatter::ROUND_HALFUP, // Round towards nearest, favor up
        
        // Parsing and formatting attributes
        'parseIntOnly' => NumberFormatter::PARSE_INT_ONLY, // Parse integers only
        'groupingUsed' => NumberFormatter::GROUPING_USED, // Use grouping separator
        'decimalAlwaysShown' => NumberFormatter::DECIMAL_ALWAYS_SHOWN, // Always show decimal point
        'maxIntegerDigits' => NumberFormatter::MAX_INTEGER_DIGITS, // Maximum integer digits
        'minIntegerDigits' => NumberFormatter::MIN_INTEGER_DIGITS, // Minimum integer digits
        'integerDigits' => NumberFormatter::INTEGER_DIGITS, // Integer digits
        'maxFractionDigits' => NumberFormatter::MAX_FRACTION_DIGITS, // Maximum fraction digits
        'minFractionDigits' => NumberFormatter::MIN_FRACTION_DIGITS, // Minimum fraction digits
        'fractionDigits' => NumberFormatter::FRACTION_DIGITS, // Fraction digits
        'multiplier' => NumberFormatter::MULTIPLIER, // Multiplier for formatting
        'groupingSize' => NumberFormatter::GROUPING_SIZE, // Grouping size
        'roundingMode' => NumberFormatter::ROUNDING_MODE, // Rounding mode
        'roundingIncrement' => NumberFormatter::ROUNDING_INCREMENT, // Rounding increment
        'formatWidth' => NumberFormatter::FORMAT_WIDTH, // Width to pad formatted output
        'paddingPosition' => NumberFormatter::PADDING_POSITION, // Padding position
        'secondaryGroupingSize' => NumberFormatter::SECONDARY_GROUPING_SIZE, // Secondary grouping size
        'significantDigitsUsed' => NumberFormatter::SIGNIFICANT_DIGITS_USED, // Use significant digits
        'minSignificantDigits' => NumberFormatter::MIN_SIGNIFICANT_DIGITS, // Minimum significant digits
        'maxSignificantDigits' => NumberFormatter::MAX_SIGNIFICANT_DIGITS, // Maximum significant digits
        'lenientParse' => NumberFormatter::LENIENT_PARSE, // Lenient parsing for rule-based formats
        
        // Text attributes
        'positivePrefix' => NumberFormatter::POSITIVE_PREFIX, // Positive prefix
        'positiveSuffix' => NumberFormatter::POSITIVE_SUFFIX, // Positive suffix
        'negativePrefix' => NumberFormatter::NEGATIVE_PREFIX, // Negative prefix
        'negativeSuffix' => NumberFormatter::NEGATIVE_SUFFIX, // Negative suffix
        'paddingCharacter' => NumberFormatter::PADDING_CHARACTER, // Padding character
        'currencyCode' => NumberFormatter::CURRENCY_CODE, // ISO currency code
        'defaultRuleset' => NumberFormatter::DEFAULT_RULESET, // Default ruleset
        'publicRulesets' => NumberFormatter::PUBLIC_RULESETS, // Public rulesets
        
        // Symbol attributes
        'decimalSeparatorSymbol' => NumberFormatter::DECIMAL_SEPARATOR_SYMBOL, // Decimal separator
        'groupingSeparatorSymbol' => NumberFormatter::GROUPING_SEPARATOR_SYMBOL, // Grouping separator
        'patternSeparatorSymbol' => NumberFormatter::PATTERN_SEPARATOR_SYMBOL, // Pattern separator
        'percentSymbol' => NumberFormatter::PERCENT_SYMBOL, // Percent sign
        'zeroDigitSymbol' => NumberFormatter::ZERO_DIGIT_SYMBOL, // Zero digit
        'digitSymbol' => NumberFormatter::DIGIT_SYMBOL, // Digit symbol in pattern
        'minusSignSymbol' => NumberFormatter::MINUS_SIGN_SYMBOL, // Minus sign
        'plusSignSymbol' => NumberFormatter::PLUS_SIGN_SYMBOL, // Plus sign
        'currencySymbol' => NumberFormatter::CURRENCY_SYMBOL, // Currency symbol
        'intlCurrencySymbol' => NumberFormatter::INTL_CURRENCY_SYMBOL, // International currency symbol
        'monetarySeparatorSymbol' => NumberFormatter::MONETARY_SEPARATOR_SYMBOL, // Monetary separator
        'exponentialSymbol' => NumberFormatter::EXPONENTIAL_SYMBOL, // Exponential symbol
        'permillSymbol' => NumberFormatter::PERMILL_SYMBOL, // Per mille symbol
        'padEscapeSymbol' => NumberFormatter::PAD_ESCAPE_SYMBOL, // Padding escape character
        'infinitySymbol' => NumberFormatter::INFINITY_SYMBOL, // Infinity symbol
        'nanSymbol' => NumberFormatter::NAN_SYMBOL, // Not-a-number symbol
        'significantDigitSymbol' => NumberFormatter::SIGNIFICANT_DIGIT_SYMBOL, // Significant digit symbol
        'monetaryGroupingSymbol' => NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL, // Monetary grouping separator
        
        // Format types
        'typeDefault' => NumberFormatter::TYPE_DEFAULT, // Default type from variable
        'typeInt32' => NumberFormatter::TYPE_INT32, // 32-bit integer
        'typeInt64' => NumberFormatter::TYPE_INT64, // 64-bit integer
        'typeDouble' => NumberFormatter::TYPE_DOUBLE, // Floating point value
        'typeCurrency' => NumberFormatter::TYPE_CURRENCY, // Currency value
    ];
}

/**
     * Wraps callable execution in a try-catch block for consistent error handling.
     *
     * @param callable $callback The function to execute.
     * @return mixed The result of the callable.
     */
    private function wrapInTry(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            throw new \Exception("An error occurred: " . $e->getMessage());
        }
    }

    /**
     * Initializes a new NumberFormatter instance.
     *
     * @param string $locale Locale for the formatter.
     * @param int $style Style for the formatter.
     * @param string|null $pattern Optional pattern for formatting.
     * @return NumberFormatter
     */
    public function initialize(string $locale, int $style, ?string $pattern = null): NumberFormatter
    {
        return $this->wrapInTry(fn() => new NumberFormatter($locale, $style, $pattern));
    }

    // Other methods (like `format`, `formatCurrency`, etc.) will follow similar implementation, leveraging `classConstants`.
}

    /**
     * Formats a number using the specified type.
     *
     * @param int|float $num The number to format.
     * @param int $type The type of formatting.
     * @return string|false The formatted number.
     */
    public function format(int|float $num, int $type = NumberFormatter::TYPE_DEFAULT): string|false
    {
        return $this->wrapInTry(fn() => $this->initialize(
            $this->locale,
            $this->style,
            $this->pattern
        )->format($num, $type));
    }

    /**
     * Formats a currency value.
     *
     * @param float $amount The currency amount.
     * @param string $currency The currency code.
     * @return string|false The formatted currency string.
     */
    public function formatCurrency(float $amount, string $currency): string|false
    {
        return $this->wrapInTry(fn() => $this->initialize(
            $this->locale,
            $this->classConstants['currency'],
            $this->pattern
        )->formatCurrency($amount, $currency));
    }

    /**
     * Retrieves a formatter attribute.
     *
     * @param int $attribute The attribute to retrieve.
     * @return int|float|false The attribute value.
     */
    public function getAttribute(int $attribute): int|float|false
    {
        return $this->wrapInTry(fn() => $this->initialize(
            $this->locale,
            $this->style,
            $this->pattern
        )->getAttribute($attribute));
    }

    /**
     * Sets a formatter attribute.
     *
     * @param int $attribute The attribute to set.
     * @param int|float $value The value to set.
     * @return bool True on success, false on failure.
     */
    public function setAttribute(int $attribute, int|float $value): bool
    {
        return $this->wrapInTry(fn() => $this->initialize(
            $this->locale,
            $this->style,
            $this->pattern
        )->setAttribute($attribute, $value));
    }

    /**
     * Parses a formatted number string into a number.
     *
     * @param string $string The formatted number string.
     * @param int $type The type of number to parse.
     * @return int|float|false The parsed number.
     */
    public function parse(string $string, int $type = NumberFormatter::TYPE_DOUBLE): int|float|false
    {
        return $this->wrapInTry(fn() => $this->initialize(
            $this->locale,
            $this->style,
            $this->pattern
        )->parse($string, $type));
    }

    /**
     * Parses a formatted currency string into a numeric value and retrieves the currency code.
     *
     * @param string $string The formatted currency string.
     * @param string &$currency A reference to store the currency code.
     * @return float|false The parsed currency amount.
     */
    public function parseCurrency(string $string, string &$currency): float|false
    {
        return $this->wrapInTry(fn() => $this->initialize(
            $this->locale,
            $this->classConstants['currency'],
            $this->pattern
        )->parseCurrency($string, $currency));
    }

    /**
     * Retrieves a formatter's text attribute.
     *
     * @param int $attribute The text attribute to retrieve.
     * @return string|false The text attribute value.
     */
    public function getTextAttribute(int $attribute): string|false
    {
        return $this->wrapInTry(fn() => $this->initialize(
            $this->locale,
            $this->style,
            $this->pattern
        )->getTextAttribute($attribute));
    }

    /**
     * Sets a formatter's text attribute.
     *
     * @param int $attribute The text attribute to set.
     * @param string $value The new value for the text attribute.
     * @return bool True on success, false on failure.
     */
    public function setTextAttribute(int $attribute, string $value): bool
    {
        return $this->wrapInTry(fn() => $this->initialize(
            $this->locale,
            $this->style,
            $this->pattern
        )->setTextAttribute($attribute, $value));
    }

    /**
     * Retrieves a formatter's symbol.
     *
     * @param int $symbol The symbol to retrieve.
     * @return string|false The symbol value.
     */
    public function getSymbol(int $symbol): string|false
    {
        return $this->wrapInTry(fn() => $this->initialize(
            $this->locale,
            $this->style,
            $this->pattern
        )->getSymbol($symbol));
    }

    /**
     * Sets a formatter's symbol.
     *
     * @param int $symbol The symbol to set.
     * @param string $value The new value for the symbol.
     * @return bool True on success, false on failure.
     */
    public function setSymbol(int $symbol, string $value): bool
    {
        return $this->wrapInTry(fn() => $this->initialize(
            $this->locale,
            $this->style,
            $this->pattern
        )->setSymbol($symbol, $value));
    }

    /**
     * Retrieves the formatter's pattern.
     *
     * @return string|false The pattern string.
     */
    public function getPattern(): string|false
    {
        return $this->wrapInTry(fn() => $this->initialize(
            $this->locale,
            $this->style,
            $this->pattern
        )->getPattern());
    }

    /**
     * Sets a new pattern for the formatter.
     *
     * @param string $pattern The new pattern to set.
     * @return bool True on success, false on failure.
     */
    public function setPattern(string $pattern): bool
    {
        return $this->wrapInTry(fn() => $this->initialize(
            $this->locale,
            $this->style,
            $this->pattern
        )->setPattern($pattern));
    }

    /**
     * Retrieves the formatter's locale.
     *
     * @param int $type The type of locale to retrieve.
     * @return string|false The locale string.
     */
    public function getLocale(int $type = \ULOC_ACTUAL_LOCALE): string|false
    {
        return $this->wrapInTry(fn() => $this->initialize(
            $this->locale,
            $this->style,
            $this->pattern
        )->getLocale($type));
    }

    /**
     * Retrieves the last error code from the formatter.
     *
     * @return int The last error code.
     */
    public function getErrorCode(): int
    {
        return $this->wrapInTry(fn() => $this->initialize(
            $this->locale,
            $this->style,
            $this->pattern
        )->getErrorCode());
    }

    /**
     * Retrieves the last error message from the formatter.
     *
     * @return string The last error message.
     */
    public function getErrorMessage(): string
    {
        return $this->wrapInTry(fn() => $this->initialize(
            $this->locale,
            $this->style,
            $this->pattern
        )->getErrorMessage());
    }