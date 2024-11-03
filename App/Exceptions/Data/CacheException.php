<?php

namespace App\Exceptions\Data;

use Exception;

class CacheException extends Exception
{
	/**
	 * Constructor for CacheException.
	 *
	 * @param string $message The exception message.
	 * @param int $code The exception code.
	 * @param Exception|null $previous The previous exception for chaining.
	 */
	public function __construct($message = "Router error occurred", $code = 0, Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}

	/**
	 * Custom string representation of the exception.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}
}
