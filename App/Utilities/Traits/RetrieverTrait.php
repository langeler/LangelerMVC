<?php

namespace App\Utilities\Traits;

/**
 * Trait RetrieverTrait
 *
 * Provides utility methods to retrieve various PHP entities such as classes, methods, functions, variables, files, extensions, and resources.
 */
trait RetrieverTrait
{
	public function getClass(object $object): string
	{
		return get_class($object);
	}

	public function getClassMethods(object|string $class): array
	{
		return get_class_methods($class);
	}

	public function getDeclaredClasses(): array
	{
		return get_declared_classes();
	}

	public function getDeclaredInterfaces(): array
	{
		return get_declared_interfaces();
	}

	public function getDefinedFunctions(): array
	{
		return get_defined_functions();
	}

	public function getDefinedVars(): array
	{
		return get_defined_vars();
	}

	public function getIncludedFiles(): array
	{
		return get_included_files();
	}

	public function getLoadedExtensions(): array
	{
		return get_loaded_extensions();
	}

	public function getObjectVars(object $object): array
	{
		return get_object_vars($object);
	}

	public function getParentClass(object|string $objectOrClass): string|false
	{
		return get_parent_class($objectOrClass);
	}

	public function getResources(?string $type = null): array
	{
		return get_resources($type);
	}
}
