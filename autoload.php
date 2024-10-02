<?php

/**
 * Autoload handler.
 *
 * This file checks if Composer's autoloader is available. If available, it uses Composer's autoloader.
 * If not, it falls back to a custom autoloader defined in the App class.
 */

// Define the path to Composer's autoload file
$composerAutoload = BASE_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

/**
 * Fallback: Register a simple PSR-4 compliant autoloader if Composer is not available.
 */
function registerCustomAutoloader() {
	spl_autoload_register(function ($class) {
		$file = BASE_PATH . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
		if (file_exists($file)) {
			require_once $file;
		} else {
			throw new Exception("Class $class not found in $file.");
		}
	});
}

/**
 * Try to load Composer's autoloader, fallback to custom autoloader if unavailable.
 */
try {
	if (file_exists($composerAutoload)) {
		require_once $composerAutoload;
	} else {
		throw new Exception("Composer autoload not found. Falling back to custom autoloader.");
	}
} catch (Exception $e) {
	registerCustomAutoloader();
}
