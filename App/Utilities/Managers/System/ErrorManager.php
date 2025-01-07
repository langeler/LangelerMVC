<?php

namespace App\Utilities\Managers\System;

// Exception Imports
use Exception;
use Throwable;

// Trait imports
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\ExistenceCheckerTrait;
use App\Utilities\Traits\TypeCheckerTrait;

/**
 * Class ErrorManager
 *
 * Provides comprehensive error and exception management for the system,
 * including logging, handling, and suppressing errors.
 */
class ErrorManager
{
	// Use traits for additional functionality
	use ArrayTrait, ExistenceCheckerTrait, TypeCheckerTrait;

	/**
	 * Defines various error levels for logging and handling.
	 *
	 * @var array
	 */
	public readonly array $errorLevels;

	/**
	 * Constructor initializes the error levels array.
	 */
	public function __construct()
	{
		$this->errorLevels = [
			'allErrors'        => E_ALL,               // All errors, warnings, and notices
			'compileError'     => E_COMPILE_ERROR,     // Fatal compile-time errors
			'compileWarning'   => E_COMPILE_WARNING,   // Compile-time warnings
			'coreError'        => E_CORE_ERROR,        // Fatal errors during PHP startup
			'coreWarning'      => E_CORE_WARNING,      // Warnings during PHP startup
			'deprecated'       => E_DEPRECATED,        // Runtime deprecation notices
			'fatalError'       => E_ERROR,             // Fatal runtime errors
			'notice'           => E_NOTICE,            // Runtime notices
			'parseError'       => E_PARSE,             // Compile-time parse errors
			'recoverableError' => E_RECOVERABLE_ERROR, // Catchable fatal errors
			'strict'           => E_STRICT,            // Runtime suggestions (deprecated in PHP 8.4)
			'userDeprecated'   => E_USER_DEPRECATED,   // User-generated deprecation messages
			'userError'        => E_USER_ERROR,        // User-generated error messages
			'userNotice'       => E_USER_NOTICE,       // User-generated notice messages
			'userWarning'      => E_USER_WARNING,      // User-generated warning messages
			'warning'          => E_WARNING,           // Runtime warnings (non-fatal)
		];
	}

	/**
	 * Dynamically creates a custom exception based on the provided fully qualified class name or an exception instance.
	 *
	 * @param string|Throwable|null $type The fully qualified class name of the exception or an existing exception instance.
	 * @param string $message The error message (default: an empty string).
	 * @param int $code The error code (default: 0).
	 * @param mixed|null $previous A previous exception for chaining (default: null).
	 * @return Throwable The dynamically created exception instance.
	 * @throws Exception If the provided type is invalid or not a subclass of Throwable.
	 */
	public function createException(
		string|Throwable|null $type,
		string $message = "",
		int $code = 0,
		mixed $previous = null
	): Throwable {
		return ($this->isObject($type) && $this->isSubclassOf($type, 'Throwable'))
			? $type
			: ($this->isString($type) && $this->classExists($type) && $this->isSubclassOf($type, 'Throwable'))
				? new $type($message, $code, $previous)
				: throw new Exception($message);
	}

	/**
	 * Executes a callback within a try-catch block, optionally wrapping caught exceptions in a custom type or instance.
	 *
	 * @param callable $callback The callback function or closure to execute.
	 * @param string|Throwable|null $exceptionType The fully qualified class name or an instance of the wrapping exception (default: null).
	 * @param callable|null $onError Optional callback executed when an exception is caught (default: null).
	 * @param string|null $context An optional context string for logging purposes (default: "default").
	 * @return mixed The result of the callback execution.
	 * @throws Throwable The original or wrapped exception if the callback fails.
	 */
	public function wrapInTry(
		callable $callback,
		string|Throwable|null $exceptionType = null,
		?callable $onError = null,
		?string $context = null
	): mixed {
		return $this->isCallable($callback)
			? (try {
				return $callback();
			} catch (Throwable $caughtException) {
				$this->logThrowable($caughtException, $context);
				$onError && $onError($caughtException);
				throw $this->createException(
					$exceptionType,
					$caughtException->getMessage(),
					$caughtException->getCode(),
					$caughtException
				);
			})
			: throw $this->createException(null, "Callback must be callable.");
	}

	/**
	 * Logs a throwable instance with additional context and severity level.
	 *
	 * @param mixed $exception The exception instance to log.
	 * @param string $context An optional context for categorizing the log (default: "default").
	 * @param string $levelKey The log level key (e.g., "userError") (default: "userError").
	 * @return bool True if the exception was logged successfully, false otherwise.
	 * @throws Throwable If the provided exception is invalid or logging fails.
	 */
	public function logThrowable(
		mixed $exception,
		string $context = 'default',
		string $levelKey = 'userError'
	): bool {
		return $this->isObject($exception) && $this->isSubclassOf($exception, 'Throwable')
			? $this->logErrorMessage(
				$exception->getMessage(),
				$exception->getFile(),
				$exception->getLine(),
				strtoupper($levelKey),
				$context
			)
			: throw $this->createException(null, "Invalid exception provided for logging.");
	}

