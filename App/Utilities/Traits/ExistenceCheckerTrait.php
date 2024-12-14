<?php

namespace App\Utilities\Traits;

/**
 * Trait ExistenceCheckerTrait
 *
 * Provides utility methods to check the existence of various PHP entities such as classes, interfaces, methods, properties, functions, files, and constants.
 */
trait ExistenceCheckerTrait
{
	public function classExists(string $className): bool
	{
		return class_exists($className);
	}

	public function interfaceExists(string $interfaceName): bool
	{
		return interface_exists($interfaceName);
	}

	public function traitExists(string $traitName): bool
	{
		return trait_exists($traitName);
	}

	public function methodExists(object|string $objectOrClass, string $methodName): bool
	{
		return method_exists($objectOrClass, $methodName);
	}

	public function propertyExists(object|string $objectOrClass, string $propertyName): bool
	{
		return property_exists($objectOrClass, $propertyName);
	}

	public function constantExists(string $className, string $constantName): bool
	{
		return defined("$className::$constantName");
	}

	public function functionExists(string $functionName): bool
	{
		return function_exists($functionName);
	}

	public function fileExists(string $path): bool
	{
		return file_exists($path);
	}
}
