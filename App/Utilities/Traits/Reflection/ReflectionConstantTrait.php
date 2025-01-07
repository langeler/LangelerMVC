<?php

namespace App\Utilities\Traits\Reflection;

use ReflectionConstant;
use ReflectionClassConstant;
use ReflectionExtension;
use ReflectionType;
use ReflectionClass;

/**
 * Trait ReflectionConstantTrait
 *
 * Covers ReflectionConstant & ReflectionClassConstant methods.
 */
trait ReflectionConstantTrait
{
	// Reflection Constant Methods

	/**
	 * Create a new ReflectionConstant instance.
	 *
	 * @param object|string $class The class or namespace where the constant resides.
	 * @param string $name The name of the constant.
	 * @return ReflectionConstant The created ReflectionConstant instance.
	 */
	public function createConstant(object|string $class, string $name): ReflectionConstant
	{
		return new ReflectionConstant($class, $name);
	}

	/**
	 * Get the extension object defining the constant.
	 *
	 * @param ReflectionConstant $constant The ReflectionConstant instance.
	 * @return ReflectionExtension|null The ReflectionExtension object or null.
	 */
	public function getConstantExtension(ReflectionConstant $constant): ?ReflectionExtension
	{
		return $constant->getExtension();
	}

	/**
	 * Get the name of the extension defining the constant.
	 *
	 * @param ReflectionConstant $constant The ReflectionConstant instance.
	 * @return string|false The name of the extension or false.
	 */
	public function getConstantExtensionName(ReflectionConstant $constant): string|false
	{
		return $constant->getExtensionName();
	}

	/**
	 * Get the file name where the constant is defined.
	 *
	 * @param ReflectionConstant $constant The ReflectionConstant instance.
	 * @return string|false The file name or false.
	 */
	public function getConstantFileName(ReflectionConstant $constant): string|false
	{
		return $constant->getFileName();
	}

	/**
	 * Get the name of the constant.
	 *
	 * @param ReflectionConstant $constant The ReflectionConstant instance.
	 * @return string The name of the constant.
	 */
	public function getConstantName(ReflectionConstant $constant): string
	{
		return $constant->getName();
	}

	/**
	 * Get the namespace of the constant.
	 *
	 * @param ReflectionConstant $constant The ReflectionConstant instance.
	 * @return string The namespace name or an empty string if not in a namespace.
	 */
	public function getConstantNamespaceName(ReflectionConstant $constant): string
	{
		return $constant->getNamespaceName();
	}

	/**
	 * Get the short name of the constant.
	 *
	 * @param ReflectionConstant $constant The ReflectionConstant instance.
	 * @return string The short name of the constant.
	 */
	public function getConstantShortName(ReflectionConstant $constant): string
	{
		return $constant->getShortName();
	}

	/**
	 * Get the value of the constant.
	 *
	 * @param ReflectionConstant $constant The ReflectionConstant instance.
	 * @return mixed The value of the constant.
	 */
	public function getConstantValue(ReflectionConstant $constant): mixed
	{
		return $constant->getValue();
	}

	/**
	 * Check if the constant is deprecated.
	 *
	 * @param ReflectionConstant $constant The ReflectionConstant instance.
	 * @return bool True if the constant is deprecated, false otherwise.
	 */
	public function isConstantDeprecated(ReflectionConstant $constant): bool
	{
		return $constant->isDeprecated();
	}

	/**
	 * Get the string representation of the ReflectionConstant object.
	 *
	 * @param ReflectionConstant $constant The ReflectionConstant instance.
	 * @return string The string representation of the constant.
	 */
	public function constantToString(ReflectionConstant $constant): string
	{
		return $constant->__toString();
	}

	// Reflection Class Constant Methods

	/**
	 * Create a new ReflectionClassConstant instance.
	 *
	 * @param string|object $class The class or object containing the constant.
	 * @param string $constantName The name of the constant.
	 * @return ReflectionClassConstant The ReflectionClassConstant instance.
	 */
	public function createClassConstant(string|object $class, string $constantName): ReflectionClassConstant
	{
		return new ReflectionClassConstant($class, $constantName);
	}

	/**
	 * Get the attributes of a class constant.
	 *
	 * @param ReflectionClassConstant $classConstant The ReflectionClassConstant instance.
	 * @param string|null $name Optional name to filter attributes.
	 * @param int $flags Optional flags for filtering attributes.
	 * @return array An array of attributes.
	 */
	public function getClassConstantAttributes(
		ReflectionClassConstant $classConstant,
		?string $name = null,
		int $flags = 0
	): array {
		return $classConstant->getAttributes($name, $flags);
	}

