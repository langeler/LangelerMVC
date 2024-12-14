<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Custom application exception class for consistent error handling.
 *
 * Wraps any thrown exception or error and provides additional context.
 */
class ContainerException extends Exception
{
	/**
	 * AppException constructor.
	 *
	 * @param string $message A message detailing the error.
	 * @param Throwable|null $previous The previous exception, if any.
	 */
	public function __construct(string $message, ?Throwable $previous = null)
	{
		parent::__construct($message, 0, $previous);
	}

	/**
	 * Converts the exception to a string with a detailed stack trace.
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		return sprintf(
			"%s: %s\n%s",
			__CLASS__,
			$this->getMessage(),
			$this->getTraceAsString()
		);
	}
}
