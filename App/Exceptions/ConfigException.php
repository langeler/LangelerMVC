<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ConfigException extends Exception
{
	/**
	 * Constructor for ConfigException.
	 *
	 * @param string $message The exception message.
	 * @param int $code The exception code.
	 * @param Throwable|null $previous The previous exception for chaining.
	 */
	public function __construct(string $message = "Router error occurred", int $code = 0, ?Throwable $previous = null)
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