	/**
	 * Logs an error message with a specific error level.
	 *
	 * @param string $message The error message to log.
	 * @param string $key The error level key (e.g., 'userNotice').
	 * @param string|null $destination An optional destination for the error log (default: null).
	 * @param string|null $extraHeaders Additional headers for the log (default: null).
	 * @return bool True if the error was successfully logged, false otherwise.
	 * @throws Exception If the message or key is invalid.
	 */
	public function logError(
		string $message,
		string $key = 'userNotice',
		?string $destination = null,
		?string $extraHeaders = null
	): bool {
		return $this->isString($message) && $this->keyExists($this->errorLevels, $key)
			? error_log($message, $this->errorLevels[$key], $destination, $extraHeaders)
			: throw $this->createException(null, "Invalid error message or error level key: $key");
	}

	/**
	 * Logs a formatted error with specific details.
	 *
	 * @param string $key The error level key (e.g., 'userError').
	 * @param string $message The error message.
	 * @param string $file The file where the error occurred.
	 * @param int $line The line number where the error occurred.
	 * @return bool True if the formatted error was successfully logged, false otherwise.
	 * @throws Exception If the key, message, or file are invalid.
	 */
	public function logFormattedError(
		string $key,
		string $message,
		string $file,
		int $line
	): bool {
		return $this->isString($key) && $this->isString($message) && $this->isString($file)
			? $this->logError($this->formatErrorMessage($key, $message, $file, $line), $key)
			: throw $this->createException(null, "Key, message, and file must be strings.");
	}

	/**
	 * Logs a formatted error with specific details.
	 *
	 * @param string $key The error level key (e.g., 'userError').
	 * @param string $message The error message.
	 * @param string $file The file where the error occurred.
	 * @param int $line The line number where the error occurred.
	 * @return bool True if the formatted error was successfully logged, false otherwise.
	 * @throws Exception If the key, message, or file are invalid.
	 */
	public function logFormattedError(
		string $key,
		string $message,
		string $file,
		int $line
	): bool {
		return $this->isString($key) && $this->isString($message) && $this->isString($file)
			? $this->logError($this->formatErrorMessage($key, $message, $file, $line), $key)
			: throw $this->createException(null, "Key, message, and file must be strings.");
	}

	/**
	 * Executes a callback while suppressing PHP errors based on a given error level.
	 *
	 * @param callable $callback The callable to execute.
	 * @param string $levelKey The error level key to suppress (default: 'allErrors').
	 * @return mixed The result of the callback execution.
	 * @throws Exception If the level key or callback is invalid.
	 */
	public function suppressErrors(
		callable $callback,
		string $levelKey = 'allErrors'
	): mixed {
		return $this->keyExists($this->errorLevels, $levelKey) && $this->isCallable($callback)
			? (try {
				error_reporting($this->errorLevels[$levelKey]);
				return $callback();
			} finally {
				error_reporting($this->errorLevels['allErrors']);
			})
			: throw $this->createException(null, "Invalid error level key or callback.");
	}

	/**
	 * Logs a detailed error message with additional context and severity level.
	 *
	 * @param string $message The error message.
	 * @param string $file The file where the error occurred.
	 * @param int $line The line number where the error occurred.
	 * @param string $key The error level key (e.g., 'error').
	 * @param string $context An optional context string (default: 'default').
	 * @return bool True if the error message was successfully logged, false otherwise.
	 * @throws Exception If any parameter is invalid.
	 */
	public function logErrorMessage(
		string $message,
		string $file,
		int $line,
		string $key = 'error',
		string $context = 'default'
	): bool {
		return $this->isString($message) && $this->isString($file) && $this->isString($key) && $this->isString($context)
			? $this->logError($this->formatErrorMessage($key, $message, $file, $line, $context), $key)
			: throw $this->createException(null, "Message, file, key, and context must be strings.");
	}

	/**
	 * Formats an error message with specific details and context.
	 *
	 * @param string $key The error level key.
	 * @param string $message The error message.
	 * @param string $file The file where the error occurred.
	 * @param int $line The line number where the error occurred.
	 * @param string $context An optional context string (default: 'default').
	 * @return string The formatted error message.
	 * @throws Exception If any parameter is invalid.
	 */
	public function formatErrorMessage(
		string $key,
		string $message,
		string $file,
		int $line,
		string $context = 'default'
	): string {
		return $this->isString($key) && $this->isString($message) && $this->isString($file) && $this->isString($context)
			? sprintf("[%s][%s] %s in %s on line %d", strtoupper($context), strtoupper($key), $message, $file, $line)
			: throw $this->createException(null, "Key, message, file, and context must be strings.");
	}

