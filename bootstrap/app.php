<?php

declare(strict_types=1);

use App\Core\Bootstrap;

$basePath = realpath(dirname(__DIR__)) ?: dirname(__DIR__);
$autoload = $basePath . '/vendor/autoload.php';

if (!is_file($autoload)) {
    http_response_code(500);
    exit('Composer autoload file not found. Run "composer install" before serving the application.');
}

require_once $autoload;

return (new Bootstrap($basePath, $basePath . '/Public'))->createApplication();
