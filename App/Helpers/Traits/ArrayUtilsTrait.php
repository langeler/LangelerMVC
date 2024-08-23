<?php

namespace App\Helpers\Traits;

/**
 * Trait ArrayUtilsTrait
 *
 * This trait provides utility methods for various array operations using built-in PHP functions.
 * It includes all built-in array functions, named clearly for easy understanding and usage.
 */
trait ArrayUtilsTrait
{
    /**
     * Apply a callback function to each element of the array.
     *
     * @param array $array The array to be processed.
     * @param callable $callback The callback function to apply on each element.
     * @return array The array with processed elements.
     */
    public function apply(array $array, callable $callback): array
    {
        return array_map($callback, $array);
    }

    /**
     * Filter elements of the array using a callback function.
     *
     * @param array $array The array to be filtered.
     * @param callable $callback The callback function to determine if an element should be included.
     * @return array The filtered array.
     */
    public function filter(array $array, callable $callback): array
    {
        return array_filter($array, $callback);
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
     * Merge one or more arrays together.
     *
     * @param array ...$arrays The arrays to be merged.
     * @return array The merged array.
     */
    public function merge(array ...$arrays): array
    {
        return array_merge(...$arrays);
    }

    /**
     * Recursively merge one or more arrays together.
     *
     * @param array ...$arrays The arrays to be merged.
     * @return array The recursively merged array.
     */
    public function mergeRecursive(array ...$arrays): array
    {
        return array_merge_recursive(...$arrays);
    }

    /**
     * Combine two arrays: one for keys and one for values.
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
     * Search the array for a given value and return the corresponding key if found.
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
     * Randomly shuffle the array.
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
     * Calculate the sum of the values in the array.
     *
     * @param array $array The array to sum values from.
     * @return float|int The sum of values.
     */
    public function sum(array $array)
    {
        return array_sum($array);
    }

    /**
     * Calculate the product of the values in the array.
     *
     * @param array $array The array to multiply values from.
     * @return float|int The product of values.
     */
    public function product(array $array)
    {
        return array_product($array);
    }

    /**
     * Remove the first element from the array and return it.
     *
     * @param array $array The array to shift.
     * @return mixed The shifted element.
     */
    public function removeFirst(array &$array)
    {
        return array_shift($array);
    }

    /**
     * Remove the last element from the array and return it.
     *
     * @param array $array The array to pop.
     * @return mixed The popped element.
     */
    public function removeLast(array &$array)
    {
        return array_pop($array);
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
     * Replace a portion of the array with new elements.
     *
     * @param array &$array The original array.
     * @param int $offset The starting point to replace.
     * @param int $length The number of elements to replace.
     * @param mixed $replacement The elements to replace with.
     * @return array The modified array.
     */
    public function replaceSegment(array &$array, int $offset, int $length = 0, $replacement = []): array
    {
        return array_splice($array, $offset, $length, $replacement);
    }

    /**
     * Extract a slice of the array.
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
     * Return all the keys from the array.
     *
     * @param array $array The array to extract keys from.
     * @return array The array of keys.
     */
    public function getKeys(array $array): array
    {
        return array_keys($array);
    }

    /**
     * Return all the values from the array.
     *
     * @param array $array The array to extract values from.
     * @return array The array of values.
     */
    public function getValues(array $array): array
    {
        return array_values($array);
    }

    /**
     * Check if a key exists in the array.
     *
     * @param mixed $key The key to check.
     * @param array $array The array to check in.
     * @return bool True if the key exists, false otherwise.
     */
    public function hasKey($key, array $array): bool
    {
        return array_key_exists($key, $array);
    }

    /**
     * Apply a callback function to each element of the array.
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
     * Recursively apply a callback function to each element of the array.
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
     * Compute the intersection of arrays.
     *
     * @param array ...$arrays The arrays to intersect.
     * @return array The intersected array.
     */
    public function intersect(array ...$arrays): array
    {
        return array_intersect(...$arrays);
    }

    /**
     * Compute the intersection of arrays using keys for comparison.
     *
     * @param array ...$arrays The arrays to intersect by keys.
     * @return array The intersected array.
     */
    public function intersectKeys(array ...$arrays): array
    {
        return array_intersect_key(...$arrays);
    }

    /**
     * Compute the difference of arrays.
     *
     * @param array ...$arrays The arrays to compare.
     * @return array The array containing all the values from the first array that are not present in any of the other arrays.
     */
    public function diff(array ...$arrays): array
    {
        return array_diff(...$arrays);
    }

    /**
     * Compute the difference of arrays using keys for comparison.
     *
     * @param array ...$arrays The arrays to compare by keys.
     * @return array The array containing all the values from the first array that are not present in any of the other arrays, comparing keys.
     */
    public function diffKeys(array ...$arrays): array
    {
        return array_diff_key(...$arrays);
    }

    /**
     * Flip all keys with their associated values in an array.
     *
     * @param array $array The array to flip.
     * @return array The flipped array.
     */
    public function flip(array $array): array
    {
        return array_flip($array);
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
     * Fill an array with values.
     *
     * @param int $startIndex The first index to fill.
     * @param int $count The number of elements to fill.
     * @param mixed $value The value to fill.
     * @return array The filled array.
     */
    public function fill(int $startIndex, int $count, $value): array
    {
        return array_fill($startIndex, $count, $value);
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
     * Pad an array to the specified length with a value.
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
     * Pick one or more random keys out of an array.
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

    /**
     * Compute the difference of arrays with additional index check.
     *
     * @param array ...$arrays The arrays to compare.
     * @return array The array containing all the values from the first array that are not present in any of the other arrays, comparing indexes.
     */
    public function diffAssoc(array ...$arrays): array
    {
        return array_diff_assoc(...$arrays);
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
     * Compute the difference of arrays using a callback function on the keys for comparison.
     *
     * @param array $array1 The first array.
     * @param array $array2 The second array.
     * @param callable $callback The callback function to use for comparison.
     * @return array The array containing all the values from the first array that are not present in the second array, using a callback function for comparison.
     */
    public function diffUsingCallback(array $array1, array $array2, callable $callback): array
    {
        return array_udiff($array1, $array2, $callback);
    }

    /**
     * Compute the intersection of arrays using a callback function on the keys for comparison.
     *
     * @param array $array1 The first array.
     * @param array $array2 The second array.
     * @param callable $callback The callback function to use for comparison.
     * @return array The intersected array, using a callback function for comparison.
     */
    public function intersectUsingCallback(array $array1, array $array2, callable $callback): array
    {
        return array_uintersect($array1, $array2, $callback);
    }

    /**
     * Split an array into chunks.
     *
     * @param array $array The array to chunk.
     * @param int $size The size of each chunk.
     * @return array The array of chunks.
     */
    public function chunk(array $array, int $size): array
    {
        return array_chunk($array, $size);
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
     * Group the array by a callback function or a key.
     *
     * @param array $array The array to group.
     * @param mixed $groupBy The key or callback function to group by.
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
     * Sort the array by a callback function.
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
     * Compute the intersection of arrays with additional index check, using a callback function for comparison.
     *
     * @param array $array1 The first array.
     * @param array $array2 The second array.
     * @param callable $keyCompare The callback function to use for comparison.
     * @return array The intersected array with additional index check.
     */
    public function intersectAssocCallback(array $array1, array $array2, callable $keyCompare): array
    {
        return array_uintersect_assoc($array1, $array2, $keyCompare);
    }

    /**
     * Compute the difference of arrays with additional index check, using a callback function for comparison.
     *
     * @param array $array1 The first array.
     * @param array $array2 The second array.
     * @param callable $keyCompare The callback function to use for comparison.
     * @return array The array containing all the values from the first array that are not present in the second array, with additional index check.
     */
    public function diffAssocCallback(array $array1, array $array2, callable $keyCompare): array
    {
        return array_udiff_assoc($array1, $array2, $keyCompare);
    }

    /**
     * Compute the intersection of arrays with additional index check, using two callback functions for comparison.
     *
     * @param array $array1 The first array.
     * @param array $array2 The second array.
     * @param callable $keyCompare The callback function to use for comparison.
     * @return array The intersected array with additional index check.
     */
    public function intersectAssocMultiCallback(array $array1, array $array2, callable $keyCompare): array
    {
        return array_uintersect_uassoc($array1, $array2, $keyCompare, $keyCompare);
    }

    /**
     * Compute the difference of arrays with additional index check, using two callback functions for comparison.
     *
     * @param array $array1 The first array.
     * @param array $array2 The second array.
     * @param callable $keyCompare The callback function to use for comparison.
     * @return array The array containing all the values from the first array that are not present in the second array, with additional index check.
     */
    public function diffAssocMultiCallback(array $array1, array $array2, callable $keyCompare): array
    {
        return array_udiff_uassoc($array1, $array2, $keyCompare, $keyCompare);
    }
}
