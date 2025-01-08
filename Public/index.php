<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use App\Services\CoreProvider;
use App\Core\App;

// Define application directories
define('BASE_PATH', realpath(dirname(__DIR__)));
define('PUBLIC_PATH', realpath(__DIR__));

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Redirect to installation script if the install directory exists
if (is_dir(BASE_PATH . '/public/install')) {
    header('Location: /install/index.php');
    exit;
}

// Autoload dependencies or terminate with an error message
if (!file_exists($autoload = BASE_PATH . '/autoload.php')) {
    exit('Autoload file not found. Ensure Composer dependencies are installed.');
}
require_once $autoload;

// Load environment variables using Dotenv
Dotenv::createImmutable(BASE_PATH)->load();

// Initialize CoreProvider, register services, and resolve ErrorManager
$coreProvider = new CoreProvider();
$coreProvider->registerServices();

// Initialize and run the App
(new App(
    coreProvider: $coreProvider,
    errorManager: $coreProvider->getCoreService('errorManager')
))->run();
