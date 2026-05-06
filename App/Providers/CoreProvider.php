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
use App\Contracts\Async\EventDispatcherInterface;
use App\Contracts\Async\FailedJobStoreInterface;
use App\Contracts\Auth\GuardInterface;
use App\Contracts\Auth\PasswordBrokerInterface;
use App\Contracts\Auth\UserProviderInterface;
use App\Contracts\Presentation\AssetManagerInterface;
use App\Contracts\Presentation\HtmlManagerInterface;
use App\Contracts\Support\ArchitectureAlignmentManagerInterface;
use App\Contracts\Support\AuditLoggerInterface;
use App\Contracts\Support\FrameworkDoctorInterface;
use App\Contracts\Support\FrameworkLayerManagerInterface;
use App\Contracts\Support\HealthManagerInterface;
use App\Contracts\Support\NotificationManagerInterface;
use App\Contracts\Support\PaymentManagerInterface;
use App\Exceptions\ContainerException;
use App\Utilities\Managers\Commerce\ShippingManager;
use App\Utilities\Managers\Presentation\AssetManager;
use App\Utilities\Managers\Presentation\HtmlManager;
use App\Utilities\Managers\Presentation\ThemeManager;
use App\Utilities\Managers\Async\DatabaseFailedJobStore;
use App\Utilities\Managers\Async\EventDispatcher;
use App\Utilities\Managers\Async\QueueManager;
use App\Utilities\Managers\Security\{
    AuthManager,
    DatabaseUserProvider,
    Gate,
    HttpSecurityManager,
    PasswordBroker,
    PermissionRegistry,
    PolicyResolver,
    SessionGuard
};
use App\Utilities\Managers\Support\{
    ArchitectureAlignmentManager,
    AuditLogger,
    FrameworkDoctor,
    FrameworkLayerManager,
    HealthManager,
    NotificationManager,
    PasskeyManager,
    PaymentManager
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
            'architecture' => ArchitectureAlignmentManager::class,
            'assets'   => AssetManager::class,
            'audit'    => AuditLogger::class,
            'auth'     => AuthManager::class,
            'console'  => ConsoleKernel::class,
            'config'   => Config::class,
            'database' => Database::class,
            'doctor' => FrameworkDoctor::class,
            'events' => EventDispatcher::class,
            'frameworkLayers' => FrameworkLayerManager::class,
            'gate' => Gate::class,
            'health' => HealthManager::class,
            'html' => HtmlManager::class,
            'httpSecurity' => HttpSecurityManager::class,
            'migrationRunner' => MigrationRunner::class,
            'notifications' => NotificationManager::class,
            'passwordBroker' => PasswordBroker::class,
            'passkeys' => PasskeyManager::class,
            'payments' => PaymentManager::class,
            'permissionRegistry' => PermissionRegistry::class,
            'queue' => QueueManager::class,
            'policyResolver' => PolicyResolver::class,
            'router'   => Router::class,
            'seedRunner' => SeedRunner::class,
            'shipping' => ShippingManager::class,
            'shippingProvider' => ShippingProvider::class,
            'session'  => Session::class,
            'themes' => ThemeManager::class,
            'guard' => SessionGuard::class,
            'userProvider' => DatabaseUserProvider::class,
            'failedJobs' => DatabaseFailedJobStore::class,

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

                $this->registerAlias(GuardInterface::class, SessionGuard::class);
                $this->registerAlias(UserProviderInterface::class, DatabaseUserProvider::class);
                $this->registerAlias(PasswordBrokerInterface::class, PasswordBroker::class);
                $this->registerAlias(ArchitectureAlignmentManagerInterface::class, ArchitectureAlignmentManager::class);
                $this->registerAlias(AssetManagerInterface::class, AssetManager::class);
                $this->registerAlias(HtmlManagerInterface::class, HtmlManager::class);
                $this->registerAlias(EventDispatcherInterface::class, EventDispatcher::class);
                $this->registerAlias(AuditLoggerInterface::class, AuditLogger::class);
                $this->registerAlias(FrameworkDoctorInterface::class, FrameworkDoctor::class);
                $this->registerAlias(FrameworkLayerManagerInterface::class, FrameworkLayerManager::class);
                $this->registerAlias(HealthManagerInterface::class, HealthManager::class);
                $this->registerAlias(NotificationManagerInterface::class, NotificationManager::class);
                $this->registerAlias(PaymentManagerInterface::class, PaymentManager::class);
                $this->registerAlias(FailedJobStoreInterface::class, DatabaseFailedJobStore::class);

                foreach ($this->legacyManagerAliases() as $legacyClass => $canonicalClass) {
                    $this->registerAlias($legacyClass, $canonicalClass);
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

    public function resolveClass(string $classOrAlias): object
    {
        $this->registerServices();

        if ($classOrAlias === 'container') {
            return $this;
        }

        $class = $this->coreServiceMap[$classOrAlias]
            ?? $this->aliases[$classOrAlias]
            ?? $classOrAlias;

        return $this->getInstance($class);
    }

    /**
     * @return array<class-string, class-string>
     */
    private function legacyManagerAliases(): array
    {
        return [
            \App\Support\Commerce\CartPricingManager::class => \App\Utilities\Managers\Commerce\CartPricingManager::class,
            \App\Support\Commerce\CatalogLifecycleManager::class => \App\Utilities\Managers\Commerce\CatalogLifecycleManager::class,
            \App\Support\Commerce\CommerceTotalsCalculator::class => \App\Utilities\Managers\Commerce\CommerceTotalsCalculator::class,
            \App\Support\Commerce\EntitlementManager::class => \App\Utilities\Managers\Commerce\EntitlementManager::class,
            \App\Support\Commerce\InventoryManager::class => \App\Utilities\Managers\Commerce\InventoryManager::class,
            \App\Support\Commerce\OrderDocumentManager::class => \App\Utilities\Managers\Commerce\OrderDocumentManager::class,
            \App\Support\Commerce\OrderLifecycleManager::class => \App\Utilities\Managers\Commerce\OrderLifecycleManager::class,
            \App\Support\Commerce\OrderReturnManager::class => \App\Utilities\Managers\Commerce\OrderReturnManager::class,
            \App\Support\Commerce\PromotionManager::class => \App\Utilities\Managers\Commerce\PromotionManager::class,
            \App\Support\Commerce\ShippingManager::class => \App\Utilities\Managers\Commerce\ShippingManager::class,
            \App\Support\Commerce\SubscriptionManager::class => \App\Utilities\Managers\Commerce\SubscriptionManager::class,
            \App\Support\Theming\ThemeManager::class => \App\Utilities\Managers\Presentation\ThemeManager::class,
        ];
    }
}
