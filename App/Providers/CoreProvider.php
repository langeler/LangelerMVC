<?php

namespace App\Services;

use App\Core\Container;
use App\Core\Config;
use App\Core\Database;
use App\Core\Router;
use App\Exceptions\ContainerException;

/**
 * Core services injector for registering essential application services.
 *
 * This class registers core services like `Config`, `Database`, and `Router` into the service container,
 * making these services available throughout the application. It supports lazy loading to optimize performance.
 */
class CoreContainer extends Container
{
	/**
	 * Registers the core services and their aliases in the application's service container.
	 * It also ensures these services are loaded lazily as singletons.
	 *
	 * @return void
	 * @throws ContainerException If an error occurs during service registration.
	 */
	public function registerServices(): void
	{
		$this->wrapInTry(
			fn() => $this->registerCoreComponents(),
			"Error registering core services."
		);
	}

	/**
	 * Registers core service aliases and lazy singletons.
	 *
	 * @return void
	 */
	protected function registerCoreComponents(): void
	{
		$this->registerCoreAliases();
		$this->registerCoreSingletons();
	}

	/**
	 * Registers service aliases in the service container for core application components.
	 * These aliases provide shorthand access to the services (e.g., 'Config' for the Config class).
	 *
	 * @return void
	 */
	protected function registerCoreAliases(): void
	{
		$this->registerAlias('Config', Config::class);
		$this->registerAlias('Database', Database::class);
		$this->registerAlias('Router', Router::class);
	}

	/**
	 * Registers the core services lazily as singletons in the service container.
	 * Lazy loading ensures that these services are only instantiated when they are needed, improving performance.
	 *
	 * @return void
	 */
	protected function registerCoreSingletons(): void
	{
		$this->registerLazySingleton(Config::class, fn() => $this->resolve(Config::class));
		$this->registerLazySingleton(Database::class, fn() => $this->resolve(Database::class));
		$this->registerLazySingleton(Router::class, fn() => $this->resolve(Router::class));
	}
}
