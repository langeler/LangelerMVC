<?php

namespace App\Exceptions\Database;

use Exception;

class ModelException extends Exception
{
	/**
	 * Constructor for ModelException.
	 *
	 * @param string $message The exception message.
	 * @param int $code The exception code.
	 * @param Exception|null $previous The previous exception for chaining.
	 */
	public function __construct($message = "Model error occurred", $code = 0, Exception $previous = null)
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
