<?php

namespace App\Helpers;

/**
 * Class ItemRetriever
 *
 * Provides utility methods to retrieve various PHP entities such as classes, methods, functions, variables, files, extensions, and resources.
 */
class ItemRetriever
{
	/**
	 * Get the class name of an object.
	 *
	 * @param object $object The object to get the class name from.
	 * @return string The class name.
	 */
	public function getClass(object $object): string
	{
		return get_class($object);
	}

	/**
	 * Get the methods of a class.
	 *
	 * @param string|object $class The class name or object to get the methods from.
	 * @return array The array of method names.
	 */
	public function getClassMethods($class): array
	{
		return get_class_methods($class);
	}

	/**
	 * Get all declared classes.
	 *
	 * @return array The array of declared class names.
	 */
	public function getDeclaredClasses(): array
	{
		return get_declared_classes();
	}

	/**
	 * Get all declared interfaces.
	 *
	 * @return array The array of declared interface names.
	 */
	public function getDeclaredInterfaces(): array
	{
		return get_declared_interfaces();
	}

	/**
	 * Get all defined functions.
	 *
	 * @return array The array of defined functions, categorized by internal and user-defined functions.
	 */
	public function getDefinedFunctions(): array
	{
		return get_defined_functions();
	}

	/**
	 * Get all defined variables in the current scope.
	 *
	 * @return array The array of defined variables.
	 */
	public function getDefinedVars(): array
	{
		return get_defined_vars();
	}

	/**
	 * Get all included files.
	 *
	 * @return array The array of included file paths.
	 */
	public function getIncludedFiles(): array
	{
		return get_included_files();
	}

	/**
	 * Get all loaded extensions.
	 *
	 * @return array The array of loaded PHP extensions.
	 */
	public function getLoadedExtensions(): array
	{
		return get_loaded_extensions();
	}

	/**
	 * Get the properties of an object.
	 *
	 * @param object $object The object to get properties from.
	 * @return array The array of object properties.
	 */
	public function getObjectVars(object $object): array
	{
		return get_object_vars($object);
	}

	/**
	 * Get the parent class of an object or class.
	 *
	 * @param object|string $objectOrClass The object or class name to get the parent class from.
	 * @return string|false The name of the parent class or false if no parent class exists.
	 */
	public function getParentClass($objectOrClass)
	{
		return get_parent_class($objectOrClass);
	}

	/**
	 * Get all active resources.
	 *
	 * @param string|null $type The resource type to filter by, or null for all resources.
	 * @return array The array of active resources.
	 */
	public function getResources(?string $type = null): array
	{
		return get_resources($type);
	}
}
