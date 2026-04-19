<?php

declare(strict_types=1);

use App\Installer\InstallerView;
use App\Installer\InstallerWizard;
use App\Providers\CoreProvider;

$basePath = realpath(dirname(__DIR__, 2)) ?: dirname(__DIR__, 2);
$publicPath = realpath(dirname(__DIR__)) ?: dirname(__DIR__);
$autoload = $basePath . '/vendor/autoload.php';

if (!is_file($autoload)) {
    http_response_code(500);
    exit('Composer autoload file not found. Run "composer install" before opening the installer.');
}

require_once $autoload;

$constants = [
    'BASE_PATH' => $basePath,
    'APP_PATH' => $basePath . '/App',
    'CONFIG_PATH' => $basePath . '/Config',
    'PUBLIC_PATH' => $publicPath,
    'STORAGE_PATH' => $basePath . '/Storage',
];

foreach ($constants as $constant => $value) {
    if (!defined($constant)) {
        define($constant, $value);
    }
}

$provider = new CoreProvider();
$provider->registerServices();
$wizard = new InstallerWizard($provider->resolveClass(\App\Utilities\Managers\FileManager::class));
$view = $provider->resolveClass(InstallerView::class);

if (!$view instanceof InstallerView) {
    throw new RuntimeException('Unable to resolve the installer view.');
}

if ($wizard->isInstalled()) {
    header('Location: /');
    exit;
}

$form = $wizard->defaults();
$result = null;
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $form = array_replace($form, array_map(
        static fn(mixed $value): string => is_string($value) ? trim($value) : (is_scalar($value) ? (string) $value : ''),
        $_POST
    ));

    try {
        $result = $wizard->install($_POST);
    } catch (Throwable $exception) {
        $errors[] = $exception->getMessage();
    }
}

echo $view->renderPage('InstallerWizard', [
    'pageTitle' => 'Install LangelerMVC',
    'form' => $form,
    'status' => $wizard->status(),
    'errors' => $errors,
    'result' => $result,
]);
