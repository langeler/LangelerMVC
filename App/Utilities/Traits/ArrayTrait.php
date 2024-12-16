<?php

namespace App\Utilities\Traits;

/**
 * Trait ArrayTrait
 *
 * Provides utility methods for common array operations with flexible parameter handling.
 */
trait ArrayTrait
{
	/**
	 * Changes the case of all keys in an array.
	 *
	 * @param array $array The input array.
	 * @param int $case Either CASE_UPPER or CASE_LOWER (default: CASE_LOWER).
	 * @return array The array with changed key case.
	 */
	public function changeKeyCase(array $array, int $case = CASE_LOWER): array
	{
		return array_change_key_case($array, $case);
	}

	/**
	 * Splits an array into chunks.
	 *
	 * @param array $array The input array.
	 * @param int $size Size of each chunk.
	 * @param bool $preserveKeys Whether to preserve keys (default: false).
	 * @return array A multidimensional array of chunks.
	 */
	public function chunk(array $array, int $size, bool $preserveKeys = false): array
	{
		return array_chunk($array, $size, $preserveKeys);
	}

	/**
	 * Returns the values from a single column in the input array.
	 *
	 * @param array $array The input array.
	 * @param int|string|null $columnKey The column of values to return.
	 * @param int|string|null $indexKey The column to use as keys.
	 * @return array The array of column values.
	 */
	public function column(array $array, int|string|null $columnKey, int|string|null $indexKey = null): array
	{
		return array_column($array, $columnKey, $indexKey);
	}

	/**
	 * Creates an array by using one array for keys and another for its values.
	 *
	 * @param array $keys Array of keys.
	 * @param array $values Array of values.
	 * @return array The combined array.
	 */
	public function combine(array $keys, array $values): array
	{
		return array_combine($keys, $values);
	}

	/**
	 * Computes the difference of arrays.
	 *
	 * @param array ...$arrays Arrays to compare.
	 * @return array The resulting difference array.
	 */
	public function diff(array ...$arrays): array
	{
		return array_diff(...$arrays);
	}

	/**
	 * Computes the difference of arrays with additional index check.
	 *
	 * @param array ...$arrays Arrays to compare.
	 * @return array The resulting difference array.
	 */
	public function diffAssoc(array ...$arrays): array
	{
		return array_diff_assoc(...$arrays);
	}

	/**
	 * Computes the difference of arrays using keys for comparison.
	 *
	 * @param array ...$arrays Arrays to compare.
	 * @return array The resulting difference array.
	 */
	public function diffKey(array ...$arrays): array
	{
		return array_diff_key(...$arrays);
	}

	/**
	 * Fills an array with values.
	 *
	 * @param int $startIndex The first index of the array.
	 * @param int $count The number of elements to insert.
	 * @param mixed $value The value to use for filling.
	 * @return array The filled array.
	 */
	public function fill(int $startIndex, int $count, mixed $value): array
	{
		return array_fill($startIndex, $count, $value);
	}

	/**
	 * Fills an array with values, specifying keys.
	 *
	 * @param array $keys Array of keys.
	 * @param mixed $value The value to use for filling.
	 * @return array The filled array.
	 */
	public function fillKeys(array $keys, mixed $value): array
	{
		return array_fill_keys($keys, $value);
	}

	/**
	 * Filters elements of an array using a callback function.
	 *
	 * @param array $array The input array.
	 * @param callable|null $callback The callback function (default: null).
	 * @return array The filtered array.
	 */
	public function filter(array $array, callable|null $callback = null): array
	{
		return array_filter($array, $callback);
	}

	/**
	 * Exchanges all keys with their associated values in an array.
	 *
	 * @param array $array The input array.
	 * @return array The array with flipped keys and values.
	 */
	public function flip(array $array): array
	{
		return array_flip($array);
	}

	/**
	 * Computes the intersection of arrays.
	 *
	 * @param array ...$arrays Arrays to intersect.
	 * @return array The resulting intersection array.
	 */
	public function intersect(array ...$arrays): array
	{
		return array_intersect(...$arrays);
	}

	/**
	 * Computes the intersection of arrays with additional index check.
	 *
	 * @param array ...$arrays Arrays to intersect.
	 * @return array The resulting intersection array.
	 */
	public function intersectAssoc(array ...$arrays): array
	{
		return array_intersect_assoc(...$arrays);
	}

