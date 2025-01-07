<?php

namespace App\Utilities\Traits\Reflection;

use ReflectionProperty;
use ReflectionClass;
use ReflectionType;

/**
 * Trait ReflectionPropertyTrait
 *
 * Covers ReflectionProperty and ReflectionParameter.
 */
trait ReflectionPropertyTrait
{

	// Reflection Property Methods

	/**
	 * Get the declaring class of the property.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @return ReflectionClass The declaring class of the property.
	 */
	public function getPropertyDeclaringClass(ReflectionProperty $property): ReflectionClass
	{
		return $property->getDeclaringClass();
	}

	/**
	 * Get the default value of the property.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @return mixed The default value of the property.
	 */
	public function getPropertyDefaultValue(ReflectionProperty $property): mixed
	{
		return $property->getDefaultValue();
	}

	/**
	 * Get the doc comment for the property.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @return string|false The doc comment or false if not available.
	 */
	public function getPropertyDocComment(ReflectionProperty $property): string|false
	{
		return $property->getDocComment();
	}

	/**
	 * Get the modifiers of the property.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @return int The modifiers bitmask of the property.
	 */
	public function getPropertyModifiers(ReflectionProperty $property): int
	{
		return $property->getModifiers();
	}

	/**
	 * Get the name of the property.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @return string The name of the property.
	 */
	public function getPropertyName(ReflectionProperty $property): string
	{
		return $property->getName();
	}

	/**
	 * Get the type of the property.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @return ReflectionType|null The type of the property or null if none exists.
	 */
	public function getPropertyType(ReflectionProperty $property): ?ReflectionType
	{
		return $property->getType();
	}

	/**
	 * Get the value of the property.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @param object|null $object The object for non-static properties or null for static ones.
	 * @return mixed The value of the property.
	 */
	public function getPropertyValue(ReflectionProperty $property, ?object $object = null): mixed
	{
		return $property->getValue($object);
	}

	/**
	 * Check if the property has a default value.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @return bool True if the property has a default value, false otherwise.
	 */
	public function hasPropertyDefaultValue(ReflectionProperty $property): bool
	{
		return $property->hasDefaultValue();
	}

	/**
	 * Check if the property has a type.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @return bool True if the property has a type, false otherwise.
	 */
	public function hasPropertyType(ReflectionProperty $property): bool
	{
		return $property->hasType();
	}

	/**
	 * Check if the property is a default property.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @return bool True if the property is a default property, false otherwise.
	 */
	public function isPropertyDefault(ReflectionProperty $property): bool
	{
		return $property->isDefault();
	}

	/**
	 * Check if the property is dynamic.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @return bool True if the property is dynamic, false otherwise.
	 */
	public function isPropertyDynamic(ReflectionProperty $property): bool
	{
		return $property->isDynamic();
	}

	/**
	 * Check if the property is initialized.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @param object|null $object The object to check for non-static properties.
	 * @return bool True if the property is initialized, false otherwise.
	 */
	public function isPropertyInitialized(ReflectionProperty $property, ?object $object = null): bool
	{
		return $property->isInitialized($object);
	}

	/**
	 * Check if the property is lazy.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @param object $object The object to check.
	 * @return bool True if the property is lazy, false otherwise.
	 */
	public function isPropertyLazy(ReflectionProperty $property, object $object): bool
	{
		return $property->isLazy($object);
	}

	/**
	 * Check if the property is private.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @return bool True if the property is private, false otherwise.
	 */
	public function isPropertyPrivate(ReflectionProperty $property): bool
	{
		return $property->isPrivate();
	}

	/**
	 * Check if the property is promoted.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @return bool True if the property is promoted, false otherwise.
	 */
	public function isPropertyPromoted(ReflectionProperty $property): bool
	{
		return $property->isPromoted();
	}

	/**
	 * Check if the property is protected.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @return bool True if the property is protected, false otherwise.
	 */
	public function isPropertyProtected(ReflectionProperty $property): bool
	{
		return $property->isProtected();
	}

	/**
	 * Check if the property is public.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @return bool True if the property is public, false otherwise.
	 */
	public function isPropertyPublic(ReflectionProperty $property): bool
	{
		return $property->isPublic();
	}

	/**
	 * Check if the property is read-only.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @return bool True if the property is read-only, false otherwise.
	 */
	public function isPropertyReadOnly(ReflectionProperty $property): bool
	{
		return $property->isReadOnly();
	}

	/**
	 * Check if the property is static.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @return bool True if the property is static, false otherwise.
	 */
	public function isPropertyStatic(ReflectionProperty $property): bool
	{
		return $property->isStatic();
	}

	/**
	 * Set the accessibility of the property.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @param bool $accessible Whether the property should be accessible.
	 * @return void
	 */
	public function setPropertyAccessible(ReflectionProperty $property, bool $accessible): void
	{
		$property->setAccessible($accessible);
	}

	/**
	 * Set the raw value of the property without triggering lazy initialization.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @param object $object The object for non-static properties.
	 * @param mixed $value The value to set.
	 * @return void
	 */
	public function setRawPropertyValueWithoutLazyInitialization(
		ReflectionProperty $property,
		object $object,
		mixed $value
	): void {
		$property->setRawValueWithoutLazyInitialization($object, $value);
	}

	/**
	 * Set the value of the property.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @param object $object The object for non-static properties.
	 * @param mixed $value The value to set.
	 * @return void
	 */
	public function setPropertyValue(ReflectionProperty $property, object $object, mixed $value): void
	{
		$property->setValue($object, $value);
	}

	/**
	 * Skip lazy initialization for the property.
	 *
	 * @param ReflectionProperty $property The ReflectionProperty instance.
	 * @param object $object The object for non-static properties.
	 * @return void
	 */
	public function skipPropertyLazyInitialization(ReflectionProperty $property, object $object): void
	{
		$property->skipLazyInitialization($object);
	}

	/**
	 * Get the string representation of the ReflectionProperty.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return string The string representation of the property.
	 */
	public function propertyToString(ReflectionProperty $reflectionProperty): string
	{
		return $reflectionProperty->__toString();
	}

	/**
	 * Get the attributes of the property.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @param string|null $name Optional name of the attribute.
	 * @param int $flags Optional flags for attribute filtering.
	 * @return array An array of attributes.
	 */
	public function getPropertyAttributes(ReflectionProperty $reflectionProperty, ?string $name = null, int $flags = 0): array
	{
		return $reflectionProperty->getAttributes($name, $flags);
	}
}
