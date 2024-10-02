<?php

namespace App\Utilities\Managers;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use ReflectionFunction;
use ReflectionParameter;
use ReflectionException;
use ReflectionObject;
use ReflectionFunctionAbstract;
use ReflectionExtension;
use ReflectionNamedType;
use ReflectionUnionType;

/**
 * Class ReflectionManager
 *
 * Provides utility methods for working with PHP Reflection classes.
 * This class covers functionalities related to ReflectionClass, ReflectionMethod, ReflectionProperty, etc.
 */
class ReflectionManager
{
	// ReflectionClass Methods

	/**
	 * Get ReflectionClass instance for a given class.
	 *
	 * @param string|object $class The class name or object.
	 * @return ReflectionClass The ReflectionClass instance.
	 */
	public function getClassInfo($class): ReflectionClass
	{
		return new ReflectionClass($class);
	}

	/**
	 * Create an instance of a class without invoking the constructor.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return object The class instance.
	 */
	public function createInstanceWithoutConstructor(ReflectionClass $reflectionClass)
	{
		return $reflectionClass->newInstanceWithoutConstructor();
	}

	/**
	 * Create an instance of a class with arguments passed to the constructor.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param array $args The arguments to pass to the constructor.
	 * @return object The class instance.
	 */
	public function createInstanceWithArgs(ReflectionClass $reflectionClass, array $args = [])
	{
		return $reflectionClass->newInstanceArgs($args);
	}

	/**
	 * Get properties of a class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return array The array of ReflectionProperty instances.
	 */
	public function getClassProperties(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getProperties();
	}

	/**
	 * Get methods of a class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return array The array of ReflectionMethod instances.
	 */
	public function getClassMethods(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getMethods();
	}

	/**
	 * Get constants of a class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return array The array of constants.
	 */
	public function getClassConstants(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getConstants();
	}

	/**
	 * Get interfaces implemented by a class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return array The array of ReflectionClass instances representing the interfaces.
	 */
	public function getClassInterfaces(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getInterfaces();
	}

	// ReflectionMethod Methods

	/**
	 * Get ReflectionMethod instance for a given class method.
	 *
	 * @param string|object $class The class name or object.
	 * @param string $methodName The name of the method.
	 * @return ReflectionMethod The ReflectionMethod instance.
	 */
	public function getMethodInfo($class, string $methodName): ReflectionMethod
	{
		return new ReflectionMethod($class, $methodName);
	}

	/**
	 * Invoke a method on an object with optional arguments.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @param object $object The object to invoke the method on.
	 * @param mixed ...$args The arguments to pass to the method.
	 * @return mixed The result of the method invocation.
	 */
	public function invokeMethod(ReflectionMethod $reflectionMethod, $object, ...$args)
	{
		return $reflectionMethod->invoke($object, ...$args);
	}

	/**
	 * Invoke a method on an object with an array of arguments.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @param object $object The object to invoke the method on.
	 * @param array $args The arguments to pass to the method.
	 * @return mixed The result of the method invocation.
	 */
	public function invokeMethodWithArgs(ReflectionMethod $reflectionMethod, $object, array $args = [])
	{
		return $reflectionMethod->invokeArgs($object, $args);
	}

	/**
	 * Get the modifiers of a method.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return int The modifiers of the method.
	 */
	public function getMethodModifiers(ReflectionMethod $reflectionMethod): int
	{
		return $reflectionMethod->getModifiers();
	}

	// ReflectionProperty Methods

	/**
	 * Get ReflectionProperty instance for a given class property.
	 *
	 * @param string|object $class The class name or object.
	 * @param string $propertyName The name of the property.
	 * @return ReflectionProperty The ReflectionProperty instance.
	 */
	public function getPropertyInfo($class, string $propertyName): ReflectionProperty
	{
		return new ReflectionProperty($class, $propertyName);
	}

	/**
	 * Get the value of a class property.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @param object|null $object The object to get the property value from, or null for static properties.
	 * @return mixed The value of the property.
	 */
	public function getPropertyValue(ReflectionProperty $reflectionProperty, $object = null)
	{
		return $reflectionProperty->getValue($object);
	}

	/**
	 * Set the value of a class property.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @param object $object The object to set the property value on.
	 * @param mixed $value The value to set.
	 * @return void
	 */
	public function setPropertyValue(ReflectionProperty $reflectionProperty, $object, $value): void
	{
		$reflectionProperty->setValue($object, $value);
	}

	/**
	 * Get the modifiers of a property.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return int The modifiers of the property.
	 */
	public function getPropertyModifiers(ReflectionProperty $reflectionProperty): int
	{
		return $reflectionProperty->getModifiers();
	}