	/**
	 * Get the class declaring the constant.
	 *
	 * @param ReflectionClassConstant $classConstant The ReflectionClassConstant instance.
	 * @return ReflectionClass The declaring class.
	 */
	public function getClassConstantDeclaringClass(ReflectionClassConstant $classConstant): ReflectionClass
	{
		return $classConstant->getDeclaringClass();
	}

	/**
	 * Get the doc comment for the class constant.
	 *
	 * @param ReflectionClassConstant $classConstant The ReflectionClassConstant instance.
	 * @return string|false The doc comment or false if none exists.
	 */
	public function getClassConstantDocComment(ReflectionClassConstant $classConstant): string|false
	{
		return $classConstant->getDocComment();
	}

	/**
	 * Get the modifiers of a class constant.
	 *
	 * @param ReflectionClassConstant $classConstant The ReflectionClassConstant instance.
	 * @return int The modifiers as a bitmask.
	 */
	public function getClassConstantModifiers(ReflectionClassConstant $classConstant): int
	{
		return $classConstant->getModifiers();
	}

	/**
	 * Get the name of a class constant.
	 *
	 * @param ReflectionClassConstant $classConstant The ReflectionClassConstant instance.
	 * @return string The name of the constant.
	 */
	public function getClassConstantName(ReflectionClassConstant $classConstant): string
	{
		return $classConstant->getName();
	}

	/**
	 * Get the type of a class constant.
	 *
	 * @param ReflectionClassConstant $classConstant The ReflectionClassConstant instance.
	 * @return ReflectionType|null The type of the constant or null if not defined.
	 */
	public function getClassConstantType(ReflectionClassConstant $classConstant): ?ReflectionType
	{
		return $classConstant->getType();
	}

	/**
	 * Get the value of a class constant.
	 *
	 * @param ReflectionClassConstant $classConstant The ReflectionClassConstant instance.
	 * @return mixed The value of the constant.
	 */
	public function getClassConstantValue(ReflectionClassConstant $classConstant): mixed
	{
		return $classConstant->getValue();
	}

	/**
	 * Check if a class constant has a type.
	 *
	 * @param ReflectionClassConstant $classConstant The ReflectionClassConstant instance.
	 * @return bool True if the constant has a type, false otherwise.
	 */
	public function hasClassConstantType(ReflectionClassConstant $classConstant): bool
	{
		return $classConstant->hasType();
	}

	/**
	 * Check if a class constant is deprecated.
	 *
	 * @param ReflectionClassConstant $classConstant The ReflectionClassConstant instance.
	 * @return bool True if deprecated, false otherwise.
	 */
	public function isClassConstantDeprecated(ReflectionClassConstant $classConstant): bool
	{
		return $classConstant->isDeprecated();
	}

	/**
	 * Check if a class constant is an enum case.
	 *
	 * @param ReflectionClassConstant $classConstant The ReflectionClassConstant instance.
	 * @return bool True if an enum case, false otherwise.
	 */
	public function isClassConstantEnumCase(ReflectionClassConstant $classConstant): bool
	{
		return $classConstant->isEnumCase();
	}

	/**
	 * Check if a class constant is final.
	 *
	 * @param ReflectionClassConstant $classConstant The ReflectionClassConstant instance.
	 * @return bool True if final, false otherwise.
	 */
	public function isClassConstantFinal(ReflectionClassConstant $classConstant): bool
	{
		return $classConstant->isFinal();
	}

	/**
	 * Check if a class constant is private.
	 *
	 * @param ReflectionClassConstant $classConstant The ReflectionClassConstant instance.
	 * @return bool True if private, false otherwise.
	 */
	public function isClassConstantPrivate(ReflectionClassConstant $classConstant): bool
	{
		return $classConstant->isPrivate();
	}

	/**
	 * Check if a class constant is protected.
	 *
	 * @param ReflectionClassConstant $classConstant The ReflectionClassConstant instance.
	 * @return bool True if protected, false otherwise.
	 */
	public function isClassConstantProtected(ReflectionClassConstant $classConstant): bool
	{
		return $classConstant->isProtected();
	}

	/**
	 * Check if a class constant is public.
	 *
	 * @param ReflectionClassConstant $classConstant The ReflectionClassConstant instance.
	 * @return bool True if public, false otherwise.
	 */
	public function isClassConstantPublic(ReflectionClassConstant $classConstant): bool
	{
		return $classConstant->isPublic();
	}

	/**
	 * Get the string representation of a class constant.
	 *
	 * @param ReflectionClassConstant $classConstant The ReflectionClassConstant instance.
	 * @return string The string representation.
	 */
	public function classConstantToString(ReflectionClassConstant $classConstant): string
	{
		return $classConstant->__toString();
	}
}
