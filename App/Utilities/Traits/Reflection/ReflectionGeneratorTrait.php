<?php

namespace App\Utilities\Traits\Reflection;

use ReflectionGenerator;
use Generator;
use ReflectionFunctionAbstract;

/**
 * Trait ReflectionGeneratorTrait
 *
 * Covers ReflectionGenerator methods.
 */
trait ReflectionGeneratorTrait
{
	// Reflection Generator Methods

	/**
	 * Get the file name of the currently executing generator.
	 *
	 * @param ReflectionGenerator $reflectionGenerator The ReflectionGenerator instance.
	 * @return string The file name where the generator is executing.
	 */
	public function getGeneratorExecutingFile(ReflectionGenerator $reflectionGenerator): string
	{
		return $reflectionGenerator->getExecutingFile();
	}

	/**
	 * Get the executing Generator object.
	 *
	 * @param ReflectionGenerator $reflectionGenerator The ReflectionGenerator instance.
	 * @return Generator The currently executing Generator object.
	 */
	public function getExecutingGenerator(ReflectionGenerator $reflectionGenerator): Generator
	{
		return $reflectionGenerator->getExecutingGenerator();
	}

	/**
	 * Get the currently executing line of the generator.
	 *
	 * @param ReflectionGenerator $reflectionGenerator The ReflectionGenerator instance.
	 * @return int The line number where the generator is currently executing.
	 */
	public function getGeneratorExecutingLine(ReflectionGenerator $reflectionGenerator): int
	{
		return $reflectionGenerator->getExecutingLine();
	}

	/**
	 * Get the function name of the generator.
	 *
	 * @param ReflectionGenerator $reflectionGenerator The ReflectionGenerator instance.
	 * @return ReflectionFunctionAbstract The function associated with the generator.
	 */
	public function getGeneratorFunction(ReflectionGenerator $reflectionGenerator): ReflectionFunctionAbstract
	{
		return $reflectionGenerator->getFunction();
	}

	/**
	 * Get the `$this` value of the generator.
	 *
	 * @param ReflectionGenerator $reflectionGenerator The ReflectionGenerator instance.
	 * @return object|null The `$this` value or null if not applicable.
	 */
	public function getGeneratorThis(ReflectionGenerator $reflectionGenerator): ?object
	{
		return $reflectionGenerator->getThis();
	}

	/**
	 * Get the trace of the executing generator.
	 *
	 * @param ReflectionGenerator $reflectionGenerator The ReflectionGenerator instance.
	 * @return array The trace information of the executing generator.
	 */
	public function getGeneratorTrace(ReflectionGenerator $reflectionGenerator): array
	{
		return $reflectionGenerator->getTrace();
	}

	/**
	 * Check if the generator execution has finished.
	 *
	 * @param ReflectionGenerator $reflectionGenerator The ReflectionGenerator instance.
	 * @return bool True if the generator has finished execution, false otherwise.
	 */
	public function isGeneratorClosed(ReflectionGenerator $reflectionGenerator): bool
	{
		return $reflectionGenerator->isClosed();
	}
}
