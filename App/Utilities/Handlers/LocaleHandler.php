<?php

namespace App\Utilities\Handlers;

use Locale;

/**
 * LocaleHandler provides utilities for managing locale information using PHP's Locale class.
 * This handler initializes constants and provides consistent methods for handling locales and their attributes.
 */
class LocaleHandler
{
    /**
     * Constructs the handler and initializes the constants property.
     *
     * @param array $classConstants Placeholder for locale-related constants.
     */
    public function __construct(
        protected readonly array $classConstants = [] // Placeholder for constants
    ) {
        $this->classConstants = [
            // Locale Constants
            'actualLocale' => Locale::ACTUAL_LOCALE, // The actual locale used.
            'validLocale' => Locale::VALID_LOCALE, // The valid locale detected.
            'defaultLocale' => Locale::DEFAULT_LOCALE, // The default locale (null by default).

            // Locale Tags
            'languageTag' => Locale::LANG_TAG, // Language tag constant.
            'extLangTag' => Locale::EXTLANG_TAG, // Extended language tag constant.
            'scriptTag' => Locale::SCRIPT_TAG, // Script tag constant.
            'regionTag' => Locale::REGION_TAG, // Region tag constant.
            'variantTag' => Locale::VARIANT_TAG, // Variant tag constant.
            'grandfatheredLangTag' => Locale::GRANDFATHERED_LANG_TAG, // Grandfathered language tag.
            'privateTag' => Locale::PRIVATE_TAG, // Private use tag constant.
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

    // ------------------------------------------------------------------
    //                          Locale Methods
    // ------------------------------------------------------------------

    /**
     * Accepts a locale from an HTTP header.
     *
     * @param string $header The HTTP Accept-Language header.
     * @return string|false The best matching locale, or false on failure.
     */
    public function acceptFromHttp(string $header): string|false
    {
        return $this->wrapInTry(fn() => Locale::acceptFromHttp($header));
    }

    /**
     * Canonicalizes a locale string.
     *
     * @param string $locale The locale string.
     * @return string|null The canonicalized locale, or null on failure.
     */
    public function canonicalize(string $locale): ?string
    {
        return $this->wrapInTry(fn() => Locale::canonicalize($locale));
    }

    /**
     * Composes a locale from subtags.
     *
     * @param array $subtags The subtags to compose.
     * @return string|false The composed locale, or false on failure.
     */
    public function composeLocale(array $subtags): string|false
    {
        return $this->wrapInTry(fn() => Locale::composeLocale($subtags));
    }

    /**
     * Filters a locale based on matching language tags.
     *
     * @param string $languageTag The language tag.
     * @param string $locale The locale.
     * @param bool $canonicalize Whether to canonicalize the locale.
     * @return bool|null True if matched, false if not, or null on failure.
     */
    public function filterMatches(string $languageTag, string $locale, bool $canonicalize = false): ?bool
    {
        return $this->wrapInTry(fn() => Locale::filterMatches($languageTag, $locale, $canonicalize));
    }

    /**
     * Gets all variants from a locale.
     *
     * @param string $locale The locale string.
     * @return array|null The array of variants, or null on failure.
     */
    public function getAllVariants(string $locale): ?array
    {
        return $this->wrapInTry(fn() => Locale::getAllVariants($locale));
    }

    /**
     * Retrieves the default locale.
     *
     * @return string The default locale.
     */
    public function getDefault(): string
    {
        return $this->wrapInTry(fn() => Locale::getDefault());
    }

    /**
     * Retrieves the display language of a locale.
     *
     * @param string $locale The locale.
     * @param string|null $displayLocale The locale for displaying the name.
     * @return string|false The display language, or false on failure.
     */
    public function getDisplayLanguage(string $locale, ?string $displayLocale = null): string|false
    {
        return $this->wrapInTry(fn() => Locale::getDisplayLanguage($locale, $displayLocale));
    }

    /**
     * Retrieves the display name of a locale.
     *
     * @param string $locale The locale.
     * @param string|null $displayLocale The locale for displaying the name.
     * @return string|false The display name, or false on failure.
     */
    public function getDisplayName(string $locale, ?string $displayLocale = null): string|false
    {
        return $this->wrapInTry(fn() => Locale::getDisplayName($locale, $displayLocale));
    }

    /**
     * Retrieves the display region of a locale.
     *
     * @param string $locale The locale.
     * @param string|null $displayLocale The locale for displaying the region.
     * @return string|false The display region, or false on failure.
     */
    public function getDisplayRegion(string $locale, ?string $displayLocale = null): string|false
    {
        return $this->wrapInTry(fn() => Locale::getDisplayRegion($locale, $displayLocale));
    }

    /**
     * Retrieves the display script of a locale.
     *
     * @param string $locale The locale.
     * @param string|null $displayLocale The locale for displaying the script.
     * @return string|false The display script, or false on failure.
     */
    public function getDisplayScript(string $locale, ?string $displayLocale = null): string|false
    {
        return $this->wrapInTry(fn() => Locale::getDisplayScript($locale, $displayLocale));
    }

    /**
     * Retrieves the display variant of a locale.
     *
     * @param string $locale The locale.
     * @param string|null $displayLocale The locale for displaying the variant.
     * @return string|false The display variant, or false on failure.
     */
    public function getDisplayVariant(string $locale, ?string $displayLocale = null): string|false
    {
        return $this->wrapInTry(fn() => Locale::getDisplayVariant($locale, $displayLocale));
    }

    /**
     * Retrieves the keywords of a locale.
     *
     * @param string $locale The locale.
     * @return array|false|null The keywords, or false/null on failure.
     */
    public function getKeywords(string $locale): array|false|null
    {
        return $this->wrapInTry(fn() => Locale::getKeywords($locale));
    }

    /**
     * Retrieves the primary language of a locale.
     *
     * @param string $locale The locale.
     * @return string|null The primary language, or null on failure.
     */
    public function getPrimaryLanguage(string $locale): ?string
    {
        return $this->wrapInTry(fn() => Locale::getPrimaryLanguage($locale));
    }

    /**
     * Retrieves the region of a locale.
     *
     * @param string $locale The locale.
     * @return string|null The region, or null on failure.
     */
    public function getRegion(string $locale): ?string
    {
        return $this->wrapInTry(fn() => Locale::getRegion($locale));
    }

    /**
     * Retrieves the script of a locale.
     *
     * @param string $locale The locale.
     * @return string|null The script, or null on failure.
     */
    public function getScript(string $locale): ?string
    {
        return $this->wrapInTry(fn() => Locale::getScript($locale));
    }

    /**
     * Looks up a locale from a list of language tags.
     *
     * @param array $languageTags The language tags.
     * @param string $locale The locale.
     * @param bool $canonicalize Whether to canonicalize the locale.
     * @param string|null $defaultLocale The default locale.
     * @return string|null The best-matching locale, or null on failure.
     */
    public function lookup(array $languageTags, string $locale, bool $canonicalize = false, ?string $defaultLocale = null): ?string
    {
        return $this->wrapInTry(fn() => Locale::lookup($languageTags, $locale, $canonicalize, $defaultLocale));
    }

    /**
     * Parses a locale string into subtags.
     *
     * @param string $locale The locale string.
     * @return array|null The parsed subtags, or null on failure.
     */
    public function parseLocale(string $locale): ?array
    {
        return $this->wrapInTry(fn() => Locale::parseLocale($locale));
    }

    /**
     * Sets the default locale.
     *
     * @param string $locale The locale to set.
     * @return bool True on success.
     */
    public function setDefault(string $locale): bool
    {
        return $this->wrapInTry(fn() => Locale::setDefault($locale));
    }
}