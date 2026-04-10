<?php

declare(strict_types=1);

namespace App\Core;

use App\Console\ConsoleKernel;
use Dotenv\Dotenv;
use App\Providers\CoreProvider;
use App\Utilities\Traits\{
	ExistenceCheckerTrait,
	ManipulationTrait
};
use RuntimeException;

/**
 * Application bootstrapper responsible for environment setup and composition.
 *
 * Keeps front-controller concerns out of the public entrypoint by centralizing
 * path registration, runtime defaults, installer redirects, environment
 * loading, and application creation.
 */
class Bootstrap
{
    use ExistenceCheckerTrait, ManipulationTrait;

    private readonly string $basePath;
    private readonly string $publicPath;

    public function __construct(string $basePath, string $publicPath)
    {
        $this->basePath = $this->normalizePath($basePath);
        $this->publicPath = $this->normalizePath($publicPath);
    }

    /**
     * Build the configured application instance.
     */
    public function createApplication(): App
    {
        $coreProvider = $this->prepareCoreProvider();

        return $coreProvider->createApplication();
    }

    public function createConsoleKernel(): ConsoleKernel
    {
        $coreProvider = $this->prepareCoreProvider();

        return $coreProvider->createConsoleKernel();
    }

    private function prepareCoreProvider(): CoreProvider
    {
        $this->registerPaths();
        $this->configureRuntimeDefaults();
        $this->redirectToInstallerIfNeeded();
        $this->loadEnvironment();

        return new CoreProvider();
    }

    private function registerPaths(): void
    {
        $this->definePathConstant('BASE_PATH', $this->basePath);
        $this->definePathConstant('APP_PATH', $this->basePath . DIRECTORY_SEPARATOR . 'App');
        $this->definePathConstant('CONFIG_PATH', $this->basePath . DIRECTORY_SEPARATOR . 'Config');
        $this->definePathConstant('PUBLIC_PATH', $this->publicPath);
        $this->definePathConstant('STORAGE_PATH', $this->basePath . DIRECTORY_SEPARATOR . 'Storage');
    }

    private function configureRuntimeDefaults(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        ini_set('log_errors', '1');
        ini_set('default_charset', 'UTF-8');
        date_default_timezone_set('UTC');

        if ($this->isHttpContext() && $this->functionExists('header_remove')) {
            @header_remove('X-Powered-By');
        }
    }

    private function redirectToInstallerIfNeeded(): void
    {
        if (!$this->isHttpContext()) {
            return;
        }

        $installerDirectory = $this->publicPath . DIRECTORY_SEPARATOR . 'install';

        if (!is_dir($installerDirectory)) {
            return;
        }

        header('Location: ' . $this->buildInstallerUrl());
        exit;
    }

    private function loadEnvironment(): void
    {
        $environmentFile = $this->basePath . DIRECTORY_SEPARATOR . '.env';

        if (!is_file($environmentFile)) {
            return;
        }

        Dotenv::createImmutable($this->basePath)->safeLoad();
    }

    private function buildInstallerUrl(): string
    {
        $scriptDirectory = dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
        $normalizedDirectory = $this->trimString(
            $this->replaceText('\\', '/', $scriptDirectory),
            '/'
        );

        return ($normalizedDirectory !== '' ? '/' . $normalizedDirectory : '') . '/install/index.php';
    }

    private function definePathConstant(string $name, string $value): void
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    private function normalizePath(string $path): string
    {
        $normalized = realpath($path);

        if ($normalized === false) {
            throw new RuntimeException(sprintf('Bootstrap path does not exist: %s', $path));
        }

        return $normalized;
    }

    private function isHttpContext(): bool
    {
        return PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg';
    }
}
