<?php

namespace App\Utilities\Managers;

use App\Utilities\Traits\Reflection\{
	ReflectionAttributeTrait,
	ReflectionClassTrait,
	ReflectionConstantTrait,
	ReflectionEnumTrait,
	ReflectionExtensionTrait,
	ReflectionFunctionTrait,
	ReflectionGeneratorTrait,
	ReflectionMethodTrait,
	ReflectionParameterTrait,
	ReflectionPropertyTrait,
	ReflectionTrait,
	ReflectionTypeTrait
};

/**
 * Class ReflectionManager
 *
 * Comprehensive utility class for working with PHP Reflection classes.
 * This class provides a unified interface for utilizing various Reflection capabilities
 * offered by PHP, such as inspecting classes, methods, properties, constants, enums, extensions,
 * attributes, and more.
 *
 * Features:
 * - Reflection for classes, constants, methods, properties, and parameters.
 * - Utilities for handling PHP 8+ attributes.
 * - Support for Enums and advanced reflection features.
 * - Simplified access to Reflection functionality via reusable traits.
 *
 * @package App\Utilities\Managers
 */
class ReflectionManager
{
	use ReflectionAttributeTrait,
		ReflectionClassTrait,
		ReflectionConstantTrait,
		ReflectionEnumTrait,
		ReflectionExtensionTrait,
		ReflectionFunctionTrait,
		ReflectionGeneratorTrait,
		ReflectionMethodTrait,
		ReflectionParameterTrait,
		ReflectionPropertyTrait,
		ReflectionTrait,
		ReflectionTypeTrait;

	/**
		 * Read-only property containing all constants from reflection classes.
		 */
		public readonly array $reflectionConstants;

		/**
		 * Constructs the ReflectionManager and initializes reflection constants.
		 *
		 * Organizes constants into groups based on their corresponding reflection classes
		 * with descriptive keys for easy access.
		 */
		public function __construct()
		{
			$this->reflectionConstants = [
				'attribute' => [
					// Checks if an attribute is an instance of a specific class/interface
					'instanceof' => ReflectionAttribute::IS_INSTANCEOF,
				],
				'class' => [
					// Class is explicitly abstract
					'explicitAbstract' => ReflectionClass::IS_EXPLICIT_ABSTRACT,
					// Class is implicitly abstract (contains abstract methods)
					'implicitAbstract' => ReflectionClass::IS_IMPLICIT_ABSTRACT,
					// Class is marked as final
					'final' => ReflectionClass::IS_FINAL,
					// Class is marked as readonly
					'readonly' => ReflectionClass::IS_READONLY,
					// Skip initialization on serialization
					'skipInitialization' => ReflectionClass::SKIP_INITIALIZATION_ON_SERIALIZE,
					// Skip destructor call during object destruction
					'skipDestructor' => ReflectionClass::SKIP_DESTRUCTOR,
				],
				'classConstant' => [
					// Constant is marked as final
					'final' => ReflectionClassConstant::IS_FINAL,
					// Constant has private visibility
					'private' => ReflectionClassConstant::IS_PRIVATE,
					// Constant has protected visibility
					'protected' => ReflectionClassConstant::IS_PROTECTED,
					// Constant has public visibility
					'public' => ReflectionClassConstant::IS_PUBLIC,
				],
				'enumBackedCase' => [
					// Enum-backed case with private visibility
					'private' => ReflectionClassConstant::IS_PRIVATE,
					// Enum-backed case with protected visibility
					'protected' => ReflectionClassConstant::IS_PROTECTED,
					// Enum-backed case with public visibility
					'public' => ReflectionClassConstant::IS_PUBLIC,
					// Enum-backed case is marked as final
					'final' => ReflectionClassConstant::IS_FINAL,
				],
				'enumUnitCase' => [
					// Enum unit case with private visibility
					'private' => ReflectionClassConstant::IS_PRIVATE,
					// Enum unit case with protected visibility
					'protected' => ReflectionClassConstant::IS_PROTECTED,
					// Enum unit case with public visibility
					'public' => ReflectionClassConstant::IS_PUBLIC,
					// Enum unit case is marked as final
					'final' => ReflectionClassConstant::IS_FINAL,
				],
				'function' => [
					// Function is marked as deprecated
					'deprecated' => ReflectionFunction::IS_DEPRECATED,
				],
				'method' => [
					// Method is marked as abstract
					'abstract' => ReflectionMethod::IS_ABSTRACT,
					// Method is marked as final
					'final' => ReflectionMethod::IS_FINAL,
					// Method has private visibility
					'private' => ReflectionMethod::IS_PRIVATE,
					// Method has protected visibility
					'protected' => ReflectionMethod::IS_PROTECTED,
					// Method has public visibility
					'public' => ReflectionMethod::IS_PUBLIC,
					// Method is static
					'static' => ReflectionMethod::IS_STATIC,
				],
				'object' => [
					// Object class is explicitly abstract
					'explicitAbstract' => ReflectionClass::IS_EXPLICIT_ABSTRACT,
					// Object class is implicitly abstract
					'implicitAbstract' => ReflectionClass::IS_IMPLICIT_ABSTRACT,
					// Object class is marked as final
					'final' => ReflectionClass::IS_FINAL,
					// Object class is marked as readonly
					'readonly' => ReflectionClass::IS_READONLY,
					// Skip initialization on serialization for object class
					'skipInitialization' => ReflectionClass::SKIP_INITIALIZATION_ON_SERIALIZE,
					// Skip destructor call during object destruction for object class
					'skipDestructor' => ReflectionClass::SKIP_DESTRUCTOR,
				],
				'property' => [
					// Property is marked as abstract
					'abstract' => ReflectionProperty::IS_ABSTRACT,
					// Property is marked as final
					'final' => ReflectionProperty::IS_FINAL,
					// Property has private visibility
					'private' => ReflectionProperty::IS_PRIVATE,
					// Property setter is private
					'privateSet' => ReflectionProperty::IS_PRIVATE_SET,
					// Property has protected visibility
					'protected' => ReflectionProperty::IS_PROTECTED,
					// Property setter is protected
					'protectedSet' => ReflectionProperty::IS_PROTECTED_SET,
					// Property has public visibility
					'public' => ReflectionProperty::IS_PUBLIC,
					// Property is marked as readonly
					'readonly' => ReflectionProperty::IS_READONLY,
					// Property is static
					'static' => ReflectionProperty::IS_STATIC,
					// Property is marked as virtual
					'virtual' => ReflectionProperty::IS_VIRTUAL,
				],
			];
		}

