<?php

namespace App\Utilities\Traits\Reflection;

use ReflectionEnum;
use ReflectionEnumBackedCase;
use ReflectionEnumUnitCase;
use ReflectionType;

/**
 * Trait ReflectionEnumTrait
 *
 * Covers ReflectionEnum, ReflectionEnumBackedCase, ReflectionEnumUnitCase.
 */
trait ReflectionEnumTrait
{
	// Reflection Enum Methods

	/**
	 * Get the backing type of an Enum, if any.
	 *
	 * @param ReflectionEnum $reflectionEnum The ReflectionEnum instance.
	 * @return ReflectionType|null The backing type of the Enum, or null if it doesn't exist.
	 */
	public function getEnumBackingType(ReflectionEnum $reflectionEnum): ?ReflectionType
	{
		return $reflectionEnum->getBackingType();
	}

	/**
	 * Returns a specific case of an Enum by name.
	 *
	 * @param ReflectionEnum $reflectionEnum The ReflectionEnum instance.
	 * @param string $caseName The name of the Enum case to retrieve.
	 * @return ReflectionEnumUnitCase|null The specified Enum case, or null if not found.
	 */
	public function getEnumCase(ReflectionEnum $reflectionEnum, string $caseName): ?ReflectionEnumUnitCase
	{
		return $reflectionEnum->getCase($caseName);
	}

	/**
	 * Get all cases of the Enum.
	 *
	 * @param ReflectionEnum $reflectionEnum The ReflectionEnum instance.
	 * @return array A list of ReflectionEnumUnitCase objects representing all Enum cases.
	 */
	public function getEnumCases(ReflectionEnum $reflectionEnum): array
	{
		return $reflectionEnum->getCases();
	}

	/**
	 * Check if the Enum has a specific case by name.
	 *
	 * @param ReflectionEnum $reflectionEnum The ReflectionEnum instance.
	 * @param string $caseName The name of the Enum case to check for.
	 * @return bool True if the Enum has the specified case, false otherwise.
	 */
	public function enumHasCase(ReflectionEnum $reflectionEnum, string $caseName): bool
	{
		return $reflectionEnum->hasCase($caseName);
	}

	/**
	 * Determine if the Enum is a Backed Enum.
	 *
	 * @param ReflectionEnum $reflectionEnum The ReflectionEnum instance.
	 * @return bool True if the Enum is a Backed Enum, false otherwise.
	 */
	public function isEnumBacked(ReflectionEnum $reflectionEnum): bool
	{
		return $reflectionEnum->isBacked();
	}

	// Reflection Enum Backed Case Methods

	/**
	 * Get the scalar value backing this Enum case.
	 *
	 * @param ReflectionEnumBackedCase $enumBackedCase The ReflectionEnumBackedCase instance.
	 * @return mixed The scalar value backing this Enum case.
	 */
	public function getEnumBackingValue(ReflectionEnumBackedCase $enumBackedCase): mixed
	{
		return $enumBackedCase->getBackingValue();
	}

	// Reflection Unit Backed Case Methods

	/**
	 * Get the reflection of the Enum that this case belongs to.
	 *
	 * @param ReflectionEnumUnitCase $enumUnitCase The ReflectionEnumUnitCase instance.
	 * @return ReflectionEnum The ReflectionEnum instance representing the Enum.
	 */
	public function getEnumFromUnitCase(ReflectionEnumUnitCase $enumUnitCase): ReflectionEnum
	{
		return $enumUnitCase->getEnum();
	}

	/**
	 * Get the enum case object described by this reflection object.
	 *
	 * @param ReflectionEnumUnitCase $enumUnitCase The ReflectionEnumUnitCase instance.
	 * @return mixed The Enum case object.
	 */
	public function getEnumUnitCaseValue(ReflectionEnumUnitCase $enumUnitCase): mixed
	{
		return $enumUnitCase->getValue();
	}
}
