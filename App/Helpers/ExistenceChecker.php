<?php

namespace App\Helpers;

/**
 * Class ExistenceChecker
 *
 * Provides utility methods to check the existence of various PHP entities such as classes, interfaces, methods, properties, functions, files, and constants.
 */
class ExistenceChecker
{
	/**
	 * Check if a class exists.
	 *
	 * @param string $className The name of the class to check.
	 * @return bool True if the class exists, false otherwise.
	 */
	public function classExists(string $className): bool
	{
		return class_exists($className);
	}

	/**
	 * Check if an interface exists.
	 *
	 * @param string $interfaceName The name of the interface to check.
	 * @return bool True if the interface exists, false otherwise.
	 */
	public function interfaceExists(string $interfaceName): bool
	{
		return interface_exists($interfaceName);
	}

	/**
	 * Check if a trait exists.
	 *
	 * @param string $traitName The name of the trait to check.
	 * @return bool True if the trait exists, false otherwise.
	 */
	public function traitExists(string $traitName): bool
	{
		return trait_exists($traitName);
	}

	/**
	 * Check if a method exists in a given class or object.
	 *
	 * @param object|string $objectOrClass The object or class name to check.
	 * @param string $methodName The name of the method to check.
	 * @return bool True if the method exists, false otherwise.
	 */
	public function methodExists($objectOrClass, string $methodName): bool
	{
		return method_exists($objectOrClass, $methodName);
	}

	/**
	 * Check if a property exists in a given class or object.
	 *
	 * @param object|string $objectOrClass The object or class name to check.
	 * @param string $propertyName The name of the property to check.
	 * @return bool True if the property exists, false otherwise.
	 */
	public function propertyExists($objectOrClass, string $propertyName): bool
	{
		return property_exists($objectOrClass, $propertyName);
	}

	/**
	 * Check if a constant exists in a given class.
	 *
	 * @param string $className The name of the class to check.
	 * @param string $constantName The name of the constant to check.
	 * @return bool True if the constant exists, false otherwise.
	 */
	public function constantExists(string $className, string $constantName): bool
	{
		return defined("$className::$constantName");
	}

	/**
	 * Check if a function exists.
	 *
	 * @param string $functionName The name of the function to check.
	 * @return bool True if the function exists, false otherwise.
	 */
	public function functionExists(string $functionName): bool
	{
		return function_exists($functionName);
	}

	/**
	 * Check if a file or directory exists.
	 *
	 * @param string $path The file or directory path to check.
	 * @return bool True if the file or directory exists, false otherwise.
	 */
	public function fileExists(string $path): bool
	{
		return file_exists($path);
	}
}