	/**
	 * Instantiate a class with constructor injection, resolving default properties inline.
	 *
	 * @param string $class Fully qualified class name.
	 * @param array $args Optional constructor arguments.
	 * @return object Instantiated class.
	 */
	public function resolveClass(string $class, array $args = []): object
	{
		return empty($args)
			? $this->newClassInstance($this->createClass($class))
			: $this->newClassInstanceArgs($this->createClass($class), $args);
	}

	/**
	 * Invoke a method dynamically and return the result.
	 *
	 * @param object|string $class Class instance or name.
	 * @param string $method Method name.
	 * @param array $args Method arguments.
	 * @return mixed Method result.
	 */
	public function invokeMethodDynamically(object|string $class, string $method, array $args = []): mixed
	{
		return $this->invokeMethod(
			$this->getClassMethod($this->createClass(is_string($class) ? $class : $class::class), $method),
			is_object($class) ? $class : null,
			...$args
		);
	}

	/**
	 * Retrieve public properties with their values in an associative array.
	 *
	 * @param string $class Fully qualified class name.
	 * @return array Associative array of public property names and values.
	 */
	public function getPublicPropertiesWithValues(string $class): array
	{
		return array_reduce(
			$this->getClassProperties($this->createClass($class), ReflectionProperty::IS_PUBLIC),
			fn($carry, $property) => $carry + [$this->getPropertyName($property) => $this->getPropertyValue($property, $class)],
			[]
		);
	}

	/**
	 * List methods with their visibility using cross-trait functionality.
	 *
	 * @param string $class Fully qualified class name.
	 * @return array Array of methods with visibility.
	 */
	public function getMethodVisibilities(string $class): array
	{
		return array_map(
			fn($method) => [
				'name' => $this->getMethodName($method),
				'visibility' => match ($this->getMethodModifiers($method)) {
					ReflectionMethod::IS_PUBLIC => 'public',
					ReflectionMethod::IS_PROTECTED => 'protected',
					ReflectionMethod::IS_PRIVATE => 'private',
					default => 'unknown'
				},
			],
			$this->getClassMethods($this->createClass($class))
		);
	}

	/**
	 * Fetch all constants from a class with metadata.
	 *
	 * @param string $class Fully qualified class name.
	 * @return array Associative array of constant names, values, and types.
	 */
	public function getConstantsWithMetadata(string $class): array
	{
		return array_map(
			fn($constant) => [
				'name' => $this->getClassConstantName($constant),
				'value' => $this->getClassConstantValue($constant),
				'type' => $this->hasClassConstantType($constant)
					? $this->getClassConstantType($constant)->getName()
					: 'mixed'
			],
			$this->getClassReflectionConstants($this->createClass($class))
		);
	}

	/**
	 * Identify and return traits used by a class.
	 *
	 * @param string $class Fully qualified class name.
	 * @return array Array of trait names.
	 */
	public function listUsedTraits(string $class): array
	{
		return array_keys($this->getClassTraits($this->createClass($class)));
	}

	/**
	 * Reflectively invoke a function or closure with arguments.
	 *
	 * @param callable $function Function or closure.
	 * @param array $args Arguments for the function.
	 * @return mixed Function result.
	 */
	public function invokeFunction(callable $function, array $args = []): mixed
	{
		return $this->invokeFunctionArgs($this->createFunction($function), $args);
	}

	/**
	 * Retrieve attribute data for a class, method, or property.
	 *
	 * @param string|object $class Class name or instance.
	 * @param string|null $member Optional property or method name.
	 * @return array Array of attribute data.
	 */
	public function getAttributeData(string|object $class, ?string $member = null): array
	{
		$reflectionClass = $this->createClass(is_string($class) ? $class : $class::class);
		return $member
			? ($this->hasClassProperty($reflectionClass, $member)
				? $this->getPropertyAttributes($this->getClassProperty($reflectionClass, $member))
				: [])
			: $this->getClassAttributes($reflectionClass);
	}

	/**
	 * Instantiate a class without invoking its constructor.
	 *
	 * @param string $class Fully qualified class name.
	 * @return object Instantiated class.
	 */
	public function instantiateWithoutConstructor(string $class): object
	{
		return $this->newClassInstanceWithoutConstructor($this->createClass($class));
	}

	/**
	 * Verify if a class has a method with specific visibility.
	 *
	 * @param string $class Fully qualified class name.
	 * @param string $method Method name.
	 * @param string $visibility Visibility type ('public', 'protected', 'private').
	 * @return bool True if the method exists with specified visibility, false otherwise.
	 */
	public function methodHasVisibility(string $class, string $method, string $visibility): bool
	{
		$reflectionMethod = $this->getClassMethod($this->createClass($class), $method);
		return match ($visibility) {
			'public' => $this->isMethodPublic($reflectionMethod),
			'protected' => $this->isMethodProtected($reflectionMethod),
			'private' => $this->isMethodPrivate($reflectionMethod),
			default => false
		};
	}
}
