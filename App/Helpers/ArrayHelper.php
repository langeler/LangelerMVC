<?php

namespace App\Helpers;

/**
 * Class ArrayHelper
 *
 * Provides utility methods for common array operations with flexible parameter handling.
 */
class ArrayHelper
{
	/**
	 * Chunk the given data into smaller arrays of a specified size.
	 *
	 * @param array $data The input data.
	 * @param int $size The size of each chunk.
	 * @return array The array of chunks.
	 */
	public function chunk(array $data, int $size): array
	{
		return array_chunk($data, $size);
	}

	/**
	 * Combine two arrays, one for keys and one for values.
	 *
	 * @param array $keys The array of keys.
	 * @param array $values The array of values.
	 * @return array The combined array.
	 */
	public function combine(array $keys, array $values): array
	{
		return array_combine($keys, $values);
	}

	/**
	 * Calculate the difference between multiple arrays.
	 *
	 * @param array ...$data Multiple arrays to compare.
	 * @return array The array containing values not present in the other arrays.
	 */
	public function diff(array ...$data): array
	{
		return array_diff(...$data);
	}

	/**
	 * Calculate the difference between multiple arrays, with index check.
	 *
	 * @param array ...$data Multiple arrays to compare with index check.
	 * @return array The array containing values not present in the other arrays with index check.
	 */
	public function diffAssoc(array ...$data): array
	{
		return array_diff_assoc(...$data);
	}

	/**
	 * Calculate the difference between arrays using keys for comparison.
	 *
	 * @param array ...$data Multiple arrays to compare keys.
	 * @return array The array containing keys not present in the other arrays.
	 */
	public function diffKey(array ...$data): array
	{
		return array_diff_key(...$data);
	}

	/**
	 * Filter the data using a callback function.
	 *
	 * @param array $data The input data.
	 * @param callable $callback The callback function to determine if an element should be included.
	 * @return array The filtered array.
	 */
	public function filter(array $data, callable $callback): array
	{
		return array_filter($data, $callback);
	}

	/**
	 * Merge multiple arrays together.
	 *
	 * @param array ...$data The arrays to merge.
	 * @return array The merged array.
	 */
	public function merge(array ...$data): array
	{
		return array_merge(...$data);
	}

	/**
	 * Recursively merge multiple arrays.
	 *
	 * @param array ...$data The arrays to merge recursively.
	 * @return array The recursively merged array.
	 */
	public function mergeRecursive(array ...$data): array
	{
		return array_merge_recursive(...$data);
	}

	/**
	 * Get all keys from the data.
	 *
	 * @param array $data The input data.
	 * @return array The array of keys.
	 */
	public function getKeys(array $data): array
	{
		return array_keys($data);
	}

	/**
	 * Get all values from the data.
	 *
	 * @param array $data The input data.
	 * @return array The array of values.
	 */
	public function getValues(array $data): array
	{
		return array_values($data);
	}

	/**
	 * Search for a value in the data and return the corresponding key if successful.
	 *
	 * @param array $data The input data.
	 * @param mixed $value The value to search for.
	 * @return mixed The corresponding key if successful, false otherwise.
	 */
	public function search(array $data, $value)
	{
		return array_search($value, $data);
	}

	/**
	 * Append a value to the end of the data.
	 *
	 * @param array &$data The input data.
	 * @param mixed $value The value to append.
	 * @return int The new number of elements in the data.
	 */
	public function append(array &$data, $value): int
	{
		return array_push($data, $value);
	}

	/**
	 * Prepend a value to the beginning of the data.
	 *
	 * @param array &$data The input data.
	 * @param mixed $value The value to prepend.
	 * @return int The new number of elements in the data.
	 */
	public function prepend(array &$data, $value): int
	{
		return array_unshift($data, $value);
	}

	/**
	 * Replace elements in the data with values from another array.
	 *
	 * @param array ...$data The arrays for replacement.
	 * @return array The array with replaced elements.
	 */
	public function replace(array ...$data): array
	{
		return array_replace(...$data);
	}

	/**
	 * Replace elements in the data recursively with values from another array.
	 *
	 * @param array ...$data The arrays for recursive replacement.
	 * @return array The array with recursively replaced elements.
	 */
	public function replaceRecursive(array ...$data): array
	{
		return array_replace_recursive(...$data);
	}

	/**
	 * Slice the data, extracting a portion of it.
	 *
	 * @param array $data The input data.
	 * @param int $offset The starting index for the slice.
	 * @param int|null $length The length of the slice.
	 * @param bool $preserveKeys Whether to preserve keys in the sliced array.
	 * @return array The sliced array.
	 */
	public function slice(array $data, int $offset, int $length = null, bool $preserveKeys = false): array
	{
		return array_slice($data, $offset, $length, $preserveKeys);
	}

	/**
	 * Reverse the order of elements in the data.
	 *
	 * @param array $data The input data.
	 * @return array The reversed array.
	 */
	public function reverse(array $data): array
	{
		return array_reverse($data);
	}

	/**
	 * Flatten a multi-dimensional array into a single-dimensional array.
	 *
	 * @param array $data The input data.
	 * @return array The flattened array.
	 */
	public function flatten(array $data): array
	{
		return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($data)), false);
	}

	/**
	 * Return an array with only unique values.
	 *
	 * @param array $data The input data.
	 * @return array The array with unique values.
	 */
	public function getUnique(array $data): array
	{
		return array_unique($data);
	}

	/**
	 * Pick one or more random keys from the data.
	 *
	 * @param array $data The input data.
	 * @param int $num The number of keys to pick.
	 * @return mixed The picked key(s).
	 */
	public function getRandomKeys(array $data, int $num = 1)
	{
		return array_rand($data, $num);
	}

	/**
	 * Extract a single column of values from the input data.
	 *
	 * @param array $data The input data.
	 * @param mixed $columnKey The key of the column to return.
	 * @param mixed $indexKey The key to index the returned array.
	 * @return array The array of values.
	 */
	public function extractColumn(array $data, $columnKey, $indexKey = null): array
	{
		return array_column($data, $columnKey, $indexKey);
	}

	/**
	 * Pad the data to a specified length with a value.
	 *
	 * @param array $data The input data.
	 * @param int $size The new size of the array.
	 * @param mixed $value The value to pad with.
	 * @return array The padded array.
	 */
	public function pad(array $data, int $size, $value): array
	{
		return array_pad($data, $size, $value);
	}

	/**
	 * Shuffle the elements of the data randomly.
	 *
	 * @param array &$data The input data to shuffle.
	 * @return void
	 */
	public function shuffle(array &$data): void
	{
		shuffle($data);
	}

	/**
	 * Calculate the intersection of multiple arrays.
	 *
	 * @param array ...$data The arrays to intersect.
	 * @return array The array containing values present in all arrays.
	 */
	public function intersect(array ...$data): array
	{
		return array_intersect(...$data);
	}

	/**
	 * Calculate the intersection of arrays with an additional index check.
	 *
	 * @param array ...$data The arrays to intersect.
	 * @return array The array containing values present in all arrays with index check.
	 */
	public function intersectAssoc(array ...$data): array
	{
		return array_intersect_assoc(...$data);
	}

	/**
	 * Calculate the intersection of arrays using keys for comparison.
	 *
	 * @param array ...$data The arrays to intersect by keys.
	 * @return array The array containing keys present in all arrays.
	 */
	public function intersectKey(array ...$data): array
	{
		return array_intersect_key(...$data);
	}
}
