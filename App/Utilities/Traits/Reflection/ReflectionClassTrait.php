<?php

namespace App\Utilities\Traits\Reflection;

use ReflectionClass;
use ReflectionObject;
use ReflectionMethod;
use ReflectionProperty;
use ReflectionClassConstant;
use ReflectionExtension;
use ReflectionType;

/**
 * Trait ReflectionClassTrait
 *
 * Covers ReflectionClass, ReflectionClassConstant, and ReflectionExtension.
 */
trait ReflectionClassTrait
{
	// Reflection Class Methods

	/**
	 * Create a new ReflectionClass instance.
	 *
	 * @param object|string $class The class name or instance to reflect.
	 * @return ReflectionClass The created ReflectionClass instance.
	 */
	public function createClass(object|string $class): ReflectionClass
	{
		return new ReflectionClass($class);
	}

	/**
	 * Get the attributes of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param string|null $name Optional attribute name filter.
	 * @param int $flags Attribute filter flags.
	 * @return array The attributes of the class.
	 */
	public function getClassAttributes(ReflectionClass $reflectionClass, ?string $name = null, int $flags = 0): array
	{
		return $reflectionClass->getAttributes($name, $flags);
	}

	/**
	 * Get a defined constant of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param string $name The name of the constant.
	 * @return mixed The value of the constant or null if not defined.
	 */
	public function getClassConstant(ReflectionClass $reflectionClass, string $name): mixed
	{
		return $reflectionClass->getConstant($name);
	}

	/**
	 * Get all constants of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return array The constants of the class.
	 */
	public function getClassConstants(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getConstants();
	}

	/**
	 * Get the constructor of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return ReflectionMethod|null The constructor method or null if not available.
	 */
	public function getClassConstructor(ReflectionClass $reflectionClass): ?ReflectionMethod
	{
		return $reflectionClass->getConstructor();
	}

	/**
	 * Get the default properties of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return array The default properties of the class.
	 */
	public function getClassDefaultProperties(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getDefaultProperties();
	}

	/**
	 * Get the doc comments of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return string|false The doc comments of the class or false if none exist.
	 */
	public function getClassDocComment(ReflectionClass $reflectionClass): string|false
	{
		return $reflectionClass->getDocComment();
	}

	/**
	 * Get the end line of the class definition.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return int|false The end line number or false if unavailable.
	 */
	public function getClassEndLine(ReflectionClass $reflectionClass): int|false
	{
		return $reflectionClass->getEndLine();
	}

	/**
	 * Get the extension object that defined the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return ReflectionExtension|null The ReflectionExtension object or null if not available.
	 */
	public function getClassExtension(ReflectionClass $reflectionClass): ?ReflectionExtension
	{
		return $reflectionClass->getExtension();
	}

	/**
	 * Get the name of the extension that defined the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return string|false The extension name or false if not available.
	 */
	public function getClassExtensionName(ReflectionClass $reflectionClass): string|false
	{
		return $reflectionClass->getExtensionName();
	}

	/**
	 * Get the filename where the class is defined.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return string|false The filename or false if unavailable.
	 */
	public function getClassFileName(ReflectionClass $reflectionClass): string|false
	{
		return $reflectionClass->getFileName();
	}

	/**
	 * Get the names of interfaces implemented by the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return array The interface names.
	 */
	public function getClassInterfaceNames(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getInterfaceNames();
	}

	/**
	 * Get the interfaces implemented by the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return array The interfaces implemented by the class.
	 */
	public function getClassInterfaces(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getInterfaces();
	}

	/**
	 * Get the lazy initializer of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return callable|null The lazy initializer or null if not defined.
	 */
	public function getClassLazyInitializer(ReflectionClass $reflectionClass): ?callable
	{
		return $reflectionClass->getLazyInitializer();
	}

	/**
	 * Get a specific method of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param string $name The method name.
	 * @return ReflectionMethod The ReflectionMethod instance for the method.
	 */
	public function getClassMethod(ReflectionClass $reflectionClass, string $name): ReflectionMethod
	{
		return $reflectionClass->getMethod($name);
	}

