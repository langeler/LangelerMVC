<?php

namespace App\Exceptions\Iterator;

/**
 * Class IteratorNotFoundException
 *
 * Thrown when an iterator is not found or is unrecognized.
 */
class IteratorNotFoundException extends IteratorException
{
	/**
	 * IteratorNotFoundException constructor.
	 *
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct(string $message = "Iterator not found", int $code = 0, ?Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}
