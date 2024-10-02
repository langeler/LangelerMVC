<?php

// Define the base directory of the application
define('BASE_PATH', realpath(dirname(__DIR__)));
define('PUBLIC_PATH', realpath(__DIR__));

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect to installation if the 'install' directory exists
$installDir = BASE_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'install';
if (is_dir($installDir)) {
    header('Location: /install/index.php');
    exit;
}

// Define the path to the autoload file
$autoloadFile = BASE_PATH . DIRECTORY_SEPARATOR . 'autoload.php';

// Validate and include the autoload file (Composer or fallback)
if (file_exists($autoloadFile)) {
    require_once $autoloadFile;
} else {
    exit('Autoload file not found. Ensure Composer dependencies are installed.');
}

// Import Dotenv and initialize the environment
use Dotenv\Dotenv;

// Initialize Dotenv and load the .env file
$dotenv = Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// Use the App class from the core namespace
use App\Core\App;

// Start the application
$app = new App();
$app->run();
