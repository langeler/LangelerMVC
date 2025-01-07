<?php

namespace App\Utilities\Traits\Reflection;

use ReflectionParameter;
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionType;

/**
 * Trait ReflectionParameterTrait
 *
 * Covers  ReflectionParameter.
 */
trait ReflectionParameterTrait
{
	// Reflection Parameter Methods

	/**
	 * Check if the parameter allows null.
	 *
	 * @param ReflectionParameter $parameter The ReflectionParameter instance.
	 * @return bool True if null is allowed, false otherwise.
	 */
	public function canParameterBeNull(ReflectionParameter $parameter): bool
	{
		return $parameter->allowsNull();
	}

	/**
	 * Check if the parameter can be passed by value.
	 *
	 * @param ReflectionParameter $parameter The ReflectionParameter instance.
	 * @return bool True if the parameter can be passed by value, false otherwise.
	 */
	public function canParameterBePassedByValue(ReflectionParameter $parameter): bool
	{
		return $parameter->canBePassedByValue();
	}

	/**
	 * Get the attributes of the parameter.
	 *
	 * @param ReflectionParameter $parameter The ReflectionParameter instance.
	 * @param string|null $name Optional attribute name to filter.
	 * @param int $flags Optional flags for retrieving attributes.
	 * @return array An array of attributes.
	 */
	public function getParameterAttributes(ReflectionParameter $parameter, ?string $name = null, int $flags = 0): array
	{
		return $parameter->getAttributes($name, $flags);
	}

	/**
	 * Get the class declaring the parameter.
	 *
	 * @param ReflectionParameter $parameter The ReflectionParameter instance.
	 * @return ReflectionClass|null The declaring class or null if none exists.
	 */
	public function getParameterDeclaringClass(ReflectionParameter $parameter): ?ReflectionClass
	{
		return $parameter->getDeclaringClass();
	}

	/**
	 * Get the function declaring the parameter.
	 *
	 * @param ReflectionParameter $parameter The ReflectionParameter instance.
	 * @return ReflectionFunctionAbstract The declaring function.
	 */
	public function getParameterDeclaringFunction(ReflectionParameter $parameter): ReflectionFunctionAbstract
	{
		return $parameter->getDeclaringFunction();
	}

	/**
	 * Get the default value of the parameter.
	 *
	 * @param ReflectionParameter $parameter The ReflectionParameter instance.
	 * @return mixed The default value.
	 */
	public function getParameterDefaultValue(ReflectionParameter $parameter): mixed
	{
		return $parameter->getDefaultValue();
	}

	/**
	 * Get the constant name of the default value if defined.
	 *
	 * @param ReflectionParameter $parameter The ReflectionParameter instance.
	 * @return string|null The constant name or null if not defined.
	 */
	public function getParameterDefaultValueConstantName(ReflectionParameter $parameter): ?string
	{
		return $parameter->getDefaultValueConstantName();
	}

	/**
	 * Get the name of the parameter.
	 *
	 * @param ReflectionParameter $parameter The ReflectionParameter instance.
	 * @return string The name of the parameter.
	 */
	public function getParameterName(ReflectionParameter $parameter): string
	{
		return $parameter->getName();
	}

	/**
	 * Get the position of the parameter in the function/method signature.
	 *
	 * @param ReflectionParameter $parameter The ReflectionParameter instance.
	 * @return int The position of the parameter.
	 */
	public function getParameterPosition(ReflectionParameter $parameter): int
	{
		return $parameter->getPosition();
	}

	/**
	 * Get the type of the parameter.
	 *
	 * @param ReflectionParameter $parameter The ReflectionParameter instance.
	 * @return ReflectionType|null The type of the parameter or null if none exists.
	 */
	public function getParameterType(ReflectionParameter $parameter): ?ReflectionType
	{
		return $parameter->getType();
	}

	/**
	 * Check if the parameter has a type.
	 *
	 * @param ReflectionParameter $parameter The ReflectionParameter instance.
	 * @return bool True if the parameter has a type, false otherwise.
	 */
	public function hasParameterType(ReflectionParameter $parameter): bool
	{
		return $parameter->hasType();
	}

	/**
	 * Check if a default value is available for the parameter.
	 *
	 * @param ReflectionParameter $parameter The ReflectionParameter instance.
	 * @return bool True if a default value is available, false otherwise.
	 */
	public function isParameterDefaultValueAvailable(ReflectionParameter $parameter): bool
	{
		return $parameter->isDefaultValueAvailable();
	}

	/**
	 * Check if the default value of the parameter is a constant.
	 *
	 * @param ReflectionParameter $parameter The ReflectionParameter instance.
	 * @return bool True if the default value is a constant, false otherwise.
	 */
	public function isParameterDefaultValueConstant(ReflectionParameter $parameter): bool
	{
		return $parameter->isDefaultValueConstant();
	}

	/**
	 * Check if the parameter is optional.
	 *
	 * @param ReflectionParameter $parameter The ReflectionParameter instance.
	 * @return bool True if the parameter is optional, false otherwise.
	 */
	public function isParameterOptional(ReflectionParameter $parameter): bool
	{
		return $parameter->isOptional();
	}

	/**
	 * Check if the parameter is passed by reference.
	 *
	 * @param ReflectionParameter $parameter The ReflectionParameter instance.
	 * @return bool True if the parameter is passed by reference, false otherwise.
	 */
	public function isParameterPassedByReference(ReflectionParameter $parameter): bool
	{
		return $parameter->isPassedByReference();
	}

	/**
	 * Check if the parameter is promoted.
	 *
	 * @param ReflectionParameter $parameter The ReflectionParameter instance.
	 * @return bool True if the parameter is promoted, false otherwise.
	 */
	public function isParameterPromoted(ReflectionParameter $parameter): bool
	{
		return $parameter->isPromoted();
	}

	/**
	 * Check if the parameter is variadic.
	 *
	 * @param ReflectionParameter $parameter The ReflectionParameter instance.
	 * @return bool True if the parameter is variadic, false otherwise.
	 */
	public function isParameterVariadic(ReflectionParameter $parameter): bool
	{
		return $parameter->isVariadic();
	}

	/**
	 * Get the string representation of the parameter.
	 *
	 * @param ReflectionParameter $parameter The ReflectionParameter instance.
	 * @return string The string representation of the parameter.
	 */
	public function parameterToString(ReflectionParameter $parameter): string
	{
		return $parameter->__toString();
	}
}