	/**
	 * Get the methods of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param int|null $filter Optional filter to limit methods by visibility.
	 * @return array The methods of the class.
	 */
	public function getClassMethods(ReflectionClass $reflectionClass, ?int $filter = null): array
	{
		return $reflectionClass->getMethods($filter);
	}

	/**
	 * Get the modifiers of the class as a bitmask.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return int The modifiers bitmask.
	 */
	public function getClassModifiers(ReflectionClass $reflectionClass): int
	{
		return $reflectionClass->getModifiers();
	}

	/**
	 * Get the name of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return string The class name.
	 */
	public function getClassName(ReflectionClass $reflectionClass): string
	{
		return $reflectionClass->getName();
	}

	/**
	 * Get the namespace name of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return string The namespace name.
	 */
	public function getClassNamespaceName(ReflectionClass $reflectionClass): string
	{
		return $reflectionClass->getNamespaceName();
	}

	/**
	 * Get the parent class of the current class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return ReflectionClass|null The parent class or null if none exists.
	 */
	public function getClassParent(ReflectionClass $reflectionClass): ?ReflectionClass
	{
		return $reflectionClass->getParentClass();
	}

	/**
	 * Get the properties of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param int|null $filter Optional filter to limit properties by visibility.
	 * @return array The properties of the class.
	 */
	public function getClassProperties(ReflectionClass $reflectionClass, ?int $filter = null): array
	{
		return $reflectionClass->getProperties($filter);
	}

	/**
	 * Get a specific property of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param string $name The property name.
	 * @return ReflectionProperty The ReflectionProperty instance for the property.
	 */
	public function getClassProperty(ReflectionClass $reflectionClass, string $name): ReflectionProperty
	{
		return $reflectionClass->getProperty($name);
	}

	/**
	 * Get a reflection constant of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param string $name The constant name.
	 * @return ReflectionClassConstant|null The ReflectionClassConstant instance or null if not found.
	 */
	public function getClassReflectionConstant(ReflectionClass $reflectionClass, string $name): ?ReflectionClassConstant
	{
		return $reflectionClass->getReflectionConstant($name);
	}

	/**
	 * Get all reflection constants of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return array An array of ReflectionClassConstant instances.
	 */
	public function getClassReflectionConstants(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getReflectionConstants();
	}

	/**
	 * Get the short name of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return string The short name of the class.
	 */
	public function getClassShortName(ReflectionClass $reflectionClass): string
	{
		return $reflectionClass->getShortName();
	}

	/**
	 * Get the starting line number of the class definition.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return int|false The starting line number or false if unavailable.
	 */
	public function getClassStartLine(ReflectionClass $reflectionClass): int|false
	{
		return $reflectionClass->getStartLine();
	}

	/**
	 * Get the static properties of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return array The static properties of the class.
	 */
	public function getClassStaticProperties(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getStaticProperties();
	}

	/**
	 * Get the value of a static property of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param string $name The static property name.
	 * @return mixed The value of the static property.
	 */
	public function getClassStaticPropertyValue(ReflectionClass $reflectionClass, string $name): mixed
	{
		return $reflectionClass->getStaticPropertyValue($name);
	}

	/**
	 * Get the trait aliases used by the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return array An array of trait aliases.
	 */
	public function getClassTraitAliases(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getTraitAliases();
	}

	/**
	 * Get the names of traits used by the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return array An array of trait names.
	 */
	public function getClassTraitNames(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getTraitNames();
	}

	/**
	 * Get the traits used by the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return array An array of traits used by the class.
	 */
	public function getClassTraits(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getTraits();
	}

	/**
	 * Check if a constant is defined in the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param string $name The constant name.
	 * @return bool True if the constant is defined, false otherwise.
	 */
	public function hasClassConstant(ReflectionClass $reflectionClass, string $name): bool
	{
		return $reflectionClass->hasConstant($name);
	}

	/**
	 * Check if a method is defined in the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param string $name The method name.
	 * @return bool True if the method is defined, false otherwise.
	 */
	public function hasClassMethod(ReflectionClass $reflectionClass, string $name): bool
	{
		return $reflectionClass->hasMethod($name);
	}

