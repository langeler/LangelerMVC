<?php

namespace App\Core;

use App\Exceptions\ContainerException;
use App\Utilities\Managers\ReflectionManager;
use App\Utilities\Traits\TypeCheckerTrait;

/**
 * Centralized Dependency Injector for handling all logic for services and dependencies.
 * Extended classes will focus on registering services for their specific domains.
 */
abstract class Container
{
	use TypeCheckerTrait;

	/**
	 * Constructor to initialize dependencies and properties.
	 */
	public function __construct(
		protected ReflectionManager $reflectionManager,
		protected array $instances = [],
		protected array $singletons = [],
		protected array $aliases = [],
		protected array $resolving = []
	) {}

	/**
	 * Register a class or service as a singleton.
	 */
	public function registerSingleton(string $className): void
	{
		$this->singletons[$className] = true;
	}

	/**
	 * Register an alias (short name to fully qualified class name).
	 */
	public function registerAlias(string $alias, string $className): void
	{
		$this->aliases[$alias] = $className;
	}

	/**
	 * Register a service as a lazily resolved singleton.
	 */
	public function registerLazySingleton(string $className, callable $factory): void
	{
		$this->instances[$className] = $factory;
		$this->registerSingleton($className);
	}

	/**
	 * Retrieve a service by class name.
	 */
	public function getService(string $className): object
	{
		return $this->wrapInTry(
			fn() => $this->resolve($className),
			"Failed to retrieve service [$className]."
		);
	}

	/**
	 * Resolve and instantiate a class with dependencies.
	 */
	protected function resolve(string $className): object
	{
		$this->guardAgainstCircularDependency($className);

		return $this->wrapInTry(
			fn() => $this->resolveInstance($className),
			"Failed to resolve class [$className]."
		);
	}

	/**
	 * Guard against circular dependencies.
	 */
	protected function guardAgainstCircularDependency(string $className): void
	{
		$this->wrapInTry(
			fn() => $this->isSet($this->resolving[$className])
				? throw new ContainerException("Circular dependency detected while resolving [$className].")
				: $this->resolving[$className] = true,
			"Error while guarding against circular dependency for [$className]."
		);
	}

	/**
	 * Resolve the actual instance of the class.
	 */
	protected function resolveInstance(string $className): object
	{
		return $this->hasSingletonInstance($className)
			? $this->resolveSingletonInstance($className)
			: $this->createAndCacheInstance($this->resolveAlias($className));
	}

	/**
	 * Create and cache a new instance of the class.
	 */
	protected function createAndCacheInstance(string $className): object
	{
		return $this->isSingleton($className)
			? $this->cacheSingletonInstance($className)
			: $this->createClassInstance($className);
	}

	/**
	 * Cache and return a singleton instance.
	 */
	protected function cacheSingletonInstance(string $className): object
	{
		return $this->instances[$className] = $this->createClassInstance($className);
	}

	/**
	 * Resolve dependencies using ReflectionManager.
	 */
	protected function resolveDependencies(array $parameters): array
	{
		return array_map(
			fn($param) => $this->wrapInTry(
				fn() => $this->resolveParameter($param),
				"Failed to resolve parameter [{$param->getName()}]."
			),
			$parameters
		);
	}

	/**
	 * Resolve a single parameter using ReflectionManager.
	 */
	protected function resolveParameter(\ReflectionParameter $param): mixed
	{
		return $this->reflectionManager->getParameterType($param)?->isBuiltin()
			? $this->getDefaultValueOrThrow($param)
			: $this->resolve($this->reflectionManager->getParameterType($param)->getName());
	}

	/**
	 * Get default value or throw an exception if not available.
	 */
	protected function getDefaultValueOrThrow(\ReflectionParameter $param): mixed
	{
		return $param->isOptional()
			? $param->getDefaultValue()
			: throw new ContainerException("Cannot resolve primitive dependency for parameter [{$param->getName()}].");
	}

	/**
	 * Resolve alias for a class name.
	 */
	protected function resolveAlias(string $className): string
	{
		return $this->aliases[$className] ?? $className;
	}

	/**
	 * Check if a class is marked as a singleton.
	 */
	protected function isSingleton(string $className): bool
	{
		return $this->isSet($this->singletons[$className]);
	}

	/**
	 * Check if the class has an existing singleton instance.
	 */
	protected function hasSingletonInstance(string $className): bool
	{
		return $this->isSet($this->instances[$className]);
	}

	/**
	 * Resolve an existing singleton instance.
	 */
	protected function resolveSingletonInstance(string $className): object
	{
		return $this->wrapInTry(
			fn() => $this->isCallable($this->instances[$className])
				? $this->instances[$className] = ($this->instances[$className])()
				: $this->instances[$className],
			"Failed to resolve singleton instance for [$className]."
		);
	}

	/**
	 * Create a class instance using ReflectionManager, resolving its dependencies.
	 */
	protected function createClassInstance(string $className): object
	{
		return $this->wrapInTry(
			fn() => $this->instantiateClass($className),
			"Failed to create instance for class [$className]."
		);
	}

	/**
	 * Instantiate a class with resolved dependencies.
	 */
	protected function instantiateClass(string $className): object
	{
		return $this->reflectionManager->getClassInfo($className)->getConstructor()
			? $this->reflectionManager->createInstanceWithArgs(
				$this->reflectionManager->getClassInfo($className),
				$this->resolveDependencies(
					$this->reflectionManager->getClassInfo($className)->getConstructor()->getParameters()
				)
			)
			: $this->reflectionManager->createInstanceWithoutConstructor($this->reflectionManager->getClassInfo($className));
	}

	/**
	 * Wrapper for consistent error handling.
	 */
	protected function wrapInTry(callable $callback, string $errorMessage): mixed
	{
		try {
			return $callback();
		} catch (\Throwable $e) {
			throw new ContainerException($errorMessage, $e);
		}
	}

	/**
	 * Abstract method for extended classes to register specific services.
	 */
	abstract public function registerServices(): void;
}
