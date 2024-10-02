<?php

namespace App\Utilities\Traits;

/**
 * Trait LocaleTrait
 *
 * Provides utility functions for handling locale-based string operations.
 */
trait LocaleTrait
{
	/**
	 * Sets the locale for string and number formatting.
	 *
	 * @param string $locale The locale to set (e.g., 'en_US.UTF-8').
	 * @return string The current locale setting.
	 */
	public function setLocale(string $locale): string
	{
		return setlocale(LC_ALL, $locale);
	}

	/**
	 * Retrieves locale-specific settings for numbers and currency.
	 *
	 * @return array The locale-specific settings, such as decimal point and thousands separator.
	 */
	public function getLocaleSettings(): array
	{
		return localeconv();
	}

	/**
	 * Compares two strings based on the current locale settings.
	 * Locale-aware string comparison (collation).
	 *
	 * @param string $str1 The first string.
	 * @param string $str2 The second string.
	 * @return int Returns < 0 if str1 is less than str2, 0 if they are equal, > 0 if str1 is greater.
	 */
	public function localeCompare(string $str1, string $str2): int
	{
		return strcoll($str1, $str2);
	}

	/**
	 * Sorts an array of strings based on the current locale settings.
	 * Locale-aware sorting.
	 *
	 * @param array $array The array of strings to sort.
	 * @return bool True on success, false otherwise.
	 */
	public function localeSort(array &$array): bool
	{
		return usort($array, 'strcoll');
	}

	/**
	 * Converts the case of a string according to locale settings.
	 * Uses mb_convert_case() with locale.
	 *
	 * @param string $input The input string.
	 * @param int $mode The mode for case conversion (e.g., MB_CASE_UPPER).
	 * @param string|null $locale The locale setting (optional).
	 * @return string The case-converted string.
	 */
	public function localeCaseConvert(string $input, int $mode, ?string $locale = null): string
	{
		if ($locale) {
			setlocale(LC_CTYPE, $locale);
		}
		return mb_convert_case($input, $mode);
	}
}
