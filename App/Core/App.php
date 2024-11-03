<?php

namespace App\Core;

use App\Exceptions\AppException;
use App\Services\CacheService;
use App\Services\CoreService;
use App\Core\Config;
use App\Core\Database;
use App\Utilities\Managers\SettingsManager;
use Exception;

/**
 * Core application class for initializing and managing essential components.
 *
 * The `App` class acts as the backbone of the application, responsible for setting up and managing:
 * - Configuration services
 * - Database connection
 * - Settings management
 * - Caching mechanisms
 *
 * It ensures all the necessary components are properly initialized and provides a central point for running the application.
 */
class App
{
	/**
	 * @var Config $config The application's configuration service.
	 */
	public Config $config;

	/**
	 * @var CacheService $cacheService Manages the caching mechanisms of the application.
	 */
	private CacheService $cacheService;

	/**
	 * @var CoreService $coreService Registers and manages the core services of the application.
	 */
	private CoreService $coreService;

	/**
	 * @var Database $database Handles database connections and queries.
	 */
	public Database $database;

	/**
	 * @var SettingsManager $settingsManager Provides access to application settings.
	 */
	public SettingsManager $settingsManager;

	/**
	 * Constructor to initialize the core components of the application.
	 *
	 * The constructor sets up core services such as configuration, database, and settings management.
	 * It also initializes caching if the settings allow it. All services are loaded through `CoreService` and `CacheService`.
	 *
	 * @throws AppException If an error occurs during initialization.
	 */
	public function __construct()
	{
		try {
			// Initialize and register core services through CoreService
			$this->coreService = new CoreService();
			$this->coreService->registerServices();

			// Initialize core components like Config, Database, and SettingsManager
			$this->InitializeCore();

			// Initialize and register cache services through CacheService
			$this->cacheService = new CacheService();
			$this->cacheService->registerServices();

			// Initialize Cache if enabled
			$this->InitializeCache();
		} catch (Exception $e) {
			// If any error occurs during initialization, throw an AppException with a detailed message
			throw new AppException("Error initializing application: " . $e->getMessage());
		}
	}

	/**
	 * Initialize the Core Services (Config, Database, SettingsManager).
	 *
	 * This method retrieves essential services from `CoreService` and assigns them to the corresponding properties.
	 *
	 * @return void
	 * @throws AppException If an error occurs while initializing core services.
	 */
	protected function InitializeCore(): void
	{
		try {
			// Retrieve and assign Config service
			$this->config = $this->coreService->getService('Config');

			// Retrieve and assign Database service
			$this->database = $this->coreService->getService('Database');

			// Retrieve and assign SettingsManager service
			$this->settingsManager = $this->coreService->getService('SettingsManager');
		} catch (Exception $e) {
			// Handle any errors during core service initialization
			throw new AppException("Error initializing core services: " . $e->getMessage());
		}
	}

	/**
	 * Initialize the Cache Services.
	 *
	 * This method retrieves cache settings from the `SettingsManager` and initializes the appropriate cache driver
	 * if caching is enabled in the application configuration.
	 *
	 * @return void
	 * @throws AppException If an error occurs while initializing cache services.
	 */
	protected function InitializeCache(): void
	{
		try {
			// Retrieve cache settings from the SettingsManager
			$cacheSettings = $this->settingsManager->getAllSettings('cache');

			// Check if caching is enabled in the settings
			if ($cacheSettings['ENABLED'] === 'true') {
				// Get and initialize the cache driver based on the settings
				$this->cacheService->getCacheDriver($cacheSettings);
			}
		} catch (Exception $e) {
			// Handle any errors during cache initialization
			throw new AppException("Error initializing cache services: " . $e->getMessage());
		}
	}

	/**
	 * Run the application.
	 *
	 * This method contains the logic to start and run the application.
	 * It should be extended as the application grows to handle routing, request processing, and more.
	 *
	 * @return void
	 */
	public function run(): void
	{
		// Application run logic should be placed here, such as routing or event dispatching.
	}
}
