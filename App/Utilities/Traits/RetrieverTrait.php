<?php

namespace App\Utilities\Traits;

/**
 * Trait RetrieverTrait
 *
 * Provides utility methods for retrieving various PHP entities such as classes, methods, functions,
 * variables, files, extensions, and resources. This trait enhances maintainability and reusability
 * by centralizing commonly used retrieval functions.
 */
trait RetrieverTrait
{
	/**
	 * Retrieves the class name of an object.
	 *
	 * @param object $object The object whose class name is to be retrieved.
	 * @return string        The class name of the object.
	 */
	public function getClass(object $object): string
	{
		return get_class($object);
	}

	/**
	 * Retrieves the methods of a class.
	 *
	 * @param object|string $class The class name or object instance.
	 * @return array               An array of method names declared in the class.
	 */
	public function getClassMethods(object|string $class): array
	{
		return get_class_methods($class);
	}

	/**
	 * Retrieves all declared classes in the current script.
	 *
	 * @return array An array of declared class names.
	 */
	public function getDeclaredClasses(): array
	{
		return get_declared_classes();
	}

	/**
	 * Retrieves all declared interfaces in the current script.
	 *
	 * @return array An array of declared interface names.
	 */
	public function getDeclaredInterfaces(): array
	{
		return get_declared_interfaces();
	}

	/**
	 * Retrieves all defined functions in the current script.
	 *
	 * @return array An array with two keys: "internal" (built-in functions) and "user" (user-defined functions).
	 */
	public function getDefinedFunctions(): array
	{
		return get_defined_functions();
	}

	/**
	 * Retrieves all currently defined variables in the current scope.
	 *
	 * @return array An associative array of variable names and their values.
	 */
	public function getDefinedVars(): array
	{
		return get_defined_vars();
	}

	/**
	 * Retrieves all files that have been included in the current script.
	 *
	 * @return array An array of file paths.
	 */
	public function getIncludedFiles(): array
	{
		return get_included_files();
	}

	/**
	 * Retrieves all currently loaded PHP extensions.
	 *
	 * @return array An array of extension names.
	 */
	public function getLoadedExtensions(): array
	{
		return get_loaded_extensions();
	}

	/**
	 * Retrieves the properties of an object.
	 *
	 * @param object $object The object whose properties are to be retrieved.
	 * @return array         An associative array of property names and their values.
	 */
	public function getObjectVars(object $object): array
	{
		return get_object_vars($object);
	}

	/**
	 * Retrieves the parent class name of a class or object.
	 *
	 * @param object|string $objectOrClass The object or class name.
	 * @return string|false                The parent class name, or false if there is no parent class.
	 */
	public function getParentClass(object|string $objectOrClass): string|false
	{
		return get_parent_class($objectOrClass);
	}

	/**
	 * Retrieves all active resources of a specific type, or all resources if no type is specified.
	 *
	 * @param string|null $type The type of resource to filter (e.g., "stream", "curl"). Null for all resources.
	 * @return array            An array of resource handles.
	 */
	public function getResources(?string $type = null): array
	{
		return get_resources($type);
	}
}
