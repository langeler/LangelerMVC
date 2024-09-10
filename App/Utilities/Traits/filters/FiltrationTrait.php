<?php

namespace App\Utilities\Traits\Filters;

/**
 * Trait FiltrationTrait
 *
 * Provides a convenient wrapper around PHP's filtering functions.
 */
trait FiltrationTrait
{
	/**
	 * Applies a filter to a single variable.
	 *
	 * @param mixed $variable The variable to process.
	 * @param int $filter The ID of the filter to apply (defaults to FILTER_DEFAULT).
	 * @param array|int|null $options Optional options or flags for processing.
	 * @return mixed Returns the filtered value or false on failure.
	 */
	public function var(mixed $variable, int $filter = FILTER_DEFAULT, array|int|null $options = null): mixed
	{
		return filter_var($variable, $filter, $options ?? 0);
	}

	/**
	 * Applies filters to an array of variables.
	 *
	 * @param array $data The array of variables to process.
	 * @param array $filters An array defining the filters for each key in $data.
	 * @param bool $addEmpty Whether to add missing keys as null in the result array.
	 * @return array|false|null Returns the filtered array, false on failure, or null on invalid input.
	 */
	public function varArray(array $data, array $filters, bool $addEmpty = true): array|false|null
	{
		return filter_var_array($data, $filters, $addEmpty);
	}

	/**
	 * Retrieves and filters a specific input variable from a given input type.
	 *
	 * @param int $type The input type (e.g., INPUT_GET, INPUT_POST, INPUT_COOKIE).
	 * @param string $variableName The name of the variable to process.
	 * @param int $filter The ID of the filter to apply (defaults to FILTER_DEFAULT).
	 * @param array|int|null $options Optional options or flags for processing.
	 * @return mixed Returns the filtered value or false if the variable is not set or invalid.
	 */
	public function input(int $type, string $variableName, int $filter = FILTER_DEFAULT, array|int|null $options = null): mixed
	{
		return filter_input($type, $variableName, $filter, $options ?? 0);
	}

	/**
	 * Applies filters to multiple input variables from a specified input type.
	 *
	 * @param int $type The input type (e.g., INPUT_GET, INPUT_POST, INPUT_COOKIE).
	 * @param array $filters An array defining the filters for each variable.
	 * @param bool $addEmpty Whether to add missing variables as null in the result array.
	 * @return array|false|null Returns the filtered input array, false on failure, or null on invalid input.
	 */
	public function inputArray(int $type, array $filters, bool $addEmpty = true): array|false|null
	{
		return filter_input_array($type, $filters, $addEmpty);
	}

	/**
	 * Processes a variable using a custom callback function.
	 *
	 * @param mixed $variable The variable to process.
	 * @param callable $callback The custom callback function.
	 * @return mixed Returns the result of the callback, or false if the callback fails.
	 */
	public function withCallback(mixed $variable, callable $callback): mixed
	{
		return $this->var($variable, FILTER_CALLBACK, ['options' => $callback]);
	}
}
