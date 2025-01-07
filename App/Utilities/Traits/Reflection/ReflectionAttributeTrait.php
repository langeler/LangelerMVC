<?php

namespace App\Utilities\Traits\Reflection;

use ReflectionAttribute;

/**
 * Trait ReflectionAttributeTrait
 *
 * Covers ReflectionAttribute methods.
 */
trait ReflectionAttributeTrait
{
	/**
	 * Get the arguments passed to the attribute.
	 *
	 * @param ReflectionAttribute $attribute The ReflectionAttribute instance.
	 * @return array An array of arguments passed to the attribute.
	 */
	public function getAttributeArguments(ReflectionAttribute $attribute): array
	{
		return $attribute->getArguments();
	}

	/**
	 * Get the name of the attribute.
	 *
	 * @param ReflectionAttribute $attribute The ReflectionAttribute instance.
	 * @return string The name of the attribute.
	 */
	public function getAttributeName(ReflectionAttribute $attribute): string
	{
		return $attribute->getName();
	}

	/**
	 * Get the target of the attribute as a bitmask.
	 *
	 * @param ReflectionAttribute $attribute The ReflectionAttribute instance.
	 * @return int The target of the attribute as a bitmask.
	 */
	public function getAttributeTarget(ReflectionAttribute $attribute): int
	{
		return $attribute->getTarget();
	}

	/**
	 * Check if the attribute is repeated on a code element.
	 *
	 * @param ReflectionAttribute $attribute The ReflectionAttribute instance.
	 * @return bool True if the attribute is repeated, false otherwise.
	 */
	public function isAttributeRepeated(ReflectionAttribute $attribute): bool
	{
		return $attribute->isRepeated();
	}

	/**
	 * Instantiate the attribute class with its arguments.
	 *
	 * @param ReflectionAttribute $attribute The ReflectionAttribute instance.
	 * @return object The instantiated attribute class.
	 */
	public function newAttributeInstance(ReflectionAttribute $attribute): object
	{
		return $attribute->newInstance();
	}
}
