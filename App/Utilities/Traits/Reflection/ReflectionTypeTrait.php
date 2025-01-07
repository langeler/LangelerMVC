<?php

namespace App\Utilities\Traits\Reflection;

use ReflectionType;
use ReflectionNamedType;
use ReflectionUnionType;
use ReflectionIntersectionType;

/**
 * Trait ReflectionTypeTrait
 *
 * Covers ReflectionType, ReflectionNamedType, ReflectionUnionType, ReflectionIntersectionType.
 */
trait ReflectionTypeTrait
{
	// Reflection Named Type Methods

	/**
	 * Get the name of the type.
	 *
	 * @param ReflectionNamedType $type The ReflectionNamedType instance.
	 * @return string The name of the type.
	 */
	public function getTypeName(ReflectionNamedType $type): string
	{
		return $type->getName();
	}

	/**
	 * Check if the type is a built-in type.
	 *
	 * @param ReflectionNamedType $type The ReflectionNamedType instance.
	 * @return bool True if the type is built-in, false otherwise.
	 */
	public function isBuiltinType(ReflectionNamedType $type): bool
	{
		return $type->isBuiltin();
	}

	// Reflection Type Methods
	/**
	 * Check if a type allows null values.
	 *
	 * @param ReflectionType $type The ReflectionType instance.
	 * @return bool True if null is allowed, false otherwise.
	 */
	public function canTypeBeNull(ReflectionType $type): bool
	{
		return $type->allowsNull();
	}

	/**
	 * Get the string representation of a type.
	 *
	 * @param ReflectionType $type The ReflectionType instance.
	 * @return string The string representation of the type.
	 */
	public function typeToString(ReflectionType $type): string
	{
		return $type->__toString();
	}

	/**
	 * Get the types included in an intersection type.
	 *
	 * @param ReflectionIntersectionType $type The ReflectionIntersectionType instance.
	 * @return array An array of ReflectionType objects.
	 */
	public function getIntersectionTypes(ReflectionIntersectionType $type): array
	{
		return $type->getTypes();
	}

	// Reflection Union Type Methods

	/**
	 * Get the types included in a union type.
	 *
	 * @param ReflectionUnionType $type The ReflectionUnionType instance.
	 * @return array An array of ReflectionType objects.
	 */
	public function getUnionTypes(ReflectionUnionType $type): array
	{
		return $type->getTypes();
	}
}
