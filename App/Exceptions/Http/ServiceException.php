<?php

namespace App\Exceptions\Http;

use Exception;

class ServiceException extends Exception
{
	/**
	 * Constructor for ServiceException.
	 *
	 * @param string $message The exception message.
	 * @param int $code The exception code.
	 * @param Exception|null $previous The previous exception for chaining.
	 */
	public function __construct($message = "Service error occurred", $code = 0, Exception $previous = null)
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
