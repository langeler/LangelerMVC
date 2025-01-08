<?php

namespace App\Services;

use App\Core\{¨
    Config,
    Container,
    Database,
    Router
};
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

    /**
     * Constructor for CoreProvider.
     *
     * Initializes the core service map to manage core services and their aliases.
     */
    public function __construct()
    {
        $this->coreServiceMap = [
            'config'   => Config::class,
            'database' => Database::class,
            'router'   => Router::class,

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
        $this->wrapInTry(
            fn() => (!$this->isArray($this->coreServiceMap) || $this->isEmpty($this->coreServiceMap))
                ? throw new ContainerException("The core service map must be a non-empty array.")
                : $this->walk(
                    $this->coreServiceMap,
                    fn($class, $alias) => [
                        $this->registerAlias($alias, $class),
                        $this->registerLazy($class, fn() => $this->resolveInstance($class))
                    ]
                ),
            new ContainerException("Error registering core services.")
        );
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
        return $this->wrapInTry(
            fn() => $this->getInstance(
                $this->coreServiceMap[$serviceAlias]
                    ?? throw new ContainerException("Unsupported core service alias: $serviceAlias")
            ),
            new ContainerException("Error retrieving core service [$serviceAlias].")
        );
    }
}
