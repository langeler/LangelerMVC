<?php

namespace App\Services;

use App\Abstracts\Http\Service;
use App\Core\Database;  // Import the Database class
use App\Core\Cache;     // Import the Cache class
use App\Core\Config;    // Import the Config class

/**
 * Core services injector for registering core application services.
 */
class CoreService extends Service
{
	/**
	 * Register core services into the container.
	 */
	public function registerServices(): void
	{
		// Register aliases first to avoid resolving dependencies prematurely
		$this->registerAlias('Cache', Cache::class);
		$this->registerAlias('Config', Config::class);
		$this->registerAlias('Database', Database::class);

		// Lazy register Cache as a singleton
		$this->registerLazySingleton('Cache', function () {
			// Just like Database and Config, resolve dependencies and return a Cache instance
			return $this->resolve(Cache::class);
		});

		// Lazy register Config as a singleton
		$this->registerLazySingleton('Config', function () {
			return $this->resolve(Config::class);
		});

		// Lazy register Database as a singleton
		$this->registerLazySingleton('Database', function () {
			return $this->resolve(Database::class);  // Resolve the Database class
		});
	}
}
