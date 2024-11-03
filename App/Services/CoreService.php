<?php

namespace App\Services;

use App\Abstracts\Http\Service;
use App\Core\Config;
use App\Core\Database;
use App\Utilities\Managers\SettingsManager;
use Exception;

/**
 * Core services injector for registering essential application services.
 *
 * This class registers core services like `Config`, `Database`, and `SettingsManager` into the service container,
 * making these services available throughout the application. It supports lazy loading to optimize performance.
 */
class CoreService extends Service
{
	/**
	 * Registers the core services and their aliases in the application's service container.
	 * It also ensures these services are loaded lazily as singletons.
	 *
	 * @return void
	 * @throws Exception If an error occurs during service registration.
	 */
	public function registerServices(): void
	{
		try {
			// Register aliases for core services
			$this->registerCoreAliases();
			// Register core services as lazy singletons
			$this->registerCoreServices();
		} catch (Exception $e) {
			// Catch any exceptions during registration and throw a more descriptive error message.
			throw new Exception("Error registering core services: " . $e->getMessage());
		}
	}

	/**
	 * Registers service aliases in the service container for core application components.
	 * These aliases provide shorthand access to the services (e.g., 'Config' for the Config class).
	 *
	 * @return void
	 */
	protected function registerCoreAliases(): void
	{
		// Register aliases for Config, Database, and SettingsManager classes
		$this->registerAlias('Config', Config::class);
		$this->registerAlias('Database', Database::class);
		$this->registerAlias('SettingsManager', SettingsManager::class);
	}

	/**
	 * Registers the core services lazily as singletons in the service container.
	 * Lazy loading ensures that these services are only instantiated when they are needed, improving performance.
	 *
	 * @return void
	 */
	protected function registerCoreServices(): void
	{
		// Register Config as a lazy singleton
		$this->registerLazySingleton('Config', fn() => $this->resolve(Config::class));
		// Register Database as a lazy singleton
		$this->registerLazySingleton('Database', fn() => $this->resolve(Database::class));
		// Register SettingsManager as a lazy singleton
		$this->registerLazySingleton('SettingsManager', fn() => $this->resolve(SettingsManager::class));
	}
}
