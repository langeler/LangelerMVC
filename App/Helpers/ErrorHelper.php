<?php

namespace App\Helpers;

use Exception;
use ErrorException;

/**
 * Class ErrorHelper
 *
 * Provides utility methods for handling errors and exceptions in PHP.
 */
class ErrorHelper
{
	// Error Handling Methods

	/**
	 * Set a custom error handler.
	 *
	 * @param callable $errorHandler The custom error handler function.
	 * @return callable|null Returns the previous error handler or null on failure.
	 */
	public function setErrorHandler(callable $errorHandler): ?callable
	{
		return set_error_handler($errorHandler);
	}

	/**
	 * Trigger a user-level error.
	 *
	 * @param string $errorMessage The error message to trigger.
	 * @param int $errorType The error type (e.g., E_USER_NOTICE).
	 * @return bool True on success, false on failure.
	 */
	public function triggerError(string $errorMessage, int $errorType = E_USER_NOTICE): bool
	{
		return trigger_error($errorMessage, $errorType);
	}

	/**
	 * Set the error reporting level.
	 *
	 * @param int $errorLevel The desired error reporting level (e.g., E_ALL).
	 * @return int The old error reporting level.
	 */
	public function setReportingLevel(int $errorLevel): int
	{
		return error_reporting($errorLevel);
	}

	/**
	 * Set a custom exception handler.
	 *
	 * @param callable $exceptionHandler The custom exception handler function.
	 * @return callable|null Returns the previous exception handler or null on failure.
	 */
	public function setExceptionHandler(callable $exceptionHandler): ?callable
	{
		return set_exception_handler($exceptionHandler);
	}

	// Exception Handling Methods

	/**
	 * Create a new Exception instance.
	 *
	 * @param string $message The exception message.
	 * @param int $code The exception code.
	 * @param Exception|null $previous Optional previous exception for chaining.
	 * @return Exception The created Exception instance.
	 */
	public function createException(string $message, int $code = 0, ?Exception $previous = null): Exception
	{
		return new Exception($message, $code, $previous);
	}

	/**
	 * Get the message from an Exception.
	 *
	 * @param Exception $exception The exception to get the message from.
	 * @return string The exception message.
	 */
	public function getExceptionMessage(Exception $exception): string
	{
		return $exception->getMessage();
	}

	/**
	 * Get the code from an Exception.
	 *
	 * @param Exception $exception The exception to get the code from.
	 * @return int The exception code.
	 */
	public function getExceptionCode(Exception $exception): int
	{
		return $exception->getCode();
	}

	/**
	 * Get the file where the Exception was thrown.
	 *
	 * @param Exception $exception The exception to get the file from.
	 * @return string The file name where the exception occurred.
	 */
	public function getExceptionFile(Exception $exception): string
	{
		return $exception->getFile();
	}

	/**
	 * Get the line number where the Exception was thrown.
	 *
	 * @param Exception $exception The exception to get the line number from.
	 * @return int The line number where the exception occurred.
	 */
	public function getExceptionLine(Exception $exception): int
	{
		return $exception->getLine();
	}

	// ErrorException Handling Methods

	/**
	 * Create a new ErrorException instance.
	 *
	 * @param string $message The error message.
	 * @param int $code The exception code.
	 * @param int $severity The severity level of the error.
	 * @param string|null $filename Optional filename where the error occurred.
	 * @param int|null $lineno Optional line number where the error occurred.
	 * @param Exception|null $previous Optional previous exception for chaining.
	 * @return ErrorException The created ErrorException instance.
	 */
	public function createErrorException(
		string $message,
		int $code = 0,
		int $severity = E_ERROR,
		?string $filename = null,
		?int $lineno = null,
		?Exception $previous = null
	): ErrorException {
		return new ErrorException($message, $code, $severity, $filename, $lineno, $previous);
	}

	/**
	 * Get the severity level of an ErrorException.
	 *
	 * @param ErrorException $exception The ErrorException instance.
	 * @return int The severity level of the ErrorException.
	 */
	public function getErrorExceptionSeverity(ErrorException $exception): int
	{
		return $exception->getSeverity();
	}
}
