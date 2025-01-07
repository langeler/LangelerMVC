<?php

namespace App\Utilities\Traits\Reflection;

use Closure;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionClass;
use ReflectionExtension;
use ReflectionType;

/**
 * Trait ReflectionFunctionTrait
 *
 * Covers ReflectionFunction, ReflectionFunctionAbstract.
 */
trait ReflectionFunctionTrait
{
	// Reflection Function Methods

	/**
	 * Create a dynamically created closure for the function.
	 *
	 * @param ReflectionFunction $reflectionFunction The ReflectionFunction instance.
	 * @return Closure The dynamically created closure.
	 */
	public function getFunctionClosure(ReflectionFunction $reflectionFunction): Closure
	{
		return $reflectionFunction->getClosure();
	}

	/**
	 * Invoke the function.
	 *
	 * @param ReflectionFunction $reflectionFunction The ReflectionFunction instance.
	 * @param mixed ...$args The arguments to pass to the function.
	 * @return mixed The result of the function invocation.
	 */
	public function invokeFunction(ReflectionFunction $reflectionFunction, mixed ...$args): mixed
	{
		return $reflectionFunction->invoke(...$args);
	}

	/**
	 * Invoke the function with arguments as an array.
	 *
	 * @param ReflectionFunction $reflectionFunction The ReflectionFunction instance.
	 * @param array $args The arguments to pass to the function.
	 * @return mixed The result of the function invocation.
	 */
	public function invokeFunctionArgs(ReflectionFunction $reflectionFunction, array $args): mixed
	{
		return $reflectionFunction->invokeArgs($args);
	}

	/**
	 * Check if the function is anonymous.
	 *
	 * @param ReflectionFunction $reflectionFunction The ReflectionFunction instance.
	 * @return bool True if the function is anonymous, false otherwise.
	 */
	public function isFunctionAnonymous(ReflectionFunction $reflectionFunction): bool
	{
		return $reflectionFunction->isAnonymous();
	}

	/**
	 * Check if the function is disabled.
	 *
	 * @param ReflectionFunction $reflectionFunction The ReflectionFunction instance.
	 * @return bool True if the function is disabled, false otherwise.
	 */
	public function isFunctionDisabled(ReflectionFunction $reflectionFunction): bool
	{
		return $reflectionFunction->isDisabled();
	}

	/**
	 * Get the string representation of the ReflectionFunction instance.
	 *
	 * @param ReflectionFunction $reflectionFunction The ReflectionFunction instance.
	 * @return string The string representation of the function.
	 */
	public function functionToString(ReflectionFunction $reflectionFunction): string
	{
		return $reflectionFunction->__toString();
	}

	// Reflection Function Abstract Methods

	/**
	 * Get the attributes of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @param string|null $name Optional name of the attribute.
	 * @param int $flags Optional flags to filter attributes.
	 * @return array An array of attributes.
	 */
	public function getFunctionAttributes(ReflectionFunctionAbstract $reflectionFunction, ?string $name = null, int $flags = 0): array
	{
		return $reflectionFunction->getAttributes($name, $flags);
	}

	/**
	 * Get the class corresponding to static:: inside a closure.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return ReflectionClass|null The corresponding class, or null if not applicable.
	 */
	public function getClosureCalledClass(ReflectionFunctionAbstract $reflectionFunction): ?ReflectionClass
	{
		return $reflectionFunction->getClosureCalledClass();
	}

	/**
	 * Get the scope class of the closure.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return ReflectionClass|null The scope class, or null if not applicable.
	 */
	public function getClosureScopeClass(ReflectionFunctionAbstract $reflectionFunction): ?ReflectionClass
	{
		return $reflectionFunction->getClosureScopeClass();
	}

	/**
	 * Get the `$this` object used in the closure.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return object|null The `$this` object, or null if not applicable.
	 */
	public function getClosureThis(ReflectionFunctionAbstract $reflectionFunction): ?object
	{
		return $reflectionFunction->getClosureThis();
	}

	/**
	 * Get the variables used in the closure.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return array An array of used variables.
	 */
	public function getClosureUsedVariables(ReflectionFunctionAbstract $reflectionFunction): array
	{
		return $reflectionFunction->getClosureUsedVariables();
	}

	/**
	 * Get the documentation comment for the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return string|false The documentation comment, or false if none exists.
	 */
	public function getFunctionDocComment(ReflectionFunctionAbstract $reflectionFunction): string|false
	{
		return $reflectionFunction->getDocComment();
	}

	/**
	 * Get the ending line of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return int|false The ending line number, or false if not applicable.
	 */
	public function getFunctionEndLine(ReflectionFunctionAbstract $reflectionFunction): int|false
	{
		return $reflectionFunction->getEndLine();
	}

	/**
	 * Get the extension defining the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return ReflectionExtension|null The defining extension, or null if not applicable.
	 */
	public function getFunctionExtension(ReflectionFunctionAbstract $reflectionFunction): ?ReflectionExtension
	{
		return $reflectionFunction->getExtension();
	}

	/**
	 * Get the name of the extension defining the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return string|false The extension name, or false if not applicable.
	 */
	public function getFunctionExtensionName(ReflectionFunctionAbstract $reflectionFunction): string|false
	{
		return $reflectionFunction->getExtensionName();
	}

	/**
	 * Get the file name where the function is defined.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return string|false The file name, or false if not applicable.
	 */
	public function getFunctionFileName(ReflectionFunctionAbstract $reflectionFunction): string|false
	{
		return $reflectionFunction->getFileName();
	}

