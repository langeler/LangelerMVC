<?php

namespace App\Utilities\Traits;

/**
 * Trait LoopTrait
 *
 * Provides utility methods to replace native loop constructs.
 */
trait LoopTrait
{
	/**
	 * Iterate over an array and return each key-value pair as a string.
	 *
	 * @param array $array The array to iterate over.
	 * @param callable $callback The callback to process each key-value pair.
	 * @return string The formatted result of the loop.
	 */
	public function each(array $array, callable $callback): string
	{
		foreach ($array as $key => $value) {
			$callback($key, $value);
		}
		return '';
	}

	/**
	 * Execute a for loop, optionally overriding start and end values.
	 *
	 * @param int $start Starting value for the loop.
	 * @param int $end Ending value for the loop.
	 * @param callable $callback The callback to execute for each iteration.
	 * @return void
	 */
	public function count(int $start, int $end, callable $callback): void
	{
		for ($i = $start; $i < $end; $i++) {
			$callback($i);
		}
	}

	/**
	 * Execute a while loop, optionally overriding start and end values.
	 *
	 * @param int $start Starting value for the loop.
	 * @param int $end Ending value for the loop.
	 * @param callable $callback The callback to execute for each iteration.
	 * @return void
	 */
	public function until(int $start, int $end, callable $callback): void
	{
		while ($start < $end) {
			$callback($start);
			$start++;
		}
	}

	/**
	 * Execute a do-while loop, optionally overriding start and end values.
	 *
	 * @param int $start Starting value for the loop.
	 * @param int $end Ending value for the loop.
	 * @param callable $callback The callback to execute for each iteration.
	 * @return void
	 */
	public function atLeastOnce(int $start, int $end, callable $callback): void
	{
		do {
			$callback($start);
			$start++;
		} while ($start < $end);
	}

	/**
	 * Repeat a loop a specified number of times.
	 *
	 * @param int $times The number of iterations to perform.
	 * @param callable $callback The callback to execute for each iteration.
	 * @return void
	 */
	public function repeat(int $times, callable $callback): void
	{
		for ($i = 0; $i < $times; $i++) {
			$callback($i);
		}
	}

	/**
	 * Execute a range-based loop.
	 *
	 * @param int $start Starting value for the range.
	 * @param int $end Ending value for the range.
	 * @param callable $callback The callback to execute for each value in the range.
	 * @return void
	 */
	public function through(int $start, int $end, callable $callback): void
	{
		foreach (range($start, $end) as $value) {
			$callback($value);
		}
	}

	/**
	 * Execute a range loop with a custom step.
	 *
	 * @param int $start Starting value for the range.
	 * @param int $end Ending value for the range.
	 * @param int $step Step value for the range.
	 * @param callable $callback The callback to execute for each value in the range.
	 * @return void
	 */
	public function stepRange(int $start, int $end, int $step, callable $callback): void
	{
		for ($i = $start; $i <= $end; $i += $step) {
			$callback($i);
		}
	}
}
