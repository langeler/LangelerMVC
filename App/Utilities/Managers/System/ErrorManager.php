<?php

namespace App\Utilities\Managers\System;

use Throwable; // Base interface for all errors and exceptions in PHP.

use App\Providers\ExceptionProvider;             // Manages and resolves exception aliases dynamically.

use App\Utilities\Traits\{
    ArrayTrait,             // Provides utility methods for array operations and validations.
    ExistenceCheckerTrait,  // Adds methods for verifying the existence of classes, methods, properties, etc.
    TypeCheckerTrait        // Offers utilities for validating and checking data types.
};

/**
 * Class ErrorManager
 *
 * Provides comprehensive error and exception management for the system,
 * including:
 *  - Dynamic resolution of exception aliases via ExceptionProvider
 *  - Logging and suppressing PHP errors
 *  - Inlined checks (no explicit if-blocks) for parameter validation
 *  - Minimal duplication, leveraging resolveException(...) for fallback
 *
 * This class replaces the old createException(...) method with resolveException(...),
 * choosing suitable exception aliases from ExceptionProvider if validation fails.
 */
class ErrorManager
{
    use ArrayTrait, ExistenceCheckerTrait, TypeCheckerTrait;

    /**
     * Read-only array mapping error level keys to PHP error constants.
     */
    public readonly array $errorLevels;

    /**
     * Constructor injecting the ExceptionProvider for dynamic exception alias resolution,
     * and defining errorLevels inline without if-blocks.
     *
     * @param ExceptionProvider $exceptionProvider Service to resolve exceptions from aliases.
     */
    public function __construct(protected ExceptionProvider $exceptionProvider)
    {
        $this->errorLevels = [
            'allErrors'        => E_ALL,
            'compileError'     => E_COMPILE_ERROR,
            'compileWarning'   => E_COMPILE_WARNING,
            'coreError'        => E_CORE_ERROR,
            'coreWarning'      => E_CORE_WARNING,
            'deprecated'       => E_DEPRECATED,
            'fatalError'       => E_ERROR,
            'notice'           => E_NOTICE,
            'parseError'       => E_PARSE,
            'recoverableError' => E_RECOVERABLE_ERROR,
            'strict'           => E_STRICT,  // Deprecated in newer PHP
            'userDeprecated'   => E_USER_DEPRECATED,
            'userError'        => E_USER_ERROR,
            'userNotice'       => E_USER_NOTICE,
            'userWarning'      => E_USER_WARNING,
            'warning'          => E_WARNING,
        ];
    }

    /**
     * Resolves an exception from an alias or returns a given Throwable as is.
     * If the alias is invalid, picks a best-fit alias from the ExceptionProvider's map.
     *
     * @param string|Throwable|null $type     Alias or direct Throwable. If invalid, uses "invalidArgument".
     * @param string                $message  Exception message (default: empty).
     * @param int                   $code     Exception code (default: 0).
     * @param mixed|null            $previous Chained previous exception (default: null).
     * @return Throwable Resolved or newly created exception instance.
     */
    public function resolveException(
        string|Throwable|null $type,
        string $message = "",
        int $code = 0,
        mixed $previous = null
    ): Throwable {
        return $this->isObject($type) && $this->isSubclassOf($type, Throwable::class)
            ? $type
            : ($this->isString($type)
                // Attempt to getException($type) from the ExceptionProvider
                ? $this->exceptionProvider->getException($type)
                // If not a valid string or object, fallback to "invalidArgument" alias
                : $this->exceptionProvider->getException('invalidArgument')
            );
    }

    /**
     * Logs a Throwable with a context and severity level,
     * or resolves a suitable exception if it's invalid.
     */
    public function logThrowable(
        mixed $exception,
        string $context = 'default',
        string $levelKey = 'userError'
    ): bool {
        return $this->isObject($exception) && $this->isSubclassOf($exception, Throwable::class)
            ? $this->logErrorMessage(
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                strtoupper($levelKey),
                $context
            )
            : throw $this->resolveException('invalidArgument', "Invalid exception provided for logging.");
    }

    /**
     * Logs a simple error message with a specific error level key.
     */
    public function logError(
        string $message,
        string $key = 'userNotice',
        ?string $destination = null,
        ?string $extraHeaders = null
    ): bool {
        return $this->isString($message) && $this->keyExists($this->errorLevels, $key)
            ? error_log($message, $this->errorLevels[$key], $destination, $extraHeaders)
            : throw $this->resolveException('invalidArgument', "Invalid error message or error level key: $key");
    }