	/**
	 * Check if a property is defined in the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param string $name The property name.
	 * @return bool True if the property is defined, false otherwise.
	 */
	public function hasClassProperty(ReflectionClass $reflectionClass, string $name): bool
	{
		return $reflectionClass->hasProperty($name);
	}

	/**
	 * Check if the class implements a specific interface.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param string $interface The interface name.
	 * @return bool True if the class implements the interface, false otherwise.
	 */
	public function implementsClassInterface(ReflectionClass $reflectionClass, string $interface): bool
	{
		return $reflectionClass->implementsInterface($interface);
	}

	/**
	 * Force the initialization of a lazy object.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param object $object The lazy object to initialize.
	 * @return void
	 */
	public function initializeClassLazyObject(ReflectionClass $reflectionClass, object $object): void
	{
		$reflectionClass->initializeLazyObject($object);
	}

	/**
	 * Check if the class is in a namespace.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return bool True if the class is in a namespace, false otherwise.
	 */
	public function isClassInNamespace(ReflectionClass $reflectionClass): bool
	{
		return $reflectionClass->inNamespace();
	}

	/**
	 * Check if the class is abstract.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return bool True if the class is abstract, false otherwise.
	 */
	public function isClassAbstract(ReflectionClass $reflectionClass): bool
	{
		return $reflectionClass->isAbstract();
	}

	/**
	 * Check if the class is anonymous.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return bool True if the class is anonymous, false otherwise.
	 */
	public function isClassAnonymous(ReflectionClass $reflectionClass): bool
	{
		return $reflectionClass->isAnonymous();
	}

	/**
	 * Check if the class is cloneable.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return bool True if the class is cloneable, false otherwise.
	 */
	public function isClassCloneable(ReflectionClass $reflectionClass): bool
	{
		return $reflectionClass->isCloneable();
	}

	/**
	 * Check if the class is an enum.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return bool True if the class is an enum, false otherwise.
	 */
	public function isClassEnum(ReflectionClass $reflectionClass): bool
	{
		return $reflectionClass->isEnum();
	}

	/**
	 * Check if the class is final.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return bool True if the class is final, false otherwise.
	 */
	public function isClassFinal(ReflectionClass $reflectionClass): bool
	{
		return $reflectionClass->isFinal();
	}

	/**
	 * Check if the class is an instance of the given object.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param object $object The object to check against.
	 * @return bool True if the class is an instance, false otherwise.
	 */
	public function isClassInstance(ReflectionClass $reflectionClass, object $object): bool
	{
		return $reflectionClass->isInstance($object);
	}

	/**
	 * Check if the class is instantiable.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return bool True if the class is instantiable, false otherwise.
	 */
	public function isClassInstantiable(ReflectionClass $reflectionClass): bool
	{
		return $reflectionClass->isInstantiable();
	}

	/**
	 * Check if the class is an interface.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return bool True if the class is an interface, false otherwise.
	 */
	public function isClassInterface(ReflectionClass $reflectionClass): bool
	{
		return $reflectionClass->isInterface();
	}

	/**
	 * Check if the class is defined internally by an extension or the core.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return bool True if the class is defined internally, false otherwise.
	 */
	public function isClassInternal(ReflectionClass $reflectionClass): bool
	{
		return $reflectionClass->isInternal();
	}

	/**
	 * Check if the class is iterable.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return bool True if the class is iterable, false otherwise.
	 */
	public function isClassIterable(ReflectionClass $reflectionClass): bool
	{
		return $reflectionClass->isIterable();
	}

	/**
	 * Alias for isClassIterable.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return bool True if the class is iterable, false otherwise.
	 */
	public function isClassIterateable(ReflectionClass $reflectionClass): bool
	{
		return $reflectionClass->isIterateable();
	}

	/**
	 * Check if the class is read-only.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return bool True if the class is read-only, false otherwise.
	 */
	public function isClassReadOnly(ReflectionClass $reflectionClass): bool
	{
		return $reflectionClass->isReadOnly();
	}

	/**
	 * Check if the class is a subclass of another class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param string $class The class name to check against.
	 * @return bool True if the class is a subclass, false otherwise.
	 */
	public function isClassSubclassOf(ReflectionClass $reflectionClass, string $class): bool
	{
		return $reflectionClass->isSubclassOf($class);
	}

