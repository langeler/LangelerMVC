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
		// Store the factory method for lazy resolution
		$this->instances[$className] = $factory;
		$this->registerSingleton($className);
	}

	/**
	 * Resolve and instantiate a class along with its dependencies.
	 *
	 * @param string $className The short class name or fully qualified class name to resolve.
	 * @return object The instantiated class with its dependencies.
	 * @throws Exception If the class cannot be instantiated.
	 */
	protected function resolve(string $className)
	{
		// Check for circular dependencies
		if (isset($this->resolving[$className])) {
			throw new Exception("Circular dependency detected while resolving [$className].");
		}

		// Mark this class as being resolved
		$this->resolving[$className] = true;

		// Check if the class is an alias (short name)
		if (isset($this->aliases[$className])) {
			$className = $this->aliases[$className];
		}

		// Check if the class is already instantiated (for singletons)
		if (isset($this->instances[$className])) {
			$instance = $this->instances[$className];

			// If it's a lazy singleton (a closure), invoke the factory method to get the instance
			if (is_callable($instance)) {
				$instance = $instance();
				$this->instances[$className] = $instance; // Cache the resolved instance
			}

			unset($this->resolving[$className]);
			return $instance;
		}

		// Use ReflectionManager to get ReflectionClass for the given class
		$reflectionClass = $this->reflectionManager->getClassInfo($className);

		if (!$reflectionClass->isInstantiable()) {
			unset($this->resolving[$className]);
			throw new Exception("Class [$className] cannot be instantiated.");
		}

		// Check if the class has a constructor
		$constructor = $reflectionClass->getConstructor();

		if ($constructor) {
			// Resolve dependencies using ReflectionManager
			$dependencies = $this->resolveDependencies($constructor->getParameters());
			// Create an instance with arguments using ReflectionManager
			$instance = $this->reflectionManager->createInstanceWithArgs($reflectionClass, $dependencies);
		} else {
			// Create an instance without constructor using ReflectionManager
			$instance = $this->reflectionManager->createInstanceWithoutConstructor($reflectionClass);
		}

		// Store the instance for future use (if it's a singleton)
		if (isset($this->singletons[$className])) {
			$this->instances[$className] = $instance;
		}

		unset($this->resolving[$className]);

		return $instance;
	}

	/**
	 * Resolve the constructor dependencies of a class using ReflectionManager.
	 *
	 * @param array $parameters The constructor parameters.
	 * @return array Resolved dependencies.
	 * @throws Exception If dependencies cannot be resolved.
	 */
	protected function resolveDependencies(array $parameters): array
	{
		$dependencies = [];

		foreach ($parameters as $param) {
			// Get parameter type via the ReflectionManager
			$type = $this->reflectionManager->getParameterType($param);

			if ($type && !$type->isBuiltin()) {
				// Resolve class dependencies by type name
				$dependencyClass = new \ReflectionClass($type->getName());
				$dependencies[] = $this->resolve($dependencyClass->getName());
			} elseif ($param->isOptional()) {
				// Handle primitive types (optional values)
				$dependencies[] = $param->getDefaultValue();
			} else {
				throw new Exception("Cannot resolve primitive dependency for parameter {$param->getName()}.");
			}
		}

		return $dependencies;
	}

	/**
	 * Abstract method for extended classes to register specific services.
	 */
	abstract public function registerServices(): void;

	/**
	 * Retrieve a service by class name.
	 *
	 * @param string $className The short or fully qualified class name of the service.
	 * @return object The resolved service instance.
	 */
	public function getService(string $className): object
	{
		return $this->resolve($className);
	}
}
