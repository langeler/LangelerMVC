<?php

declare(strict_types=1);

use App\Core\Bootstrap;

$basePath = realpath(dirname(__DIR__)) ?: dirname(__DIR__);
$autoload = $basePath . '/vendor/autoload.php';

if (!is_file($autoload)) {
    fwrite(STDERR, "Composer autoload file not found. Run \"composer install\" before using the console.\n");
    exit(1);
}

require_once $autoload;

return (new Bootstrap($basePath, $basePath . '/Public'))->createConsoleKernel();
