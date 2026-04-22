<?php

declare(strict_types=1);

use App\Installer\InstallerView;
use App\Installer\InstallerWizard;
use App\Providers\CoreProvider;
use App\Utilities\Managers\Security\HttpSecurityManager;

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
$httpSecurity = $provider->resolveClass(HttpSecurityManager::class);

if ($httpSecurity instanceof HttpSecurityManager && !headers_sent()) {
    $isSecureRequest = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';

    foreach ($httpSecurity->defaultHeaders($isSecureRequest) as $name => $value) {
        header($name . ': ' . $value, true);
    }
}

if (!$view instanceof InstallerView) {
    throw new RuntimeException('Unable to resolve the installer view.');
}

if ($wizard->isInstalled()) {
    header('Location: /');
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('langelermvc_installer');
    session_start();
}

$csrfToken = (string) ($_SESSION['installer_csrf_token'] ?? '');

if ($csrfToken === '') {
    $csrfToken = bin2hex(random_bytes(32));
    $_SESSION['installer_csrf_token'] = $csrfToken;
}

$form = $wizard->defaults();
$result = null;
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $submittedToken = trim((string) ($_POST['_token'] ?? ''));

    if ($submittedToken === '' || !hash_equals($csrfToken, $submittedToken)) {
        http_response_code(419);
        $errors[] = 'The installer security token expired or was missing. Refresh the page and try again.';
    } else {
    $form = array_replace($form, array_map(
        static fn(mixed $value): string => is_string($value) ? trim($value) : (is_scalar($value) ? (string) $value : ''),
        $_POST
    ));

        try {
            $result = $wizard->install($_POST);
            $csrfToken = bin2hex(random_bytes(32));
            $_SESSION['installer_csrf_token'] = $csrfToken;
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }
    }
}

echo $view->renderPage('InstallerWizard', [
    'pageTitle' => 'Install LangelerMVC',
    'csrfToken' => $csrfToken,
    'form' => $form,
    'status' => $wizard->status(),
    'errors' => $errors,
    'result' => $result,
]);