	/**
	 * Check if the class is a trait.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return bool True if the class is a trait, false otherwise.
	 */
	public function isClassTrait(ReflectionClass $reflectionClass): bool
	{
		return $reflectionClass->isTrait();
	}

	/**
	 * Check if an object is lazy and uninitialized.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param object $object The object to check.
	 * @return bool True if the object is lazy and uninitialized, false otherwise.
	 */
	public function isClassUninitializedLazyObject(ReflectionClass $reflectionClass, object $object): bool
	{
		return $reflectionClass->isUninitializedLazyObject($object);
	}

	/**
	 * Check if the class is user-defined.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return bool True if the class is user-defined, false otherwise.
	 */
	public function isClassUserDefined(ReflectionClass $reflectionClass): bool
	{
		return $reflectionClass->isUserDefined();
	}

	/**
	 * Mark a lazy object as initialized without calling the initializer or factory.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param object $object The object to mark as initialized.
	 * @return void
	 */
	public function markClassLazyInitialized(ReflectionClass $reflectionClass, object $object): void
	{
		$reflectionClass->markLazyObjectAsInitialized($object);
	}


	/**
	 * Create a new class instance from given arguments.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param mixed ...$args The arguments to pass to the constructor.
	 * @return object The created class instance.
	 */
	public function newClassInstance(ReflectionClass $reflectionClass, mixed ...$args): object
	{
		return $reflectionClass->newInstance(...$args);
	}

	/**
	 * Create a new class instance from given arguments using an array.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param array $args The arguments to pass to the constructor.
	 * @return object The created class instance.
	 */
	public function newClassInstanceArgs(ReflectionClass $reflectionClass, array $args): object
	{
		return $reflectionClass->newInstanceArgs($args);
	}

	/**
	 * Create a new class instance without invoking the constructor.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return object The created class instance.
	 */
	public function newClassInstanceWithoutConstructor(ReflectionClass $reflectionClass): object
	{
		return $reflectionClass->newInstanceWithoutConstructor();
	}

	/**
	 * Create a new lazy ghost instance.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param callable|null $initializer Optional initializer for the lazy ghost.
	 * @return object The created lazy ghost instance.
	 */
	public function newClassLazyGhost(ReflectionClass $reflectionClass, ?callable $initializer = null): object
	{
		return $reflectionClass->newLazyGhost($initializer);
	}

	/**
	 * Create a new lazy proxy instance.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param callable $factory The factory callable for the lazy proxy.
	 * @return object The created lazy proxy instance.
	 */
	public function newClassLazyProxy(ReflectionClass $reflectionClass, callable $factory): object
	{
		return $reflectionClass->newLazyProxy($factory);
	}

	/**
	 * Reset an object and mark it as lazy ghost.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param object $object The object to reset and mark as lazy ghost.
	 * @param callable|null $initializer Optional initializer for the lazy object.
	 * @return void
	 */
	public function resetClassAsLazyGhost(ReflectionClass $reflectionClass, object $object, ?callable $initializer = null): void
	{
		$reflectionClass->resetAsLazyGhost($object, $initializer);
	}


	/**
	 * Reset an object and mark it as lazy proxy.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param object $object The object to reset and mark as lazy proxy.
	 * @param callable $factory The factory callable for the lazy proxy.
	 * @return void
	 */
	public function resetClassAsLazyProxy(ReflectionClass $reflectionClass, object $object, callable $factory): void
	{
		$reflectionClass->resetAsLazyProxy($object, $factory);
	}


	/**
	 * Set a static property value.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param string $propertyName The name of the static property.
	 * @param mixed $value The value to set for the static property.
	 * @return void
	 */
	public function setClassStaticPropertyValue(ReflectionClass $reflectionClass, string $propertyName, mixed $value): void
	{
		$reflectionClass->setStaticPropertyValue($propertyName, $value);
	}

	/**
	 * Get the string representation of the ReflectionClass object.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return string The string representation of the class.
	 */
	public function classToString(ReflectionClass $reflectionClass): string
	{
		return $reflectionClass->__toString();
	}
}