    /**
     * Logs a formatted error with details (key, message, file, line).
     */
    public function logFormattedError(
        string $key,
        string $message,
        string $file,
        int $line
    ): bool {
        return $this->isString($key) && $this->isString($message) && $this->isString($file)
            ? $this->logError($this->formatErrorMessage($key, $message, $file, $line), $key)
            : throw $this->resolveException('invalidArgument', "Key, message, and file must be strings.");
    }

    /**
     * Temporarily suppresses errors at a given level for the execution of $callback.
     */
    public function suppressErrors(callable $callback, string $levelKey = 'allErrors'): mixed
    {
        return $this->keyExists($this->errorLevels, $levelKey) && $this->isCallable($callback)
            ? (try {
                error_reporting($this->errorLevels[$levelKey]);
                return $callback();
            } finally {
                error_reporting($this->errorLevels['allErrors']);
            })
            : throw $this->resolveException('invalidArgument', "Invalid error level key or callback.");
    }

    /**
     * Logs a detailed error message with a context and severity level.
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
            : throw $this->resolveException('invalidArgument', "Message, file, key, and context must be strings.");
    }

    /**
     * Builds a formatted error message with file/line and context details.
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
            : throw $this->resolveException('invalidArgument', "Key, message, file, and context must be strings.");
    }

    /**
     * Restores the default PHP error and exception handlers.
     */
    public function resetErrorHandlers(): void
    {
        restore_error_handler()
            || throw $this->resolveException('runtime', "Failed to restore error handler.");
        restore_exception_handler()
            || throw $this->resolveException('runtime', "Failed to restore exception handler.");
    }

    /**
     * Generates a backtrace for the current call stack.
     */
    public function backtrace(int $options = DEBUG_BACKTRACE_PROVIDE_OBJECT, int $limit = 0): array
    {
        return $this->isInt($options) && $this->isInt($limit)
            ? debug_backtrace($options, $limit)
            : throw $this->resolveException('invalidArgument', "Invalid options or limit for backtrace.");
    }

    /**
     * Prints a backtrace of the current call stack to standard output.
     */
    public function printBacktrace(int $options = DEBUG_BACKTRACE_IGNORE_ARGS, int $limit = 0): void
    {
        $this->isInt($options) && $this->isInt($limit)
            ? debug_print_backtrace($options, $limit)
            : throw $this->resolveException('invalidArgument', "Invalid options or limit for printBacktrace.");
    }

    /**
     * Clears the last error recorded by PHP's error handler.
     */
    public function clearError(): void
    {
        error_clear_last();
        !$this->isNull(error_get_last())
            ? throw $this->resolveException('runtime', "Failed to clear the last error.")
            : null;
    }

    /**
     * Retrieves the last error recorded by PHP's error handler, if any.
     */
    public function lastError(): ?array
    {
        return $this->isArray($error = error_get_last())
            ? $this->changeKeyCase($error, CASE_LOWER)
            : null;
    }

    /**
     * Sets a custom error handler at the specified level key.
     */
    public function setErrorHandler(callable $handler, string $levelKey = 'allErrors'): callable
    {
        return $this->isCallable($handler) && $this->keyExists($this->errorLevels, $levelKey)
            ? set_error_handler($handler, $this->errorLevels[$levelKey])
            : throw $this->resolveException('invalidArgument', "Invalid handler or error level key: $levelKey");
    }

    /**
     * Sets a custom exception handler callback for uncaught exceptions.
     */
    public function setExceptionHandler(callable $handler): ?callable
    {
        return $this->isCallable($handler)
            ? set_exception_handler($handler)
            : throw $this->resolveException('invalidArgument', "Handler must be callable.");
    }

    /**
     * Triggers a user-level error.
     */
    public function triggerError(string $message, string $levelKey = 'userNotice'): void
    {
        $this->isString($message) && $this->keyExists($this->errorLevels, $levelKey)
            ? trigger_error($message, $this->errorLevels[$levelKey])
            : throw $this->resolveException('invalidArgument', "Invalid error message or error level key: $levelKey");
    }

    /**
     * Triggers a user-defined error with default 'userError' level.
     */
    public function userError(string $message, string $levelKey = 'userError'): void
    {
        $this->triggerError($message, $levelKey);
    }
}
