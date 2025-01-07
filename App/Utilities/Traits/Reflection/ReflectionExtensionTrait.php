<?php

namespace App\Utilities\Traits\Reflection;

use ReflectionExtension;
use ReflectionClass;
use ReflectionFunction;

/**
 * Trait ReflectionExtension
 *
 * Covers ReflectionExtension methods.
 */
trait ReflectionExtensionTrait
{

	// Reflection Extension Methods

/**
 * Get the classes defined in the extension.
 *
 * @param ReflectionExtension $extension The ReflectionExtension instance.
 * @return array An array of ReflectionClass objects.
 */
public function getExtensionClasses(ReflectionExtension $extension): array
{
	return $extension->getClasses();
}

/**
 * Get the names of the classes defined in the extension.
 *
 * @param ReflectionExtension $extension The ReflectionExtension instance.
 * @return array An array of class names.
 */
public function getExtensionClassNames(ReflectionExtension $extension): array
{
	return $extension->getClassNames();
}

/**
 * Get the constants defined in the extension.
 *
 * @param ReflectionExtension $extension The ReflectionExtension instance.
 * @return array An associative array of constants.
 */
public function getExtensionConstants(ReflectionExtension $extension): array
{
	return $extension->getConstants();
}

/**
 * Get the dependencies of the extension.
 *
 * @param ReflectionExtension $extension The ReflectionExtension instance.
 * @return array An associative array of dependencies.
 */
public function getExtensionDependencies(ReflectionExtension $extension): array
{
	return $extension->getDependencies();
}

/**
 * Get the functions defined in the extension.
 *
 * @param ReflectionExtension $extension The ReflectionExtension instance.
 * @return array An array of ReflectionFunction objects.
 */
public function getExtensionFunctions(ReflectionExtension $extension): array
{
	return $extension->getFunctions();
}

/**
 * Get the INI entries for the extension.
 *
 * @param ReflectionExtension $extension The ReflectionExtension instance.
 * @return array An associative array of INI entries.
 */
public function getExtensionINIEntries(ReflectionExtension $extension): array
{
	return $extension->getINIEntries();
}

/**
 * Get the name of the extension.
 *
 * @param ReflectionExtension $extension The ReflectionExtension instance.
 * @return string The name of the extension.
 */
public function getExtensionName(ReflectionExtension $extension): string
{
	return $extension->getName();
}

/**
 * Get the version of the extension.
 *
 * @param ReflectionExtension $extension The ReflectionExtension instance.
 * @return string|null The version of the extension or null if not available.
 */
public function getExtensionVersion(ReflectionExtension $extension): ?string
{
	return $extension->getVersion();
}


/**
 * Print information about the extension.
 *
 * @param ReflectionExtension $extension The ReflectionExtension instance.
 * @return void
 */
public function printExtensionInfo(ReflectionExtension $extension): void
{
	$extension->info();
}

/**
 * Check if the extension is persistent.
 *
 * @param ReflectionExtension $extension The ReflectionExtension instance.
 * @return bool True if the extension is persistent, false otherwise.
 */
public function isExtensionPersistent(ReflectionExtension $extension): bool
{
	return $extension->isPersistent();
}

/**
 * Check if the extension is temporary.
 *
 * @param ReflectionExtension $extension The ReflectionExtension instance.
 * @return bool True if the extension is temporary, false otherwise.
 */
public function isExtensionTemporary(ReflectionExtension $extension): bool
{
	return $extension->isTemporary();
}

/**
 * Get the string representation of the ReflectionExtension object.
 *
 * @param ReflectionExtension $extension The ReflectionExtension instance.
 * @return string The string representation of the extension.
 */
public function extensionToString(ReflectionExtension $extension): string
{
	return $extension->__toString();
}
