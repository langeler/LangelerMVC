<?php

namespace App\Exceptions\Manager;

use Exception;

/**
 * Class IteratorException
 *
 * Custom exception class for iterator-related errors in IteratorManager.
 */
class IteratorException extends Exception
{
	/**
	 * IteratorException constructor.
	 *
	 * @param string $message
	 * @param int $code
	 * @param Exception|null $previous
	 */
	public function __construct(string $message = "", int $code = 0, ?Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}