	/**
	 * Computes the intersection of arrays using keys for comparison.
	 *
	 * @param array ...$arrays Arrays to intersect.
	 * @return array The resulting intersection array.
	 */
	public function intersectKey(array ...$arrays): array
	{
		return array_intersect_key(...$arrays);
	}

	/**
	 * Applies a callback to the elements of the given arrays.
	 *
	 * @param callable $callback Callback function.
	 * @param array ...$arrays Input arrays.
	 * @return array The resulting array.
	 */
	public function map(callable $callback, array ...$arrays): array
	{
		return array_map($callback, ...$arrays);
	}
}

	/**
	 * Create an array containing a range of elements.
	 *
	 * @param int|string $start Starting value of the range.
	 * @param int|string $end Ending value of the range.
	 * @param int $step Increment between elements (default: 1).
	 * @return array Array containing the range of values.
	 */
	public function create(int|string $start, int|string $end, int $step = 1): array
	{
		return range($start, $end, $step);
	}

	/**
	 * Assign variables as if they were an array.
	 *
	 * @param array $variables List of variable names.
	 * @param array $values Corresponding values for the variables.
	 * @return array Associative array of variables and their values.
	 */
	public function assign(array $variables, array $values): array
	{
		return compact(...$variables);
	}

	/**
	 * Check if all elements in an array satisfy a callback function.
	 *
	 * @param array $array The array to check.
	 * @param callable $callback Callback function applied to each element.
	 * @return bool True if all elements satisfy the callback, false otherwise.
	 */
	public function all(array $array, callable $callback): bool
	{
		return array_all($array, $callback);
	}

	/**
	 * Check if at least one element in an array satisfies a callback function.
	 *
	 * @param array $array The array to check.
	 * @param callable $callback Callback function applied to each element.
	 * @return bool True if any element satisfies the callback, false otherwise.
	 */
	public function any(array $array, callable $callback): bool
	{
		return array_any($array, $callback);
	}

	/**
	 * Return the current element in an array.
	 *
	 * @param array $array The input array.
	 * @return mixed|null The current array element or null if empty.
	 */
	public function current(array $array): mixed
	{
		return current($array);
	}

	/**
	 * Move the internal pointer to the last element and return it.
	 *
	 * @param array &$array The input array.
	 * @return mixed|null The last element or null if empty.
	 */
	public function end(array &$array): mixed
	{
		return end($array);
	}

	/**
	 * Fetch the key of the current element in an array.
	 *
	 * @param array $array The input array.
	 * @return int|string|null The key of the current element or null if empty.
	 */
	public function key(array $array): int|string|null
	{
		return key($array);
	}

	/**
	 * Advance the internal pointer and return the next element.
	 *
	 * @param array &$array The input array.
	 * @return mixed|null The next array element or null if end of array.
	 */
	public function next(array &$array): mixed
	{
		return next($array);
	}

	/**
	 * Return the current element in an array (alias of current).
	 *
	 * @param array $array The input array.
	 * @return mixed|null The current array element or null if empty.
	 */
	public function pos(array $array): mixed
	{
		return pos($array);
	}

	/**
	 * Rewind the internal pointer and return the previous element.
	 *
	 * @param array &$array The input array.
	 * @return mixed|null The previous array element or null if at the start.
	 */
	public function prev(array &$array): mixed
	{
		return prev($array);
	}

	/**
	 * Reset the internal pointer to the first element and return it.
	 *
	 * @param array &$array The input array.
	 * @return mixed|null The first element or null if empty.
	 */
	public function reset(array &$array): mixed
	{
		return reset($array);
	}

	/**
	 * Find the first element satisfying a callback function.
	 *
	 * @param array $array The input array.
	 * @param callable $callback Callback applied to each element.
	 * @return mixed|null The first matching element or null if none found.
	 */
	public function find(array $array, callable $callback): mixed
	{
		return array_find($array, $callback);
	}

	/**
	 * Find the key of the first element satisfying a callback function.
	 *
	 * @param array $array The input array.
	 * @param callable $callback Callback applied to each element.
	 * @return int|string|null The key of the matching element or null if none found.
	 */
	public function findKey(array $array, callable $callback): int|string|null
	{
		return array_find_key($array, $callback);
	}

	/**
	 * Get the first key of an array.
	 *
	 * @param array $array The input array.
	 * @return int|string|null The first key or null if empty.
	 */
	public function keyFirst(array $array): mixed
	{
		return array_key_first($array);
	}

	/**
	 * Get the last key of an array.
	 *
	 * @param array $array The input array.
	 * @return int|string|null The last key or null if empty.
	 */
	public function keyLast(array $array): mixed
	{
		return array_key_last($array);
	}

	/**
	 * Import variables from an array into the current symbol table.
	 *
	 * @param array &$array Input array.
	 * @param int $flags Extraction flags (default: EXTR_OVERWRITE).
	 * @return int Number of variables successfully imported.
	 */
	public function extract(array &$array, int $flags = EXTR_OVERWRITE): int
	{
		return extract($array, $flags);
	}

	/**
	 * Compute the difference of arrays using a callback on keys.
	 *
	 * @param array $array1 First array.
	 * @param array $array2 Second array.
	 * @param callable $callback Callback for key comparison.
	 * @return array The resulting difference array.
	 */
	public function diffUKey(array $array1, array $array2, callable $callback): array
	{
		return array_diff_ukey($array1, $array2, $callback);
	}

	/**
	 * Compute the intersection of arrays using a callback on keys.
	 *
	 * @param array $array1 First array.
	 * @param array $array2 Second array.
	 * @param callable $callback Callback for key comparison.
	 * @return array The resulting intersection array.
	 */
	public function intersectUKey(array $array1, array $array2, callable $callback): array
	{
		return array_intersect_ukey($array1, $array2, $callback);
	}

	/**
	 * Compute the difference of arrays with data and index comparison using callbacks.
	 *
	 * @param array ...$arrays Arrays to compare.
	 * @return array The resulting difference array.
	 */
	public function uDiffUAssoc(array ...$arrays): array
	{
		return array_udiff_uassoc(...$arrays);
	}

	/**
	 * Compute the intersection of arrays using a callback for data comparison.
	 *
	 * @param array ...$arrays Arrays to intersect.
	 * @return array The resulting intersection array.
	 */
	public function uIntersect(array ...$arrays): array
	{
		return array_uintersect(...$arrays);
	}

	/**
	 * Compute the intersection of arrays with data and index comparison using callbacks.
	 *
	 * @param array ...$arrays Arrays to intersect.
	 * @return array The resulting intersection array.
	 */
	public function uIntersectUAssoc(array ...$arrays): array
	{
		return array_uintersect_uassoc(...$arrays);
	}

	/**
	 * Check if a key exists in an array.
	 *
	 * @param array $array The input array.
	 * @param int|string $key The key to check for.
	 * @return bool True if the key exists, false otherwise.
	 */
	public function keyExists(array $array, int|string $key): bool
	{
		return key_exists($key, $array); // Alias of array_key_exists.
	}

	/**
	 * Get all keys of an array.
	 *
	 * @param array $data The input array.
	 * @return array The keys of the array.
	 */
	public function getKeys(array $data): array
	{
		return array_keys($data);
	}

	/**
	 * Get all values of an array.
	 *
	 * @param array $data The input array.
	 * @return array The values of the array.
	 */
	public function getValues(array $data): array
	{
		return array_values($data);
	}

	/**
	 * Search for a value in an array and return its key.
	 *
	 * @param array $data The input array.
	 * @param mixed $value The value to search for.
	 * @return mixed The key of the found element or false if not found.
	 */
	public function search(array $data, mixed $value): mixed
	{
		return array_search($value, $data);
	}

	/**
	 * Flatten a multi-dimensional array.
	 *
	 * @param array $data The multi-dimensional array.
	 * @return array The flattened array.
	 */
	public function flatten(array $data): array
	{
		return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($data)), false);
	}

	/**
	 * Count the occurrences of values in an array.
	 *
	 * @param array $array The input array.
	 * @return array An associative array with the values as keys and their counts as values.
	 */
	public function countValues(array $array): array
	{
		return array_count_values($array);
	}

	/**
	 * Compute the difference of arrays using a callback for data comparison.
	 *
	 * @param array $array1 The first array.
	 * @param array $array2 The second array.
	 * @param callable $callback The comparison function.
	 * @return array The resulting difference array.
	 */
	public function uDiff(array $array1, array $array2, callable $callback): array
	{
		return array_udiff($array1, $array2, $callback);
	}

	/**
	 * Compute the difference of arrays with index check using a callback.
	 *
	 * @param array $array1 The first array.
	 * @param array $array2 The second array.
	 * @param callable $callback The comparison function for values.
	 * @return array The resulting difference array.
	 */
	public function uDiffAssoc(array $array1, array $array2, callable $callback): array
	{
		return array_udiff_assoc($array1, $array2, $callback);
	}

	/**
	 * Compute the intersection of arrays using a callback for data comparison.
	 *
	 * @param array $array1 The first array.
	 * @param array $array2 The second array.
	 * @param callable $callback The comparison function.
	 * @return array The resulting intersection array.
	 */
	public function uIntersect(array $array1, array $array2, callable $callback): array
	{
		return array_uintersect($array1, $array2, $callback);
	}

	/**
	 * Compute the intersection of arrays with index check using a callback.
	 *
	 * @param array $array1 The first array.
	 * @param array $array2 The second array.
	 * @param callable $callback The comparison function for values.
	 * @return array The resulting intersection array.
	 */
	public function uIntersectAssoc(array $array1, array $array2, callable $callback): array
	{
		return array_uintersect_assoc($array1, $array2, $callback);
	}

	/**
	 * Merges one or more arrays recursively.
	 *
	 * @param array ...$arrays The arrays to merge.
	 * @return array The merged array.
	 */
	public function mergeRecursive(array ...$arrays): array
	{
		return array_merge_recursive(...$arrays);
	}

	/**
	 * Sorts multiple or multi-dimensional arrays.
	 *
	 * @param array &$array The array to sort.
	 * @param int $sortFlags Sorting options (default: SORT_REGULAR).
	 * @return bool True on success, false on failure.
	 */
	public function multisort(array &$array, int $sortFlags = SORT_REGULAR): bool
	{
		return array_multisort($array, $sortFlags);
	}

	/**
	 * Pads an array to the specified length with a value.
	 *
	 * - If the specified size is positive and greater than the array's length, the array is padded with the provided value.
	 * - If the specified size is negative, the padding is added at the beginning.
	 * - If the size is less than the array's length, the array is truncated to the specified size.
	 *
	 * @param array $array The input array.
	 * @param int $size The size of the resulting array.
	 * @param mixed $value The value to pad with.
	 * @return array The padded or truncated array.
	 */
	public function pad(array $array, int $size, mixed $value): array
	{
		return array_pad($array, $size, $value);
	}
	/**
	 * Replaces elements from passed arrays into the first array.
	 *
	 * @param array ...$arrays The arrays to replace.
	 * @return array The resulting array.
	 */
	public function replace(array ...$arrays): array
	{
		return array_replace(...$arrays);
	}

	/**
	 * Replaces elements recursively in the first array.
	 *
	 * @param array ...$arrays The arrays to replace.
	 * @return array The resulting array.
	 */
	public function replaceRecursive(array ...$arrays): array
	{
		return array_replace_recursive(...$arrays);
	}

	/**
	 * Applies a user-defined function to every member of an array.
	 *
	 * @param array &$array The input array.
	 * @param callable $callback The user-defined callback.
	 * @param mixed|null $userdata Optional userdata.
	 * @return bool True on success, false on failure.
	 */
	public function walk(array &$array, callable $callback, mixed $userdata = null): bool
	{
		return array_walk($array, $callback, $userdata);
	}

	/**
	 * Computes the difference of arrays with index check using a callback.
	 *
	 * @param array $array1 The first array.
	 * @param array $array2 The second array.
	 * @param callable $callback Callback for index comparison.
	 * @return array The resulting difference array.
	 */
	public function diffUAssoc(array $array1, array $array2, callable $callback): array
	{
		return array_diff_uassoc($array1, $array2, $callback);
	}

	/**
	 * Computes the intersection of arrays with index check using a callback.
	 *
	 * @param array $array1 The first array.
	 * @param array $array2 The second array.
	 * @param callable $callback Callback for index comparison.
	 * @return array The resulting intersection array.
	 */
	public function intersectUAssoc(array $array1, array $array2, callable $callback): array
	{
		return array_intersect_uassoc($array1, $array2, $callback);
	}

	/**
	 * Computes the intersection using a callback for data comparison.
	 *
	 * @param array $array1 The first array.
	 * @param array $array2 The second array.
	 * @param callable $callback Callback for data comparison.
	 * @return array The resulting intersection array.
	 */
	public function uIntersect(array $array1, array $array2, callable $callback): array
	{
		return array_uintersect($array1, $array2, $callback);
	}

	/**
	 * Sorts an array using a case-insensitive natural order algorithm.
	 *
	 * @param array &$array The input array.
	 * @return bool True on success, false on failure.
	 */
	public function natcasesort(array &$array): bool
	{
		return natcasesort($array);
	}

	/**
	 * Sorts an array using a natural order algorithm.
	 *
	 * @param array &$array The input array.
	 * @return bool True on success, false on failure.
	 */
	public function natsort(array &$array): bool
	{
		return natsort($array);
	}

	/**
	 * Gets the size of an array.
	 *
	 * @param array $array The input array.
	 * @param int $mode Count mode (default: COUNT_NORMAL).
	 * @return int The size of the array.
	 */
	public function sizeOf(array $array, int $mode = COUNT_NORMAL): int
	{
		return sizeof($array, $mode);
	}

	/**
	 * Selects one or more random keys from an array.
	 *
	 * @param array $array The input array.
	 * @param int $num The number of random keys to select (default: 1).
	 * @return mixed The selected key(s).
	 */
	public function rand(array $array, int $num = 1): mixed
	{
		return array_rand($array, $num);
	}

	/**
	 * Reverses the order of the elements in an array.
	 *
	 * @param array $array The input array.
	 * @param bool $preserveKeys Whether to preserve keys (default: false).
	 * @return array The reversed array.
	 */
	public function reverse(array $array, bool $preserveKeys = false): array
	{
		return array_reverse($array, $preserveKeys);
	}

	/**
	 * Removes and returns the last element of an array.
	 *
	 * @param array &$array The input array.
	 * @return mixed The removed element or null if the array is empty.
	 */
	public function pop(array &$array): mixed
	{
		return array_pop($array);
	}

	/**
	 * Pushes one or more elements onto the end of an array.
	 *
	 * @param array &$array The input array.
	 * @param mixed ...$values The values to push onto the array.
	 * @return int The new number of elements in the array.
	 */
	public function push(array &$array, mixed ...$values): int
	{
		return array_push($array, ...$values);
	}

	/**
	 * Removes and returns the first element of an array.
	 *
	 * @param array &$array The input array.
	 * @return mixed The removed element or null if the array is empty.
	 */
	public function shift(array &$array): mixed
	{
		return array_shift($array);
	}

	/**
	 * Prepends one or more elements to the beginning of an array.
	 *
	 * @param array &$array The input array.
	 * @param mixed ...$values The values to prepend to the array.
	 * @return int The new number of elements in the array.
	 */
	public function unshift(array &$array, mixed ...$values): int
	{
		return array_unshift($array, ...$values);
	}

	/**
	 * Extracts a portion of an array.
	 *
	 * @param array $array The input array.
	 * @param int $offset The starting offset.
	 * @param int|null $length The number of elements to extract (default: null).
	 * @param bool $preserveKeys Whether to preserve keys (default: false).
	 * @return array The extracted portion of the array.
	 */
	public function slice(array $array, int $offset, ?int $length = null, bool $preserveKeys = false): array
	{
		return array_slice($array, $offset, $length, $preserveKeys);
	}

	/**
	 * Removes a portion of the array and replaces it with something else.
	 *
	 * @param array &$array The input array.
	 * @param int $offset The starting offset.
	 * @param int|null $length The number of elements to remove (default: null).
	 * @param mixed $replacement The replacement array (default: []).
	 * @return array The removed portion of the array.
	 */
	public function splice(array &$array, int $offset, ?int $length = null, mixed $replacement = []): array
	{
		return array_splice($array, $offset, $length, $replacement);
	}

	/**
	 * Calculates the product of values in an array.
	 *
	 * @param array $array The input array.
	 * @return int|float The product of values.
	 */
	public function product(array $array): int|float
	{
		return array_product($array);
	}

	/**
	 * Calculates the sum of values in an array.
	 *
	 * @param array $array The input array.
	 * @return int|float The sum of values.
	 */
	public function sum(array $array): int|float
	{
		return array_sum($array);
	}

	/**
	 * Removes duplicate values from an array.
	 *
	 * @param array $array The input array.
	 * @param int $flags Sorting flags (default: SORT_STRING).
	 * @return array The array with duplicate values removed.
	 */
	public function unique(array $array, int $flags = SORT_STRING): array
	{
		return array_unique($array, $flags);
	}

	/**
	 * Applies a callback recursively to all elements of an array.
	 *
	 * @param array &$array The input array.
	 * @param callable $callback The callback function to apply.
	 * @return bool True on success, false on failure.
	 */
	public function walkRecursive(array &$array, callable $callback): bool
	{
		return array_walk_recursive($array, $callback);
	}

	/**
	 * Checks if the given array is a list.
	 *
	 * @param array $array The input array.
	 * @return bool True if the array is a list, false otherwise.
	 */
	public function isList(array $array): bool
	{
		return array_is_list($array);
	}

	/**
	 * Checks if a specified key exists in an array.
	 *
	 * @param int|string $key The key to check for.
	 * @param array $array The input array.
	 * @return bool True if the key exists, false otherwise.
	 */
	public function arraykeyExists(int|string $key, array $array): bool
	{
		return array_key_exists($key, $array);
	}

	/**
	 * Alias for computing the difference of arrays using a callback on keys.
	 *
	 * @param array $array1 First array.
	 * @param array $array2 Second array.
	 * @param callable $callback Callback for key comparison.
	 * @return array The resulting difference array.
	 */
	public function differenceByKeys(array $array1, array $array2, callable $callback): array
	{
		return $this->diffUKey($array1, $array2, $callback);
	}

	/**
	 * Alias for replacing elements from passed arrays into the first array.
	 *
	 * @param array ...$arrays The arrays to replace.
	 * @return array The resulting array.
	 */
	public function replaceElements(array ...$arrays): array
	{
		return $this->replace(...$arrays);
	}

	/**
	 * Filters an array by preserving only non-empty values.
	 *
	 * @param array $array The input array.
	 * @return array The filtered array.
	 */
	public function filterNonEmpty(array $array): array
	{
		return array_filter($array, fn($value) => !empty($value));
	}

	/**
	 * Computes the difference of arrays recursively using keys.
	 *
	 * @param array $array1 The first array.
	 * @param array $array2 The second array.
	 * @return array The resulting array after recursive key difference.
	 */
	public function diffKeyRecursive(array $array1, array $array2): array
	{
		$result = array_diff_key($array1, $array2);
		foreach ($result as $key => $value) {
			if (is_array($value) && isset($array2[$key]) && is_array($array2[$key])) {
				$result[$key] = $this->diffKeyRecursive($value, $array2[$key]);
			}
		}
		return $result;
	}

	/**
	 * Reduces an array to a single value using a callback function.
	 *
	 * @param array $array The input array.
	 * @param callable $callback The callback function.
	 * @param mixed|null $initial The initial value.
	 * @return mixed The resulting value after reduction.
	 */
	public function reduce(array $array, callable $callback, mixed $initial = null): mixed
	{
		return array_reduce($array, $callback, $initial);
	}

	/**
	 * Shuffles an array.
	 *
	 * @param array $array The input array.
	 * @return array The shuffled array.
	 */
	public function shuffle(array $array): array
	{
		$keys = array_keys($array);
		shuffle($keys);
		return array_combine($keys, $array);
	}

	/**
	 * Sorts an array recursively.
	 *
	 * @param array &$array The input array.
	 * @return void
	 */
	public function sortRecursive(array &$array): void
	{
		foreach ($array as &$value) {
			if (is_array($value)) {
				$this->sortRecursive($value);
			}
		}
		sort($array);
	}

	/**
	 * Merges arrays ensuring unique values.
	 *
	 * @param array ...$arrays Arrays to merge.
	 * @return array The resulting array with unique values.
	 */
	public function mergeUnique(array ...$arrays): array
	{
		return array_unique(array_merge(...$arrays));
	}

	/**
	 * Partitions an array into two groups based on a callback.
	 *
	 * @param array $array The input array.
	 * @param callable $callback The callback function.
	 * @return array A two-element array: [elements passing, elements failing].
	 */
	public function partition(array $array, callable $callback): array
	{
		$pass = [];
		$fail = [];
		foreach ($array as $key => $value) {
			if ($callback($value, $key)) {
				$pass[$key] = $value;
			} else {
				$fail[$key] = $value;
			}
		}
		return [$pass, $fail];
	}
}
