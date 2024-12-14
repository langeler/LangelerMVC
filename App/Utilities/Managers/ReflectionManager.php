<?php

namespace App\Utilities\Managers;

use ReflectionClass;
use ReflectionClassConstant;
use ReflectionEnum;
use ReflectionEnumBackedCase;
use ReflectionEnumUnitCase;
use ReflectionException;
use ReflectionExtension;
use ReflectionFiber;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionGenerator;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionObject;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionReference;
use ReflectionType;
use ReflectionUnionType;
use Reflector;

/**
 * Class ReflectionManager
 *
 * Comprehensive utility class for working with PHP Reflection classes.
 * Provides methods for analyzing and interacting with PHP classes, methods, properties, functions, enums, extensions, and more.
 */
class ReflectionManager
{

		/**
		 * Create a new ReflectionClass instance.
		 *
		 * @param string|object $class The class name or object instance.
		 * @return ReflectionClass
		 * @throws ReflectionException
		 */
		public function getReflectionClass(string|object $class): ReflectionClass
		{
			return new ReflectionClass($class);
		}

		/**
		 * Get the name of the class.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @return string
		 */
		public function getClassName(ReflectionClass $reflectionClass): string
		{
			return $reflectionClass->getName();
		}

		/**
		 * Get the namespace of the class.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @return string
		 */
		public function getNamespaceName(ReflectionClass $reflectionClass): string
		{
			return $reflectionClass->getNamespaceName();
		}

		/**
		 * Check if the class is in a namespace.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @return bool
		 */
		public function isClassInNamespace(ReflectionClass $reflectionClass): bool
		{
			return $reflectionClass->inNamespace();
		}

		/**
		 * Get the short name of the class.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @return string
		 */
		public function getShortName(ReflectionClass $reflectionClass): string
		{
			return $reflectionClass->getShortName();
		}

		/**
		 * Get the file name where the class is defined.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @return string|null
		 */
		public function getFileName(ReflectionClass $reflectionClass): ?string
		{
			return $reflectionClass->getFileName();
		}

		/**
		 * Get the starting line of the class.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @return int|null
		 */
		public function getStartLine(ReflectionClass $reflectionClass): ?int
		{
			return $reflectionClass->getStartLine();
		}

		/**
		 * Get the ending line of the class.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @return int|null
		 */
		public function getEndLine(ReflectionClass $reflectionClass): ?int
		{
			return $reflectionClass->getEndLine();
		}

		/**
		 * Get the doc comment of the class.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @return string|false
		 */
		public function getDocComment(ReflectionClass $reflectionClass): string|false
		{
			return $reflectionClass->getDocComment();
		}

		/**
		 * Get the attributes of the class.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @return array
		 */
		public function getAttributes(ReflectionClass $reflectionClass): array
		{
			return $reflectionClass->getAttributes();
		}

		/**
		 * Get the class modifiers as a bitmask.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @return int
		 */
		public function getModifiers(ReflectionClass $reflectionClass): int
		{
			return $reflectionClass->getModifiers();
		}

		/**
		 * Check if the class is abstract.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @return bool
		 */
		public function isAbstract(ReflectionClass $reflectionClass): bool
		{
			return $reflectionClass->isAbstract();
		}

		/**
		 * Check if the class is final.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @return bool
		 */
		public function isFinal(ReflectionClass $reflectionClass): bool
		{
			return $reflectionClass->isFinal();
		}

		/**
		 * Check if the class is cloneable.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @return bool
		 */
		public function isCloneable(ReflectionClass $reflectionClass): bool
		{
			return $reflectionClass->isCloneable();
		}

		/**
		 * Check if the class is user-defined.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @return bool
		 */
		public function isUserDefined(ReflectionClass $reflectionClass): bool
		{
			return $reflectionClass->isUserDefined();
		}

		/**
		 * Check if the class is internal.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @return bool
		 */
		public function isInternal(ReflectionClass $reflectionClass): bool
		{
			return $reflectionClass->isInternal();
		}

		/**
		 * Check if the class is an interface.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @return bool
		 */
		public function isInterface(ReflectionClass $reflectionClass): bool
		{
			return $reflectionClass->isInterface();
		}

		/**
		 * Check if the class is a trait.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @return bool
		 */
		public function isTrait(ReflectionClass $reflectionClass): bool
		{
			return $reflectionClass->isTrait();
		}

		/**
		 * Get the parent class of the given class.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @return ReflectionClass|null
		 */
		public function getParentClass(ReflectionClass $reflectionClass): ?ReflectionClass
		{
			return $reflectionClass->getParentClass();
		}

		/**
		 * Get all methods of the class.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @return ReflectionMethod[]
		 */
		public function getMethods(ReflectionClass $reflectionClass): array
		{
			return $reflectionClass->getMethods();
		}

		/**
		 * Get a specific method of the class.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @param string $name
		 * @return ReflectionMethod
		 * @throws ReflectionException
		 */
		public function getMethod(ReflectionClass $reflectionClass, string $name): ReflectionMethod
		{
			return $reflectionClass->getMethod($name);
		}

		/**
		 * Get the constructor of the class.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @return ReflectionMethod|null
		 */
		public function getConstructor(ReflectionClass $reflectionClass): ?ReflectionMethod
		{
			return $reflectionClass->getConstructor();
		}

		/**
		 * Get all properties of the class.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @return ReflectionProperty[]
		 */
		public function getProperties(ReflectionClass $reflectionClass): array
		{
			return $reflectionClass->getProperties();
		}

		/**
		 * Get a specific property of the class.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @param string $name
		 * @return ReflectionProperty
		 * @throws ReflectionException
		 */
		public function getProperty(ReflectionClass $reflectionClass, string $name): ReflectionProperty
		{
			return $reflectionClass->getProperty($name);
		}

		/**
		 * Check if the class has a specific method.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @param string $methodName
		 * @return bool
		 */
		public function hasMethod(ReflectionClass $reflectionClass, string $methodName): bool
		{
			return $reflectionClass->hasMethod($methodName);
		}

