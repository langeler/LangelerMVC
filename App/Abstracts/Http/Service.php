<?php

namespace App\Abstracts\Http;

use App\Utilities\Managers\ReflectionManager;
use Exception;

/**
 * Centralized Dependency Injector for handling all logic for services and dependencies.
 * Extended classes will focus on registering services for their specific domains.
 */
abstract class Service
{
	/**
	 * Store instantiated services.
	 */
	protected array $instances = [];

	/**
	 * Store services marked for singleton lifecycle.
	 */
	protected array $singletons = [];

	/**
	 * Map short class names to fully qualified class names.
	 */
	protected array $aliases = [];

	/**
	 * Track services that are being resolved to prevent circular dependencies.
	 */
	protected array $resolving = [];

	/**
	 * Instance of the ReflectionManager for handling reflection-related tasks.
	 */
	protected ReflectionManager $reflectionManager;

	/**
	 * Constructor to initialize the ReflectionManager.
	 */
	public function __construct()
	{
		$this->reflectionManager = new ReflectionManager();
	}

	/**
	 * Register a class or service as a singleton.
	 * Singletons are instantiated only once and reused.
	 *
	 * @param string $className The short class name of the service.
	 */
	public function registerSingleton(string $className): void
	{
		$this->singletons[$className] = true;
	}

	/**
	 * Register an alias (short name to fully qualified class name).
	 *
	 * @param string $alias The short class name.
	 * @param string $className The fully qualified class name.
	 */
	public function registerAlias(string $alias, string $className): void
	{
		$this->aliases[$alias] = $className;
	}

	/**
	 * Register a service as a lazily resolved singleton.
	 * It is instantiated only when accessed for the first time.
	 *
	 * @param string $className The short class name or fully qualified class name.
	 * @param callable $factory A callable that returns the instance.
	 */
	public function registerLazySingleton(string $className, callable $factory): void
	{
		$this->instances[$className] = $factory;
		$this->registerSingleton($className);
	}

	/**
	 * Retrieve a service by class name.
	 *
	 * @param string $className The short or fully qualified class name of the service.
	 * @return object The resolved service instance.
	 * @throws Exception If the service could not be resolved.
	 */
	public function getService(string $className): object
	{
		try {
			return $this->resolve($className);
		} catch (Exception $e) {
			throw new Exception("Failed to get service [$className]: " . $e->getMessage());
		}
	}

	/**
	 * Resolve and instantiate a class along with its dependencies.
	 *
	 * @param string $className The short class name or fully qualified class name to resolve.
	 * @return object The instantiated class with its dependencies.
	 * @throws Exception If the class cannot be instantiated.
	 */
	protected function resolve(string $className): object
	{
		// Check for circular dependencies
		if (isset($this->resolving[$className])) {
			throw new Exception("Circular dependency detected while resolving [$className].");
		}

		$this->resolving[$className] = true;

		try {
			// Resolve alias if exists
			$className = $this->resolveAlias($className);

			// Check for existing singleton instance
			if ($this->hasSingletonInstance($className)) {
				return $this->resolveSingletonInstance($className);
			}

			// Use ReflectionManager to resolve class dependencies
			$instance = $this->createClassInstance($className);

			// Cache the instance if it’s a singleton
			if ($this->isSingleton($className)) {
				$this->instances[$className] = $instance;
			}

			return $instance;
		} catch (Exception $e) {
			throw new Exception("Failed to resolve class [$className]: " . $e->getMessage());
		} finally {
			unset($this->resolving[$className]);
		}
	}

	/**
	 * Resolve constructor dependencies of a class using ReflectionManager.
	 *
	 * @param array $parameters The constructor parameters.
	 * @return array Resolved dependencies.
	 * @throws Exception If dependencies cannot be resolved.
	 */
	protected function resolveDependencies(array $parameters): array
	{
		$dependencies = [];

		foreach ($parameters as $param) {
			try {
				$dependencies[] = $this->resolveParameter($param);
			} catch (Exception $e) {
				throw new Exception("Failed to resolve parameter [{$param->getName()}]: " . $e->getMessage());
			}
		}

		return $dependencies;
	}

	/**
	 * Resolve a single parameter using the ReflectionManager.
	 */
	protected function resolveParameter(\ReflectionParameter $param)
	{
		$type = $this->reflectionManager->getParameterType($param);

		if ($type && !$type->isBuiltin()) {
			return $this->resolve($type->getName());
		}

		if ($param->isOptional()) {
			return $param->getDefaultValue();
		}

		throw new Exception("Cannot resolve primitive dependency for parameter [{$param->getName()}].");
	}

	/**
	 * Resolve alias for class name if exists.
	 */
	protected function resolveAlias(string $className): string
	{
		return $this->aliases[$className] ?? $className;
	}

	/**
	 * Check if class is marked as a singleton.
	 */
	protected function isSingleton(string $className): bool
	{
		return isset($this->singletons[$className]);
	}

	/**
	 * Check if the class has an existing singleton instance.
	 */
	protected function hasSingletonInstance(string $className): bool
	{
		return isset($this->instances[$className]);
	}

	/**
	 * Resolve an existing singleton instance.
	 */
	protected function resolveSingletonInstance(string $className): object
	{
		$instance = $this->instances[$className];

		if (is_callable($instance)) {
			$instance = $instance();
			$this->instances[$className] = $instance;
		}

		return $instance;
	}

	/**
	 * Create a class instance using ReflectionManager, resolving its dependencies.
	 */
	protected function createClassInstance(string $className): object
	{
		$reflectionClass = $this->reflectionManager->getClassInfo($className);

		if (!$reflectionClass->isInstantiable()) {
			throw new Exception("Class [$className] cannot be instantiated.");
		}

		$constructor = $reflectionClass->getConstructor();
		if ($constructor) {
			$dependencies = $this->resolveDependencies($constructor->getParameters());
			return $this->reflectionManager->createInstanceWithArgs($reflectionClass, $dependencies);
		}

		return $this->reflectionManager->createInstanceWithoutConstructor($reflectionClass);
	}

	/**
	 * Abstract method for extended classes to register specific services.
	 */
	abstract public function registerServices(): void;
}
