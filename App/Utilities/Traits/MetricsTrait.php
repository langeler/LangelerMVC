<?php

namespace App\Utilities\Traits;

/**
 * Trait MetricsTrait
 *
 * Provides utility functions for measuring similarity and distance between strings.
 */
trait MetricsTrait
{
	/**
	 * Calculates the Levenshtein distance between two strings.
	 *
	 * @param string $str1 The first string.
	 * @param string $str2 The second string.
	 * @return int The Levenshtein distance between the two strings.
	 */
	public function distance(string $str1, string $str2): int
	{
		return levenshtein($str1, $str2);
	}

	/**
	 * Calculates the similarity percentage between two strings.
	 *
	 * @param string $str1 The first string.
	 * @param string $str2 The second string.
	 * @return float The percentage similarity between the two strings.
	 */
	public function similarityScore(string $str1, string $str2): float
	{
		similar_text($str1, $str2, $percent);
		return $percent;
	}

	/**
	 * Compares two strings phonetically using the Soundex algorithm.
	 *
	 * @param string $str1 The first string.
	 * @param string $str2 The second string.
	 * @return bool True if the strings sound similar, false otherwise.
	 */
	public function soundsLike(string $str1, string $str2): bool
	{
		return soundex($str1) === soundex($str2);
	}

	/**
	 * Compares two strings phonetically using the Metaphone algorithm.
	 *
	 * @param string $str1 The first string.
	 * @param string $str2 The second string.
	 * @return bool True if the strings sound similar, false otherwise.
	 */
	public function metaphoneMatch(string $str1, string $str2): bool
	{
		return metaphone($str1) === metaphone($str2);
	}

	/**
	 * Calculates the Jaro-Winkler similarity between two strings.
	 * Jaro-Winkler is often used for name matching.
	 *
	 * @param string $str1 The first string.
	 * @param string $str2 The second string.
	 * @return float The Jaro-Winkler similarity (between 0 and 1).
	 */
	public function jaroWinklerMatch(string $str1, string $str2): float
	{
		return $this->calculateJaroWinkler($str1, $str2);
	}

	/**
	 * A custom method to calculate the Jaro-Winkler similarity.
	 * This can be a third-party implementation or custom logic.
	 *
	 * @param string $str1
	 * @param string $str2
	 * @return float
	 */
	private function calculateJaroWinkler(string $str1, string $str2): float
	{
		// Custom implementation of the Jaro-Winkler similarity algorithm
		return 0.0;  // Replace with actual implementation
	}
}
