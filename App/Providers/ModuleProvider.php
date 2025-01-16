<?php

namespace App\Providers;

use App\Core\Container;
use App\Exceptions\ContainerException;

/**
 * ModuleProvider Class
 *
 * Manages module-related services and dependencies.
 * Populates module map via a dedicated method called by ModuleManager.
 */
class ModuleProvider extends Container
{
	/**
	 * Map of module aliases to their class namespaces.
	 *
	 * @var array<string, string>
	 */
	protected array $moduleMap = [];

	/**
	 * Populates the module map dynamically.
	 *
	 * @param array $classes Array of classes in the format [namespace, className].
	 * @return void
	 * @throws ContainerException If the module map cannot be populated.
	 */
	public function populate(array $classes): void
	{
		$this->wrapInTry(
			fn() => $this->moduleMap = $this->reduce(
				$classes,
				fn($acc, $class) => $this->merge($acc, [$class[1] => $class[0]]),
				[]
			),
			new ContainerException("Failed to populate module map.")
		);
	}

	/**
	 * Registers module-related services in the container.
	 *
	 * Dynamically maps and lazily loads resolved module classes.
	 *
	 * @return void
	 * @throws ContainerException If an error occurs during module registration.
	 */
	public function registerServices(): void
	{
		$this->wrapInTry(
			fn() => $this->walk(
				$this->moduleMap,
				fn($class, $alias) => [
					$this->registerAlias($alias, $class),
					$this->registerLazy($class, fn() => $this->processInstance($class))
				]
			),
			new ContainerException("Error registering module services.")
		);
	}

	/**
	 * Resolves and returns a module class instance by its alias.
	 *
	 * @param string $alias The alias of the module class.
	 * @return object The resolved module instance.
	 * @throws ContainerException If the module class cannot be resolved.
	 */
	public function getModule(string $alias): object
	{
		return $this->wrapInTry(
			fn() => $this->getInstance($this->moduleMap[$alias]),
			new ContainerException("Module [$alias] could not be resolved.")
		);
	}
}
