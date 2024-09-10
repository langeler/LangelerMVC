<?php

namespace App\Utilities\Traits\Patterns;

/**
 * Trait PatternTrait
 *
 * Provides a convenient wrapper around PHP's preg_* functions for regular expression operations.
 */
trait PatternTrait
{
	/**
	 * Perform a regular expression match.
	 *
	 * @param string $pattern The pattern to search for, as a string.
	 * @param string $subject The input string.
	 * @param array|null $matches If matches are found, they will be stored in this array.
	 * @param int $flags Optional flags to modify the behavior.
	 * @param int $offset The offset in the subject string at which to start searching.
	 * @return int|false Returns 1 if a match is found, 0 if none, or false on error.
	 */
	public function match(string $pattern, string $subject, ?array &$matches = null, int $flags = 0, int $offset = 0): int|false
	{
		return preg_match($pattern, $subject, $matches, $flags, $offset);
	}

	/**
	 * Perform a global regular expression match.
	 *
	 * @param string $pattern The pattern to search for.
	 * @param string $subject The input string.
	 * @param array|null $matches If matches are found, they will be stored in this array.
	 * @param int $flags Optional flags to modify the behavior.
	 * @param int $offset The offset in the subject string at which to start searching.
	 * @return int|false Returns the number of full pattern matches (which might be zero), or false on error.
	 */
	public function matchAll(string $pattern, string $subject, ?array &$matches = null, int $flags = 0, int $offset = 0): int|false
	{
		return preg_match_all($pattern, $subject, $matches, $flags, $offset);
	}

	/**
	 * Perform a regular expression search and replace.
	 *
	 * @param string|array $pattern The pattern to search for.
	 * @param string|array $replacement The string or array with which to replace.
	 * @param string|array $subject The string or array of strings to search and replace.
	 * @param int $limit The maximum possible replacements for each pattern in each subject string (defaults to -1 for no limit).
	 * @param int|null $count If specified, this variable will be filled with the number of replacements done.
	 * @return string|array|null Returns the resulting string or array if matches are found, or null on error.
	 */
	public function replace(string|array $pattern, string|array $replacement, string|array $subject, int $limit = -1, ?int &$count = null): string|array|null
	{
		return preg_replace($pattern, $replacement, $subject, $limit, $count);
	}

	/**
	 * Perform a regular expression search and replace using a callback.
	 *
	 * @param string|array $pattern The pattern to search for.
	 * @param callable $callback A callback that will be called and passed an array of matched elements.
	 * @param string|array $subject The string or array of strings to search and replace.
	 * @param int $limit The maximum possible replacements for each pattern in each subject string (defaults to -1 for no limit).
	 * @param int|null $count If specified, this variable will be filled with the number of replacements done.
	 * @return string|array|null Returns the resulting string or array if matches are found, or null on error.
	 */
	public function replaceCallback(string|array $pattern, callable $callback, string|array $subject, int $limit = -1, ?int &$count = null): string|array|null
	{
		return preg_replace_callback($pattern, $callback, $subject, $limit, $count);
	}

	/**
	 * Split a string by a regular expression.
	 *
	 * @param string $pattern The pattern to split by.
	 * @param string $subject The input string.
	 * @param int $limit If specified, this is the maximum number of substrings returned.
	 * @param int $flags Optional flags to modify the behavior.
	 * @return array|false Returns an array of substrings if the match is successful, or false on failure.
	 */
	public function split(string $pattern, string $subject, int $limit = -1, int $flags = 0): array|false
	{
		return preg_split($pattern, $subject, $limit, $flags);
	}

	/**
	 * Quote regular expression characters.
	 *
	 * @param string $str The input string.
	 * @param string $delimiter If specified, this is the optional delimiter to escape.
	 * @return string Returns the quoted (escaped) string.
	 */
	public function quote(string $str, string $delimiter = null): string
	{
		return preg_quote($str, $delimiter);
	}
}
