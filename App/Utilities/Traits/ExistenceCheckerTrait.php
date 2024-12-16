<?php

namespace App\Utilities\Traits;

/**
 * Trait ExistenceCheckerTrait
 *
 * Provides utility methods to check the existence of various PHP entities such as classes, interfaces,
 * traits, methods, properties, functions, files, and constants. This trait helps ensure efficient and
 * reusable code by centralizing existence-checking operations.
 */
trait ExistenceCheckerTrait
{
	/**
	 * Checks if a class exists.
	 *
	 * @param string $className The fully qualified class name.
	 * @return bool True if the class exists, false otherwise.
	 */
	public function classExists(string $className): bool
	{
		return class_exists($className);
	}

	/**
	 * Checks if an interface exists.
	 *
	 * @param string $interfaceName The fully qualified interface name.
	 * @return bool True if the interface exists, false otherwise.
	 */
	public function interfaceExists(string $interfaceName): bool
	{
		return interface_exists($interfaceName);
	}

	/**
	 * Checks if a trait exists.
	 *
	 * @param string $traitName The fully qualified trait name.
	 * @return bool True if the trait exists, false otherwise.
	 */
	public function traitExists(string $traitName): bool
	{
		return trait_exists($traitName);
	}

	/**
	 * Checks if a method exists in a class or object.
	 *
	 * @param object|string $objectOrClass The class name or object instance.
	 * @param string $methodName           The name of the method.
	 * @return bool True if the method exists, false otherwise.
	 */
	public function methodExists(object|string $objectOrClass, string $methodName): bool
	{
		return method_exists($objectOrClass, $methodName);
	}

	/**
	 * Checks if a property exists in a class or object.
	 *
	 * @param object|string $objectOrClass The class name or object instance.
	 * @param string $propertyName         The name of the property.
	 * @return bool True if the property exists, false otherwise.
	 */
	public function propertyExists(object|string $objectOrClass, string $propertyName): bool
	{
		return property_exists($objectOrClass, $propertyName);
	}

	/**
	 * Checks if a constant exists in a class.
	 *
	 * @param string $className    The fully qualified class name.
	 * @param string $constantName The name of the constant.
	 * @return bool True if the constant exists, false otherwise.
	 */
	public function constantExists(string $className, string $constantName): bool
	{
		return defined("$className::$constantName");
	}

	/**
	 * Checks if a function exists.
	 *
	 * @param string $functionName The name of the function.
	 * @return bool True if the function exists, false otherwise.
	 */
	public function functionExists(string $functionName): bool
	{
		return function_exists($functionName);
	}
}