	/**
	 * Restores the default error and exception handlers.
	 *
	 * @return void
	 * @throws Exception If restoring either the error or exception handler fails.
	 */
	public function resetErrorHandlers(): void {
		restore_error_handler() || throw $this->createException(null, "Failed to restore error handler.");
		restore_exception_handler() || throw $this->createException(null, "Failed to restore exception handler.");
	}

	/**
	 * Generates a backtrace of the current call stack.
	 *
	 * @param int $options Backtrace options (default: DEBUG_BACKTRACE_PROVIDE_OBJECT).
	 * @param int $limit The maximum number of stack frames to return (default: 0 for no limit).
	 * @return array An array of backtrace frames.
	 * @throws Exception If the options or limit are invalid.
	 */
	public function backtrace(int $options = DEBUG_BACKTRACE_PROVIDE_OBJECT, int $limit = 0): array {
		return $this->isInt($options) && $this->isInt($limit)
			? debug_backtrace($options, $limit)
			: throw $this->createException(null, "Invalid options or limit for backtrace.");
	}

	/**
	 * Prints a backtrace of the current call stack to standard output.
	 *
	 * @param int $options Backtrace options (default: DEBUG_BACKTRACE_IGNORE_ARGS).
	 * @param int $limit The maximum number of stack frames to print (default: 0 for no limit).
	 * @return void
	 * @throws Exception If the options or limit are invalid.
	 */
	public function printBacktrace(int $options = DEBUG_BACKTRACE_IGNORE_ARGS, int $limit = 0): void {
		$this->isInt($options) && $this->isInt($limit)
			? debug_print_backtrace($options, $limit)
			: throw $this->createException(null, "Invalid options or limit for printBacktrace.");
	}

	/**
	 * Clears the last error recorded by PHP's error handler.
	 *
	 * @return void
	 * @throws Exception If the last error could not be cleared.
	 */
	public function clearError(): void {
		error_clear_last();
		!$this->isNull(error_get_last())
			? throw $this->createException(null, "Failed to clear the last error.")
			: null;
	}

	/**
	 * Retrieves the last error recorded by PHP's error handler.
	 *
	 * @return array|null An associative array of the last error, or null if no error exists.
	 */
	public function lastError(): ?array {
		return $this->isArray($error = error_get_last())
			? $this->changeKeyCase($error, CASE_LOWER)
			: null;
	}

	/**
	 * Sets a custom error handler.
	 *
	 * @param callable $handler A valid callable that will handle PHP errors.
	 * @param string $levelKey The error level key (default: "allErrors").
	 * @return callable The previously defined error handler, or null on error.
	 * @throws Exception If the handler is not callable or the level key is invalid.
	 */
	public function setErrorHandler(callable $handler, string $levelKey = 'allErrors'): callable {
		return $this->isCallable($handler) && $this->keyExists($this->errorLevels, $levelKey)
			? set_error_handler($handler, $this->errorLevels[$levelKey])
			: throw $this->createException(null, "Invalid handler or error level key: $levelKey");
	}

	/**
	 * Sets a custom exception handler.
	 *
	 * @param callable $handler A valid callable that will handle uncaught exceptions.
	 * @return callable|null The previously defined exception handler, or null if no handler was defined.
	 * @throws Exception If the handler is not callable.
	 */
	public function setExceptionHandler(callable $handler): ?callable {
		return $this->isCallable($handler)
			? set_exception_handler($handler)
			: throw $this->createException(null, "Handler must be callable.");
	}

	/**
	 * Triggers a user-level error.
	 *
	 * @param string $message The error message.
	 * @param string $levelKey The error level key (e.g., "userNotice") (default: "userNotice").
	 * @return void
	 * @throws Exception If the message is not a string or the level key is invalid.
	 */
	public function triggerError(string $message, string $levelKey = 'userNotice'): void {
		$this->isString($message) && $this->keyExists($this->errorLevels, $levelKey)
			? trigger_error($message, $this->errorLevels[$levelKey])
			: throw $this->createException(null, "Invalid error message or error level key: $levelKey");
	}

	/**
	 * Triggers a user-defined error with a default level key of "userError".
	 *
	 * @param string $message The error message.
	 * @param string $levelKey The error level key (default: "userError").
	 * @return void
	 * @throws Exception If the message is not a string or the level key is invalid.
	 */
	public function userError(string $message, string $levelKey = 'userError'): void {
		$this->triggerError($message, $levelKey);
	}
}
