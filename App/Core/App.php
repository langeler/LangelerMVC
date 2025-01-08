<?php

namespace App\Core;

use App\Providers\CoreProvider;
use App\Utilities\Traits\ErrorTrait;

/**
 * Core application class for managing essential components.
 *
 * This class initializes the application, ensuring that required core services
 * are resolved and validated. It leverages the CoreProvider to manage services and
 * uses ErrorManager for centralized error handling and logging.
 *
 * @package App\Core
 */
class App
{
    use ErrorTrait; // Provides wrapInTry for consistent error handling.

    /**
     * Constructor for the App class.
     *
     * Resolves and validates essential core services: Config, Database, and Router.
     * Any failure during resolution is handled and transformed into appropriate exceptions.
     *
     * @param CoreProvider $coreProvider Handles registration and resolution of core services.
     * @param ErrorManager $errorManager Manages error logs and exception transformations.
     */
    public function __construct(
        protected CoreProvider $coreProvider,
        protected ErrorManager $errorManager
    ) {
        // Log successful initialization of the application.
        $this->errorManager->logError("App successfully initialized.", 'userNotice');
    }

    /**
     * Resolves and validates required core services: Config, Database, and Router.
     *
     * Each service is resolved individually using the CoreProvider. Any failure during
     * resolution is transformed into an appropriate exception using ErrorManager.
     *
     * @return void
     * @throws \Exception If any required service cannot be resolved.
     */
    protected function resolveRequiredServices(): void
    {
        $this->wrapInTry(
            fn() => $this->coreProvider->getCoreService('config'),
            fn($caught) => $this->errorManager->resolveException(
                'app',
                "Failed to resolve Config service: " . $caught->getMessage(),
                $caught->getCode(),
                $caught
            )
        );

        $this->wrapInTry(
            fn() => $this->coreProvider->getCoreService('database'),
            fn($caught) => $this->errorManager->resolveException(
                'app',
                "Failed to resolve Database service: " . $caught->getMessage(),
                $caught->getCode(),
                $caught
            )
        );

        $this->wrapInTry(
            fn() => $this->coreProvider->getCoreService('router'),
            fn($caught) => $this->errorManager->resolveException(
                'app',
                "Failed to resolve Router service: " . $caught->getMessage(),
                $caught->getCode(),
                $caught
            )
        );
    }

    /**
     * Runs the application by dispatching a request via the Router.
     *
     * Transforms any errors during dispatch into "app" exceptions and logs them.
     *
     * @return void
     */
    public function run(): void
    {
        // Log the start of the application run.
        $this->errorManager->logError("App run invoked.", 'userNotice');

        $this->wrapInTry(
            fn() => $this->coreProvider->getCoreService('router')->dispatch(
                $_SERVER['REQUEST_URI']   ?? '/',
                $_SERVER['REQUEST_METHOD'] ?? 'GET'
            ),
            fn($caught) => $this->errorManager->resolveException(
                'app',
                "Application run failed: " . $caught->getMessage(),
                $caught->getCode(),
                $caught
            )
        );

        // Log successful application dispatch.
        $this->errorManager->logError("App run completed successfully.", 'userNotice');
    }
}