		/**
		 * Check if the class has a specific property.
		 *
		 * @param ReflectionClass $reflectionClass
		 * @param string $propertyName
		 * @return bool
		 */
		public function hasProperty(ReflectionClass $reflectionClass, string $propertyName): bool
		{
			return $reflectionClass->hasProperty($propertyName);
		}
	}

	/**
	 * Export a class as a string.
	 *
	 * @param mixed $argument The class name or an instance.
	 * @param bool $return Whether to return the export as a string.
	 * @return string|null The exported class as a string if $return is true, otherwise null.
	 */
	public static function export(mixed $argument, bool $return = false): ?string
	{
		return ReflectionClass::export($argument, $return);
	}

	/**
	 * Get the default properties of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return array An array of default property values.
	 */
	public function getDefaultProperties(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getDefaultProperties();
	}

	/**
	 * Get the ReflectionExtension object for the extension which defined the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return ReflectionExtension|null The ReflectionExtension object or null if not defined by an extension.
	 */
	public function getExtension(ReflectionClass $reflectionClass): ?ReflectionExtension
	{
		return $reflectionClass->getExtension();
	}

	/**
	 * Get the name of the extension which defined the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return string|null The name of the extension or null if not defined by an extension.
	 */
	public function getExtensionName(ReflectionClass $reflectionClass): ?string
	{
		return $reflectionClass->getExtensionName();
	}

	/**
	 * Get the interfaces implemented by the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return ReflectionClass[] An array of ReflectionClass instances for each interface.
	 */
	public function getInterfaces(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getInterfaces();
	}

	/**
	 * Get the lazy initializer of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return callable|null The lazy initializer or null if none exists.
	 */
	public function getLazyInitializer(ReflectionClass $reflectionClass): ?callable
	{
		return $reflectionClass->getLazyInitializer();
	}

	/**
	 * Force initialization of a lazy object.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return void
	 */
	public function initializeLazyObject(ReflectionClass $reflectionClass): void
	{
		$reflectionClass->initializeLazyObject();
	}

	/**
	 * Mark a lazy object as initialized without calling its initializer or factory.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return void
	 */
	public function markLazyObjectAsInitialized(ReflectionClass $reflectionClass): void
	{
		$reflectionClass->markLazyObjectAsInitialized();
	}

	/**
	 * Reset an object and mark it as lazy.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param object $object The object to reset.
	 * @return void
	 */
	public function resetAsLazyGhost(ReflectionClass $reflectionClass, object $object): void
	{
		$reflectionClass->resetAsLazyGhost($object);
	}

	/**
	 * Reset an object and mark it as lazy proxy.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param object $object The object to reset.
	 * @return void
	 */
	public function resetAsLazyProxy(ReflectionClass $reflectionClass, object $object): void
	{
		$reflectionClass->resetAsLazyProxy($object);
	}

	/**
	 * Create a new lazy ghost instance of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return object The new lazy ghost instance.
	 */
	public function newLazyGhost(ReflectionClass $reflectionClass): object
	{
		return $reflectionClass->newLazyGhost();
	}

	/**
	 * Create a new lazy proxy instance of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return object The new lazy proxy instance.
	 */
	public function newLazyProxy(ReflectionClass $reflectionClass): object
	{
		return $reflectionClass->newLazyProxy();
	}

	/**
	 * Check if an object is lazy and uninitialized.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param object $object The object to check.
	 * @return bool True if the object is lazy and uninitialized, false otherwise.
	 */
	public function isUninitializedLazyObject(ReflectionClass $reflectionClass, object $object): bool
	{
		return $reflectionClass->isUninitializedLazyObject($object);
	}

	/**
	 * Check if the class is readonly.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return bool True if the class is readonly, false otherwise.
	 */
	public function isReadOnly(ReflectionClass $reflectionClass): bool
	{
		return $reflectionClass->isReadOnly();
	}

	/**
	 * Check if the class is an enum.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return bool True if the class is an enum, false otherwise.
	 */
	public function isEnum(ReflectionClass $reflectionClass): bool
	{
		return $reflectionClass->isEnum();
	}

	/**
	 * Get a specific constant value from the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param string $name The name of the constant.
	 * @return mixed The value of the constant, or null if not found.
	 */
	public function getConstant(ReflectionClass $reflectionClass, string $name): mixed
	{
		return $reflectionClass->getConstant($name);
	}

	/**
	 * Get all constants defined in the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return array An associative array of constants (name => value).
	 */
	public function getConstants(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getConstants();
	}

	/**
	 * Get a ReflectionClassConstant object for a specific constant.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param string $name The name of the constant.
	 * @return ReflectionClassConstant|null The ReflectionClassConstant object or null if not found.
	 */
	public function getReflectionConstant(ReflectionClass $reflectionClass, string $name): ?ReflectionClassConstant
	{
		return $reflectionClass->getReflectionConstant($name);
	}

	/**
	 * Get all ReflectionClassConstant objects for constants in the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return ReflectionClassConstant[] An array of ReflectionClassConstant objects.
	 */
	public function getReflectionConstants(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getReflectionConstants();
	}

	/**
	 * Check if the class defines a specific constant.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param string $name The name of the constant.
	 * @return bool True if the constant is defined, false otherwise.
	 */
	public function hasConstant(ReflectionClass $reflectionClass, string $name): bool
	{
		return $reflectionClass->hasConstant($name);
	}

	/**
	 * Get all static properties of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return array An associative array of static properties (name => value).
	 */
	public function getStaticProperties(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getStaticProperties();
	}

	/**
	 * Get the value of a specific static property in the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param string $name The name of the static property.
	 * @return mixed The value of the static property.
	 */
	public function getStaticPropertyValue(ReflectionClass $reflectionClass, string $name): mixed
	{
		return $reflectionClass->getStaticPropertyValue($name);
	}

	/**
	 * Set the value of a specific static property in the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param string $name The name of the static property.
	 * @param mixed $value The value to set.
	 * @return void
	 */
	public function setStaticPropertyValue(ReflectionClass $reflectionClass, string $name, mixed $value): void
	{
		$reflectionClass->setStaticPropertyValue($name, $value);
	}

	/**
	 * Get trait aliases used by the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return array An associative array of trait aliases (alias => original).
	 */
	public function getTraitAliases(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getTraitAliases();
	}

	/**
	 * Get the names of traits used by the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return string[] An array of trait names.
	 */
	public function getTraitNames(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getTraitNames();
	}

	/**
	 * Get the traits used by the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return ReflectionClass[] An array of ReflectionClass objects for each trait.
	 */
	public function getTraits(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getTraits();
	}

	/**
	 * Get the names of interfaces implemented by the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return string[] An array of interface names.
	 */
	public function getInterfaceNames(ReflectionClass $reflectionClass): array
	{
		return $reflectionClass->getInterfaceNames();
	}

	/**
	 * Check if the class implements a specific interface.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param string $interfaceName The name of the interface.
	 * @return bool True if the class implements the interface, false otherwise.
	 */
	public function implementsInterface(ReflectionClass $reflectionClass, string $interfaceName): bool
	{
		return $reflectionClass->implementsInterface($interfaceName);
	}

	/**
	 * Check if the class is anonymous.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return bool True if the class is anonymous, false otherwise.
	 */
	public function isAnonymous(ReflectionClass $reflectionClass): bool
	{
		return $reflectionClass->isAnonymous();
	}

	/**
	 * Check if an object is an instance of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param object $object The object to check.
	 * @return bool True if the object is an instance, false otherwise.
	 */
	public function isInstance(ReflectionClass $reflectionClass, object $object): bool
	{
		return $reflectionClass->isInstance($object);
	}

	/**
	 * Check if the class is instantiable.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return bool True if the class is instantiable, false otherwise.
	 */
	public function isInstantiable(ReflectionClass $reflectionClass): bool
	{
		return $reflectionClass->isInstantiable();
	}

	/**
	 * Check if the class is iterable.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return bool True if the class is iterable, false otherwise.
	 */
	public function isIterable(ReflectionClass $reflectionClass): bool
	{
		return $reflectionClass->isIterable();
	}

	/**
	 * Check if the class is a subclass of another class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param string $className The name of the parent class.
	 * @return bool True if the class is a subclass, false otherwise.
	 */
	public function isSubclassOf(ReflectionClass $reflectionClass, string $className): bool
	{
		return $reflectionClass->isSubclassOf($className);
	}

	/**
	 * Create a new instance of the class.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param mixed ...$args The arguments to pass to the constructor.
	 * @return object The new instance.
	 */
	public function newInstance(ReflectionClass $reflectionClass, mixed ...$args): object
	{
		return $reflectionClass->newInstance(...$args);
	}

	/**
	 * Create a new instance of the class with arguments as an array.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @param array $args The arguments to pass to the constructor.
	 * @return object The new instance.
	 */
	public function newInstanceArgs(ReflectionClass $reflectionClass, array $args): object
	{
		return $reflectionClass->newInstanceArgs($args);
	}

	/**
	 * Create a new instance of the class without invoking the constructor.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return object The new instance.
	 */
	public function newInstanceWithoutConstructor(ReflectionClass $reflectionClass): object
	{
		return $reflectionClass->newInstanceWithoutConstructor();
	}

	/**
	 * Get the string representation of the ReflectionClass.
	 *
	 * @param ReflectionClass $reflectionClass The ReflectionClass instance.
	 * @return string The string representation.
	 */
	public function toString(ReflectionClass $reflectionClass): string
	{
		return $reflectionClass->__toString();
	}

	/**
	 * Create a new ReflectionClassConstant instance.
	 *
	 * @param string|object $class The class name or object instance.
	 * @param string $constantName The name of the class constant.
	 * @return ReflectionClassConstant
	 * @throws ReflectionException
	 */
	public function getReflectionClassConstant(string|object $class, string $constantName): ReflectionClassConstant
	{
		return new ReflectionClassConstant($class, $constantName);
	}

	/**
	 * Export a class constant.
	 *
	 * @param string $class The class name.
	 * @param string $constant The constant name.
	 * @param bool $return Whether to return the export as a string.
	 * @return string|null The exported class constant as a string if $return is true, otherwise null.
	 */
	public static function export(string $class, string $constant, bool $return = false): ?string
	{
		return ReflectionClassConstant::export($class, $constant, $return);
	}

	/**
	 * Get the attributes of the class constant.
	 *
	 * @param ReflectionClassConstant $reflectionClassConstant The ReflectionClassConstant instance.
	 * @return array An array of attributes.
	 */
	public function getAttributes(ReflectionClassConstant $reflectionClassConstant): array
	{
		return $reflectionClassConstant->getAttributes();
	}

	/**
	 * Get the declaring class of the constant.
	 *
	 * @param ReflectionClassConstant $reflectionClassConstant The ReflectionClassConstant instance.
	 * @return ReflectionClass The declaring class.
	 */
	public function getDeclaringClass(ReflectionClassConstant $reflectionClassConstant): ReflectionClass
	{
		return $reflectionClassConstant->getDeclaringClass();
	}

	/**
	 * Get the doc comment of the class constant.
	 *
	 * @param ReflectionClassConstant $reflectionClassConstant The ReflectionClassConstant instance.
	 * @return string|false The doc comment or false if none exists.
	 */
	public function getDocComment(ReflectionClassConstant $reflectionClassConstant): string|false
	{
		return $reflectionClassConstant->getDocComment();
	}

	/**
	 * Get the modifiers of the class constant as a bitmask.
	 *
	 * @param ReflectionClassConstant $reflectionClassConstant The ReflectionClassConstant instance.
	 * @return int The modifiers bitmask.
	 */
	public function getModifiers(ReflectionClassConstant $reflectionClassConstant): int
	{
		return $reflectionClassConstant->getModifiers();
	}

	/**
	 * Get the name of the class constant.
	 *
	 * @param ReflectionClassConstant $reflectionClassConstant The ReflectionClassConstant instance.
	 * @return string The name of the constant.
	 */
	public function getName(ReflectionClassConstant $reflectionClassConstant): string
	{
		return $reflectionClassConstant->getName();
	}

	/**
	 * Get the type of the class constant.
	 *
	 * @param ReflectionClassConstant $reflectionClassConstant The ReflectionClassConstant instance.
	 * @return ReflectionType|null The type of the class constant or null if none exists.
	 */
	public function getType(ReflectionClassConstant $reflectionClassConstant): ?ReflectionType
	{
		return $reflectionClassConstant->getType();
	}

	/**
	 * Check if the class constant has a type.
	 *
	 * @param ReflectionClassConstant $reflectionClassConstant The ReflectionClassConstant instance.
	 * @return bool True if the class constant has a type, false otherwise.
	 */
	public function hasType(ReflectionClassConstant $reflectionClassConstant): bool
	{
		return $reflectionClassConstant->hasType();
	}

	/**
	 * Get the value of the class constant.
	 *
	 * @param ReflectionClassConstant $reflectionClassConstant The ReflectionClassConstant instance.
	 * @return mixed The value of the constant.
	 */
	public function getValue(ReflectionClassConstant $reflectionClassConstant): mixed
	{
		return $reflectionClassConstant->getValue();
	}

	/**
	 * Check if the class constant is deprecated.
	 *
	 * @param ReflectionClassConstant $reflectionClassConstant The ReflectionClassConstant instance.
	 * @return bool True if the class constant is deprecated, false otherwise.
	 */
	public function isDeprecated(ReflectionClassConstant $reflectionClassConstant): bool
	{
		return $reflectionClassConstant->isDeprecated();
	}

	/**
	 * Check if the class constant is an Enum case.
	 *
	 * @param ReflectionClassConstant $reflectionClassConstant The ReflectionClassConstant instance.
	 * @return bool True if the class constant is an Enum case, false otherwise.
	 */
	public function isEnumCase(ReflectionClassConstant $reflectionClassConstant): bool
	{
		return $reflectionClassConstant->isEnumCase();
	}

	/**
	 * Check if the class constant is final.
	 *
	 * @param ReflectionClassConstant $reflectionClassConstant The ReflectionClassConstant instance.
	 * @return bool True if the class constant is final, false otherwise.
	 */
	public function isFinal(ReflectionClassConstant $reflectionClassConstant): bool
	{
		return $reflectionClassConstant->isFinal();
	}

	/**
	 * Check if the class constant is private.
	 *
	 * @param ReflectionClassConstant $reflectionClassConstant The ReflectionClassConstant instance.
	 * @return bool True if the class constant is private, false otherwise.
	 */
	public function isPrivate(ReflectionClassConstant $reflectionClassConstant): bool
	{
		return $reflectionClassConstant->isPrivate();
	}

	/**
	 * Check if the class constant is protected.
	 *
	 * @param ReflectionClassConstant $reflectionClassConstant The ReflectionClassConstant instance.
	 * @return bool True if the class constant is protected, false otherwise.
	 */
	public function isProtected(ReflectionClassConstant $reflectionClassConstant): bool
	{
		return $reflectionClassConstant->isProtected();
	}

	/**
	 * Check if the class constant is public.
	 *
	 * @param ReflectionClassConstant $reflectionClassConstant The ReflectionClassConstant instance.
	 * @return bool True if the class constant is public, false otherwise.
	 */
	public function isPublic(ReflectionClassConstant $reflectionClassConstant): bool
	{
		return $reflectionClassConstant->isPublic();
	}

	/**
	 * Get the string representation of the ReflectionClassConstant.
	 *
	 * @param ReflectionClassConstant $reflectionClassConstant The ReflectionClassConstant instance.
	 * @return string The string representation.
	 */
	public function toString(ReflectionClassConstant $reflectionClassConstant): string
	{
		return $reflectionClassConstant->__toString();
	}

	/**
	 * Create a new ReflectionConstant instance.
	 *
	 * @param string|object $class The class name or object instance.
	 * @param string $constantName The name of the constant.
	 * @return ReflectionConstant
	 * @throws ReflectionException
	 */
	public function getReflectionConstant(string|object $class, string $constantName): ReflectionConstant
	{
		return new ReflectionConstant($class, $constantName);
	}

	/**
	 * Get the ReflectionExtension that defines the constant.
	 *
	 * @param ReflectionConstant $reflectionConstant The ReflectionConstant instance.
	 * @return ReflectionExtension|null The ReflectionExtension or null if not defined by an extension.
	 */
	public function getExtension(ReflectionConstant $reflectionConstant): ?ReflectionExtension
	{
		return $reflectionConstant->getExtension();
	}

	/**
	 * Get the name of the extension that defines the constant.
	 *
	 * @param ReflectionConstant $reflectionConstant The ReflectionConstant instance.
	 * @return string|null The name of the extension or null if not defined by an extension.
	 */
	public function getExtensionName(ReflectionConstant $reflectionConstant): ?string
	{
		return $reflectionConstant->getExtensionName();
	}

	/**
	 * Get the file name where the constant is defined.
	 *
	 * @param ReflectionConstant $reflectionConstant The ReflectionConstant instance.
	 * @return string|null The file name or null if not available.
	 */
	public function getFileName(ReflectionConstant $reflectionConstant): ?string
	{
		return $reflectionConstant->getFileName();
	}

	/**
	 * Get the name of the constant.
	 *
	 * @param ReflectionConstant $reflectionConstant The ReflectionConstant instance.
	 * @return string The name of the constant.
	 */
	public function getName(ReflectionConstant $reflectionConstant): string
	{
		return $reflectionConstant->getName();
	}

	/**
	 * Get the namespace of the constant.
	 *
	 * @param ReflectionConstant $reflectionConstant The ReflectionConstant instance.
	 * @return string|null The namespace name or null if not namespaced.
	 */
	public function getNamespaceName(ReflectionConstant $reflectionConstant): ?string
	{
		return $reflectionConstant->getNamespaceName();
	}

	/**
	 * Get the short name of the constant.
	 *
	 * @param ReflectionConstant $reflectionConstant The ReflectionConstant instance.
	 * @return string The short name of the constant.
	 */
	public function getShortName(ReflectionConstant $reflectionConstant): string
	{
		return $reflectionConstant->getShortName();
	}

	/**
	 * Get the value of the constant.
	 *
	 * @param ReflectionConstant $reflectionConstant The ReflectionConstant instance.
	 * @return mixed The value of the constant.
	 */
	public function getValue(ReflectionConstant $reflectionConstant): mixed
	{
		return $reflectionConstant->getValue();
	}

	/**
	 * Check if the constant is deprecated.
	 *
	 * @param ReflectionConstant $reflectionConstant The ReflectionConstant instance.
	 * @return bool True if the constant is deprecated, false otherwise.
	 */
	public function isDeprecated(ReflectionConstant $reflectionConstant): bool
	{
		return $reflectionConstant->isDeprecated();
	}

	/**
	 * Get the string representation of the ReflectionConstant.
	 *
	 * @param ReflectionConstant $reflectionConstant The ReflectionConstant instance.
	 * @return string The string representation.
	 */
	public function toString(ReflectionConstant $reflectionConstant): string
	{
		return $reflectionConstant->__toString();
	}

	/**
	 * Create a new ReflectionEnum instance.
	 *
	 * @param string $enumName The name of the enum.
	 * @return ReflectionEnum
	 * @throws ReflectionException
	 */
	public function getReflectionEnum(string $enumName): ReflectionEnum
	{
		return new ReflectionEnum($enumName);
	}

	/**
	 * Get the backing type of an Enum, if any.
	 *
	 * @param ReflectionEnum $reflectionEnum The ReflectionEnum instance.
	 * @return ReflectionNamedType|null The backing type of the enum or null if not backed.
	 */
	public function getBackingType(ReflectionEnum $reflectionEnum): ?ReflectionNamedType
	{
		return $reflectionEnum->getBackingType();
	}

	/**
	 * Get a specific case of an Enum.
	 *
	 * @param ReflectionEnum $reflectionEnum The ReflectionEnum instance.
	 * @param string $caseName The name of the case.
	 * @return ReflectionEnumUnitCase The reflection of the specific case.
	 * @throws ReflectionException
	 */
	public function getCase(ReflectionEnum $reflectionEnum, string $caseName): ReflectionEnumUnitCase
	{
		return $reflectionEnum->getCase($caseName);
	}

	/**
	 * Get a list of all cases on an Enum.
	 *
	 * @param ReflectionEnum $reflectionEnum The ReflectionEnum instance.
	 * @return ReflectionEnumUnitCase[] An array of all cases on the enum.
	 */
	public function getCases(ReflectionEnum $reflectionEnum): array
	{
		return $reflectionEnum->getCases();
	}

	/**
	 * Check if an Enum has a specific case.
	 *
	 * @param ReflectionEnum $reflectionEnum The ReflectionEnum instance.
	 * @param string $caseName The name of the case.
	 * @return bool True if the case exists, false otherwise.
	 */
	public function hasCase(ReflectionEnum $reflectionEnum, string $caseName): bool
	{
		return $reflectionEnum->hasCase($caseName);
	}

	/**
	 * Determine if an Enum is a Backed Enum.
	 *
	 * @param ReflectionEnum $reflectionEnum The ReflectionEnum instance.
	 * @return bool True if the enum is backed, false otherwise.
	 */
	public function isBacked(ReflectionEnum $reflectionEnum): bool
	{
		return $reflectionEnum->isBacked();
	}

	/**
	 * Create a new ReflectionEnumUnitCase instance.
	 *
	 * @param string $enumName The name of the enum.
	 * @param string $caseName The name of the case.
	 * @return ReflectionEnumUnitCase
	 * @throws ReflectionException
	 */
	public function getReflectionEnumUnitCase(string $enumName, string $caseName): ReflectionEnumUnitCase
	{
		return new ReflectionEnumUnitCase($enumName, $caseName);
	}

	/**
	 * Get the reflection of the Enum for this case.
	 *
	 * @param ReflectionEnumUnitCase $reflectionEnumUnitCase The ReflectionEnumUnitCase instance.
	 * @return ReflectionEnum The reflection of the Enum.
	 */
	public function getEnum(ReflectionEnumUnitCase $reflectionEnumUnitCase): ReflectionEnum
	{
		return $reflectionEnumUnitCase->getEnum();
	}

	/**
	 * Get the value of the Enum case object described by this reflection object.
	 *
	 * @param ReflectionEnumUnitCase $reflectionEnumUnitCase The ReflectionEnumUnitCase instance.
	 * @return object The Enum case object.
	 */
	public function getValue(ReflectionEnumUnitCase $reflectionEnumUnitCase): object
	{
		return $reflectionEnumUnitCase->getValue();
	}

	/**
	 * Create a new ReflectionEnumBackedCase instance.
	 *
	 * @param string $enumName The name of the enum.
	 * @param string $caseName The name of the case.
	 * @return ReflectionEnumBackedCase
	 * @throws ReflectionException
	 */
	public function getReflectionEnumBackedCase(string $enumName, string $caseName): ReflectionEnumBackedCase
	{
		return new ReflectionEnumBackedCase($enumName, $caseName);
	}

	/**
	 * Get the scalar value backing this Enum case.
	 *
	 * @param ReflectionEnumBackedCase $reflectionEnumBackedCase The ReflectionEnumBackedCase instance.
	 * @return int|string The scalar value backing this Enum case.
	 */
	public function getBackingValue(ReflectionEnumBackedCase $reflectionEnumBackedCase): int|string
	{
		return $reflectionEnumBackedCase->getBackingValue();
	}

	/**
	 * Create a new ReflectionZendExtension instance.
	 *
	 * @param string $name The name of the Zend extension.
	 * @return ReflectionZendExtension
	 * @throws ReflectionException
	 */
	public function getReflectionZendExtension(string $name): ReflectionZendExtension
	{
		return new ReflectionZendExtension($name);
	}

	/**
	 * Get the author of the Zend extension.
	 *
	 * @param ReflectionZendExtension $extension The ReflectionZendExtension instance.
	 * @return string The author of the extension.
	 */
	public function getZendExtensionAuthor(ReflectionZendExtension $extension): string
	{
		return $extension->getAuthor();
	}

	/**
	 * Get the copyright information of the Zend extension.
	 *
	 * @param ReflectionZendExtension $extension The ReflectionZendExtension instance.
	 * @return string The copyright information.
	 */
	public function getZendExtensionCopyright(ReflectionZendExtension $extension): string
	{
		return $extension->getCopyright();
	}

	/**
	 * Get the name of the Zend extension.
	 *
	 * @param ReflectionZendExtension $extension The ReflectionZendExtension instance.
	 * @return string The name of the extension.
	 */
	public function getZendExtensionName(ReflectionZendExtension $extension): string
	{
		return $extension->getName();
	}

	/**
	 * Get the URL of the Zend extension.
	 *
	 * @param ReflectionZendExtension $extension The ReflectionZendExtension instance.
	 * @return string The URL of the extension.
	 */
	public function getZendExtensionURL(ReflectionZendExtension $extension): string
	{
		return $extension->getURL();
	}

	/**
	 * Get the version of the Zend extension.
	 *
	 * @param ReflectionZendExtension $extension The ReflectionZendExtension instance.
	 * @return string The version of the extension.
	 */
	public function getZendExtensionVersion(ReflectionZendExtension $extension): string
	{
		return $extension->getVersion();
	}

	/**
	 * Get the string representation of the Zend extension.
	 *
	 * @param ReflectionZendExtension $extension The ReflectionZendExtension instance.
	 * @return string The string representation.
	 */
	public function zendExtensionToString(ReflectionZendExtension $extension): string
	{
		return $extension->__toString();
	}

	/**
	 * Export a Zend extension as a string.
	 *
	 * @param string $name The name of the Zend extension.
	 * @param bool $return Whether to return the export as a string.
	 * @return string|null The exported extension as a string if $return is true, otherwise null.
	 */
	public static function exportZendExtension(string $name, bool $return = false): ?string
	{
		return ReflectionZendExtension::export($name, $return);
	}

	/**
	 * Create a new ReflectionExtension instance.
	 *
	 * @param string $name The name of the extension.
	 * @return ReflectionExtension
	 * @throws ReflectionException
	 */
	public function getReflectionExtension(string $name): ReflectionExtension
	{
		return new ReflectionExtension($name);
	}

	/**
	 * Get the classes defined by the extension.
	 *
	 * @param ReflectionExtension $extension The ReflectionExtension instance.
	 * @return ReflectionClass[] An array of ReflectionClass objects for each class.
	 */
	public function getExtensionClasses(ReflectionExtension $extension): array
	{
		return $extension->getClasses();
	}

	/**
	 * Get the names of the classes defined by the extension.
	 *
	 * @param ReflectionExtension $extension The ReflectionExtension instance.
	 * @return string[] An array of class names.
	 */
	public function getExtensionClassNames(ReflectionExtension $extension): array
	{
		return $extension->getClassNames();
	}

	/**
	 * Get the constants defined by the extension.
	 *
	 * @param ReflectionExtension $extension The ReflectionExtension instance.
	 * @return array An associative array of constants (name => value).
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
	 * Get the functions defined by the extension.
	 *
	 * @param ReflectionExtension $extension The ReflectionExtension instance.
	 * @return ReflectionFunction[] An array of ReflectionFunction objects.
	 */
	public function getExtensionFunctions(ReflectionExtension $extension): array
	{
		return $extension->getFunctions();
	}

	/**
	 * Get the INI entries defined by the extension.
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
	 * @return string The version of the extension.
	 */
	public function getExtensionVersion(ReflectionExtension $extension): string
	{
		return $extension->getVersion();
	}

	/**
	 * Print the info of the extension.
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
	 * Get the string representation of the extension.
	 *
	 * @param ReflectionExtension $extension The ReflectionExtension instance.
	 * @return string The string representation.
	 */
	public function extensionToString(ReflectionExtension $extension): string
	{
		return $extension->__toString();
	}

	/**
	 * Export an extension as a string.
	 *
	 * @param string $name The name of the extension.
	 * @param bool $return Whether to return the export as a string.
	 * @return string|null The exported extension as a string if $return is true, otherwise null.
	 */
	public static function exportExtension(string $name, bool $return = false): ?string
	{
		return ReflectionExtension::export($name, $return);
	}

	/**
	 * Create a new ReflectionFunction instance.
	 *
	 * @param string|Closure $function The name of the function or a closure.
	 * @return ReflectionFunction
	 * @throws ReflectionException
	 */
	public function getReflectionFunction(string|Closure $function): ReflectionFunction
	{
		return new ReflectionFunction($function);
	}

	/**
	 * Export a function as a string.
	 *
	 * @param string $name The name of the function.
	 * @param bool $return Whether to return the export as a string.
	 * @return string|null The exported function as a string if $return is true, otherwise null.
	 */
	public static function exportFunction(string $name, bool $return = false): ?string
	{
		return ReflectionFunction::export($name, $return);
	}

	/**
	 * Get a dynamically created closure for the function.
	 *
	 * @param ReflectionFunction $reflectionFunction The ReflectionFunction instance.
	 * @return Closure The dynamically created closure.
	 */
	public function getClosure(ReflectionFunction $reflectionFunction): Closure
	{
		return $reflectionFunction->getClosure();
	}

	/**
	 * Invoke the function.
	 *
	 * @param ReflectionFunction $reflectionFunction The ReflectionFunction instance.
	 * @param mixed ...$args The arguments to pass to the function.
	 * @return mixed The result of the function invocation.
	 */
	public function invokeFunction(ReflectionFunction $reflectionFunction, mixed ...$args): mixed
	{
		return $reflectionFunction->invoke(...$args);
	}

	/**
	 * Invoke the function with arguments as an array.
	 *
	 * @param ReflectionFunction $reflectionFunction The ReflectionFunction instance.
	 * @param array $args The arguments to pass to the function.
	 * @return mixed The result of the function invocation.
	 */
	public function invokeFunctionArgs(ReflectionFunction $reflectionFunction, array $args): mixed
	{
		return $reflectionFunction->invokeArgs($args);
	}

	/**
	 * Check if the function is anonymous.
	 *
	 * @param ReflectionFunction $reflectionFunction The ReflectionFunction instance.
	 * @return bool True if the function is anonymous, false otherwise.
	 */
	public function isAnonymousFunction(ReflectionFunction $reflectionFunction): bool
	{
		return $reflectionFunction->isAnonymous();
	}

	/**
	 * Check if the function is disabled.
	 *
	 * @param ReflectionFunction $reflectionFunction The ReflectionFunction instance.
	 * @return bool True if the function is disabled, false otherwise.
	 */
	public function isFunctionDisabled(ReflectionFunction $reflectionFunction): bool
	{
		return $reflectionFunction->isDisabled();
	}

	/**
	 * Get the string representation of the ReflectionFunction.
	 *
	 * @param ReflectionFunction $reflectionFunction The ReflectionFunction instance.
	 * @return string The string representation of the function.
	 */
	public function reflectionFunctionToString(ReflectionFunction $reflectionFunction): string
	{
		return $reflectionFunction->__toString();
	}

	/**
	 * Clone a ReflectionFunctionAbstract object.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return ReflectionFunctionAbstract The cloned object.
	 */
	public function cloneReflectionFunctionAbstract(ReflectionFunctionAbstract $reflectionFunctionAbstract): ReflectionFunctionAbstract
	{
		return clone $reflectionFunctionAbstract;
	}

	/**
	 * Get the attributes of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return array An array of attributes.
	 */
	public function getAttributes(ReflectionFunctionAbstract $reflectionFunctionAbstract): array
	{
		return $reflectionFunctionAbstract->getAttributes();
	}

	/**
	 * Get the class corresponding to static:: inside a closure.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return ReflectionClass|null The corresponding class or null if not applicable.
	 */
	public function getClosureCalledClass(ReflectionFunctionAbstract $reflectionFunctionAbstract): ?ReflectionClass
	{
		return $reflectionFunctionAbstract->getClosureCalledClass();
	}

	/**
	 * Get the class corresponding to the scope inside a closure.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return ReflectionClass|null The corresponding scope class or null if not applicable.
	 */
	public function getClosureScopeClass(ReflectionFunctionAbstract $reflectionFunctionAbstract): ?ReflectionClass
	{
		return $reflectionFunctionAbstract->getClosureScopeClass();
	}

	/**
	 * Get the object corresponding to $this inside a closure.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return object|null The object or null if not applicable.
	 */
	public function getClosureThis(ReflectionFunctionAbstract $reflectionFunctionAbstract): ?object
	{
		return $reflectionFunctionAbstract->getClosureThis();
	}

	/**
	 * Get an array of used variables in the closure.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return array The array of used variables.
	 */
	public function getClosureUsedVariables(ReflectionFunctionAbstract $reflectionFunctionAbstract): array
	{
		return $reflectionFunctionAbstract->getClosureUsedVariables();
	}

	/**
	 * Get the doc comment of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return string|false The doc comment or false if not available.
	 */
	public function getDocComment(ReflectionFunctionAbstract $reflectionFunctionAbstract): string|false
	{
		return $reflectionFunctionAbstract->getDocComment();
	}

	/**
	 * Get the end line number of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return int|null The end line number or null if not available.
	 */
	public function getEndLine(ReflectionFunctionAbstract $reflectionFunctionAbstract): ?int
	{
		return $reflectionFunctionAbstract->getEndLine();
	}

	/**
	 * Get the extension information of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return ReflectionExtension|null The extension information or null if not applicable.
	 */
	public function getExtension(ReflectionFunctionAbstract $reflectionFunctionAbstract): ?ReflectionExtension
	{
		return $reflectionFunctionAbstract->getExtension();
	}

	/**
	 * Get the extension name of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return string|null The extension name or null if not applicable.
	 */
	public function getExtensionName(ReflectionFunctionAbstract $reflectionFunctionAbstract): ?string
	{
		return $reflectionFunctionAbstract->getExtensionName();
	}

	/**
	 * Get the file name where the function is defined.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return string|null The file name or null if not available.
	 */
	public function getFileName(ReflectionFunctionAbstract $reflectionFunctionAbstract): ?string
	{
		return $reflectionFunctionAbstract->getFileName();
	}

	/**
	 * Get the name of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return string The name of the function.
	 */
	public function getName(ReflectionFunctionAbstract $reflectionFunctionAbstract): string
	{
		return $reflectionFunctionAbstract->getName();
	}

	/**
	 * Get the namespace name of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return string|null The namespace name or null if not namespaced.
	 */
	public function getNamespaceName(ReflectionFunctionAbstract $reflectionFunctionAbstract): ?string
	{
		return $reflectionFunctionAbstract->getNamespaceName();
	}

	/**
	 * Get the number of parameters of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return int The number of parameters.
	 */
	public function getNumberOfParameters(ReflectionFunctionAbstract $reflectionFunctionAbstract): int
	{
		return $reflectionFunctionAbstract->getNumberOfParameters();
	}

	/**
	 * Get the number of required parameters of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return int The number of required parameters.
	 */
	public function getNumberOfRequiredParameters(ReflectionFunctionAbstract $reflectionFunctionAbstract): int
	{
		return $reflectionFunctionAbstract->getNumberOfRequiredParameters();
	}

	/**
	 * Get the parameters of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return ReflectionParameter[] An array of ReflectionParameter objects.
	 */
	public function getParameters(ReflectionFunctionAbstract $reflectionFunctionAbstract): array
	{
		return $reflectionFunctionAbstract->getParameters();
	}

	/**
	 * Get the specified return type of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return ReflectionType|null The return type or null if not specified.
	 */
	public function getReturnType(ReflectionFunctionAbstract $reflectionFunctionAbstract): ?ReflectionType
	{
		return $reflectionFunctionAbstract->getReturnType();
	}

	/**
	 * Get the short name of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return string The short name of the function.
	 */
	public function getShortName(ReflectionFunctionAbstract $reflectionFunctionAbstract): string
	{
		return $reflectionFunctionAbstract->getShortName();
	}

	/**
	 * Get the starting line number of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return int|null The starting line number or null if not available.
	 */
	public function getStartLine(ReflectionFunctionAbstract $reflectionFunctionAbstract): ?int
	{
		return $reflectionFunctionAbstract->getStartLine();
	}

	/**
	 * Get the static variables of the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return array An associative array of static variables (name => value).
	 */
	public function getStaticVariables(ReflectionFunctionAbstract $reflectionFunctionAbstract): array
	{
		return $reflectionFunctionAbstract->getStaticVariables();
	}

	/**
	 * Get the tentative return type associated with the function.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return ReflectionType|null The tentative return type or null if not specified.
	 */
	public function getTentativeReturnType(ReflectionFunctionAbstract $reflectionFunctionAbstract): ?ReflectionType
	{
		return $reflectionFunctionAbstract->getTentativeReturnType();
	}

	/**
	 * Check if the function has a specified return type.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return bool True if a return type is specified, false otherwise.
	 */
	public function hasReturnType(ReflectionFunctionAbstract $reflectionFunctionAbstract): bool
	{
		return $reflectionFunctionAbstract->hasReturnType();
	}

	/**
	 * Check if the function has a tentative return type.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return bool True if a tentative return type exists, false otherwise.
	 */
	public function hasTentativeReturnType(ReflectionFunctionAbstract $reflectionFunctionAbstract): bool
	{
		return $reflectionFunctionAbstract->hasTentativeReturnType();
	}

	/**
	 * Check if the function is in a namespace.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return bool True if the function is in a namespace, false otherwise.
	 */
	public function inNamespace(ReflectionFunctionAbstract $reflectionFunctionAbstract): bool
	{
		return $reflectionFunctionAbstract->inNamespace();
	}

	/**
	 * Check if the function is a closure.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return bool True if the function is a closure, false otherwise.
	 */
	public function isClosure(ReflectionFunctionAbstract $reflectionFunctionAbstract): bool
	{
		return $reflectionFunctionAbstract->isClosure();
	}

	/**
	 * Check if the function is deprecated.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return bool True if the function is deprecated, false otherwise.
	 */
	public function isDeprecated(ReflectionFunctionAbstract $reflectionFunctionAbstract): bool
	{
		return $reflectionFunctionAbstract->isDeprecated();
	}

	/**
	 * Check if the function is a generator.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return bool True if the function is a generator, false otherwise.
	 */
	public function isGenerator(ReflectionFunctionAbstract $reflectionFunctionAbstract): bool
	{
		return $reflectionFunctionAbstract->isGenerator();
	}

	/**
	 * Check if the function is internal.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return bool True if the function is internal, false otherwise.
	 */
	public function isInternal(ReflectionFunctionAbstract $reflectionFunctionAbstract): bool
	{
		return $reflectionFunctionAbstract->isInternal();
	}

	/**
	 * Check if the function is static.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return bool True if the function is static, false otherwise.
	 */
	public function isStatic(ReflectionFunctionAbstract $reflectionFunctionAbstract): bool
	{
		return $reflectionFunctionAbstract->isStatic();
	}

	/**
	 * Check if the function is user-defined.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return bool True if the function is user-defined, false otherwise.
	 */
	public function isUserDefined(ReflectionFunctionAbstract $reflectionFunctionAbstract): bool
	{
		return $reflectionFunctionAbstract->isUserDefined();
	}

	/**
	 * Check if the function is variadic.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return bool True if the function is variadic, false otherwise.
	 */
	public function isVariadic(ReflectionFunctionAbstract $reflectionFunctionAbstract): bool
	{
		return $reflectionFunctionAbstract->isVariadic();
	}

	/**
	 * Check if the function returns a reference.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return bool True if the function returns a reference, false otherwise.
	 */
	public function returnsReference(ReflectionFunctionAbstract $reflectionFunctionAbstract): bool
	{
		return $reflectionFunctionAbstract->returnsReference();
	}

	/**
	 * Get the string representation of the ReflectionFunctionAbstract.
	 *
	 * @param ReflectionFunctionAbstract $reflectionFunctionAbstract The ReflectionFunctionAbstract instance.
	 * @return string The string representation.
	 */
	public function toString(ReflectionFunctionAbstract $reflectionFunctionAbstract): string
	{
		return $reflectionFunctionAbstract->__toString();
	}

	/**
	 * Create a new ReflectionMethod instance.
	 *
	 * @param string|object $class The class name or object instance.
	 * @param string $methodName The name of the method.
	 * @return ReflectionMethod
	 * @throws ReflectionException
	 */
	public function getReflectionMethod(string|object $class, string $methodName): ReflectionMethod
	{
		return new ReflectionMethod($class, $methodName);
	}

	/**
	 * Create a ReflectionMethod from a method name.
	 *
	 * @param string $className The name of the class.
	 * @param string $methodName The name of the method.
	 * @return ReflectionMethod
	 */
	public function createFromMethodName(string $className, string $methodName): ReflectionMethod
	{
		return ReflectionMethod::createFromMethodName($className, $methodName);
	}

	/**
	 * Export a ReflectionMethod as a string.
	 *
	 * @param string $class The class name.
	 * @param string $name The method name.
	 * @param bool $return Whether to return the export as a string.
	 * @return string|null The exported method as a string if $return is true, otherwise null.
	 */
	public static function export(string $class, string $name, bool $return = false): ?string
	{
		return ReflectionMethod::export($class, $name, $return);
	}

	/**
	 * Get a dynamically created closure for the method.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @param object|null $object The object for instance methods, or null for static methods.
	 * @return Closure The dynamically created closure.
	 */
	public function getClosure(ReflectionMethod $reflectionMethod, ?object $object = null): Closure
	{
		return $reflectionMethod->getClosure($object);
	}

	/**
	 * Get the declaring class of the reflected method.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return ReflectionClass The declaring class.
	 */
	public function getDeclaringClass(ReflectionMethod $reflectionMethod): ReflectionClass
	{
		return $reflectionMethod->getDeclaringClass();
	}

	/**
	 * Get the modifiers of the method as a bitmask.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return int The modifiers bitmask.
	 */
	public function getModifiers(ReflectionMethod $reflectionMethod): int
	{
		return $reflectionMethod->getModifiers();
	}

	/**
	 * Get the prototype of the method.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return ReflectionMethod The method prototype.
	 */
	public function getPrototype(ReflectionMethod $reflectionMethod): ReflectionMethod
	{
		return $reflectionMethod->getPrototype();
	}

	/**
	 * Check if the method has a prototype.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return bool True if the method has a prototype, false otherwise.
	 */
	public function hasPrototype(ReflectionMethod $reflectionMethod): bool
	{
		return $reflectionMethod->hasPrototype();
	}

	/**
	 * Invoke the method.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @param object $object The object for instance methods.
	 * @param mixed ...$args The arguments to pass to the method.
	 * @return mixed The result of the method invocation.
	 */
	public function invoke(ReflectionMethod $reflectionMethod, object $object, mixed ...$args): mixed
	{
		return $reflectionMethod->invoke($object, ...$args);
	}

	/**
	 * Invoke the method with arguments as an array.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @param object $object The object for instance methods.
	 * @param array $args The arguments to pass to the method.
	 * @return mixed The result of the method invocation.
	 */
	public function invokeArgs(ReflectionMethod $reflectionMethod, object $object, array $args): mixed
	{
		return $reflectionMethod->invokeArgs($object, $args);
	}

	/**
	 * Check if the method is abstract.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return bool True if the method is abstract, false otherwise.
	 */
	public function isAbstract(ReflectionMethod $reflectionMethod): bool
	{
		return $reflectionMethod->isAbstract();
	}

	/**
	 * Check if the method is a constructor.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return bool True if the method is a constructor, false otherwise.
	 */
	public function isConstructor(ReflectionMethod $reflectionMethod): bool
	{
		return $reflectionMethod->isConstructor();
	}

	/**
	 * Check if the method is a destructor.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return bool True if the method is a destructor, false otherwise.
	 */
	public function isDestructor(ReflectionMethod $reflectionMethod): bool
	{
		return $reflectionMethod->isDestructor();
	}

	/**
	 * Check if the method is final.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return bool True if the method is final, false otherwise.
	 */
	public function isFinal(ReflectionMethod $reflectionMethod): bool
	{
		return $reflectionMethod->isFinal();
	}

	/**
	 * Check if the method is private.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return bool True if the method is private, false otherwise.
	 */
	public function isPrivate(ReflectionMethod $reflectionMethod): bool
	{
		return $reflectionMethod->isPrivate();
	}

	/**
	 * Check if the method is protected.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return bool True if the method is protected, false otherwise.
	 */
	public function isProtected(ReflectionMethod $reflectionMethod): bool
	{
		return $reflectionMethod->isProtected();
	}

	/**
	 * Check if the method is public.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return bool True if the method is public, false otherwise.
	 */
	public function isPublic(ReflectionMethod $reflectionMethod): bool
	{
		return $reflectionMethod->isPublic();
	}

	/**
	 * Set the accessibility of the method.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @param bool $accessible Whether the method should be accessible.
	 * @return void
	 */
	public function setAccessible(ReflectionMethod $reflectionMethod, bool $accessible): void
	{
		$reflectionMethod->setAccessible($accessible);
	}

	/**
	 * Get the string representation of the ReflectionMethod.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return string The string representation.
	 */
	public function toString(ReflectionMethod $reflectionMethod): string
	{
		return $reflectionMethod->__toString();
	}

	/**
	 * Create a new ReflectionNamedType instance.
	 *
	 * @param ReflectionType $reflectionType The ReflectionType instance.
	 * @return ReflectionNamedType|null Returns a ReflectionNamedType object if the type is named, otherwise null.
	 */
	public function getReflectionNamedType(ReflectionType $reflectionType): ?ReflectionNamedType
	{
		return $reflectionType instanceof ReflectionNamedType ? $reflectionType : null;
	}

	/**
	 * Get the name of the type as a string.
	 *
	 * @param ReflectionNamedType $reflectionNamedType The ReflectionNamedType instance.
	 * @return string The name of the type.
	 */
	public function getTypeName(ReflectionNamedType $reflectionNamedType): string
	{
		return $reflectionNamedType->getName();
	}

	/**
	 * Check if the type is a built-in type.
	 *
	 * @param ReflectionNamedType $reflectionNamedType The ReflectionNamedType instance.
	 * @return bool True if the type is built-in, false otherwise.
	 */
	public function isBuiltinType(ReflectionNamedType $reflectionNamedType): bool
	{
		return $reflectionNamedType->isBuiltin();
	}

	/**
	 * Create a new ReflectionObject instance.
	 *
	 * @param object $object The object to reflect.
	 * @return ReflectionObject
	 * @throws ReflectionException
	 */
	public function getReflectionObject(object $object): ReflectionObject
	{
		return new ReflectionObject($object);
	}

	/**
	 * Export a ReflectionObject as a string.
	 *
	 * @param object $object The object to reflect.
	 * @param bool $return Whether to return the export as a string.
	 * @return string|null The exported object as a string if $return is true, otherwise null.
	 */
	public static function exportReflectionObject(object $object, bool $return = false): ?string
	{
		return ReflectionObject::export($object, $return);
	}

	/**
	 * Create a new ReflectionParameter instance.
	 *
	 * @param string|array $function The function name or [class, method].
	 * @param string|int $parameter The parameter name or position.
	 * @return ReflectionParameter
	 * @throws ReflectionException
	 */
	public function getReflectionParameter(string|array $function, string|int $parameter): ReflectionParameter
	{
		return new ReflectionParameter($function, $parameter);
	}

	/**
	 * Export a ReflectionParameter as a string.
	 *
	 * @param string|array $function The function name or [class, method].
	 * @param string|int $parameter The parameter name or position.
	 * @param bool $return Whether to return the export as a string.
	 * @return string|null The exported parameter as a string if $return is true, otherwise null.
	 */
	public static function exportParameter(string|array $function, string|int $parameter, bool $return = false): ?string
	{
		return ReflectionParameter::export($function, $parameter, $return);
	}

	/**
	 * Get the attributes of the parameter.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return array An array of attributes.
	 */
	public function getAttributes(ReflectionParameter $reflectionParameter): array
	{
		return $reflectionParameter->getAttributes();
	}

	/**
	 * Check if null is allowed for the parameter.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return bool True if null is allowed, false otherwise.
	 */
	public function allowsNull(ReflectionParameter $reflectionParameter): bool
	{
		return $reflectionParameter->allowsNull();
	}

	/**
	 * Check if the parameter can be passed by value.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return bool True if the parameter can be passed by value, false otherwise.
	 */
	public function canBePassedByValue(ReflectionParameter $reflectionParameter): bool
	{
		return $reflectionParameter->canBePassedByValue();
	}

	/**
	 * Get the declaring class of the parameter.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return ReflectionClass|null The declaring class or null if not applicable.
	 */
	public function getDeclaringClass(ReflectionParameter $reflectionParameter): ?ReflectionClass
	{
		return $reflectionParameter->getDeclaringClass();
	}

	/**
	 * Get the declaring function of the parameter.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return ReflectionFunctionAbstract The declaring function.
	 */
	public function getDeclaringFunction(ReflectionParameter $reflectionParameter): ReflectionFunctionAbstract
	{
		return $reflectionParameter->getDeclaringFunction();
	}

	/**
	 * Get the default value of the parameter.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return mixed The default value.
	 * @throws ReflectionException
	 */
	public function getDefaultValue(ReflectionParameter $reflectionParameter): mixed
	{
		return $reflectionParameter->getDefaultValue();
	}

	/**
	 * Get the constant name of the default value, if any.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return string|null The constant name or null if not applicable.
	 */
	public function getDefaultValueConstantName(ReflectionParameter $reflectionParameter): ?string
	{
		return $reflectionParameter->getDefaultValueConstantName();
	}

	/**
	 * Get the name of the parameter.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return string The name of the parameter.
	 */
	public function getName(ReflectionParameter $reflectionParameter): string
	{
		return $reflectionParameter->getName();
	}

	/**
	 * Get the position of the parameter in the function/method.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return int The position of the parameter.
	 */
	public function getPosition(ReflectionParameter $reflectionParameter): int
	{
		return $reflectionParameter->getPosition();
	}

	/**
	 * Get the type of the parameter.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return ReflectionType|null The parameter's type or null if not specified.
	 */
	public function getType(ReflectionParameter $reflectionParameter): ?ReflectionType
	{
		return $reflectionParameter->getType();
	}

	/**
	 * Check if the parameter has a type.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return bool True if the parameter has a type, false otherwise.
	 */
	public function hasType(ReflectionParameter $reflectionParameter): bool
	{
		return $reflectionParameter->hasType();
	}

	/**
	 * Check if the parameter expects an array.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return bool True if the parameter expects an array, false otherwise.
	 */
	public function isArray(ReflectionParameter $reflectionParameter): bool
	{
		return $reflectionParameter->isArray();
	}

	/**
	 * Check if the parameter must be callable.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return bool True if the parameter must be callable, false otherwise.
	 */
	public function isCallable(ReflectionParameter $reflectionParameter): bool
	{
		return $reflectionParameter->isCallable();
	}

	/**
	 * Check if a default value is available for the parameter.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return bool True if a default value is available, false otherwise.
	 */
	public function isDefaultValueAvailable(ReflectionParameter $reflectionParameter): bool
	{
		return $reflectionParameter->isDefaultValueAvailable();
	}

	/**
	 * Check if the default value of the parameter is a constant.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return bool True if the default value is a constant, false otherwise.
	 */
	public function isDefaultValueConstant(ReflectionParameter $reflectionParameter): bool
	{
		return $reflectionParameter->isDefaultValueConstant();
	}

	/**
	 * Check if the parameter is optional.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return bool True if the parameter is optional, false otherwise.
	 */
	public function isOptional(ReflectionParameter $reflectionParameter): bool
	{
		return $reflectionParameter->isOptional();
	}

	/**
	 * Check if the parameter is passed by reference.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return bool True if the parameter is passed by reference, false otherwise.
	 */
	public function isPassedByReference(ReflectionParameter $reflectionParameter): bool
	{
		return $reflectionParameter->isPassedByReference();
	}

	/**
	 * Check if the parameter is promoted to a property.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return bool True if the parameter is promoted to a property, false otherwise.
	 */
	public function isPromoted(ReflectionParameter $reflectionParameter): bool
	{
		return $reflectionParameter->isPromoted();
	}

	/**
	 * Check if the parameter is variadic.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return bool True if the parameter is variadic, false otherwise.
	 */
	public function isVariadic(ReflectionParameter $reflectionParameter): bool
	{
		return $reflectionParameter->isVariadic();
	}

	/**
	 * Get the string representation of the ReflectionParameter.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return string The string representation.
	 */
	public function parameterToString(ReflectionParameter $reflectionParameter): string
	{
		return $reflectionParameter->__toString();
	}

	/**
	 * Clone a ReflectionParameter object.
	 *
	 * @param ReflectionParameter $reflectionParameter The ReflectionParameter instance.
	 * @return ReflectionParameter The cloned object.
	 */
	public function cloneReflectionParameter(ReflectionParameter $reflectionParameter): ReflectionParameter
	{
		return clone $reflectionParameter;
	}

	/**
	 * Create a new ReflectionProperty instance.
	 *
	 * @param string|object $class The class name or object instance.
	 * @param string $propertyName The name of the property.
	 * @return ReflectionProperty
	 * @throws ReflectionException
	 */
	public function getReflectionProperty(string|object $class, string $propertyName): ReflectionProperty
	{
		return new ReflectionProperty($class, $propertyName);
	}

	/**
	 * Export a ReflectionProperty as a string.
	 *
	 * @param string $class The class name.
	 * @param string $name The property name.
	 * @param bool $return Whether to return the export as a string.
	 * @return string|null The exported property as a string if $return is true, otherwise null.
	 */
	public static function export(string $class, string $name, bool $return = false): ?string
	{
		return ReflectionProperty::export($class, $name, $return);
	}

	/**
	 * Get the attributes of the property.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return array An array of attributes.
	 */
	public function getAttributes(ReflectionProperty $reflectionProperty): array
	{
		return $reflectionProperty->getAttributes();
	}

	/**
	 * Get the declaring class of the property.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return ReflectionClass The declaring class.
	 */
	public function getDeclaringClass(ReflectionProperty $reflectionProperty): ReflectionClass
	{
		return $reflectionProperty->getDeclaringClass();
	}

	/**
	 * Get the default value declared for the property.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return mixed|null The default value or null if none exists.
	 */
	public function getDefaultValue(ReflectionProperty $reflectionProperty): mixed
	{
		return $reflectionProperty->getDefaultValue();
	}

	/**
	 * Get the doc comment of the property.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return string|false The doc comment or false if not available.
	 */
	public function getDocComment(ReflectionProperty $reflectionProperty): string|false
	{
		return $reflectionProperty->getDocComment();
	}

	/**
	 * Get the modifiers of the property as a bitmask.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return int The modifiers bitmask.
	 */
	public function getModifiers(ReflectionProperty $reflectionProperty): int
	{
		return $reflectionProperty->getModifiers();
	}

	/**
	 * Get the name of the property.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return string The name of the property.
	 */
	public function getName(ReflectionProperty $reflectionProperty): string
	{
		return $reflectionProperty->getName();
	}

	/**
	 * Get the type of the property.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return ReflectionType|null The type of the property or null if none exists.
	 */
	public function getType(ReflectionProperty $reflectionProperty): ?ReflectionType
	{
		return $reflectionProperty->getType();
	}

	/**
	 * Get the value of the property.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @param object|null $object The object to fetch the value from (for non-static properties).
	 * @return mixed The value of the property.
	 */
	public function getValue(ReflectionProperty $reflectionProperty, ?object $object = null): mixed
	{
		return $reflectionProperty->getValue($object);
	}

	/**
	 * Check if the property has a default value declared.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return bool True if a default value is declared, false otherwise.
	 */
	public function hasDefaultValue(ReflectionProperty $reflectionProperty): bool
	{
		return $reflectionProperty->hasDefaultValue();
	}

	/**
	 * Check if the property has a type.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return bool True if the property has a type, false otherwise.
	 */
	public function hasType(ReflectionProperty $reflectionProperty): bool
	{
		return $reflectionProperty->hasType();
	}

	/**
	 * Check if the property is a default property.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return bool True if the property is a default property, false otherwise.
	 */
	public function isDefault(ReflectionProperty $reflectionProperty): bool
	{
		return $reflectionProperty->isDefault();
	}

	/**
	 * Check whether the property is initialized.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @param object|null $object The object to check (for non-static properties).
	 * @return bool True if the property is initialized, false otherwise.
	 */
	public function isInitialized(ReflectionProperty $reflectionProperty, ?object $object = null): bool
	{
		return $reflectionProperty->isInitialized($object);
	}

	/**
	 * Check whether the property is lazy.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return bool True if the property is lazy, false otherwise.
	 */
	public function isLazy(ReflectionProperty $reflectionProperty): bool
	{
		return $reflectionProperty->isLazy();
	}

	/**
	 * Check if the property is private.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return bool True if the property is private, false otherwise.
	 */
	public function isPrivate(ReflectionProperty $reflectionProperty): bool
	{
		return $reflectionProperty->isPrivate();
	}

	/**
	 * Check if the property is promoted.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return bool True if the property is promoted, false otherwise.
	 */
	public function isPromoted(ReflectionProperty $reflectionProperty): bool
	{
		return $reflectionProperty->isPromoted();
	}

	/**
	 * Check if the property is protected.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return bool True if the property is protected, false otherwise.
	 */
	public function isProtected(ReflectionProperty $reflectionProperty): bool
	{
		return $reflectionProperty->isProtected();
	}

	/**
	 * Check if the property is public.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return bool True if the property is public, false otherwise.
	 */
	public function isPublic(ReflectionProperty $reflectionProperty): bool
	{
		return $reflectionProperty->isPublic();
	}

	/**
	 * Check if the property is readonly.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return bool True if the property is readonly, false otherwise.
	 */
	public function isReadOnly(ReflectionProperty $reflectionProperty): bool
	{
		return $reflectionProperty->isReadOnly();
	}

	/**
	 * Check if the property is static.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return bool True if the property is static, false otherwise.
	 */
	public function isStatic(ReflectionProperty $reflectionProperty): bool
	{
		return $reflectionProperty->isStatic();
	}

	/**
	 * Set the accessibility of the property.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @param bool $accessible Whether the property should be accessible.
	 * @return void
	 */
	public function setAccessible(ReflectionProperty $reflectionProperty, bool $accessible): void
	{
		$reflectionProperty->setAccessible($accessible);
	}

	/**
	 * Set the raw value of the property without triggering lazy initialization.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @param object|null $object The object (for non-static properties).
	 * @param mixed $value The value to set.
	 * @return void
	 */
	public function setRawValueWithoutLazyInitialization(
		ReflectionProperty $reflectionProperty,
		?object $object,
		mixed $value
	): void {
		$reflectionProperty->setRawValueWithoutLazyInitialization($object, $value);
	}

	/**
	 * Set the value of the property.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @param object|null $object The object (for non-static properties).
	 * @param mixed $value The value to set.
	 * @return void
	 */
	public function setValue(ReflectionProperty $reflectionProperty, ?object $object, mixed $value): void
	{
		$reflectionProperty->setValue($object, $value);
	}

	/**
	 * Mark the property as non-lazy.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return void
	 */
	public function skipLazyInitialization(ReflectionProperty $reflectionProperty): void
	{
		$reflectionProperty->skipLazyInitialization();
	}

	/**
	 * Get the string representation of the ReflectionProperty.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return string The string representation.
	 */
	public function toString(ReflectionProperty $reflectionProperty): string
	{
		return $reflectionProperty->__toString();
	}

	/**
	 * Clone a ReflectionProperty object.
	 *
	 * @param ReflectionProperty $reflectionProperty The ReflectionProperty instance.
	 * @return ReflectionProperty The cloned object.
	 */
	public function cloneReflectionProperty(ReflectionProperty $reflectionProperty): ReflectionProperty
	{
		return clone $reflectionProperty;
	}

	/**
	 * Check if null is allowed for the type.
	 *
	 * @param ReflectionType $reflectionType The ReflectionType instance.
	 * @return bool True if null is allowed, false otherwise.
	 */
	public function allowsNull(ReflectionType $reflectionType): bool
	{
		return $reflectionType->allowsNull();
	}

	/**
	 * Get the string representation of the ReflectionType.
	 *
	 * @param ReflectionType $reflectionType The ReflectionType instance.
	 * @return string The string representation.
	 */
	public function typeToString(ReflectionType $reflectionType): string
	{
		return $reflectionType->__toString();
	}

	/**
	 * Get the types included in the union type.
	 *
	 * @param ReflectionUnionType $reflectionUnionType The ReflectionUnionType instance.
	 * @return ReflectionType[] An array of ReflectionType objects.
	 */
	public function getUnionTypes(ReflectionUnionType $reflectionUnionType): array
	{
		return $reflectionUnionType->getTypes();
	}

	/**
	 * Create a new ReflectionGenerator instance.
	 *
	 * @param Generator $generator The Generator object to reflect.
	 * @return ReflectionGenerator
	 * @throws ReflectionException
	 */
	public function getReflectionGenerator(Generator $generator): ReflectionGenerator
	{
		return new ReflectionGenerator($generator);
	}

	/**
	 * Get the file name of the currently executing generator.
	 *
	 * @param ReflectionGenerator $reflectionGenerator The ReflectionGenerator instance.
	 * @return string The file name.
	 */
	public function getExecutingFile(ReflectionGenerator $reflectionGenerator): string
	{
		return $reflectionGenerator->getExecutingFile();
	}

	/**
	 * Get the currently executing Generator object.
	 *
	 * @param ReflectionGenerator $reflectionGenerator The ReflectionGenerator instance.
	 * @return Generator The executing Generator object.
	 */
	public function getExecutingGenerator(ReflectionGenerator $reflectionGenerator): Generator
	{
		return $reflectionGenerator->getExecutingGenerator();
	}

	/**
	 * Get the currently executing line of the generator.
	 *
	 * @param ReflectionGenerator $reflectionGenerator The ReflectionGenerator instance.
	 * @return int The executing line number.
	 */
	public function getExecutingLine(ReflectionGenerator $reflectionGenerator): int
	{
		return $reflectionGenerator->getExecutingLine();
	}

	/**
	 * Get the function name of the generator.
	 *
	 * @param ReflectionGenerator $reflectionGenerator The ReflectionGenerator instance.
	 * @return ReflectionFunctionAbstract The function of the generator.
	 */
	public function getFunction(ReflectionGenerator $reflectionGenerator): ReflectionFunctionAbstract
	{
		return $reflectionGenerator->getFunction();
	}

	/**
	 * Get the $this value of the generator.
	 *
	 * @param ReflectionGenerator $reflectionGenerator The ReflectionGenerator instance.
	 * @return object|null The $this value or null if not applicable.
	 */
	public function getThis(ReflectionGenerator $reflectionGenerator): ?object
	{
		return $reflectionGenerator->getThis();
	}

	/**
	 * Get the trace of the executing generator.
	 *
	 * @param ReflectionGenerator $reflectionGenerator The ReflectionGenerator instance.
	 * @return array An array representing the generator's execution trace.
	 */
	public function getTrace(ReflectionGenerator $reflectionGenerator): array
	{
		return $reflectionGenerator->getTrace();
	}

	/**
	 * Check if the generator's execution has finished.
	 *
	 * @param ReflectionGenerator $reflectionGenerator The ReflectionGenerator instance.
	 * @return bool True if the execution is finished, false otherwise.
	 */
	public function isClosed(ReflectionGenerator $reflectionGenerator): bool
	{
		return $reflectionGenerator->isClosed();
	}

	/**
	 * Create a new ReflectionFiber instance.
	 *
	 * @param Fiber $fiber The Fiber object to reflect.
	 * @return ReflectionFiber
	 * @throws ReflectionException
	 */
	public function getReflectionFiber(Fiber $fiber): ReflectionFiber
	{
		return new ReflectionFiber($fiber);
	}

	/**
	 * Get the callable used to create the Fiber.
	 *
	 * @param ReflectionFiber $reflectionFiber The ReflectionFiber instance.
	 * @return callable|null The callable or null if not available.
	 */
	public function getFiberCallable(ReflectionFiber $reflectionFiber): ?callable
	{
		return $reflectionFiber->getCallable();
	}

	/**
	 * Get the file name of the current execution point in the Fiber.
	 *
	 * @param ReflectionFiber $reflectionFiber The ReflectionFiber instance.
	 * @return string The file name.
	 */
	public function getFiberExecutingFile(ReflectionFiber $reflectionFiber): string
	{
		return $reflectionFiber->getExecutingFile();
	}

	/**
	 * Get the line number of the current execution point in the Fiber.
	 *
	 * @param ReflectionFiber $reflectionFiber The ReflectionFiber instance.
	 * @return int The line number.
	 */
	public function getFiberExecutingLine(ReflectionFiber $reflectionFiber): int
	{
		return $reflectionFiber->getExecutingLine();
	}

	/**
	 * Get the reflected Fiber instance.
	 *
	 * @param ReflectionFiber $reflectionFiber The ReflectionFiber instance.
	 * @return Fiber The reflected Fiber instance.
	 */
	public function getFiber(ReflectionFiber $reflectionFiber): Fiber
	{
		return $reflectionFiber->getFiber();
	}

	/**
	 * Get the backtrace of the current execution point in the Fiber.
	 *
	 * @param ReflectionFiber $reflectionFiber The ReflectionFiber instance.
	 * @return array An array representing the Fiber's backtrace.
	 */
	public function getFiberTrace(ReflectionFiber $reflectionFiber): array
	{
		return $reflectionFiber->getTrace();
	}

	/**
	 * Get the types included in the intersection type.
	 *
	 * @param ReflectionIntersectionType $reflectionIntersectionType The ReflectionIntersectionType instance.
	 * @return ReflectionType[] An array of ReflectionType objects.
	 */
	public function getIntersectionTypes(ReflectionIntersectionType $reflectionIntersectionType): array
	{
		return $reflectionIntersectionType->getTypes();
	}

	/**
	 * Create a ReflectionReference from an array element.
	 *
	 * @param array $array The array containing the reference.
	 * @param string|int $key The key of the array element.
	 * @return ReflectionReference|null The ReflectionReference object or null if not applicable.
	 */
	public function getReflectionReference(array $array, string|int $key): ?ReflectionReference
	{
		return ReflectionReference::fromArrayElement($array, $key);
	}

	/**
	 * Get the unique ID of a reference.
	 *
	 * @param ReflectionReference $reflectionReference The ReflectionReference instance.
	 * @return string The unique ID of the reference.
	 */
	public function getReferenceId(ReflectionReference $reflectionReference): string
	{
		return $reflectionReference->getId();
	}

	/**
	 * Get the arguments passed to the attribute.
	 *
	 * @param ReflectionAttribute $reflectionAttribute The ReflectionAttribute instance.
	 * @return array An array of arguments passed to the attribute.
	 */
	public function getAttributeArguments(ReflectionAttribute $reflectionAttribute): array
	{
		return $reflectionAttribute->getArguments();
	}

	/**
	 * Get the name of the attribute.
	 *
	 * @param ReflectionAttribute $reflectionAttribute The ReflectionAttribute instance.
	 * @return string The name of the attribute.
	 */
	public function getAttributeName(ReflectionAttribute $reflectionAttribute): string
	{
		return $reflectionAttribute->getName();
	}

	/**
	 * Get the target of the attribute as a bitmask.
	 *
	 * @param ReflectionAttribute $reflectionAttribute The ReflectionAttribute instance.
	 * @return int The target bitmask.
	 */
	public function getAttributeTarget(ReflectionAttribute $reflectionAttribute): int
	{
		return $reflectionAttribute->getTarget();
	}

	/**
	 * Check whether the attribute of this name has been repeated on a code element.
	 *
	 * @param ReflectionAttribute $reflectionAttribute The ReflectionAttribute instance.
	 * @return bool True if the attribute is repeated, false otherwise.
	 */
	public function isAttributeRepeated(ReflectionAttribute $reflectionAttribute): bool
	{
		return $reflectionAttribute->isRepeated();
	}

	/**
	 * Instantiate the attribute class represented by this ReflectionAttribute instance and arguments.
	 *
	 * @param ReflectionAttribute $reflectionAttribute The ReflectionAttribute instance.
	 * @return object The instantiated attribute class object.
	 */
	public function newAttributeInstance(ReflectionAttribute $reflectionAttribute): object
	{
		return $reflectionAttribute->newInstance();
	}

	/**
	 * Export a Reflector instance as a string.
	 *
	 * @param Reflector $reflector The Reflector instance.
	 * @return string The exported string representation.
	 */
	public static function exportReflector(Reflector $reflector): string
	{
		return Reflector::export();
	}

	/**
	 * Create a new ReflectionException instance.
	 *
	 * @param string $message The exception message.
	 * @param int $code The exception code.
	 * @param Throwable|null $previous The previous throwable for chaining.
	 * @return ReflectionException The new ReflectionException instance.
	 */
	public function createReflectionException(
		string $message = '',
		int $code = 0,
		?Throwable $previous = null
	): ReflectionException {
		return new ReflectionException($message, $code, $previous);
	}
