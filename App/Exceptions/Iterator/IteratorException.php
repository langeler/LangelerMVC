<?php

namespace App\Exceptions\Iterator;

use Exception;
use Throwable;

/**
 * Class IteratorException
 *
 * Base exception for all iterator-related errors.
 */
class IteratorException extends Exception
{
	/**
	 * IteratorException constructor.
	 *
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}
