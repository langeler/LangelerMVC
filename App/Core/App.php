<?php

namespace App\Core;

use App\Core\Cache;
use App\Core\Config;
use App\Services\CoreService;
use App\Core\Database;  // Add Database class

/**
 * Class App
 *
 * Core application class to initialize and manage routing, configuration, and caching.
 */
class App
{
	private Cache $cache;
	private Config $config;
	private CoreService $coreService;
	private Database $database;  // Add Database instance

	/**
	 * Constructor to initialize the core components.
	 */
	public function __construct()
	{
		// Initialize CoreService and register services
		$this->coreService = new CoreService();
		$this->coreService->registerServices();

		// Retrieve Cache from CoreService (as a singleton)
		$this->cache = $this->coreService->getService('Cache');

		// Retrieve Config from CoreService using short names
		$this->config = $this->coreService->getService('Config');

		// Retrieve Database from CoreService using the alias
		$this->database = $this->coreService->getService('Database');  // Now using alias 'Database'
	}


	/**
	 * Run the application.
	 */
	public function run(): void
	{
		// Application logic can go here
	}
}
