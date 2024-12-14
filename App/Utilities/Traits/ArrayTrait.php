<?php

namespace App\Utilities\Traits;

/**
 * Trait ArrayTrait
 *
 * Provides utility methods for common array operations with flexible parameter handling.
 */
trait ArrayTrait
{
	public function reduce(array $data, callable $callback, mixed $initial): mixed
	{
		return array_reduce($data, $callback, $initial);
	}

	public function chunk(array $data, int $size): array
	{
		return array_chunk($data, $size);
	}

	public function combine(array $keys, array $values): array
	{
		return array_combine($keys, $values);
	}

	public function diff(array ...$data): array
	{
		return array_diff(...$data);
	}

	public function diffAssoc(array ...$data): array
	{
		return array_diff_assoc(...$data);
	}

	public function diffKey(array ...$data): array
	{
		return array_diff_key(...$data);
	}

	public function filter(array $data, callable $callback): array
	{
		return array_filter($data, $callback);
	}

	public function merge(array ...$data): array
	{
		return array_merge(...$data);
	}

	public function mergeRecursive(array ...$data): array
	{
		return array_merge_recursive(...$data);
	}

	public function getKeys(array $data): array
	{
		return array_keys($data);
	}

	public function getValues(array $data): array
	{
		return array_values($data);
	}

	public function search(array $data, mixed $value): mixed
	{
		return array_search($value, $data);
	}

	public function append(array &$data, mixed $value): int
	{
		return array_push($data, $value);
	}

	public function prepend(array &$data, mixed $value): int
	{
		return array_unshift($data, $value);
	}

	public function replace(array ...$data): array
	{
		return array_replace(...$data);
	}

	public function replaceRecursive(array ...$data): array
	{
		return array_replace_recursive(...$data);
	}

	public function slice(array $data, int $offset, ?int $length = null, bool $preserveKeys = false): array
	{
		return array_slice($data, $offset, $length, $preserveKeys);
	}

	public function reverse(array $data): array
	{
		return array_reverse($data);
	}

	public function flatten(array $data): array
	{
		return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($data)), false);
	}

	public function getUnique(array $data): array
	{
		return array_unique($data);
	}

	public function getRandomKeys(array $data, int $num = 1): mixed
	{
		return array_rand($data, $num);
	}

	public function extractColumn(array $data, int|string|null $columnKey, int|string|null $indexKey = null): array
	{
		return array_column($data, $columnKey, $indexKey);
	}

	public function pad(array $data, int $size, mixed $value): array
	{
		return array_pad($data, $size, $value);
	}

	public function shuffle(array &$data): void
	{
		shuffle($data);
	}

	public function intersect(array ...$data): array
	{
		return array_intersect(...$data);
	}

	public function intersectAssoc(array ...$data): array
	{
		return array_intersect_assoc(...$data);
	}

	public function intersectKey(array ...$data): array
	{
		return array_intersect_key(...$data);
	}

	public function changeKeyCase(array $data, int $case = CASE_LOWER): array
	{
		return array_change_key_case($data, $case);
	}

	public function fill(int $startIndex, int $count, mixed $value): array
	{
		return array_fill($startIndex, $count, $value);
	}

	public function fillKeys(array $keys, mixed $value): array
	{
		return array_fill_keys($keys, $value);
	}

	public function flip(array $data): array
	{
		return array_flip($data);
	}

	public function keyExists(array $data, int|string $key): bool
	{
		return array_key_exists($key, $data);
	}

	public function map(callable $callback, array ...$arrays): array
	{
		return array_map($callback, ...$arrays);
	}

	public function multisort(array &$array, int $sortFlags = SORT_REGULAR): bool
	{
		return array_multisort($array, $sortFlags);
	}

	public function pop(array &$array): mixed
	{
		return array_pop($array);
	}

	public function product(array $array): int|float
	{
		return array_product($array);
	}

	public function shift(array &$array): mixed
	{
		return array_shift($array);
	}

	public function sum(array $array): int|float
	{
		return array_sum($array);
	}

	public function walk(array &$array, callable $callback, mixed $userdata = null): bool
	{
		return array_walk($array, $callback, $userdata);
	}

	public function walkRecursive(array &$array, callable $callback, mixed $userdata = null): bool
	{
		return array_walk_recursive($array, $callback, $userdata);
	}

	public function arsort(array &$array, int $sortFlags = SORT_REGULAR): bool
	{
		return arsort($array, $sortFlags);
	}

	public function asort(array &$array, int $sortFlags = SORT_REGULAR): bool
	{
		return asort($array, $sortFlags);
	}

	public function count(array $array, int $mode = COUNT_NORMAL): int
	{
		return count($array, $mode);
	}

	public function inArray(mixed $needle, array $haystack, bool $strict = false): bool
	{
		return in_array($needle, $haystack, $strict);
	}

	public function ksort(array &$array, int $sortFlags = SORT_REGULAR): bool
	{
		return ksort($array, $sortFlags);
	}

	public function krsort(array &$array, int $sortFlags = SORT_REGULAR): bool
	{
		return krsort($array, $sortFlags);
	}

	public function natcasesort(array &$array): bool
	{
		return natcasesort($array);
	}

	public function natsort(array &$array): bool
	{
		return natsort($array);
	}

	public function rsort(array &$array, int $sortFlags = SORT_REGULAR): bool
	{
		return rsort($array, $sortFlags);
	}

	public function sort(array &$array, int $sortFlags = SORT_REGULAR): bool
	{
		return sort($array, $sortFlags);
	}

	public function usort(array &$array, callable $callback): bool
	{
		return usort($array, $callback);
	}

	public function uasort(array &$array, callable $callback): bool
	{
		return uasort($array, $callback);
	}

	public function uksort(array &$array, callable $callback): bool
	{
		return uksort($array, $callback);
	}

	public function splice(array &$array, int $offset, ?int $length = null, mixed $replacement = []): array
	{
		return array_splice($array, $offset, $length, $replacement);
	}

	public function compact(array $variableNames, array $variables): array
	{
		return compact(...$variableNames);
	}
}
