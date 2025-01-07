<?php

namespace App\Utilities\Traits\Reflection;

use ReflectionMethod;
use ReflectionClass;
use Closure;;

/**
 * Trait ReflectionMethodTrait
 *
 * Covers ReflectionMethod methods.
 */
trait ReflectionMethodTrait
{
	// Reflection Method Methods

	/**
	 * Get the dynamically created closure for the method.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @param object|null $object The object for instance methods, or null for static methods.
	 * @return Closure The dynamically created closure.
	 */
	public function getMethodClosure(ReflectionMethod $reflectionMethod, ?object $object = null): Closure
	{
		return $reflectionMethod->getClosure($object);
	}

	/**
	 * Get the declaring class of the reflected method.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return ReflectionClass The declaring class.
	 */
	public function getMethodDeclaringClass(ReflectionMethod $reflectionMethod): ReflectionClass
	{
		return $reflectionMethod->getDeclaringClass();
	}

	/**
	 * Get the modifiers of the method as a bitmask.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return int The modifiers bitmask.
	 */
	public function getMethodModifiers(ReflectionMethod $reflectionMethod): int
	{
		return $reflectionMethod->getModifiers();
	}

	/**
	 * Get the prototype of the method.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return ReflectionMethod The method prototype.
	 */
	public function getMethodPrototype(ReflectionMethod $reflectionMethod): ReflectionMethod
	{
		return $reflectionMethod->getPrototype();
	}

	/**
	 * Check if the method has a prototype.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return bool True if the method has a prototype, false otherwise.
	 */
	public function methodHasPrototype(ReflectionMethod $reflectionMethod): bool
	{
		return $reflectionMethod->hasPrototype();
	}

	/**
	 * Invoke the method.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @param object|null $object The object for instance methods, or null for static methods.
	 * @param mixed ...$args The arguments to pass to the method.
	 * @return mixed The result of the method invocation.
	 */
	public function invokeMethod(ReflectionMethod $reflectionMethod, ?object $object = null, mixed ...$args): mixed
	{
		return $reflectionMethod->invoke($object, ...$args);
	}

	/**
	 * Invoke the method with arguments as an array.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @param object|null $object The object for instance methods, or null for static methods.
	 * @param array $args The arguments to pass to the method.
	 * @return mixed The result of the method invocation.
	 */
	public function invokeMethodArgs(ReflectionMethod $reflectionMethod, ?object $object = null, array $args): mixed
	{
		return $reflectionMethod->invokeArgs($object, $args);
	}

	/**
	 * Check if the method is abstract.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return bool True if the method is abstract, false otherwise.
	 */
	public function isMethodAbstract(ReflectionMethod $reflectionMethod): bool
	{
		return $reflectionMethod->isAbstract();
	}

	/**
	 * Check if the method is a constructor.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return bool True if the method is a constructor, false otherwise.
	 */
	public function isConstructorMethod(ReflectionMethod $reflectionMethod): bool
	{
		return $reflectionMethod->isConstructor();
	}

	/**
	 * Check if the method is a destructor.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return bool True if the method is a destructor, false otherwise.
	 */
	public function isDestructorMethod(ReflectionMethod $reflectionMethod): bool
	{
		return $reflectionMethod->isDestructor();
	}

	/**
	 * Check if the method is final.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return bool True if the method is final, false otherwise.
	 */
	public function isMethodFinal(ReflectionMethod $reflectionMethod): bool
	{
		return $reflectionMethod->isFinal();
	}

	/**
	 * Check if the method is private.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return bool True if the method is private, false otherwise.
	 */
	public function isMethodPrivate(ReflectionMethod $reflectionMethod): bool
	{
		return $reflectionMethod->isPrivate();
	}

	/**
	 * Check if the method is protected.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return bool True if the method is protected, false otherwise.
	 */
	public function isMethodProtected(ReflectionMethod $reflectionMethod): bool
	{
		return $reflectionMethod->isProtected();
	}

	/**
	 * Check if the method is public.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return bool True if the method is public, false otherwise.
	 */
	public function isMethodPublic(ReflectionMethod $reflectionMethod): bool
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
	public function setMethodAccessible(ReflectionMethod $reflectionMethod, bool $accessible): void
	{
		$reflectionMethod->setAccessible($accessible);
	}

	/**
	 * Get the string representation of the ReflectionMethod.
	 *
	 * @param ReflectionMethod $reflectionMethod The ReflectionMethod instance.
	 * @return string The string representation of the method.
	 */
	public function methodToString(ReflectionMethod $reflectionMethod): string
	{
		return $reflectionMethod->__toString();
	}
}
