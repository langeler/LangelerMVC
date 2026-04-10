<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\{
    App,
    MigrationRunner,
    Config,
    Container,
    Database,
    Router,
    SeedRunner,
    Session
};
use App\Console\ConsoleKernel;
use App\Exceptions\ContainerException;
use App\Utilities\Managers\System\ErrorManager;

/**
 * CoreProvider Class
 *
 * Manages the registration and resolution of essential core application services such as `Config`, `Database`, and `Router`.
 * It supports lazy loading and ensures singleton instances for efficient service usage throughout the application.
 *
 * Features:
 * - Dynamic mapping of core service aliases to their fully qualified class names.
 * - Lazy loading of core services to optimize application performance.
 * - Centralized service management for seamless dependency injection.
 *
 * @package App\Services
 */
class CoreProvider extends Container
{
    /**
     * A mapping of core service aliases to their fully qualified class names.
     *
     * @var array<string, string>
     */
    protected readonly array $coreServiceMap;
    private bool $servicesRegistered = false;

    /**
     * Constructor for CoreProvider.
     *
     * Initializes the core service map to manage core services and their aliases.
     */
    public function __construct()
    {
        parent::__construct();

        $this->coreServiceMap = [
            'app'      => App::class,
            'console'  => ConsoleKernel::class,
            'config'   => Config::class,
            'database' => Database::class,
            'migrationRunner' => MigrationRunner::class,
            'router'   => Router::class,
            'seedRunner' => SeedRunner::class,
            'session'  => Session::class,

            // System
            'errorManager' => ErrorManager::class,
        ];
    }

    /**
     * Registers the core services in the service container.
     *
     * Maps core service aliases to their respective fully qualified class names and registers them
     * as lazy singletons for efficient usage.
     *
     * @return void
     * @throws ContainerException If an error occurs during service registration.
     */
    public function registerServices(): void
    {
        if ($this->servicesRegistered) {
            return;
        }

        $this->wrapInTry(
            function (): void {
                if (!$this->isArray($this->coreServiceMap) || $this->isEmpty($this->coreServiceMap)) {
                    throw new ContainerException("The core service map must be a non-empty array.");
                }

                foreach ($this->coreServiceMap as $alias => $class) {
                    $this->registerAlias($alias, $class);
                    $this->registerLazy($class, fn() => $this->registerInstance($class));
                }

                $this->servicesRegistered = true;
            },
            new ContainerException("Error registering core services.")
        );
    }

    /**
     * Creates the application instance after ensuring core services are registered.
     */
    public function createApplication(): App
    {
        $this->registerServices();

        $errorManager = $this->getCoreService('errorManager');

        if (!$errorManager instanceof ErrorManager) {
            throw new ContainerException('Failed to resolve the core error manager.');
        }

        return new App($this, $errorManager);
    }

    public function createConsoleKernel(): ConsoleKernel
    {
        $this->registerServices();

        $kernel = $this->getCoreService('console');

        if (!$kernel instanceof ConsoleKernel) {
            throw new ContainerException('Failed to resolve the console kernel.');
        }

        return $kernel;
    }

    /**
     * Resolves a core service instance based on its alias or class name.
     *
     * @param string $serviceAlias The service alias or class name.
     * @return object The resolved core service instance.
     * @throws ContainerException If the specified service is not supported or another error occurs.
     */
    public function getCoreService(string $serviceAlias): object
    {
        if ($serviceAlias === 'container') {
            return $this;
        }

        return $this->wrapInTry(
            fn() => $this->getInstance(
                $this->coreServiceMap[$serviceAlias]
                    ?? throw new ContainerException("Unsupported core service alias: $serviceAlias")
            ),
            new ContainerException("Error retrieving core service [$serviceAlias].")
        );
    }
}