	// ReflectionFunction Methods

	/**
	 * Get ReflectionFunction instance for a given function.
	 *
	 * @param string $functionName The name of the function.
	 * @return ReflectionFunction The ReflectionFunction instance.
	 */
	public function getFunctionInfo(string $functionName): ReflectionFunction
	{
		return new ReflectionFunction($functionName);
	}

	/**
	 * Invoke a function with optional arguments.
	 *
	 * @param ReflectionFunction $reflectionFunction The ReflectionFunction instance.
	 * @param mixed ...$args The arguments to pass to the function.
	 * @return mixed The result of the function invocation.
	 */
	public function invokeFunction(ReflectionFunction $reflectionFunction, ...$args)
	{
		return $reflectionFunction->invoke(...$args);
	}

	/**
	 * Invoke a function with an array of arguments.
	 *
	 * @param ReflectionFunction $reflectionFunction The ReflectionFunction instance.
	 * @param array $args The arguments to pass to the function.
	 * @return mixed The result of the function invocation.
	 */
	public function invokeFunctionWithArgs(ReflectionFunction $reflectionFunction, array $args = [])
	{
		return $reflectionFunction->invokeArgs($args);
	}

	/**
	 * Get the closure of a function.
	 *
	 * @param ReflectionFunction $reflectionFunction The ReflectionFunction instance.
	 * @return \Closure The closure of the function.
	 */
	public function getFunctionClosure(ReflectionFunction $reflectionFunction): \Closure
	{
		return $reflectionFunction->getClosure();
	}

	// ReflectionParameter Methods

	/**
	 * Get ReflectionParameter instance for a given function or method parameter.
	 *
	 * @param ReflectionFunctionAbstract $function The function or method.
	 * @param string|int $paramName The parameter name or position.
	 * @return ReflectionParameter The ReflectionParameter instance.
	 */
	public function getParameterInfo(ReflectionFunctionAbstract $function, $paramName): ReflectionParameter
	{
		return new ReflectionParameter([$function, $paramName]);
	}

	/**
	 * Get the name of a parameter.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return string The name of the parameter.
	 */
	public function getParameterName(ReflectionParameter $reflectionParameter): string
	{
		return $reflectionParameter->getName();
	}

	/**
	 * Get the type of a parameter.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return ReflectionNamedType|null The type of the parameter, or null if not available.
	 */
	public function getParameterType(ReflectionParameter $reflectionParameter): ?ReflectionNamedType
	{
		$type = $reflectionParameter->getType();

		// Handle union types
		if ($type instanceof ReflectionUnionType) {
			// Use the first non-nullable type if available
			foreach ($type->getTypes() as $subType) {
				if (!$subType->allowsNull()) {
					return $subType;
				}
			}
		} elseif ($type instanceof ReflectionNamedType) {
			return $type;
		}

		return null;
	}

	/**
	 * Check if a parameter is optional.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return bool True if the parameter is optional, false otherwise.
	 */
	public function isParameterOptional(ReflectionParameter $reflectionParameter): bool
	{
		return $reflectionParameter->isOptional();
	}

	// ReflectionException Methods

	/**
	 * Handle a ReflectionException.
	 *
	 * @param ReflectionException $exception The ReflectionException instance.
	 * @return string The exception message.
	 */
	public function handleReflectionException(ReflectionException $exception): string
	{
		return $exception->getMessage();
	}

	// ReflectionObject Methods

	/**
	 * Create a reflection object from an instance.
	 *
	 * @param object $instance The instance of the class.
	 * @return ReflectionObject The ReflectionObject instance.
	 */
	public function reflectInstance($instance): ReflectionObject
	{
		return new ReflectionObject($instance);
	}

	// ReflectionFunctionAbstract Methods

	/**
	 * Get the number of parameters a function or method accepts.
	 *
	 * @param ReflectionFunctionAbstract $function The ReflectionFunctionAbstract instance.
	 * @return int The number of parameters.
	 */
	public function getFunctionParameterCount(ReflectionFunctionAbstract $function): int
	{
		return $function->getNumberOfParameters();
	}

	// ReflectionExtension Methods

	/**
	 * Get the version of an extension.
	 *
	 * @param string $extensionName The name of the extension.
	 * @return string|null The version of the extension.
	 * @throws ReflectionException if the extension does not exist.
	 */
	public function getExtensionVersion(string $extensionName): ?string
	{
		return (new ReflectionExtension($extensionName))->getVersion();
	}
}