	/**
	 * Get the namespace name of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return string The namespace name.
	 */
	public function getFunctionNamespaceName(ReflectionFunctionAbstract $reflectionFunction): string
	{
		return $reflectionFunction->getNamespaceName();
	}

	/**
	 * Get the number of parameters the function takes.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return int The number of parameters.
	 */
	public function getFunctionNumberOfParameters(ReflectionFunctionAbstract $reflectionFunction): int
	{
		return $reflectionFunction->getNumberOfParameters();
	}

	/**
	 * Get the number of required parameters the function takes.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return int The number of required parameters.
	 */
	public function getFunctionNumberOfRequiredParameters(ReflectionFunctionAbstract $reflectionFunction): int
	{
		return $reflectionFunction->getNumberOfRequiredParameters();
	}

	/**
	 * Get the parameters of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return array An array of ReflectionParameter instances.
	 */
	public function getFunctionParameters(ReflectionFunctionAbstract $reflectionFunction): array
	{
		return $reflectionFunction->getParameters();
	}

	/**
	 * Get the name of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return string The name of the function.
	 */
	public function getFunctionName(ReflectionFunctionAbstract $reflectionFunction): string
	{
		return $reflectionFunction->getName();
	}

	/**
	 * Get the return type of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return ReflectionType|null The return type or null if none exists.
	 */
	public function getFunctionReturnType(ReflectionFunctionAbstract $reflectionFunction): ?ReflectionType
	{
		return $reflectionFunction->getReturnType();
	}

	/**
	 * Get the short name of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return string The short name of the function.
	 */
	public function getFunctionShortName(ReflectionFunctionAbstract $reflectionFunction): string
	{
		return $reflectionFunction->getShortName();
	}

	/**
	 * Get the starting line of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return int|false The starting line number, or false if not applicable.
	 */
	public function getFunctionStartLine(ReflectionFunctionAbstract $reflectionFunction): int|false
	{
		return $reflectionFunction->getStartLine();
	}

	/**
	 * Get the static variables of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return array An array of static variables.
	 */
	public function getFunctionStaticVariables(ReflectionFunctionAbstract $reflectionFunction): array
	{
		return $reflectionFunction->getStaticVariables();
	}

	/**
	 * Get the tentative return type of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return ReflectionType|null The tentative return type, or null if not applicable.
	 */
	public function getFunctionTentativeReturnType(ReflectionFunctionAbstract $reflectionFunction): ?ReflectionType
	{
		return $reflectionFunction->getTentativeReturnType();
	}

	/**
	 * Check if the function has a specified return type.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return bool True if the function has a return type, false otherwise.
	 */
	public function functionHasReturnType(ReflectionFunctionAbstract $reflectionFunction): bool
	{
		return $reflectionFunction->hasReturnType();
	}

	/**
	 * Check if the function has a tentative return type.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return bool True if the function has a tentative return type, false otherwise.
	 */
	public function functionHasTentativeReturnType(ReflectionFunctionAbstract $reflectionFunction): bool
	{
		return $reflectionFunction->hasTentativeReturnType();
	}

	/**
	 * Check if the function is in a namespace.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return bool True if the function is in a namespace, false otherwise.
	 */
	public function isFunctionInNamespace(ReflectionFunctionAbstract $reflectionFunction): bool
	{
		return $reflectionFunction->inNamespace();
	}

	/**
	 * Check if the function is a closure.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return bool True if the function is a closure, false otherwise.
	 */
	public function isFunctionClosure(ReflectionFunctionAbstract $reflectionFunction): bool
	{
		return $reflectionFunction->isClosure();
	}

	/**
	 * Check if the function is deprecated.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return bool True if the function is deprecated, false otherwise.
	 */
	public function isFunctionDeprecated(ReflectionFunctionAbstract $reflectionFunction): bool
	{
		return $reflectionFunction->isDeprecated();
	}

	/**
	 * Check if the function is a generator.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return bool True if the function is a generator, false otherwise.
	 */
	public function isFunctionGenerator(ReflectionFunctionAbstract $reflectionFunction): bool
	{
		return $reflectionFunction->isGenerator();
	}

	/**
	 * Check if the function is internal.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return bool True if the function is internal, false otherwise.
	 */
	public function isFunctionInternal(ReflectionFunctionAbstract $reflectionFunction): bool
	{
		return $reflectionFunction->isInternal();
	}

	/**
	 * Check if the function is static.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return bool True if the function is static, false otherwise.
	 */
	public function isFunctionStatic(ReflectionFunctionAbstract $reflectionFunction): bool
	{
		return $reflectionFunction->isStatic();
	}

	/**
	 * Check if the function is user-defined.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return bool True if the function is user-defined, false otherwise.
	 */
	public function isFunctionUserDefined(ReflectionFunctionAbstract $reflectionFunction): bool
	{
		return $reflectionFunction->isUserDefined();
	}

	/**
	 * Check if the function is variadic.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return bool True if the function is variadic, false otherwise.
	 */
	public function isFunctionVariadic(ReflectionFunctionAbstract $reflectionFunction): bool
	{
		return $reflectionFunction->isVariadic();
	}

	/**
	 * Check if the function returns a reference.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return bool True if the function returns a reference, false otherwise.
	 */
	public function functionReturnsReference(ReflectionFunctionAbstract $reflectionFunction): bool
	{
		return $reflectionFunction->returnsReference();
	}

	/**
	 * Get the string representation of the ReflectionFunctionAbstract instance.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunction The ReflectionFunctionAbstract instance.
	 * @return string The string representation of the function.
	 */
	public function functionToString(ReflectionFunctionAbstract $reflectionFunction): string
	{
		return $reflectionFunction->__toString();
	}
}
