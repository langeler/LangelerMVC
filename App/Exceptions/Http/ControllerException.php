<?php

namespace App\Exceptions\Http;

use Exception;
use Throwable;

class ControllerException extends Exception
{
	/**
	 * Constructor for ControllerException.
	 *
	 * @param string $message The exception message.
	 * @param int $code The exception code.
	 * @param Throwable|null $previous The previous exception for chaining.
	 */
	public function __construct($message = "Controller error occurred", $code = 0, ?Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}

	/**
	 * Custom string representation of the exception.
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}
}
