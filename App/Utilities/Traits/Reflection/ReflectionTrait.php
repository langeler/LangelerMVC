<?php

namespace App\Utilities\Traits\Reflection;

use Reflector;
use ReflectionException;
use ReflectionFiber;
use ReflectionReference;
use Throwable;
use Fiber;

/**
 * Trait ReflectionTrait
 *
 * Covers ReflectionReference, ReflectionFiber, ReflectionException,
 * Reflector, and Throwable (leftovers).
 */
trait ReflectionTrait
{
	// ReflectionException Methods

	/**
	 * Create a new ReflectionException instance.
	 *
	 * @param string $message The exception message.
	 * @param int $code The exception code.
	 * @param Throwable|null $previous The previous throwable for exception chaining.
	 * @return ReflectionException The created ReflectionException instance.
	 */
	public function createException(
		string $message = "",
		int $code = 0,
		?Throwable $previous = null
	): ReflectionException {
		return new ReflectionException($message, $code, $previous);
	}

	/**
	 * Get the message of a ReflectionException.
	 *
	 * @param ReflectionException $exception The ReflectionException instance.
	 * @return string The exception message.
	 */
	public function getExceptionMessage(ReflectionException $exception): string
	{
		return $exception->getMessage();
	}

	/**
	 * Get the previous throwable from a ReflectionException.
	 *
	 * @param ReflectionException $exception The ReflectionException instance.
	 * @return Throwable|null The previous throwable, or null if none exists.
	 */
	public function getExceptionPrevious(ReflectionException $exception): ?Throwable
	{
		return $exception->getPrevious();
	}

	/**
	 * Get the exception code of a ReflectionException.
	 *
	 * @param ReflectionException $exception The ReflectionException instance.
	 * @return int The exception code.
	 */
	public function getExceptionCode(ReflectionException $exception): int
	{
		return $exception->getCode();
	}

	/**
	 * Get the file where a ReflectionException was thrown.
	 *
	 * @param ReflectionException $exception The ReflectionException instance.
	 * @return string The file name.
	 */
	public function getExceptionFile(ReflectionException $exception): string
	{
		return $exception->getFile();
	}

	/**
	 * Get the line where a ReflectionException was thrown.
	 *
	 * @param ReflectionException $exception The ReflectionException instance.
	 * @return int The line number.
	 */
	public function getExceptionLine(ReflectionException $exception): int
	{
		return $exception->getLine();
	}

	/**
	 * Get the stack trace of a ReflectionException as an array.
	 *
	 * @param ReflectionException $exception The ReflectionException instance.
	 * @return array The stack trace as an array.
	 */
	public function getExceptionTrace(ReflectionException $exception): array
	{
		return $exception->getTrace();
	}

	/**
	 * Get the stack trace of a ReflectionException as a string.
	 *
	 * @param ReflectionException $exception The ReflectionException instance.
	 * @return string The stack trace as a string.
	 */
	public function getExceptionTraceAsString(ReflectionException $exception): string
	{
		return $exception->getTraceAsString();
	}

	/**
	 * Get the string representation of a ReflectionException.
	 *
	 * @param ReflectionException $exception The ReflectionException instance.
	 * @return string The string representation of the exception.
	 */
	public function exceptionToString(ReflectionException $exception): string
	{
		return $exception->__toString();
	}

	// ReflectionFiber Methods

	/**
	 * Create a new ReflectionFiber instance.
	 *
	 * @param Fiber $fiber The Fiber instance to reflect.
	 * @return ReflectionFiber The created ReflectionFiber instance.
	 */
	public function createFiber(Fiber $fiber): ReflectionFiber
	{
		return new ReflectionFiber($fiber);
	}

	/**
	 * Get the callable used to create a Fiber.
	 *
	 * @param ReflectionFiber $reflectionFiber The ReflectionFiber instance.
	 * @return callable The callable used to create the Fiber.
	 */
	public function getFiberCallable(ReflectionFiber $reflectionFiber): callable
	{
		return $reflectionFiber->getCallable();
	}

	/**
	 * Get the file name of the current execution point in a Fiber.
	 *
	 * @param ReflectionFiber $reflectionFiber The ReflectionFiber instance.
	 * @return string|null The file name, or null if not available.
	 */
	public function getFiberExecutingFile(ReflectionFiber $reflectionFiber): ?string
	{
		return $reflectionFiber->getExecutingFile();
	}

	/**
	 * Get the line number of the current execution point in a Fiber.
	 *
	 * @param ReflectionFiber $reflectionFiber The ReflectionFiber instance.
	 * @return int|null The line number, or null if not available.
	 */
	public function getFiberExecutingLine(ReflectionFiber $reflectionFiber): ?int
	{
		return $reflectionFiber->getExecutingLine();
	}

	/**
	 * Get the reflected Fiber instance.
	 *
	 * @param ReflectionFiber $reflectionFiber The ReflectionFiber instance.
	 * @return Fiber The reflected Fiber instance.
	 */
	public function getFiberInstance(ReflectionFiber $reflectionFiber): Fiber
	{
		return $reflectionFiber->getFiber();
	}

	/**
	 * Get the backtrace of the current execution point in a Fiber.
	 *
	 * @param ReflectionFiber $reflectionFiber The ReflectionFiber instance.
	 * @param int $options Optional backtrace options. Default is DEBUG_BACKTRACE_PROVIDE_OBJECT.
	 * @return array An array representing the Fiber's backtrace.
	 */
	public function getFiberTrace(ReflectionFiber $reflectionFiber, int $options = DEBUG_BACKTRACE_PROVIDE_OBJECT): array
	{
		return $reflectionFiber->getTrace($options);
	}

	// ReflectionReference Methods

	/**
	 * Create a new ReflectionReference instance from an array element.
	 *
	 * @param array $array The array containing the reference.
	 * @param int|string $key The key of the array element.
	 * @return ReflectionReference|null The created ReflectionReference instance, or null if not applicable.
	 */
	public function createReference(array $array, int|string $key): ?ReflectionReference
	{
		return ReflectionReference::fromArrayElement($array, $key);
	}

	/**
	 * Get the unique ID of a ReflectionReference.
	 *
	 * @param ReflectionReference $reflectionReference The ReflectionReference instance.
	 * @return string The unique ID of the reference.
	 */
	public function getReferenceId(ReflectionReference $reflectionReference): string
	{
		return $reflectionReference->getId();
	}
}
