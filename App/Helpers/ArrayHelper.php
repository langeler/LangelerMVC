<?php

namespace App\Helpers;

/**
 * Class ArrayHelper
 *
 * Provides utility methods for common array operations, with clear and humanized method names.
 */
class ArrayHelper
{
	/**
	 * Chunk an array into smaller arrays of a specified size.
	 *
	 * @param array $array The input array.
	 * @param int $size The size of each chunk.
	 * @return array The array of chunks.
	 */
	public function chunk(array $array, int $size): array
	{
		return array_chunk($array, $size);
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
	 * Calculate the difference between arrays.
	 *
	 * @param array $array1 The first array.
	 * @param array ...$arrays Arrays to compare against.
	 * @return array The array containing values from the first array that are not present in any of the other arrays.
	 */
	public function diff(array $array1, array ...$arrays): array
	{
		return array_diff($array1, ...$arrays);
	}

	/**
	 * Calculate the difference between arrays using keys for comparison.
	 *
	 * @param array $array1 The first array.
	 * @param array ...$arrays Arrays to compare against.
	 * @return array The array containing values from the first array that are not present in any of the other arrays, comparing by keys.
	 */
	public function diffKeys(array $array1, array ...$arrays): array
	{
		return array_diff_key($array1, ...$arrays);
	}

	/**
	 * Fill an array with values, specifying keys.
	 *
	 * @param array $keys The array of keys.
	 * @param mixed $value The value to fill.
	 * @return array The filled array.
	 */
	public function fillKeys(array $keys, $value): array
	{
		return array_fill_keys($keys, $value);
	}

	/**
	 * Filter the array using a callback function.
	 *
	 * @param array $array The array to filter.
	 * @param callable $callback The callback function to determine if an element should be included.
	 * @return array The filtered array.
	 */
	public function filter(array $array, callable $callback): array
	{
		return array_filter($array, $callback);
	}

	/**
	 * Merge one or more arrays together.
	 *
	 * @param array ...$arrays The arrays to merge.
	 * @return array The merged array.
	 */
	public function merge(array ...$arrays): array
	{
		return array_merge(...$arrays);
	}

	/**
	 * Calculate the intersection of arrays.
	 *
	 * @param array $array1 The first array.
	 * @param array ...$arrays Arrays to compare against.
	 * @return array The array containing values present in all arrays.
	 */
	public function intersect(array $array1, array ...$arrays): array
	{
		return array_intersect($array1, ...$arrays);
	}

	/**
	 * Get all keys from an array.
	 *
	 * @param array $array The input array.
	 * @return array The array of keys.
	 */
	public function getKeys(array $array): array
	{
		return array_keys($array);
	}

	/**
	 * Get all values from an array.
	 *
	 * @param array $array The input array.
	 * @return array The array of values.
	 */
	public function getValues(array $array): array
	{
		return array_values($array);
	}

	/**
	 * Check if a key exists in an array.
	 *
	 * @param mixed $key The key to check.
	 * @param array $array The array to check.
	 * @return bool True if the key exists, false otherwise.
	 */
	public function hasKey($key, array $array): bool
	{
		return array_key_exists($key, $array);
	}

	/**
	 * Map a callback function to each element of an array.
	 *
	 * @param callable $callback The callback function to apply.
	 * @param array ...$arrays The input arrays.
	 * @return array The array with mapped values.
	 */
	public function map(callable $callback, array ...$arrays): array
	{
		return array_map($callback, ...$arrays);
	}

	/**
	 * Calculate the sum of values in an array.
	 *
	 * @param array $array The array to sum values from.
	 * @return float|int The sum of values.
	 */
	public function sum(array $array)
	{
		return array_sum($array);
	}

	/**
	 * Calculate the product of values in an array.
	 *
	 * @param array $array The array to multiply values from.
	 * @return float|int The product of values.
	 */
	public function product(array $array)
	{
		return array_product($array);
	}

	/**
	 * Search for a value in an array and return the corresponding key if found.
	 *
	 * @param array $array The array to search in.
	 * @param mixed $value The value to search for.
	 * @param bool $strict Whether to use strict comparison (===).
	 * @return mixed The key of the found element, or false if not found.
	 */
	public function search(array $array, $value, bool $strict = false)
	{
		return array_search($value, $array, $strict);
	}

	/**
	 * Remove the first element from an array and return it.
	 *
	 * @param array &$array The array to shift.
	 * @return mixed The shifted element.
	 */
	public function removeFirst(array &$array)
	{
		return array_shift($array);
	}

	/**
	 * Remove the last element from an array and return it.
	 *
	 * @param array &$array The array to pop.
	 * @return mixed The popped element.
	 */
	public function removeLast(array &$array)
	{
		return array_pop($array);
	}

	/**
	 * Append one or more elements to the end of an array.
	 *
	 * @param array &$array The array to append to.
	 * @param mixed ...$values The values to append.
	 * @return int The new number of elements in the array.
	 */
	public function append(array &$array, ...$values): int
	{
		return array_push($array, ...$values);
	}

	/**
	 * Prepend one or more elements to the beginning of an array.
	 *
	 * @param array &$array The array to prepend to.
	 * @param mixed ...$values The values to prepend.
	 * @return int The new number of elements in the array.
	 */
	public function prepend(array &$array, ...$values): int
	{
		return array_unshift($array, ...$values);
	}

	/**
	 * Replace elements in an array with values from another array.
	 *
	 * @param array $array The array to replace elements in.
	 * @param array ...$replacements The replacement arrays.
	 * @return array The array with replaced elements.
	 */
	public function replace(array $array, array ...$replacements): array
	{
		return array_replace($array, ...$replacements);
	}

	/**
	 * Recursively replace elements in an array with values from another array.
	 *
	 * @param array $array The array to replace elements in.
	 * @param array ...$replacements The replacement arrays.
	 * @return array The array with recursively replaced elements.
	 */
	public function replaceRecursive(array $array, array ...$replacements): array
	{
		return array_replace_recursive($array, ...$replacements);
	}

	/**
	 * Slice an array, extracting a portion of it.
	 *
	 * @param array $array The array to slice.
	 * @param int $offset The starting index for the slice.
	 * @param int|null $length The length of the slice.
	 * @param bool $preserveKeys Whether to preserve keys in the sliced array.
	 * @return array The sliced array.
	 */
	public function slice(array $array, int $offset, int $length = null, bool $preserveKeys = false): array
	{
		return array_slice($array, $offset, $length, $preserveKeys);
	}

	/**
	 * Sort an array by a callback function.
	 *
	 * @param array $array The array to sort.
	 * @param callable $callback The callback function to determine the order.
	 * @return array The sorted array.
	 */
	public function sort(array $array, callable $callback): array
	{
		usort($array, $callback);
		return $array;
	}

	/**
	 * Reverse the order of elements in an array.
	 *
	 * @param array $array The array to reverse.
	 * @return array The reversed array.
	 */
	public function reverse(array $array): array
	{
		return array_reverse($array);
	}

	/**
	 * Walk through an array applying a callback function to each element.
	 *
	 * @param array &$array The array to walk.
	 * @param callable $callback The callback function to apply.
	 * @return bool True on success, false on failure.
	 */
	public function walk(array &$array, callable $callback): bool
	{
		return array_walk($array, $callback);
	}

	/**
	 * Recursively walk through an array applying a callback function to each element.
	 *
	 * @param array &$array The array to walk recursively.
	 * @param callable $callback The callback function to apply.
	 * @return bool True on success, false on failure.
	 */
	public function walkRecursive(array &$array, callable $callback): bool
	{
		return array_walk_recursive($array, $callback);
	}

	/**
	 * Flatten a multi-dimensional array into a single-dimensional array.
	 *
	 * @param array $array The array to flatten.
	 * @return array The flattened array.
	 */
	public function flatten(array $array): array
	{
		$result = [];
		array_walk_recursive($array, function ($item) use (&$result) {
			$result[] = $item;
		});
		return $result;
	}

	/**
	 * Group elements of an array by a callback or key.
	 *
	 * @param array $array The array to group.
	 * @param callable|string $groupBy The key or callback to group by.
	 * @return array The grouped array.
	 */
	public function groupBy(array $array, $groupBy): array
	{
		$result = [];
		foreach ($array as $key => $value) {
			$groupKey = is_callable($groupBy) ? $groupBy($value, $key) : $value[$groupBy];
			$result[$groupKey][] = $value;
		}
		return $result;
	}

	/**
	 * Compute the intersection of arrays with additional index check.
	 *
	 * @param array ...$arrays The arrays to intersect.
	 * @return array The intersected array.
	 */
	public function intersectAssoc(array ...$arrays): array
	{
		return array_intersect_assoc(...$arrays);
	}

	/**
	 * Compute the difference of arrays with additional index check.
	 *
	 * @param array ...$arrays The arrays to compare.
	 * @return array The array containing all the values from the first array that are not present in any of the other arrays, with additional index check.
	 */
	public function diffAssoc(array ...$arrays): array
	{
		return array_diff_assoc(...$arrays);
	}

	/**
	 * Compute the intersection of arrays using a callback function for comparison.
	 *
	 * @param array $array1 The first array.
	 * @param array $array2 The second array.
	 * @param callable $callback The callback function to use for comparison.
	 * @return array The intersected array, using the callback for comparison.
	 */
	public function intersectUsingCallback(array $array1, array $array2, callable $callback): array
	{
		return array_uintersect($array1, $array2, $callback);
	}

	/**
	 * Compute the difference of arrays using a callback function for comparison.
	 *
	 * @param array $array1 The first array.
	 * @param array $array2 The second array.
	 * @param callable $callback The callback function to use for comparison.
	 * @return array The array containing all the values from the first array that are not present in the second array, using the callback for comparison.
	 */
	public function diffUsingCallback(array $array1, array $array2, callable $callback): array
	{
		return array_udiff($array1, $array2, $callback);
	}

	/**
	 * Reduce the array to a single value using a callback function.
	 *
	 * @param array $array The array to reduce.
	 * @param callable $callback The callback function to apply.
	 * @param mixed $initial The initial value for reduction.
	 * @return mixed The resulting value.
	 */
	public function reduce(array $array, callable $callback, $initial = null)
	{
		return array_reduce($array, $callback, $initial);
	}

	/**
	 * Shuffle the array randomly.
	 *
	 * @param array $array The array to shuffle.
	 * @return array The shuffled array.
	 */
	public function shuffle(array $array): array
	{
		shuffle($array);
		return $array;
	}

	/**
	 * Pad an array to a specified length with a value.
	 *
	 * @param array $array The input array.
	 * @param int $size The new size of the array.
	 * @param mixed $value The value to pad with.
	 * @return array The padded array.
	 */
	public function pad(array $array, int $size, $value): array
	{
		return array_pad($array, $size, $value);
	}

	/**
	 * Return the array with only unique values.
	 *
	 * @param array $array The array to be processed.
	 * @return array The array with unique values.
	 */
	public function getUnique(array $array): array
	{
		return array_unique($array);
	}

	/**
	 * Pick one or more random keys from the array.
	 *
	 * @param array $array The array to pick from.
	 * @param int $num The number of keys to pick.
	 * @return mixed The picked key(s).
	 */
	public function getRandomKeys(array $array, int $num = 1)
	{
		return array_rand($array, $num);
	}

	/**
	 * Return the values from a single column in the input array.
	 *
	 * @param array $array The array to extract the column from.
	 * @param mixed $columnKey The key of the column to return.
	 * @param mixed $indexKey The key to index the returned array.
	 * @return array The array of values.
	 */
	public function extractColumn(array $array, $columnKey, $indexKey = null): array
	{
		return array_column($array, $columnKey, $indexKey);
	}
}
