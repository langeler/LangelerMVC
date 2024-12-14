<?php

namespace App\Core;

use App\Exceptions\AppException;
use App\Providers\CoreProvider;
use App\Core\Config;
use App\Core\Database;
use App\Core\Router;
use Throwable;

/**
 * Core application class for initializing and managing essential components.
 *
 * The `App` class acts as the backbone of the application, responsible for setting up and managing:
 * - Configuration services
 * - Database connection
 * - Routing
 *
 * It ensures all the necessary components are properly initialized and provides a central point for running the application.
 */
class App
{
	/**
	 * Constructor to initialize the core components of the application.
	 *
	 * @param CoreProvider $coreProvider The provider for core services.
	 * @throws AppException If an error occurs during initialization.
	 */
	public function __construct(
		protected CoreProvider $coreProvider,
		public Config $config = null,
		public Database $database = null,
		public Router $router = null
	) {
		$this->wrapInTry(
			fn() => $this->initializeCore(),
			"Error initializing application."
		);
	}

	/**
	 * Initialize the core services.
	 *
	 * Retrieves essential services from `CoreProvider` and assigns them to the corresponding properties.
	 *
	 * @return void
	 * @throws AppException If an error occurs while initializing core services.
	 */
	protected function initializeCore(): void
	{
		$this->config = $this->wrapInTry(
			fn() => $this->coreProvider->getService('Config'),
			"Failed to initialize Config service."
		);

		$this->database = $this->wrapInTry(
			fn() => $this->coreProvider->getService('Database'),
			"Failed to initialize Database service."
		);

		$this->router = $this->wrapInTry(
			fn() => $this->coreProvider->getService('Router'),
			"Failed to initialize Router service."
		);
	}

	/**
	 * Run the application.
	 *
	 * Contains the logic to start and run the application.
	 * Handles routing, request processing, and other core tasks.
	 *
	 * @return void
	 */
	public function run(): void
	{
		$this->wrapInTry(
			fn() => $this->router->dispatch(),
			"Error running the application."
		);
	}

	/**
	 * Wrapper for consistent error handling.
	 *
	 * Executes a callback within a try/catch block and throws an `AppException` on failure.
	 *
	 * @param callable $callback The callback to execute.
	 * @param string $errorMessage The error message to include in the exception.
	 * @return mixed The result of the callback execution.
	 * @throws AppException If an exception occurs during execution.
	 */
	protected function wrapInTry(callable $callback, string $errorMessage): mixed
	{
		try {
			return $callback();
		} catch (Throwable $e) {
			throw new AppException($errorMessage, 0, $e);
		}
	}
}
