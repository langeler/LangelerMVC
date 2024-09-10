<?php

namespace App\Helpers;

/**
 * Class LoopHelper
 *
 * Provides access to common PHP loop structures using properties directly.
 * Properties are overridden if optional values are passed to the methods.
 */
class LoopHelper
{
	// Predefined values for loop variables
	public int $start;
	public int $end;
	public int $step;
	public string $result;  // The result string passed like start, end, and step

	/**
	 * LoopHelper constructor to initialize start, end, step, and result.
	 *
	 * @param int $start The starting value for loops.
	 * @param int $end The ending value for loops.
	 * @param int $step The step value for loops.
	 * @param string $result The initial result value.
	 */
	public function __construct(int $start, int $end, int $step, string $result)
	{
		$this->start = $start;
		$this->end = $end;
		$this->step = $step;
		$this->result = $result;
	}

	/**
	 * Iterate over an array and return each key-value pair as a string.
	 *
	 * @param array|null $array Optional array to iterate over, defaults to an empty array.
	 * @return string The formatted result of the loop.
	 */
	public function each(?array $array = []): string
	{
		foreach ($array as $key => $value) {
			$this->result .= "$key => $value\n";
		}

		return $this->result;
	}

	/**
	 * Execute a for loop, optionally overriding start and end properties.
	 *
	 * @param int|null $start Optional start value, defaults to the class's start property.
	 * @param int|null $end Optional end value, defaults to the class's end property.
	 * @return string The formatted result of the loop.
	 */
	public function count(?int $start = null, ?int $end = null): string
	{
		// Override class properties if values are passed
		$this->start = $start ?? $this->start;
		$this->end = $end ?? $this->end;

		for ($i = $this->start; $i < $this->end; $i++) {
			$this->result .= "Iteration: $i\n";
		}

		return $this->result;
	}

	/**
	 * Execute a while loop, optionally overriding start and end properties.
	 *
	 * @param int|null $start Optional start value, defaults to the class's start property.
	 * @param int|null $end Optional end value, defaults to the class's end property.
	 * @return string The formatted result of the loop.
	 */
	public function until(?int $start = null, ?int $end = null): string
	{
		// Override class properties if values are passed
		$this->start = $start ?? $this->start;
		$this->end = $end ?? $this->end;

		$i = $this->start;
		while ($i < $this->end) {
			$this->result .= "Iteration: $i\n";
			$i++;
		}

		return $this->result;
	}

	/**
	 * Execute a do-while loop, optionally overriding start and end properties.
	 *
	 * @param int|null $start Optional start value, defaults to the class's start property.
	 * @param int|null $end Optional end value, defaults to the class's end property.
	 * @return string The formatted result of the loop.
	 */
	public function atLeastOnce(?int $start = null, ?int $end = null): string
	{
		// Override class properties if values are passed
		$this->start = $start ?? $this->start;
		$this->end = $end ?? $this->end;

		$i = $this->start;
		do {
			$this->result .= "Iteration: $i\n";
			$i++;
		} while ($i < $this->end);

		return $this->result;
	}

	/**
	 * Repeat a loop a specified number of times, allowing an optional override.
	 *
	 * @param int|null $times The number of times to repeat, defaults to 10.
	 * @return string The formatted result of the loop.
	 */
	public function repeat(?int $times = null): string
	{
		$times = $times ?? 10;

		for ($i = 0; $i < $times; $i++) {
			$this->result .= "Iteration: $i\n";
		}

		return $this->result;
	}

	/**
	 * Execute a range-based loop, optionally overriding start and end properties.
	 *
	 * @param int|null $start Optional start value, defaults to the class's start property.
	 * @param int|null $end Optional end value, defaults to the class's end property.
	 * @return string The formatted result of the loop.
	 */
	public function through(?int $start = null, ?int $end = null): string
	{
		// Override class properties if values are passed
		$this->start = $start ?? $this->start;
		$this->end = $end ?? $this->end;

		foreach (range($this->start, $this->end) as $number) {
			$this->result .= "Iteration: $number\n";
		}

		return $this->result;
	}

	/**
	 * Execute a range loop with a custom step, optionally overriding start, end, and step properties.
	 *
	 * @param int|null $start Optional start value, defaults to the class's start property.
	 * @param int|null $end Optional end value, defaults to the class's end property.
	 * @param int|null $step Optional step value, defaults to the class's step property.
	 * @return string The formatted result of the loop.
	 */
	public function stepRange(?int $start = null, ?int $end = null, ?int $step = null): string
	{
		// Override class properties if values are passed
		$this->start = $start ?? $this->start;
		$this->end = $end ?? $this->end;
		$this->step = $step ?? $this->step;

		for ($i = $this->start; $i < $this->end; $i += $this->step) {
			$this->result .= "Iteration: $i\n";
		}

		return $this->result;
	}
}
