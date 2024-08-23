<?php

namespace App\Helpers\Traits;

/**
 * Trait LoopUtilsTrait
 *
 * This trait provides utility methods for different looping structures available in PHP.
 * It ensures that each loop type is represented only once, with a focus on clarity and usability.
 */
trait LoopUtilsTrait
{
    /**
     * Iterate over an array using a callback for each element.
     *
     * @param array $array The array to iterate over.
     * @param callable $callback The callback function to apply on each element.
     */
    public function iterate(array $array, callable $callback): void
    {
        foreach ($array as $key => $value) {
            $callback($value, $key);
        }
    }

    /**
     * Execute a standard while loop with a callable condition.
     *
     * @param callable $condition The condition to evaluate on each iteration.
     * @param callable $callback The callback function to execute while the condition is true.
     */
    public function whileLoop(callable $condition, callable $callback): void
    {
        while ($condition()) {
            $callback();
        }
    }

    /**
     * Execute a standard do-while loop with a callable condition.
     *
     * @param callable $condition The condition to evaluate after each iteration.
     * @param callable $callback The callback function to execute at least once and while the condition is true.
     */
    public function doWhileLoop(callable $condition, callable $callback): void
    {
        do {
            $callback();
        } while ($condition());
    }

    /**
     * Execute a standard for loop with a start and end condition.
     *
     * @param int $start The starting index for the loop.
     * @param int $end The end condition for the loop.
     * @param callable $callback The callback function to execute for each iteration.
     */
    public function forLoop(int $start, int $end, callable $callback): void
    {
        for ($i = $start; $i < $end; $i++) {
            $callback($i);
        }
    }

    /**
     * Iterate over a range of numbers with a callback.
     *
     * @param int $start The starting number of the range.
     * @param int $end The ending number of the range.
     * @param callable $callback The callback function to apply on each number in the range.
     */
    public function rangeLoop(int $start, int $end, callable $callback): void
    {
        foreach (range($start, $end) as $value) {
            $callback($value);
        }
    }

    /**
     * Repeat a loop a specified number of times.
     *
     * @param int $times The number of times to repeat the loop.
     * @param callable $callback The callback function to execute on each iteration.
     */
    public function repeat(int $times, callable $callback): void
    {
        for ($i = 0; $i < $times; $i++) {
            $callback($i);
        }
    }

    /**
     * Recursively iterate over an array with a callback.
     *
     * @param array $array The array to recursively iterate over.
     * @param callable $callback The callback function to apply on each element in the array.
     */
    public function recursiveIterate(array $array, callable $callback): void
    {
        array_walk_recursive($array, $callback);
    }
}
