<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Contracts\Async\EventDispatcherInterface;
use App\Contracts\Async\QueueDriverInterface;
use App\Contracts\Auth\PasswordBrokerInterface;
use App\Contracts\Support\NotificationChannelInterface;
use App\Contracts\Support\NotificationManagerInterface;
use App\Core\Config;
use App\Core\Database;
use App\Core\MigrationRunner;
use App\Core\SeedRunner;
use App\Core\Session;
use App\Drivers\Notifications\DatabaseNotificationChannel;
use App\Drivers\Notifications\MailNotificationChannel;
use App\Drivers\Payments\TestingPaymentDriver;
use App\Drivers\Queue\DatabaseQueueDriver;
use App\Modules\AdminModule\Presenters\AdminResource;
use App\Modules\AdminModule\Services\AdminAccessService;
use App\Modules\CartModule\Listeners\MergeCartOnLoginListener;
use App\Modules\CartModule\Migrations\AddCartDiscountColumns;
use App\Modules\CartModule\Migrations\CreateCartTables;
use App\Modules\CartModule\Migrations\CreatePromotionTables;
use App\Modules\CartModule\Migrations\CreatePromotionUsageTable;
use App\Modules\CartModule\Presenters\CartResource;
use App\Modules\CartModule\Repositories\CartItemRepository;
use App\Modules\CartModule\Repositories\CartRepository;
use App\Modules\CartModule\Repositories\PromotionRepository;
use App\Modules\CartModule\Seeds\CartSeed;
use App\Modules\CartModule\Services\CartService;
use App\Modules\OrderModule\Listeners\OrderLifecycleNotificationListener;
use App\Modules\OrderModule\Migrations\AddOrderCommerceStateColumns;
use App\Modules\OrderModule\Migrations\AddOrderDiscountSnapshotColumns;
use App\Modules\OrderModule\Migrations\AddOrderShipmentTrackingColumns;
use App\Modules\OrderModule\Migrations\CreateOrderEntitlementsTable;
use App\Modules\OrderModule\Migrations\CreateOrderTables;
use App\Modules\OrderModule\Migrations\CreateOrderSubscriptionsTable;
use App\Modules\OrderModule\Migrations\CreatePaymentWebhookEventsTable;
use App\Modules\OrderModule\Presenters\OrderResource;
use App\Modules\OrderModule\Repositories\OrderAddressRepository;
use App\Modules\OrderModule\Repositories\OrderEntitlementRepository;
use App\Modules\OrderModule\Repositories\OrderItemRepository;
use App\Modules\OrderModule\Repositories\OrderRepository;
use App\Modules\OrderModule\Repositories\OrderSubscriptionRepository;
use App\Modules\OrderModule\Repositories\PaymentWebhookEventRepository;
use App\Modules\OrderModule\Seeds\OrderSeed;
use App\Modules\OrderModule\Services\OrderService;
use App\Modules\ShopModule\Listeners\CatalogActivityNotificationListener;
use App\Modules\ShopModule\Migrations\AddProductFulfillmentColumns;
use App\Modules\ShopModule\Migrations\CreateShopTables;
use App\Modules\ShopModule\Presenters\ShopResource;
use App\Modules\ShopModule\Repositories\CategoryRepository;
use App\Modules\ShopModule\Repositories\ProductRepository;
use App\Modules\ShopModule\Seeds\ShopSeed;
use App\Modules\ShopModule\Services\CatalogService;
use App\Modules\UserModule\Migrations\CreateUserPlatformTables;
use App\Modules\UserModule\Repositories\PermissionRepository;
use App\Modules\UserModule\Repositories\RoleRepository;
use App\Modules\UserModule\Repositories\UserRepository;
use App\Modules\UserModule\Seeds\UserPlatformSeed;
use App\Modules\WebModule\Migrations\CreatePagesTable;
use App\Modules\WebModule\Repositories\PageRepository;
use App\Modules\WebModule\Seeds\PageSeed;
use App\Modules\WebModule\Services\PageService;
use App\Providers\CoreProvider;
use App\Providers\ExceptionProvider;
use App\Providers\NotificationProvider;
use App\Providers\PaymentProvider;
use App\Providers\QueueProvider;
use App\Support\Commerce\CatalogLifecycleManager;
use App\Support\Commerce\CartPricingManager;
use App\Support\Commerce\CommerceTotalsCalculator;
use App\Support\Commerce\EntitlementManager;
use App\Support\Commerce\InventoryManager;
use App\Support\Commerce\OrderLifecycleManager;
use App\Support\Commerce\PromotionManager;
use App\Support\Commerce\ShippingManager;
use App\Support\Commerce\SubscriptionManager;
use App\Support\Payments\PaymentIntent;
use App\Utilities\Managers\Async\DatabaseFailedJobStore;
use App\Utilities\Managers\Async\EventDispatcher;
use App\Utilities\Managers\Async\QueueManager;
use App\Utilities\Managers\Data\CryptoManager;
use App\Utilities\Managers\Data\ModuleManager;
use App\Utilities\Managers\Data\SessionManager;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\CacheManager;
use App\Utilities\Managers\Security\AuthManager;
use App\Utilities\Managers\Security\DatabaseUserProvider;
use App\Utilities\Managers\Security\Gate;
use App\Utilities\Managers\Security\HttpSecurityManager;
use App\Utilities\Managers\Security\PermissionRegistry;
use App\Utilities\Managers\Security\PolicyResolver;
use App\Utilities\Managers\Security\SessionGuard;
use App\Utilities\Managers\Support\AuditLogger;
use App\Utilities\Managers\Support\HealthManager;
use App\Utilities\Managers\Support\MailManager;
use App\Utilities\Managers\Support\NotificationManager;
use App\Utilities\Managers\Support\PaymentManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Managers\SettingsManager;
use PHPUnit\Framework\TestCase;
use PDO;
use RuntimeException;

final class TestFrameworkConfig extends Config
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(private array $settings)
    {
    }

    public function get(string $file, ?string $key = null, mixed $default = null): mixed
    {
        $bucket = $this->settings[strtolower($file)] ?? null;

        if ($key === null) {
            return $bucket ?? $default;
        }

        if (!is_array($bucket)) {
            return $default;
        }

        $current = $bucket;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($current)) {
                return $default;
            }

            if (array_key_exists($segment, $current)) {
                $current = $current[$segment];
                continue;
            }

            $matched = null;

            foreach ($current as $candidateKey => $candidateValue) {
                if (strcasecmp((string) $candidateKey, $segment) === 0) {
                    $matched = $candidateValue;
                    break;
                }
            }

            if ($matched === null) {
                return $default;
            }

            $current = $matched;
        }

        return $current;
    }
}

final class TestModuleManager extends ModuleManager
{
    /**
     * @param array<int, class-string> $classes
     * @param array<string, string> $modules
     * @param array<string, object> $resolved
     */
    public function __construct(
        private array $classes,
        private array $modules = [],
        private array $resolved = []
    ) {
    }

    /**
     * @param array<string, object> $resolved
     */
    public function setResolved(array $resolved): void
    {
        $this->resolved = $resolved;
    }

    public function getModules(): array
    {
        return $this->modules;
    }

    public function getClasses(string $module, string $subDir, array $filter = ['extension' => 'php'], array $sort = []): array
    {
        return $this->filterClasses($module, $subDir);
    }

    public function collectClasses(string $subDir, array $filter = ['extension' => 'php'], array $sort = []): array
    {
        return $this->filterClasses(null, $subDir);
    }

    public function resolveModule(string $alias): object
    {
        return $this->resolved[$alias]
            ?? throw new RuntimeException(sprintf('Unresolved module service [%s] in test registry.', $alias));
    }

    /**
     * @return array<int, array{class:string,shortName:string,file:string}>
     */
    private function filterClasses(?string $module, string $subDir): array
    {
        $results = [];

        foreach ($this->classes as $class) {
            if ($module !== null && !str_contains($class, '\\' . $module . '\\')) {
                continue;
            }

            if ($subDir !== '' && !str_contains($class, '\\' . $subDir . '\\')) {
                continue;
            }

            $segments = explode('\\', $class);
            $shortName = (string) end($segments);

            $results[] = [
                'class' => $class,
                'shortName' => $shortName,
                'file' => str_replace('\\', '/', $class) . '.php',
            ];
        }

        return $results;
    }
}

final class TestCoreProvider extends CoreProvider
{
    /**
     * @param array<string, object> $resolved
     */
    public function __construct(private array $resolved = [])
    {
        parent::__construct();
    }

    public function setResolved(string $classOrAlias, object $instance): void
    {
        $this->resolved[$classOrAlias] = $instance;
    }

    public function resolveClass(string $classOrAlias): object
    {
        return $this->resolved[$classOrAlias]
            ?? throw new RuntimeException(sprintf('Unresolved core service [%s] in test provider.', $classOrAlias));
    }
}

final class TestQueueProvider extends QueueProvider
{
    public function __construct(private readonly QueueDriverInterface $driver)
    {
        parent::__construct();
    }

    public function registerServices(): void
    {
    }

    public function getQueueDriver(array $settings): QueueDriverInterface
    {
        return $this->driver;
    }

    public function getSupportedDrivers(): array
    {
        return [$this->driver->driverName()];
    }
}

final class TestNotificationProvider extends NotificationProvider
{
    /**
     * @param array<string, NotificationChannelInterface> $channels
     */
    public function __construct(private readonly array $channels)
    {
        parent::__construct();
    }

    public function registerServices(): void
    {
    }

    public function getChannel(string $name): NotificationChannelInterface
    {
        return $this->channels[$name]
            ?? throw new RuntimeException(sprintf('Unknown notification channel [%s].', $name));
    }

    public function getSupportedChannels(): array
    {
        return array_keys($this->channels);
    }
}

final class TestPaymentProvider extends PaymentProvider
{
    public function __construct(private readonly TestingPaymentDriver $driver)
    {
        parent::__construct();
    }

    public function registerServices(): void
    {
    }

    public function getPaymentDriver(array $settings): \App\Contracts\Support\PaymentDriverInterface
    {
        return $this->driver;
    }

    public function getSupportedDrivers(): array
    {
        return [$this->driver->driverName()];
    }
}

final class FrameworkCompletionTest extends TestCase
{
    private array $sessionBackup = [];
    private array $cookieBackup = [];
    private array $serverBackup = [];
    private array $tempPaths = [];

    protected function setUp(): void
    {
        $this->sessionBackup = $_SESSION ?? [];
        $this->cookieBackup = $_COOKIE ?? [];
        $this->serverBackup = $_SERVER ?? [];
        $_SESSION = [];
        $_COOKIE = [];
        $_SERVER = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->sessionBackup;
        $_COOKIE = $this->cookieBackup;
        $_SERVER = $this->serverBackup;

        foreach ($this->tempPaths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $this->tempPaths = [];
    }

    public function testFullPlatformMigrationsAndSeedsRunInDependencyOrder(): void
    {
        $database = $this->makeSqliteDatabase();
        $errors = new ErrorManager(new ExceptionProvider());
        $modules = new TestModuleManager([
            CreateOrderTables::class,
            AddOrderCommerceStateColumns::class,
            AddOrderShipmentTrackingColumns::class,
            CreateOrderEntitlementsTable::class,
            CreateOrderSubscriptionsTable::class,
            CreatePaymentWebhookEventsTable::class,
            OrderSeed::class,
            CreatePagesTable::class,
            PageSeed::class,
            CreateCartTables::class,
            AddCartDiscountColumns::class,
            CreatePromotionTables::class,
            CreatePromotionUsageTable::class,
            CartSeed::class,
            CreateShopTables::class,
            AddProductFulfillmentColumns::class,
            ShopSeed::class,
            CreateUserPlatformTables::class,
            UserPlatformSeed::class,
        ], [
            'WebModule' => '/tmp/WebModule',
            'UserModule' => '/tmp/UserModule',
            'ShopModule' => '/tmp/ShopModule',
            'CartModule' => '/tmp/CartModule',
            'OrderModule' => '/tmp/OrderModule',
        ]);

        $migrations = new MigrationRunner($database, $modules, $errors);
        $seeds = new SeedRunner($database, $modules, $errors);

        $executedMigrations = $migrations->migrate();
        $executedSeeds = $seeds->run();

        self::assertLessThan(
            array_search('CreateCartTables', $executedMigrations, true),
            array_search('CreateUserPlatformTables', $executedMigrations, true)
        );
        self::assertLessThan(
            array_search('CreateOrderTables', $executedMigrations, true),
            array_search('CreateCartTables', $executedMigrations, true)
        );
        self::assertLessThan(
            array_search('AddCartDiscountColumns', $executedMigrations, true),
            array_search('CreateCartTables', $executedMigrations, true)
        );
        self::assertLessThan(
            array_search('CreatePromotionTables', $executedMigrations, true),
            array_search('AddCartDiscountColumns', $executedMigrations, true)
        );
        self::assertLessThan(
            array_search('CreatePromotionUsageTable', $executedMigrations, true),
            array_search('CreatePromotionTables', $executedMigrations, true)
        );
        self::assertLessThan(
            array_search('AddOrderCommerceStateColumns', $executedMigrations, true),
            array_search('CreateOrderTables', $executedMigrations, true)
        );
        self::assertLessThan(
            array_search('AddOrderShipmentTrackingColumns', $executedMigrations, true),
            array_search('AddOrderCommerceStateColumns', $executedMigrations, true)
        );
        self::assertLessThan(
            array_search('CreateOrderEntitlementsTable', $executedMigrations, true),
            array_search('AddOrderShipmentTrackingColumns', $executedMigrations, true)
        );
        self::assertLessThan(
            array_search('CreateOrderSubscriptionsTable', $executedMigrations, true),
            array_search('CreateOrderEntitlementsTable', $executedMigrations, true)
        );
        self::assertLessThan(
            array_search('CreatePaymentWebhookEventsTable', $executedMigrations, true),
            array_search('CreateOrderTables', $executedMigrations, true)
        );
        self::assertLessThan(
            array_search('AddProductFulfillmentColumns', $executedMigrations, true),
            array_search('CreateShopTables', $executedMigrations, true)
        );
        self::assertLessThan(
            array_search('CartSeed', $executedSeeds, true),
            array_search('ShopSeed', $executedSeeds, true)
        );
        self::assertLessThan(
            array_search('OrderSeed', $executedSeeds, true),
            array_search('CartSeed', $executedSeeds, true)
        );

        self::assertSame(2, (int) $database->fetchColumn('SELECT COUNT(*) FROM users'));
        self::assertSame(3, (int) $database->fetchColumn('SELECT COUNT(*) FROM products'));
        self::assertSame(1, (int) $database->fetchColumn('SELECT COUNT(*) FROM carts'));
        self::assertSame(1, (int) $database->fetchColumn('SELECT COUNT(*) FROM orders'));
        self::assertSame(4, (int) $database->fetchColumn('SELECT COUNT(*) FROM pages'));

        $pageService = new PageService(
            new PageRepository($database),
            $this->makeConfig(),
            $errors
        );
        $page = $pageService->forSlug('home')->execute();

        self::assertSame('database', $page['page']['source']);
    }

    public function testWebModuleFallsBackCleanlyBeforePagesTableExists(): void
    {
        $database = $this->makeSqliteDatabase();
        $service = new PageService(
            new PageRepository($database),
            $this->makeConfig(),
            new ErrorManager(new ExceptionProvider())
        );

        $page = $service->forSlug('home')->execute();

        self::assertSame('memory', $page['page']['source']);
        self::assertSame('LangelerMVC is running.', $page['page']['headline']);
    }

    public function testAuthEventsMergeGuestCartAndOrderLifecycleQueuesNotifications(): void
    {
        $stack = $this->makePlatformStack(seedCart: false, seedOrders: false);

        $addResult = $stack['cartService']
            ->forAction('addItem', ['slug' => 'starter-platform-license', 'quantity' => 2])
            ->execute();
        self::assertSame(200, $addResult['status']);
        self::assertSame(1, $addResult['cart']['item_count']);
        self::assertSame(2, $addResult['cart']['items'][0]['quantity']);

        self::assertTrue($stack['auth']->attempt([
            'email' => 'customer@langelermvc.test',
            'password' => 'customer12345',
        ]));

        $userCart = $stack['carts']->findActiveByUserId(2);
        self::assertNotNull($userCart);
        self::assertSame(2, (int) ($stack['cartItems']->summaryForCart((int) $userCart?->getKey())[0]['quantity'] ?? 0));
        self::assertNull($stack['carts']->findActiveBySessionKey((string) ($_SESSION['cart']['session_key'] ?? '__missing__')));
        $loginNotifications = $stack['notifications']->databaseNotifications(2);
        self::assertCount(1, $loginNotifications);
        self::assertSame(2, (int) ($loginNotifications[0]['data']['merged_items'] ?? 0));
        self::assertStringContainsString('merged into your account cart', (string) ($loginNotifications[0]['data']['message'] ?? ''));

        $checkout = $stack['orderService']->forAction('checkout', [
            'name' => 'Demo Customer',
            'email' => 'customer@langelermvc.test',
            'line_one' => 'Framework Street 1',
            'postal_code' => '12345',
            'city' => 'Stockholm',
            'country' => 'Sweden',
        ])->execute();

        self::assertSame(201, $checkout['status']);
        self::assertSame('placed', $checkout['order']['status']);
        self::assertSame('card', $checkout['order']['payment_method']);
        self::assertSame('authorize_capture', $checkout['order']['payment_flow']);
        self::assertSame(1, count($stack['queue']->pending('notifications')));

        while ($stack['queue']->work('notifications', 1) > 0) {
        }

        self::assertSame(2, count($stack['notifications']->databaseNotifications()));
        self::assertCount(1, $stack['mail']->outbox());

        $orderJson = (new OrderResource($checkout))->toArray();
        self::assertSame('OrderModule', $orderJson['meta']['module']);
        self::assertSame('placed', $orderJson['data']['order']['status']);
    }

    public function testPaymentCompatibilitySurfaceSupportsRedirectReconciliationAndIdempotentCheckout(): void
    {
        $stack = $this->makePlatformStack(seedCart: false, seedOrders: false);

        $stack['cartService']
            ->forAction('addItem', ['slug' => 'starter-platform-license', 'quantity' => 1])
            ->execute();

        self::assertTrue($stack['auth']->attempt([
            'email' => 'customer@langelermvc.test',
            'password' => 'customer12345',
        ]));

        self::assertContains('paypal', $stack['payments']->availableDrivers());
        self::assertContains('wallet', $stack['payments']->supportedMethods('paypal'));
        self::assertContains('redirect', $stack['payments']->supportedFlows('paypal'));

        $checkout = $stack['orderService']->forAction('checkout', [
            'name' => 'Demo Customer',
            'email' => 'customer@langelermvc.test',
            'line_one' => 'Framework Street 1',
            'postal_code' => '12345',
            'city' => 'Stockholm',
            'country' => 'Sweden',
            'payment_driver' => 'paypal',
            'payment_method' => 'wallet',
            'payment_flow' => 'redirect',
            'idempotency_key' => 'checkout-wallet-demo-0001',
        ])->execute();

        self::assertSame(202, $checkout['status']);
        self::assertSame('awaiting_payment_action', $checkout['order']['status']);
        self::assertSame('paypal', $checkout['order']['payment_driver']);
        self::assertSame('wallet', $checkout['order']['payment_method']);
        self::assertSame('redirect', $checkout['order']['payment_flow']);
        self::assertSame('/orders/' . $checkout['order']['id'], $checkout['redirect']);
        self::assertTrue((bool) ($checkout['order']['payment_customer_action_required'] ?? false));
        self::assertSame('redirect', $checkout['order']['payment_next_action']['type'] ?? null);

        $duplicate = $stack['orderService']->forAction('checkout', [
            'name' => 'Demo Customer',
            'email' => 'customer@langelermvc.test',
            'line_one' => 'Framework Street 1',
            'postal_code' => '12345',
            'city' => 'Stockholm',
            'country' => 'Sweden',
            'payment_driver' => 'paypal',
            'payment_method' => 'wallet',
            'payment_flow' => 'redirect',
            'idempotency_key' => 'checkout-wallet-demo-0001',
        ])->execute();

        self::assertSame(200, $duplicate['status']);
        self::assertSame($checkout['order']['id'], $duplicate['order']['id']);
        self::assertSame('/orders/' . $duplicate['order']['id'], $duplicate['redirect']);

        $stack['auth']->logout();
        self::assertTrue($stack['auth']->attempt([
            'email' => 'admin@langelermvc.test',
            'password' => 'admin12345',
        ]));

        $reconciled = $stack['orderService']->forAction('reconcile', [], [
            'order' => (int) $checkout['order']['id'],
        ])->execute();

        self::assertSame(200, $reconciled['status']);
        self::assertSame('authorized', $reconciled['order']['payment_status']);
        self::assertSame('placed', $reconciled['order']['status']);
        self::assertSame([], $reconciled['order']['payment_next_action']);

        $captured = $stack['orderService']->forAction('capture', [], [
            'order' => (int) $checkout['order']['id'],
        ])->execute();

        self::assertSame(200, $captured['status']);
        self::assertSame('captured', $captured['order']['payment_status']);
        self::assertSame('processing', $captured['order']['status']);

        while ($stack['queue']->work('notifications', 1) > 0) {
        }

        self::assertGreaterThanOrEqual(2, count($stack['notifications']->databaseNotifications()));
        self::assertGreaterThanOrEqual(2, count($stack['mail']->outbox()));
    }

    public function testPaymentWebhooksVerifySignaturesRecordEventsAndReconcileIdempotently(): void
    {
        $stack = $this->makePlatformStack(seedCart: false, seedOrders: false);

        $stack['cartService']
            ->forAction('addItem', ['slug' => 'starter-platform-license', 'quantity' => 1])
            ->execute();

        self::assertTrue($stack['auth']->attempt([
            'email' => 'customer@langelermvc.test',
            'password' => 'customer12345',
        ]));

        $checkout = $stack['orderService']->forAction('checkout', [
            'name' => 'Webhook Customer',
            'email' => 'customer@langelermvc.test',
            'line_one' => 'Framework Street 1',
            'postal_code' => '12345',
            'city' => 'Stockholm',
            'country' => 'Sweden',
            'payment_driver' => 'testing',
            'payment_method' => 'card',
            'payment_flow' => 'async',
            'idempotency_key' => 'webhook-async-checkout-0001',
        ])->execute();

        self::assertSame(202, $checkout['status']);
        self::assertSame('processing', $checkout['order']['payment_status']);

        $rejectedPayload = [
            'event_id' => 'evt_rejected_signature',
            'event_type' => 'payment.captured',
            'payment_reference' => (string) $checkout['order']['payment_reference'],
            'status' => 'captured',
        ];
        $rejectedRaw = $stack['payments']->canonicalWebhookPayload($rejectedPayload);
        $rejected = $stack['orderService']->forAction('paymentWebhook', [
            ...$rejectedPayload,
            '_webhook_raw_body' => $rejectedRaw,
            '_webhook_headers' => [
                'X-Langeler-Signature' => 'sha256=invalid',
                'X-Langeler-Event' => 'evt_rejected_signature',
                'X-Langeler-Timestamp' => (string) time(),
            ],
        ], [
            'driver' => 'testing',
        ])->execute();

        self::assertSame(401, $rejected['status']);
        self::assertSame('failed', $rejected['webhook']['event']['processing_status'] ?? null);

        $payload = [
            'event_id' => 'evt_async_captured',
            'event_type' => 'payment.captured',
            'payment_reference' => (string) $checkout['order']['payment_reference'],
            'status' => 'captured',
        ];
        $raw = $stack['payments']->canonicalWebhookPayload($payload);
        $signature = $stack['payments']->webhookPayloadSignature('testing', $raw);
        $headers = [
            'X-Langeler-Signature' => 'sha256=' . $signature,
            'X-Langeler-Event' => 'evt_async_captured',
            'X-Langeler-Timestamp' => (string) time(),
        ];

        $processed = $stack['orderService']->forAction('paymentWebhook', [
            ...$payload,
            '_webhook_raw_body' => $raw,
            '_webhook_headers' => $headers,
        ], [
            'driver' => 'testing',
        ])->execute();

        self::assertSame(200, $processed['status'], (string) ($processed['message'] ?? ''));
        self::assertSame('processed', $processed['webhook']['event']['processing_status'] ?? null);
        self::assertTrue((bool) ($processed['webhook']['event']['signature_verified'] ?? false));
        self::assertSame('captured', $processed['order']['payment_status']);
        self::assertSame('processing', $processed['order']['status']);

        $duplicate = $stack['orderService']->forAction('paymentWebhook', [
            ...$payload,
            '_webhook_raw_body' => $raw,
            '_webhook_headers' => $headers,
        ], [
            'driver' => 'testing',
        ])->execute();

        self::assertSame(200, $duplicate['status']);
        self::assertTrue((bool) ($duplicate['webhook']['idempotent'] ?? false));
        self::assertSame(2, (int) $stack['database']->fetchColumn('SELECT COUNT(*) FROM payment_webhook_events'));
        self::assertSame(1, (int) $stack['database']->fetchColumn("SELECT COUNT(*) FROM payment_webhook_events WHERE event_id = 'evt_async_captured'"));
    }

    public function testGuestCheckoutUsesPublicReturnSurfaceAndExposesPaymentLookupPayload(): void
    {
        $stack = $this->makePlatformStack(seedCart: false, seedOrders: false);

        $stack['cartService']
            ->forAction('addItem', ['slug' => 'starter-platform-license', 'quantity' => 1])
            ->execute();

        $form = $stack['orderService']->forAction('checkoutForm')->execute();
        $formJson = (new OrderResource($form))->toArray();

        self::assertSame(200, $form['status']);
        self::assertSame('testing', $form['payment']['driver']);
        self::assertContains('paypal', $form['payment']['available_drivers']);
        self::assertSame('/orders/complete', $form['lookup']['complete_url']);
        self::assertSame('/orders/cancelled', $form['lookup']['cancelled_url']);
        self::assertArrayHasKey('payment', $formJson['data']);
        self::assertArrayHasKey('checkout', $formJson['data']);
        self::assertArrayHasKey('shipping', $formJson['data']);
        self::assertArrayHasKey('lookup', $formJson['data']);
        self::assertSame('SE', $form['shipping']['country']);
        self::assertContains('Mina Paket', array_map(
            static fn(array $app): string => (string) ($app['label'] ?? ''),
            (array) ($form['shipping']['tracking_apps'] ?? [])
        ));

        $checkout = $stack['orderService']->forAction('checkout', [
            'name' => 'Guest Customer',
            'email' => 'guest@langelermvc.test',
            'line_one' => 'Framework Street 1',
            'postal_code' => '12345',
            'city' => 'Stockholm',
            'country' => 'Sweden',
            'payment_driver' => 'paypal',
            'payment_method' => 'wallet',
            'payment_flow' => 'redirect',
            'idempotency_key' => 'guest-public-return-0001',
        ])->execute();

        $reference = (string) ($checkout['order']['payment_reference'] ?? '');

        self::assertSame(202, $checkout['status']);
        self::assertNotSame('', $reference);
        self::assertSame('/orders/complete/' . $reference, $checkout['redirect']);

        $returned = $stack['orderService']->forAction('completeReturn', [], [
            'reference' => $reference,
        ])->execute();

        self::assertSame(200, $returned['status']);
        self::assertSame($checkout['order']['order_number'], $returned['order']['order_number']);
        self::assertSame([], $returned['order']['addresses']);
        self::assertArrayNotHasKey('contact_email', $returned['order']);
        self::assertSame(
            $checkout['order']['payment_next_action']['url'] ?? null,
            $returned['order']['actions']['continue_payment'] ?? null
        );
        self::assertSame('/orders', $returned['lookup']['orders_url']);
    }

    public function testCheckoutAppliesFinancialBreakdownAndRestoresInventoryOnCancel(): void
    {
        $stack = $this->makePlatformStack(seedCart: false, seedOrders: false);

        self::assertTrue($stack['auth']->attempt([
            'email' => 'customer@langelermvc.test',
            'password' => 'customer12345',
        ]));

        $product = $stack['products']->findPublishedBySlug('starter-platform-license');
        self::assertNotNull($product);
        $initialStock = (int) ($product?->getAttribute('stock') ?? 0);

        $stack['cartService']
            ->forAction('addItem', ['slug' => 'starter-platform-license', 'quantity' => 1])
            ->execute();

        $cart = $stack['cartService']->forAction('show')->execute();

        self::assertSame(9900, $cart['cart']['subtotal_minor']);
        self::assertSame(1490, $cart['cart']['shipping_minor']);
        self::assertSame(2848, $cart['cart']['tax_minor']);
        self::assertSame(14238, $cart['cart']['total_minor']);
        self::assertSame('postnord-service-point', $cart['cart']['shipping_option']);
        self::assertSame('PostNord', $cart['cart']['shipping_carrier_label']);

        $checkout = $stack['orderService']->forAction('checkout', [
            'name' => 'Demo Customer',
            'email' => 'customer@langelermvc.test',
            'line_one' => 'Framework Street 1',
            'postal_code' => '12345',
            'city' => 'Stockholm',
            'country' => 'Sweden',
            'shipping_option' => 'instabox-locker',
            'service_point_id' => 'IBOX-123',
            'service_point_name' => 'Instabox Hornstull',
        ])->execute();

        self::assertSame(201, $checkout['status']);
        self::assertSame(9900, $checkout['order']['subtotal_minor']);
        self::assertSame(990, $checkout['order']['shipping_minor']);
        self::assertSame(2723, $checkout['order']['tax_minor']);
        self::assertSame(13613, $checkout['order']['total_minor']);
        self::assertSame('instabox-locker', $checkout['order']['shipping_option']);
        self::assertSame('Instabox', $checkout['order']['shipping_carrier_label']);
        self::assertSame('Instabox Hornstull', $checkout['order']['shipping_service_point_name']);
        self::assertSame('reserved', $checkout['order']['inventory_status']);
        self::assertSame('unfulfilled', $checkout['order']['fulfillment_status']);

        $stockAfterCheckout = $stack['products']->findPublishedBySlug('starter-platform-license');
        self::assertSame($initialStock - 1, (int) ($stockAfterCheckout?->getAttribute('stock') ?? -1));

        $cancelled = $stack['orderService']->forAction('cancel', [], [
            'order' => (int) $checkout['order']['id'],
        ])->execute();

        self::assertSame(200, $cancelled['status']);
        self::assertSame('cancelled', $cancelled['order']['payment_status']);
        self::assertSame('released', $cancelled['order']['inventory_status']);
        self::assertSame('cancelled', $cancelled['order']['fulfillment_status']);

        $stockAfterCancellation = $stack['products']->findPublishedBySlug('starter-platform-license');
        self::assertSame($initialStock, (int) ($stockAfterCancellation?->getAttribute('stock') ?? -1));
    }

    public function testPromotionCodesUpdateCartPricingAndPersistIntoOrderSnapshots(): void
    {
        $stack = $this->makePlatformStack(seedCart: false, seedOrders: false);

        self::assertTrue($stack['auth']->attempt([
            'email' => 'customer@langelermvc.test',
            'password' => 'customer12345',
        ]));

        $stack['cartService']
            ->forAction('addItem', ['slug' => 'starter-platform-license', 'quantity' => 1])
            ->execute();

        $applied = $stack['cartService']->forAction('applyDiscount', [
            'coupon_code' => 'LOCKER49',
        ])->execute();

        self::assertSame(200, $applied['status'], (string) ($applied['message'] ?? ''));
        self::assertSame('LOCKER49', $applied['cart']['discount_code']);
        self::assertSame(1000, $applied['cart']['discount_minor']);
        self::assertSame(1490, $applied['cart']['shipping_base_minor']);
        self::assertSame(1000, $applied['cart']['shipping_discount_minor']);
        self::assertSame(490, $applied['cart']['shipping_minor']);
        self::assertSame(12988, $applied['cart']['total_minor']);

        $checkout = $stack['orderService']->forAction('checkout', [
            'name' => 'Demo Customer',
            'email' => 'customer@langelermvc.test',
            'line_one' => 'Framework Street 1',
            'postal_code' => '12345',
            'city' => 'Stockholm',
            'country' => 'Sweden',
            'shipping_option' => 'instabox-locker',
            'service_point_id' => 'IBOX-123',
            'service_point_name' => 'Instabox Hornstull',
        ])->execute();

        self::assertSame(201, $checkout['status']);
        self::assertSame('LOCKER49', $checkout['order']['discount_code']);
        self::assertSame('Locker 49 kr', $checkout['order']['discount_label']);
        self::assertSame(500, $checkout['order']['discount_minor']);
        self::assertSame(990, $checkout['order']['shipping_base_minor']);
        self::assertSame(500, $checkout['order']['shipping_discount_minor']);
        self::assertSame(490, $checkout['order']['shipping_minor']);
        self::assertSame(12988, $checkout['order']['total_minor']);

        $stack['auth']->logout();
        self::assertTrue($stack['auth']->attempt([
            'email' => 'admin@langelermvc.test',
            'password' => 'admin12345',
        ]));

        $detail = $stack['adminService']->forAction('order', [], [
            'order' => (int) $checkout['order']['id'],
        ])->execute();

        self::assertSame('LOCKER49', $detail['order']['discount_code']);
        self::assertSame(500, $detail['order']['shipping_discount_minor']);
    }

    public function testAdminPromotionManagementPersistsDatabaseBackedCoupons(): void
    {
        $stack = $this->makePlatformStack(seedCart: false, seedOrders: false);

        self::assertTrue($stack['auth']->attempt([
            'email' => 'admin@langelermvc.test',
            'password' => 'admin12345',
        ]));

        $created = $stack['adminService']->forAction('savePromotion', [
            'code' => 'ADMIN250',
            'label' => 'Admin 250 SEK',
            'description' => 'Database-backed exact amount coupon for admin workflow coverage.',
            'type' => 'fixed_amount',
            'applies_to' => 'cart_subtotal',
            'active' => '1',
            'amount_minor' => 2500,
            'min_subtotal_minor' => 5000,
            'usage_limit' => 5,
            'per_customer_limit' => 1,
            'per_segment_limit' => 1,
            'allowed_currencies' => 'SEK',
            'allowed_countries' => 'SE',
            'allowed_product_slugs' => 'starter-platform-license',
            'allowed_fulfillment_types' => 'physical_shipping',
            'allowed_customer_segments' => 'customer',
        ])->execute();

        self::assertSame(200, $created['status'], (string) ($created['message'] ?? ''));
        self::assertSame('/admin/promotions', $created['redirect']);
        self::assertSame(1, $created['promotion_metrics']['database_promotions']);
        self::assertSame(1, $created['promotion_metrics']['active_database_promotions']);

        $promotion = null;

        foreach ($created['promotions'] as $entry) {
            if (($entry['code'] ?? '') === 'ADMIN250') {
                $promotion = $entry;
                break;
            }
        }

        self::assertIsArray($promotion);
        self::assertSame('/admin/promotions/' . $promotion['id'] . '/deactivate', $promotion['deactivate_path'] ?? null);
        self::assertSame(['SEK'], $promotion['criteria']['allowed_currencies'] ?? []);
        self::assertSame(['starter-platform-license'], $promotion['criteria']['allowed_product_slugs'] ?? []);
        self::assertSame(['customer'], $promotion['criteria']['allowed_customer_segments'] ?? []);
        self::assertSame(1, $promotion['criteria']['per_customer_limit'] ?? 0);
        self::assertSame(1, $promotion['criteria']['per_segment_limit'] ?? 0);

        $json = (new AdminResource($created))->toArray();
        self::assertSame('AdminModule', $json['meta']['module']);
        self::assertArrayHasKey('promotions', $json['data']);
        self::assertArrayHasKey('promotion_metrics', $json['data']);

        $stack['auth']->logout();
        self::assertTrue($stack['auth']->attempt([
            'email' => 'customer@langelermvc.test',
            'password' => 'customer12345',
        ]));

        $stack['cartService']
            ->forAction('addItem', ['slug' => 'starter-platform-license', 'quantity' => 1])
            ->execute();

        $applied = $stack['cartService']->forAction('applyDiscount', [
            'coupon_code' => 'ADMIN250',
        ])->execute();

        self::assertSame(200, $applied['status'], (string) ($applied['message'] ?? ''));
        self::assertSame('ADMIN250', $applied['cart']['discount_code']);
        self::assertSame(2500, $applied['cart']['discount_minor']);

        $checkout = $stack['orderService']->forAction('checkout', [
            'name' => 'Demo Customer',
            'email' => 'customer@langelermvc.test',
            'line_one' => 'Framework Street 1',
            'postal_code' => '12345',
            'city' => 'Stockholm',
            'country' => 'Sweden',
        ])->execute();

        self::assertSame(201, $checkout['status'], (string) ($checkout['message'] ?? ''));
        self::assertSame('ADMIN250', $checkout['order']['discount_code']);
        self::assertSame(2500, $checkout['order']['discount_minor']);

        $usage = $stack['promotionRepository']->usageSummaries();
        self::assertCount(1, $usage);
        self::assertSame('ADMIN250', $usage[0]['promotion_code']);
        self::assertSame((int) $checkout['order']['id'], $usage[0]['order_id']);
        self::assertSame(2500, $usage[0]['discount_minor']);
        self::assertSame('customer@langelermvc.test', $usage[0]['context']['customer_email'] ?? '');
        self::assertSame(['customer'], $usage[0]['context']['customer_segments'] ?? []);
        self::assertSame(1, (int) $stack['promotionRepository']->findByCode('ADMIN250')?->getAttribute('usage_count'));

        $stack['cartService']
            ->forAction('addItem', ['slug' => 'starter-platform-license', 'quantity' => 1])
            ->execute();
        $limited = $stack['cartService']->forAction('applyDiscount', [
            'coupon_code' => 'ADMIN250',
        ])->execute();

        self::assertSame(422, $limited['status']);
        self::assertSame('This promotion has reached its per-customer usage limit.', $limited['message']);

        $segmentLimited = $stack['promotions']->evaluate('ADMIN250', [[
            'product_id' => 1,
            'slug' => 'starter-platform-license',
            'category_id' => 1,
            'fulfillment_type' => 'physical_shipping',
            'quantity' => 1,
            'line_total_minor' => 9900,
        ]], 'SEK', [
            'country' => 'SE',
            'zone' => 'SE',
            'selected' => [
                'carrier_code' => 'postnord',
                'code' => 'postnord-service-point',
                'effective_rate_minor' => 990,
            ],
        ], [
            'user_id' => 999,
            'customer_email' => 'other@langelermvc.test',
            'customer_segments' => ['customer'],
        ]);

        self::assertFalse($segmentLimited['applied']);
        self::assertSame('This promotion has reached its customer-segment usage limit.', $segmentLimited['message']);

        $stack['auth']->logout();
        self::assertTrue($stack['auth']->attempt([
            'email' => 'admin@langelermvc.test',
            'password' => 'admin12345',
        ]));

        $deactivated = $stack['adminService']->forAction('deactivatePromotion', [], [
            'promotion' => (int) ($promotion['id'] ?? 0),
        ])->execute();

        self::assertSame(200, $deactivated['status']);
        self::assertSame(1, $deactivated['promotion_metrics']['inactive_database_promotions']);

        $blocked = $stack['promotions']->evaluate('ADMIN250', [[
            'product_id' => 1,
            'slug' => 'starter-platform-license',
            'category_id' => 1,
            'fulfillment_type' => 'physical_shipping',
            'quantity' => 1,
            'line_total_minor' => 9900,
        ]], 'SEK', [
            'country' => 'SE',
            'zone' => 'SE',
            'selected' => [
                'carrier_code' => 'postnord',
                'code' => 'postnord-service-point',
                'effective_rate_minor' => 990,
            ],
        ]);

        self::assertFalse($blocked['applied']);
        self::assertSame('This promotion code is not currently active.', $blocked['message']);

        $reactivated = $stack['adminService']->forAction('activatePromotion', [], [
            'promotion' => (int) ($promotion['id'] ?? 0),
        ])->execute();

        self::assertSame(200, $reactivated['status']);
        self::assertSame(1, $reactivated['promotion_metrics']['active_database_promotions']);

        $deleted = $stack['adminService']->forAction('deletePromotion', [], [
            'promotion' => (int) ($promotion['id'] ?? 0),
        ])->execute();

        self::assertSame(200, $deleted['status']);
        self::assertSame(0, $deleted['promotion_metrics']['database_promotions']);
        self::assertNull($stack['promotionRepository']->findByCode('ADMIN250'));
    }

    public function testAdminWebPageManagementPublishesAndRetiresDatabasePages(): void
    {
        $stack = $this->makePlatformStack(seedCart: false, seedOrders: false);

        self::assertTrue($stack['auth']->attempt([
            'email' => 'admin@langelermvc.test',
            'password' => 'admin12345',
        ]));

        $initial = $stack['adminService']->forAction('pages')->execute();
        self::assertSame(200, $initial['status']);
        self::assertSame(4, $initial['page_metrics']['pages']);
        self::assertArrayHasKey('page_form', $initial);

        $created = $stack['adminService']->forAction('savePage', [
            'title' => 'Release Notes',
            'slug' => 'release-notes',
            'content' => 'Release notes authored through the admin dashboard.',
            'is_published' => '0',
        ])->execute();

        self::assertSame(200, $created['status'], (string) ($created['message'] ?? ''));
        self::assertSame('/admin/pages', $created['redirect']);
        self::assertSame(5, $created['page_metrics']['pages']);
        self::assertSame(1, $created['page_metrics']['draft_pages']);

        $page = $stack['pages']->findBySlug('release-notes');
        self::assertNotNull($page);
        $pageId = (int) $page?->getKey();
        self::assertGreaterThan(0, $pageId);

        $json = (new AdminResource($created))->toArray();
        self::assertArrayHasKey('pages', $json['data']);
        self::assertArrayHasKey('page_metrics', $json['data']);

        $published = $stack['adminService']->forAction('publishPage', [], [
            'page' => $pageId,
        ])->execute();

        self::assertSame(200, $published['status']);
        self::assertSame(5, $published['page_metrics']['published_pages']);

        $pageService = new PageService(
            $stack['pages'],
            $stack['config'],
            new ErrorManager(new ExceptionProvider())
        );
        $publicPage = $pageService->forSlug('release-notes')->execute();

        self::assertSame('database', $publicPage['page']['source']);
        self::assertSame('Release Notes', $publicPage['page']['title']);

        $updated = $stack['adminService']->forAction('updatePage', [
            'title' => 'Release Notes 2026',
            'slug' => 'release-notes',
            'content' => 'Updated release notes authored through the admin dashboard.',
            'is_published' => '1',
        ], [
            'page' => $pageId,
        ])->execute();

        self::assertSame(200, $updated['status']);
        self::assertSame('Release Notes 2026', $stack['pages']->findBySlug('release-notes')?->getAttribute('title'));

        $home = $stack['pages']->findBySlug('home');
        self::assertNotNull($home);
        $blocked = $stack['adminService']->forAction('deletePage', [], [
            'page' => (int) $home?->getKey(),
        ])->execute();

        self::assertSame(409, $blocked['status']);

        $deleted = $stack['adminService']->forAction('deletePage', [], [
            'page' => $pageId,
        ])->execute();

        self::assertSame(200, $deleted['status']);
        self::assertSame(4, $deleted['page_metrics']['pages']);
        self::assertNull($stack['pages']->findBySlug('release-notes'));
    }

    public function testDigitalFulfillmentSkipsShippingAndSupportsCriteriaPromotions(): void
    {
        $stack = $this->makePlatformStack(seedCart: false, seedOrders: false);

        self::assertTrue($stack['auth']->attempt([
            'email' => 'customer@langelermvc.test',
            'password' => 'customer12345',
        ]));

        $product = $stack['products']->findPublishedBySlug('starter-platform-license');
        self::assertNotNull($product);
        $productId = (int) $product?->getKey();
        $stack['products']->update($productId, [
            'stock' => 0,
            'fulfillment_type' => 'digital_download',
            'fulfillment_policy' => json_encode([
                'download_limit' => 3,
                'download_url' => 'https://downloads.langelermvc.test/starter-platform-license.zip',
                'access_days' => 30,
            ], JSON_THROW_ON_ERROR),
        ]);

        $added = $stack['cartService']
            ->forAction('addItem', ['slug' => 'starter-platform-license', 'quantity' => 2])
            ->execute();

        self::assertSame(200, $added['status']);
        self::assertSame('digital_download', $added['cart']['items'][0]['fulfillment_type']);
        self::assertSame('digital-delivery', $added['cart']['shipping_option']);
        self::assertSame(0, $added['cart']['shipping_minor']);
        self::assertSame(['digital_download'], $added['cart']['fulfillment']['types']);

        $applied = $stack['cartService']
            ->forAction('applyDiscount', ['coupon_code' => 'DIGITAL25'])
            ->execute();

        self::assertSame(200, $applied['status']);
        self::assertSame('DIGITAL25', $applied['cart']['discount_code']);
        self::assertSame(4950, $applied['cart']['discount_minor']);
        self::assertSame(0, $applied['cart']['shipping_minor']);

        $checkout = $stack['orderService']->forAction('checkout', [
            'name' => 'Digital Customer',
            'email' => 'customer@langelermvc.test',
            'line_one' => 'Online',
            'postal_code' => '00000',
            'city' => 'Stockholm',
            'country' => 'Sweden',
            'payment_flow' => 'purchase',
        ])->execute();

        self::assertSame(201, $checkout['status']);
        self::assertSame('captured', $checkout['order']['payment_status']);
        self::assertSame('completed', $checkout['order']['status']);
        self::assertSame('access_granted', $checkout['order']['fulfillment_status']);
        self::assertSame('digital-delivery', $checkout['order']['shipping_option']);
        self::assertSame('', $checkout['order']['shipping_carrier']);
        self::assertSame(0, $checkout['order']['shipping_minor']);
        self::assertSame('not_required', $checkout['order']['inventory_status']);
        self::assertSame('DIGITAL25', $checkout['order']['discount_code']);
        self::assertSame(4950, $checkout['order']['discount_minor']);
        self::assertCount(1, $checkout['order']['entitlements']);

        $entitlement = $checkout['order']['entitlements'][0];
        self::assertSame('digital_download', $entitlement['type']);
        self::assertSame('active', $entitlement['status']);
        self::assertSame(6, $entitlement['download_limit']);
        self::assertSame('https://downloads.langelermvc.test/starter-platform-license.zip', $entitlement['access_url']);
        self::assertNotSame('', $entitlement['access_key']);

        $access = $stack['orderService']->forAction('accessEntitlement', [], [
            'key' => (string) $entitlement['access_key'],
        ])->execute();

        self::assertSame(200, $access['status']);
        self::assertSame(1, $access['entitlement']['downloads_used']);
        self::assertSame('https://downloads.langelermvc.test/starter-platform-license.zip', $access['entitlement']['access_url']);

        $stack['auth']->logout();
        self::assertTrue($stack['auth']->attempt([
            'email' => 'admin@langelermvc.test',
            'password' => 'admin12345',
        ]));

        $detail = $stack['adminService']->forAction('order', [], [
            'order' => (int) $checkout['order']['id'],
        ])->execute();

        self::assertArrayNotHasKey('pack', $detail['order']['actions']);
        self::assertSame('active', $detail['order']['entitlements'][0]['status']);
        self::assertNotEmpty($detail['order']['entitlements'][0]['revoke_path']);

        $revoked = $stack['adminService']->forAction('revokeEntitlement', [], [
            'order' => (int) $checkout['order']['id'],
            'entitlement' => (int) $entitlement['id'],
        ])->execute();

        self::assertSame(200, $revoked['status']);
        self::assertSame('revoked', $revoked['order']['entitlements'][0]['status']);

        $blocked = $stack['orderService']->forAction('accessEntitlement', [], [
            'key' => (string) $entitlement['access_key'],
        ])->execute();

        self::assertSame(403, $blocked['status']);

        $reactivated = $stack['adminService']->forAction('activateEntitlement', [], [
            'order' => (int) $checkout['order']['id'],
            'entitlement' => (int) $entitlement['id'],
        ])->execute();

        self::assertSame(200, $reactivated['status']);
        self::assertSame('active', $reactivated['order']['entitlements'][0]['status']);
    }

    public function testSubscriptionRuntimeManagesSchedulesAdminTransitionsAndProviderEvents(): void
    {
        $stack = $this->makePlatformStack(seedCart: false, seedOrders: false);

        self::assertTrue($stack['auth']->attempt([
            'email' => 'customer@langelermvc.test',
            'password' => 'customer12345',
        ]));

        $product = $stack['products']->findPublishedBySlug('starter-platform-license');
        self::assertNotNull($product);
        $stack['products']->update((int) $product?->getKey(), [
            'price_minor' => 19900,
            'stock' => 0,
            'fulfillment_type' => 'subscription',
            'fulfillment_policy' => json_encode([
                'plan_code' => 'platform-pro-monthly',
                'plan_label' => 'Platform Pro Monthly',
                'interval' => 'monthly',
                'trial_days' => 7,
                'max_retries' => 2,
                'dunning_retry_days' => [1, 3],
                'access_url' => 'https://access.langelermvc.test/platform-pro',
                'provider_subscription_reference' => 'sub_testing_platform_pro',
            ], JSON_THROW_ON_ERROR),
        ]);

        $added = $stack['cartService']
            ->forAction('addItem', ['slug' => 'starter-platform-license', 'quantity' => 1])
            ->execute();

        self::assertSame(200, $added['status']);
        self::assertSame('subscription', $added['cart']['items'][0]['fulfillment_type']);
        self::assertSame('digital-delivery', $added['cart']['shipping_option']);

        $checkout = $stack['orderService']->forAction('checkout', [
            'name' => 'Subscription Customer',
            'email' => 'customer@langelermvc.test',
            'line_one' => 'Online',
            'postal_code' => '00000',
            'city' => 'Stockholm',
            'country' => 'Sweden',
            'payment_flow' => 'purchase',
        ])->execute();

        self::assertSame(201, $checkout['status']);
        self::assertSame('captured', $checkout['order']['payment_status']);
        self::assertSame('access_granted', $checkout['order']['fulfillment_status']);
        self::assertCount(1, $checkout['order']['entitlements']);
        self::assertCount(1, $checkout['order']['subscriptions']);
        self::assertSame('subscription', $checkout['order']['entitlements'][0]['type']);
        self::assertSame('trialing', $checkout['order']['subscriptions'][0]['status']);
        self::assertSame('monthly', $checkout['order']['subscriptions'][0]['interval']);
        self::assertSame('sub_testing_platform_pro', $checkout['order']['subscriptions'][0]['provider_subscription_reference']);
        self::assertNotSame('', (string) ($checkout['order']['subscriptions'][0]['next_billing_at'] ?? ''));

        $subscriptionId = (int) $checkout['order']['subscriptions'][0]['id'];
        $entitlementId = (int) $checkout['order']['entitlements'][0]['id'];

        $stack['auth']->logout();
        self::assertTrue($stack['auth']->attempt([
            'email' => 'admin@langelermvc.test',
            'password' => 'admin12345',
        ]));

        $paused = $stack['adminService']->forAction('pauseSubscription', [], [
            'order' => (int) $checkout['order']['id'],
            'subscription' => $subscriptionId,
        ])->execute();

        self::assertSame(200, $paused['status']);
        self::assertSame('paused', $paused['order']['subscriptions'][0]['status']);
        self::assertSame('pending', $paused['order']['entitlements'][0]['status']);

        $resumed = $stack['adminService']->forAction('resumeSubscription', [], [
            'order' => (int) $checkout['order']['id'],
            'subscription' => $subscriptionId,
        ])->execute();

        self::assertSame(200, $resumed['status']);
        self::assertSame('active', $resumed['order']['subscriptions'][0]['status']);
        self::assertSame('active', $resumed['order']['entitlements'][0]['status']);

        $failurePayload = [
            'event_id' => 'evt_sub_failure_1',
            'event_type' => 'subscription.payment_failed',
            'provider_subscription_reference' => 'sub_testing_platform_pro',
        ];
        $failureRaw = $stack['payments']->canonicalWebhookPayload($failurePayload);
        $failureWebhook = $stack['orderService']->forAction('subscriptionWebhook', [
            ...$failurePayload,
            '_webhook_raw_body' => $failureRaw,
            '_webhook_headers' => [
                'X-Langeler-Signature' => $stack['payments']->webhookPayloadSignature('testing', $failureRaw),
                'X-Langeler-Timestamp' => (string) time(),
            ],
        ], [
            'driver' => 'testing',
        ])->execute();

        self::assertSame(200, $failureWebhook['status']);
        self::assertSame('past_due', $failureWebhook['order']['subscriptions'][0]['status']);
        self::assertSame(1, $failureWebhook['order']['subscriptions'][0]['retry_count']);

        $exhaustedPayload = [
            'event_id' => 'evt_sub_failure_2',
            'event_type' => 'invoice.payment_failed',
            'provider_subscription_reference' => 'sub_testing_platform_pro',
        ];
        $exhaustedRaw = $stack['payments']->canonicalWebhookPayload($exhaustedPayload);
        $exhaustedWebhook = $stack['orderService']->forAction('subscriptionWebhook', [
            ...$exhaustedPayload,
            '_webhook_raw_body' => $exhaustedRaw,
            '_webhook_headers' => [
                'X-Langeler-Signature' => $stack['payments']->webhookPayloadSignature('testing', $exhaustedRaw),
                'X-Langeler-Timestamp' => (string) time(),
            ],
        ], [
            'driver' => 'testing',
        ])->execute();

        self::assertSame(200, $exhaustedWebhook['status']);
        self::assertSame('unpaid', $exhaustedWebhook['order']['subscriptions'][0]['status']);
        self::assertSame(2, $exhaustedWebhook['order']['subscriptions'][0]['retry_count']);
        self::assertSame('pending', $stack['entitlementRepository']->mapSummary($stack['entitlementRepository']->find($entitlementId))['status']);

        $ordersBeforeRenewal = (int) $stack['database']->fetchColumn('SELECT COUNT(*) FROM orders');
        $renewalPayload = [
            'event_id' => 'evt_sub_renewed_1',
            'event_type' => 'subscription.renewed',
            'provider_subscription_reference' => 'sub_testing_platform_pro',
            'amount_minor' => 19900,
            'currency' => 'SEK',
        ];
        $renewalRaw = $stack['payments']->canonicalWebhookPayload($renewalPayload);
        $renewalWebhook = $stack['orderService']->forAction('subscriptionWebhook', [
            ...$renewalPayload,
            '_webhook_raw_body' => $renewalRaw,
            '_webhook_headers' => [
                'X-Langeler-Signature' => $stack['payments']->webhookPayloadSignature('testing', $renewalRaw),
                'X-Langeler-Timestamp' => (string) time(),
            ],
        ], [
            'driver' => 'testing',
        ])->execute();

        self::assertSame(200, $renewalWebhook['status']);
        self::assertSame('active', $renewalWebhook['order']['subscriptions'][0]['status']);
        self::assertSame(1, $renewalWebhook['order']['subscriptions'][0]['renewal_count']);
        self::assertSame($ordersBeforeRenewal + 1, (int) $stack['database']->fetchColumn('SELECT COUNT(*) FROM orders'));
        self::assertSame('active', $stack['entitlementRepository']->mapSummary($stack['entitlementRepository']->find($entitlementId))['status']);

        $duplicateRenewal = $stack['orderService']->forAction('subscriptionWebhook', [
            ...$renewalPayload,
            '_webhook_raw_body' => $renewalRaw,
            '_webhook_headers' => [
                'X-Langeler-Signature' => $stack['payments']->webhookPayloadSignature('testing', $renewalRaw),
                'X-Langeler-Timestamp' => (string) time(),
            ],
        ], [
            'driver' => 'testing',
        ])->execute();

        self::assertSame(200, $duplicateRenewal['status']);
        self::assertTrue($duplicateRenewal['webhook']['idempotent']);
        self::assertSame($ordersBeforeRenewal + 1, (int) $stack['database']->fetchColumn('SELECT COUNT(*) FROM orders'));

        $cancelled = $stack['adminService']->forAction('cancelSubscription', [], [
            'order' => (int) $checkout['order']['id'],
            'subscription' => $subscriptionId,
        ])->execute();

        self::assertSame(200, $cancelled['status']);
        self::assertSame('cancelled', $cancelled['order']['subscriptions'][0]['status']);
        self::assertSame('revoked', $cancelled['order']['entitlements'][0]['status']);
    }

    public function testCheckoutRejectsIneligiblePromotionForSelectedShippingMethod(): void
    {
        $stack = $this->makePlatformStack(seedCart: false, seedOrders: false);

        self::assertTrue($stack['auth']->attempt([
            'email' => 'customer@langelermvc.test',
            'password' => 'customer12345',
        ]));

        $stack['cartService']
            ->forAction('addItem', ['slug' => 'starter-platform-license', 'quantity' => 1])
            ->execute();

        $checkout = $stack['orderService']->forAction('checkout', [
            'name' => 'Demo Customer',
            'email' => 'customer@langelermvc.test',
            'line_one' => 'Framework Street 1',
            'postal_code' => '12345',
            'city' => 'Stockholm',
            'country' => 'Sweden',
            'shipping_option' => 'budbee-home',
            'coupon_code' => 'FRIFRAKT',
        ])->execute();

        self::assertSame(422, $checkout['status']);
        self::assertStringContainsString('free-shipping eligible', (string) ($checkout['message'] ?? ''));
    }

    public function testAdminOrderActionsUseDashboardWrappersAndSharedLifecycleTransitions(): void
    {
        $stack = $this->makePlatformStack(seedCart: false, seedOrders: false);

        self::assertTrue($stack['auth']->attempt([
            'email' => 'customer@langelermvc.test',
            'password' => 'customer12345',
        ]));

        $stack['cartService']
            ->forAction('addItem', ['slug' => 'starter-platform-license', 'quantity' => 1])
            ->execute();

        $checkout = $stack['orderService']->forAction('checkout', [
            'name' => 'Demo Customer',
            'email' => 'customer@langelermvc.test',
            'line_one' => 'Framework Street 1',
            'postal_code' => '12345',
            'city' => 'Stockholm',
            'country' => 'Sweden',
            'shipping_option' => 'budbee-home',
        ])->execute();

        $stack['auth']->logout();
        self::assertTrue($stack['auth']->attempt([
            'email' => 'admin@langelermvc.test',
            'password' => 'admin12345',
        ]));

        $detail = $stack['adminService']->forAction('order', [], [
            'order' => (int) $checkout['order']['id'],
        ])->execute();

        self::assertSame('/admin/orders/' . $checkout['order']['id'] . '/capture', $detail['order']['actions']['capture'] ?? null);
        self::assertSame('/admin/orders/' . $checkout['order']['id'] . '/cancel', $detail['order']['actions']['cancel'] ?? null);

        $captured = $stack['adminService']->forAction('captureOrder', [], [
            'order' => (int) $checkout['order']['id'],
        ])->execute();

        self::assertSame(200, $captured['status']);
        self::assertSame('/admin/orders/' . $checkout['order']['id'], $captured['redirect']);
        self::assertSame('captured', $captured['order']['payment_status']);
        self::assertSame('ready_to_fulfill', $captured['order']['fulfillment_status']);
        self::assertSame('committed', $captured['order']['inventory_status']);
        self::assertSame('Budbee', $captured['order']['shipping_carrier_label']);
        self::assertSame('/admin/orders/' . $checkout['order']['id'] . '/pack', $captured['order']['actions']['pack'] ?? null);
        self::assertSame('/admin/orders/' . $checkout['order']['id'] . '/refund', $captured['order']['actions']['refund'] ?? null);

        $packed = $stack['adminService']->forAction('packOrder', [], [
            'order' => (int) $checkout['order']['id'],
        ])->execute();

        self::assertSame(200, $packed['status']);
        self::assertSame('packed', $packed['order']['fulfillment_status']);
        self::assertSame('processing', $packed['order']['status']);
        self::assertSame('/admin/orders/' . $checkout['order']['id'] . '/ship', $packed['order']['actions']['ship'] ?? null);
        self::assertSame('/admin/orders/' . $checkout['order']['id'] . '/book-shipment', $packed['order']['actions']['book_shipment'] ?? null);
        self::assertSame('/admin/orders/' . $checkout['order']['id'] . '/service-points', $packed['order']['actions']['service_points'] ?? null);

        $servicePoints = $stack['adminService']->forAction('servicePointsOrder', [
            'carrier_code' => 'budbee',
            'postal_code' => '11842',
            'city' => 'Stockholm',
            'service_level' => 'locker',
        ], [
            'order' => (int) $checkout['order']['id'],
        ])->execute();

        self::assertSame(200, $servicePoints['status']);
        self::assertNotEmpty($servicePoints['service_points']);
        self::assertStringStartsWith('BB-SP-', (string) ($servicePoints['service_points'][0]['id'] ?? ''));

        $booked = $stack['adminService']->forAction('bookShipmentOrder', [
            'carrier_code' => 'budbee',
            'service_point_id' => (string) ($servicePoints['service_points'][0]['id'] ?? ''),
            'service_point_name' => (string) ($servicePoints['service_points'][0]['label'] ?? ''),
        ], [
            'order' => (int) $checkout['order']['id'],
        ])->execute();

        self::assertSame(200, $booked['status']);
        self::assertSame('packed', $booked['order']['fulfillment_status']);
        self::assertNotSame('', (string) ($booked['order']['tracking_number'] ?? ''));
        self::assertStringContainsString('/budbee/', (string) ($booked['order']['shipment_label_url'] ?? ''));
        self::assertSame('/admin/orders/' . $checkout['order']['id'] . '/cancel-shipment', $booked['order']['actions']['cancel_shipment'] ?? null);

        $cancelledShipment = $stack['adminService']->forAction('cancelShipmentOrder', [
            'reason' => 'Operator selected a different service point.',
        ], [
            'order' => (int) $checkout['order']['id'],
        ])->execute();

        self::assertSame(200, $cancelledShipment['status']);
        self::assertSame('packed', $cancelledShipment['order']['fulfillment_status']);
        self::assertSame('', (string) ($cancelledShipment['order']['tracking_number'] ?? ''));
        self::assertSame('cancelled', (string) ($cancelledShipment['order']['tracking_events'][1]['status'] ?? ''));

        $bookedAgain = $stack['adminService']->forAction('bookShipmentOrder', [
            'carrier_code' => 'budbee',
            'service_point_id' => (string) ($servicePoints['service_points'][1]['id'] ?? ''),
            'service_point_name' => (string) ($servicePoints['service_points'][1]['label'] ?? ''),
        ], [
            'order' => (int) $checkout['order']['id'],
        ])->execute();

        self::assertSame(200, $bookedAgain['status']);
        self::assertNotSame('', (string) ($bookedAgain['order']['tracking_number'] ?? ''));

        $shipped = $stack['adminService']->forAction('shipOrder', [
            'carrier_code' => 'budbee',
        ], [
            'order' => (int) $checkout['order']['id'],
        ])->execute();

        self::assertSame(200, $shipped['status']);
        self::assertSame('shipped', $shipped['order']['fulfillment_status']);
        self::assertSame('fulfilled', $shipped['order']['status']);
        self::assertSame('Budbee', $shipped['order']['shipping_carrier_label']);
        self::assertSame($bookedAgain['order']['tracking_number'], $shipped['order']['tracking_number']);
        self::assertSame($bookedAgain['order']['shipment_reference'], $shipped['order']['shipment_reference']);
        self::assertSame('https://budbee.com', $shipped['order']['tracking_url']);
        self::assertContains('Mina Paket', array_map(
            static fn(array $app): string => (string) ($app['label'] ?? ''),
            (array) ($shipped['order']['tracking_apps'] ?? [])
        ));
        self::assertSame('/admin/orders/' . $checkout['order']['id'] . '/sync-tracking', $shipped['order']['actions']['sync_tracking'] ?? null);
        self::assertSame('/admin/orders/' . $checkout['order']['id'] . '/cancel-shipment', $shipped['order']['actions']['cancel_shipment'] ?? null);
        self::assertSame('/admin/orders/' . $checkout['order']['id'] . '/deliver', $shipped['order']['actions']['deliver'] ?? null);

        $synced = $stack['adminService']->forAction('syncTrackingOrder', [
            'tracking_status' => 'in_transit',
            'location' => 'Stockholm terminal',
        ], [
            'order' => (int) $checkout['order']['id'],
        ])->execute();

        self::assertSame(200, $synced['status']);
        self::assertSame('shipped', $synced['order']['fulfillment_status']);
        self::assertSame('in_transit', (string) ($synced['order']['tracking_events'][4]['status'] ?? ''));

        $delivered = $stack['adminService']->forAction('deliverOrder', [], [
            'order' => (int) $checkout['order']['id'],
        ])->execute();

        self::assertSame(200, $delivered['status']);
        self::assertSame('delivered', $delivered['order']['fulfillment_status']);
        self::assertSame('completed', $delivered['order']['status']);
        self::assertNotSame('', (string) ($delivered['order']['delivered_at'] ?? ''));
        self::assertGreaterThanOrEqual(6, count((array) ($delivered['order']['tracking_events'] ?? [])));
        self::assertArrayNotHasKey('deliver', $delivered['order']['actions']);
    }

    public function testAdminCatalogLifecycleActionsApplyGuardrailsAndSharedManagement(): void
    {
        $stack = $this->makePlatformStack(seedCart: false, seedOrders: false);

        self::assertTrue($stack['auth']->attempt([
            'email' => 'admin@langelermvc.test',
            'password' => 'admin12345',
        ]));

        $createdCategory = $stack['adminService']->forAction('saveCategory', [
            'name' => 'Disposable Category',
            'slug' => 'disposable-category',
            'description' => 'Temporary category for lifecycle verification.',
            'is_published' => true,
        ])->execute();

        self::assertSame(200, $createdCategory['status']);

        $category = null;

        foreach ($createdCategory['categories'] as $entry) {
            if (($entry['slug'] ?? '') === 'disposable-category') {
                $category = $entry;
                break;
            }
        }

        self::assertIsArray($category);
        self::assertSame('/admin/catalog/categories/' . $category['id'] . '/delete', $category['delete_path'] ?? null);

        $createdProduct = $stack['adminService']->forAction('saveProduct', [
            'category_id' => (int) $category['id'],
            'name' => 'Disposable Product',
            'slug' => 'disposable-product',
            'description' => 'Temporary product for lifecycle verification.',
            'price_minor' => 2500,
            'currency' => 'SEK',
            'visibility' => 'published',
            'stock' => 3,
            'media' => '',
        ])->execute();

        self::assertSame(200, $createdProduct['status']);

        $product = null;

        foreach ($createdProduct['catalog'] as $entry) {
            if (($entry['slug'] ?? '') === 'disposable-product') {
                $product = $entry;
                break;
            }
        }

        self::assertIsArray($product);
        self::assertSame('/admin/catalog/products/' . $product['id'] . '/archive', $product['archive_path'] ?? null);

        $blockedCategoryDelete = $stack['adminService']->forAction('deleteCategory', [], [
            'category' => (int) $category['id'],
        ])->execute();

        self::assertSame(409, $blockedCategoryDelete['status']);
        self::assertStringContainsString('still contains products', $blockedCategoryDelete['message']);

        $archivedProduct = $stack['adminService']->forAction('archiveProduct', [], [
            'product' => (int) $product['id'],
        ])->execute();

        self::assertSame(200, $archivedProduct['status']);

        $archivedSummary = null;

        foreach ($archivedProduct['catalog'] as $entry) {
            if (($entry['slug'] ?? '') === 'disposable-product') {
                $archivedSummary = $entry;
                break;
            }
        }

        self::assertIsArray($archivedSummary);
        self::assertSame('archived', $archivedSummary['visibility'] ?? null);

        $deletedProduct = $stack['adminService']->forAction('deleteProduct', [], [
            'product' => (int) $product['id'],
        ])->execute();

        self::assertSame(200, $deletedProduct['status']);
        self::assertNull($stack['products']->findBySlug('disposable-product'));

        $unpublishedCategory = $stack['adminService']->forAction('unpublishCategory', [], [
            'category' => (int) $category['id'],
        ])->execute();

        self::assertSame(200, $unpublishedCategory['status']);

        $deletedCategory = $stack['adminService']->forAction('deleteCategory', [], [
            'category' => (int) $category['id'],
        ])->execute();

        self::assertSame(200, $deletedCategory['status']);
        self::assertNull($stack['categories']->findBySlug('disposable-category'));
    }

    public function testPaymentCatalogExposesFrameworkDriversForSupportedProviders(): void
    {
        $stack = $this->makePlatformStack(seedCart: false, seedOrders: false);
        $catalog = $stack['payments']->driverCatalog();

        self::assertSame(
            ['testing', 'card', 'crypto', 'paypal', 'klarna', 'swish', 'qliro', 'walley'],
            $stack['payments']->availableDrivers()
        );
        self::assertSame('Credit / Debit Card', $catalog['card']['label']);
        self::assertContains('SE', $catalog['swish']['regions']);
        self::assertContains('bnpl', $catalog['klarna']['methods']);
        self::assertContains('wallet', $catalog['paypal']['methods']);
        self::assertContains('crypto', $catalog['crypto']['methods']);

        $scenarios = [
            'card' => ['method' => 'card', 'flow' => 'authorize_capture', 'status' => 'authorized', 'type' => null],
            'paypal' => ['method' => 'wallet', 'flow' => 'redirect', 'status' => 'requires_action', 'type' => 'redirect'],
            'klarna' => ['method' => 'bnpl', 'flow' => 'redirect', 'status' => 'requires_action', 'type' => 'klarna_sdk'],
            'swish' => ['method' => 'local_instant', 'flow' => 'redirect', 'status' => 'requires_action', 'type' => 'swish'],
            'qliro' => ['method' => 'bnpl', 'flow' => 'redirect', 'status' => 'requires_action', 'type' => 'iframe'],
            'walley' => ['method' => 'bnpl', 'flow' => 'redirect', 'status' => 'requires_action', 'type' => 'redirect'],
            'crypto' => ['method' => 'crypto', 'flow' => 'async', 'status' => 'processing', 'type' => 'crypto_invoice'],
        ];

        foreach ($scenarios as $driver => $scenario) {
            $intent = $stack['payments']->createIntent(
                15000,
                'SEK',
                'Driver compatibility verification',
                ['asset' => 'BTC'],
                $scenario['method'],
                $scenario['flow'],
                'compatibility-' . $driver,
                $driver
            );
            $result = $stack['payments']->authorize($intent);

            self::assertTrue($result->successful, 'Expected authorization to succeed for [' . $driver . '].');
            self::assertSame($driver, $result->driver);
            self::assertSame($driver, $result->intent->driver);
            self::assertSame($scenario['status'], $result->intent->status);

            if ($scenario['type'] !== null) {
                self::assertSame($scenario['type'], $result->intent->nextAction['type'] ?? null);
            }
        }
    }

    public function testAdminAndCommerceSurfacesExposeCompletedHtmlAndJsonParity(): void
    {
        $stack = $this->makePlatformStack(seedCart: true, seedOrders: true);

        self::assertTrue($stack['auth']->attempt([
            'email' => 'admin@langelermvc.test',
            'password' => 'admin12345',
        ]));

        $catalog = $stack['catalogService']->forAction('catalog', ['page' => 1])->execute();
        $catalogJson = (new ShopResource($catalog))->toArray();

        self::assertSame(200, $catalog['status']);
        self::assertNotEmpty($catalog['products']);
        self::assertSame('ShopModule', $catalogJson['meta']['module']);
        self::assertNotEmpty($catalogJson['data']['products']);

        $cartPage = $stack['cartService']->forAction('show')->execute();
        $cartJson = (new CartResource($cartPage))->toArray();

        self::assertSame(200, $cartPage['status']);
        self::assertArrayHasKey('cart', $cartJson['data']);

        $orders = $stack['adminService']->forAction('orders')->execute();
        $pagesAdmin = $stack['adminService']->forAction('pages')->execute();
        $catalogAdmin = $stack['adminService']->forAction('catalog')->execute();
        $promotionsAdmin = $stack['adminService']->forAction('promotions')->execute();
        $cartsAdmin = $stack['adminService']->forAction('carts')->execute();
        $operations = $stack['adminService']->forAction('operations')->execute();
        $adminJson = (new AdminResource($operations))->toArray();

        self::assertSame(200, $catalogAdmin['status']);
        self::assertSame(200, $pagesAdmin['status']);
        self::assertArrayHasKey('page_form', $pagesAdmin);
        self::assertArrayHasKey('page_metrics', $pagesAdmin);
        self::assertNotEmpty($pagesAdmin['pages']);
        self::assertNotEmpty($catalogAdmin['catalog']);
        self::assertArrayHasKey('category_form', $catalogAdmin);
        self::assertArrayHasKey('product_form', $catalogAdmin);
        self::assertArrayHasKey('catalog_metrics', $catalogAdmin);
        self::assertSame(200, $promotionsAdmin['status']);
        self::assertArrayHasKey('promotion_form', $promotionsAdmin);
        self::assertArrayHasKey('promotion_metrics', $promotionsAdmin);
        self::assertArrayHasKey('promotion_usage', $promotionsAdmin);
        self::assertNotEmpty($promotionsAdmin['configured_promotions']);
        self::assertSame(200, $cartsAdmin['status']);
        self::assertNotEmpty($cartsAdmin['carts']);
        self::assertSame(200, $orders['status']);
        self::assertNotEmpty($orders['orders']);
        self::assertNotEmpty($orders['orders'][0]['view_path'] ?? '');
        self::assertSame(200, $operations['status']);
        self::assertArrayHasKey('queue', $operations['operations']);
        self::assertArrayHasKey('health', $operations['operations']);
        self::assertArrayHasKey('audit', $operations['operations']);
        self::assertArrayHasKey('payments', $adminJson['data']['operations']);
        self::assertArrayHasKey('methods', $operations['operations']['payments']);
        self::assertArrayHasKey('flows', $operations['operations']['payments']);
        self::assertArrayHasKey('catalog', $operations['operations']['payments']);
        self::assertArrayHasKey('paypal', $operations['operations']['payments']['catalog']);

        $createdCategory = $stack['adminService']->forAction('saveCategory', [
            'name' => 'Operations Blueprints',
            'slug' => 'operations-blueprints',
            'description' => 'Admin-created category.',
            'is_published' => '1',
        ])->execute();

        self::assertSame(200, $createdCategory['status']);
        self::assertTrue(
            in_array(
                'operations-blueprints',
                array_map(static fn(array $category): string => (string) ($category['slug'] ?? ''), $createdCategory['categories']),
                true
            )
        );

        $createdCategoryId = 0;

        foreach ((array) $createdCategory['categories'] as $category) {
            if (($category['slug'] ?? '') === 'operations-blueprints') {
                $createdCategoryId = (int) ($category['id'] ?? 0);
                break;
            }
        }

        self::assertGreaterThan(0, $createdCategoryId);
        self::assertSame(1, count($stack['queue']->pending('notifications')));

        $createdProduct = $stack['adminService']->forAction('saveProduct', [
            'category_id' => $createdCategoryId,
            'name' => 'Operations Runbook',
            'slug' => 'operations-runbook',
            'description' => 'Admin-created product.',
            'price_minor' => 12900,
            'currency' => 'SEK',
            'visibility' => 'draft',
            'stock' => 5,
            'media' => '/assets/images/starter-platform-license.svg',
        ])->execute();

        self::assertSame(200, $createdProduct['status']);
        self::assertTrue(
            in_array(
                'operations-runbook',
                array_map(static fn(array $product): string => (string) ($product['slug'] ?? ''), $createdProduct['catalog']),
                true
            )
        );
        self::assertSame(2, count($stack['queue']->pending('notifications')));

        while ($stack['queue']->work('notifications', 1) > 0) {
        }

        $adminNotifications = $stack['notifications']->databaseNotifications(1);
        self::assertCount(2, $adminNotifications);
        self::assertSame('shop.category.saved', $adminNotifications[0]['data']['event'] ?? null);
        self::assertSame('created', $adminNotifications[0]['data']['action'] ?? null);
        self::assertSame('shop.product.saved', $adminNotifications[1]['data']['event'] ?? null);
        self::assertSame('created', $adminNotifications[1]['data']['action'] ?? null);

        $orderDetail = $stack['adminService']->forAction('order', [], [
            'order' => (int) ($orders['orders'][0]['id'] ?? 0),
        ])->execute();

        self::assertSame(200, $orderDetail['status']);
        self::assertSame($orders['orders'][0]['order_number'] ?? null, $orderDetail['order']['order_number'] ?? null);
        self::assertNotEmpty($orderDetail['order']['items']);
        self::assertArrayHasKey('public_view', $orderDetail['order']['actions']);
    }

    public function testShopSeededCatalogMediaPointsToTrackedPublicAssets(): void
    {
        $stack = $this->makePlatformStack(seedCart: false, seedOrders: false);
        $catalog = $stack['catalogService']->forAction('catalog', ['page' => 1])->execute();
        $projectRoot = dirname(__DIR__, 2);

        self::assertNotEmpty($catalog['products']);

        foreach ($catalog['products'] as $product) {
            self::assertIsArray($product);
            self::assertNotEmpty($product['media'] ?? []);

            foreach ((array) ($product['media'] ?? []) as $mediaPath) {
                self::assertIsString($mediaPath);
                self::assertFileExists($projectRoot . '/Public' . $mediaPath);
            }
        }
    }

    private function makePlatformStack(bool $seedCart, bool $seedOrders): array
    {
        $database = $this->makeSqliteDatabase();
        $errors = new ErrorManager(new ExceptionProvider());
        $config = $this->makeConfig();
        $moduleClasses = [
            CreatePagesTable::class,
            PageSeed::class,
            CreateUserPlatformTables::class,
            UserPlatformSeed::class,
            CreateShopTables::class,
            AddProductFulfillmentColumns::class,
            ShopSeed::class,
            CreateCartTables::class,
            AddCartDiscountColumns::class,
            CreatePromotionTables::class,
            CreatePromotionUsageTable::class,
            CreateOrderTables::class,
            AddOrderCommerceStateColumns::class,
            AddOrderDiscountSnapshotColumns::class,
            AddOrderShipmentTrackingColumns::class,
            CreateOrderEntitlementsTable::class,
            CreateOrderSubscriptionsTable::class,
            CreatePaymentWebhookEventsTable::class,
            MergeCartOnLoginListener::class,
            CatalogActivityNotificationListener::class,
            OrderLifecycleNotificationListener::class,
        ];

        if ($seedCart) {
            $moduleClasses[] = CartSeed::class;
        }

        if ($seedOrders) {
            $moduleClasses[] = OrderSeed::class;
        }

        $modules = new TestModuleManager($moduleClasses, [
            'WebModule' => '/tmp/WebModule',
            'UserModule' => '/tmp/UserModule',
            'ShopModule' => '/tmp/ShopModule',
            'CartModule' => '/tmp/CartModule',
            'OrderModule' => '/tmp/OrderModule',
            'AdminModule' => '/tmp/AdminModule',
        ]);

        $migrations = new MigrationRunner($database, $modules, $errors);
        $migrations->migrate();

        $seeds = new SeedRunner($database, $modules, $errors);
        $seeds->run('WebModule');
        $seeds->run('UserModule');
        $seeds->run('ShopModule');

        if ($seedCart) {
            $seeds->run('CartModule');
        }

        if ($seedOrders) {
            $seeds->run('OrderModule');
        }

        $crypto = $this->makeCryptoStub();
        $fileManager = new FileManager();
        $sessionManager = new SessionManager($fileManager, $errors, $database);
        $session = new Session($config, $sessionManager, $crypto, $errors);
        $mail = new MailManager($config, $fileManager, $errors);
        $provider = new DatabaseUserProvider($config, $database, $crypto, $errors);
        $guard = new SessionGuard($session, $config, $provider, $crypto, $errors);
        $registry = new PermissionRegistry($config);
        $gate = new Gate($guard, $provider, $registry, new PolicyResolver());
        $passwordBroker = $this->createStub(PasswordBrokerInterface::class);

        $queueDriver = new DatabaseQueueDriver($database);
        $queueProvider = new TestQueueProvider($queueDriver);
        $failedJobs = new DatabaseFailedJobStore($database);
        $coreProvider = new TestCoreProvider();
        $notificationProvider = new TestNotificationProvider([
            'database' => new DatabaseNotificationChannel($database),
            'mail' => new MailNotificationChannel($mail),
        ]);
        $paymentProvider = new PaymentProvider();
        $queue = new QueueManager($config, $queueProvider, $failedJobs, $modules, $coreProvider, $errors);
        $notifications = new NotificationManager($config, $notificationProvider, $database, $coreProvider);
        $payments = new PaymentManager($config, $paymentProvider);
        $events = new EventDispatcher($queue, $modules, $coreProvider, $config);
        $audit = new AuditLogger($database, $config, $errors);
        $router = $this->makeRouterDouble();
        $health = new HealthManager(
            $config,
            $database,
            $cache = $this->makeCacheDouble(),
            $sessionManager,
            $queue,
            $notifications,
            $payments,
            new \App\Utilities\Managers\Support\PasskeyManager($config, $session, $errors),
            $mail,
            new \App\Utilities\Managers\Support\OtpManager($config),
            $modules,
            $router,
            $events,
            $audit,
            new class implements \App\Contracts\Support\FrameworkDoctorInterface {
                public function inspect(bool $strict = false): array
                {
                    return [
                        'status' => 200,
                        'healthy' => true,
                        'errors' => [],
                        'warnings' => [],
                        'checks' => [],
                    ];
                }
            }
        );
        $auth = new AuthManager($guard, $gate, $passwordBroker, $provider, $registry, $events, $audit);
        $httpSecurity = new HttpSecurityManager($config, $cache);

        $users = new UserRepository($database);
        $roles = new RoleRepository($database);
        $permissions = new PermissionRepository($database);
        $pages = new PageRepository($database);
        $products = new ProductRepository($database);
        $categories = new CategoryRepository($database);
        $carts = new CartRepository($database);
        $cartItems = new CartItemRepository($database);
        $promotionRepository = new PromotionRepository($database);
        $orders = new OrderRepository($database);
        $orderItems = new OrderItemRepository($database);
        $entitlementRepository = new OrderEntitlementRepository($database);
        $subscriptionRepository = new OrderSubscriptionRepository($database);
        $webhookEvents = new PaymentWebhookEventRepository($database);
        $addresses = new OrderAddressRepository($database);
        $totals = new CommerceTotalsCalculator($config);
        $shipping = new ShippingManager($config);
        $promotions = new PromotionManager($config, $promotionRepository);
        $pricing = new CartPricingManager($shipping, $promotions, $totals);
        $inventory = new InventoryManager($products);
        $catalogLifecycle = new CatalogLifecycleManager($categories, $products, $cartItems, $orderItems, $events, $audit, $auth);
        $entitlements = new EntitlementManager($entitlementRepository, $orders, $orderItems, $events, $auth, $audit, $config);
        $subscriptions = new SubscriptionManager($subscriptionRepository, $orders, $orderItems, $entitlementRepository, $events, $auth, $audit, $config);
        $lifecycle = new OrderLifecycleManager($database, $orders, $orderItems, $payments, $events, $auth, $audit, $inventory, $shipping, $entitlements, $subscriptions);

        $catalogService = new CatalogService($products, $categories);
        $cartService = new CartService($carts, $cartItems, $products, $session, $auth, $pricing);
        $orderService = new OrderService(
            $database,
            $orders,
            $orderItems,
            $addresses,
            $carts,
            $cartItems,
            $pricing,
            $inventory,
            $lifecycle,
            $shipping,
            $entitlements,
            $subscriptions,
            $payments,
            $events,
            $auth,
            $session,
            $httpSecurity,
            $audit,
            $promotionRepository,
            $webhookEvents
        );
        $adminService = new AdminAccessService(
            $auth,
            $users,
            $roles,
            $permissions,
            $pages,
            $products,
            $categories,
            $carts,
            $cartItems,
            $promotionRepository,
            $orders,
            $orderItems,
            $addresses,
            $catalogLifecycle,
            $pricing,
            $totals,
            $lifecycle,
            $shipping,
            $entitlements,
            $subscriptions,
            $modules,
            $cache,
            $sessionManager,
            $queue,
            $notifications,
            $payments,
            $events,
            $health,
            $audit,
            $router,
            $config
        );

        $modules->setResolved([
            MergeCartOnLoginListener::class => new MergeCartOnLoginListener(
                $cartService,
                $notifications,
                $users
            ),
            CatalogActivityNotificationListener::class => new CatalogActivityNotificationListener(
                $notifications,
                $users
            ),
            OrderLifecycleNotificationListener::class => new OrderLifecycleNotificationListener(
                $notifications,
                $orders,
                $addresses,
                $users
            ),
        ]);
        $coreProvider->setResolved(NotificationManagerInterface::class, $notifications);
        $coreProvider->setResolved(QueueManager::class, $queue);
        $coreProvider->setResolved(AuditLogger::class, $audit);
        $coreProvider->setResolved(HealthManager::class, $health);

        return [
            'adminService' => $adminService,
            'auth' => $auth,
            'cartItems' => $cartItems,
            'cartService' => $cartService,
            'carts' => $carts,
            'categories' => $categories,
            'catalogService' => $catalogService,
            'config' => $config,
            'database' => $database,
            'events' => $events,
            'entitlements' => $entitlements,
            'entitlementRepository' => $entitlementRepository,
            'subscriptions' => $subscriptions,
            'subscriptionRepository' => $subscriptionRepository,
            'inventory' => $inventory,
            'lifecycle' => $lifecycle,
            'mail' => $mail,
            'notifications' => $notifications,
            'orderService' => $orderService,
            'orders' => $orders,
            'pages' => $pages,
            'payments' => $payments,
            'pricing' => $pricing,
            'products' => $products,
            'paymentWebhookEvents' => $webhookEvents,
            'promotions' => $promotions,
            'promotionRepository' => $promotionRepository,
            'queue' => $queue,
            'shipping' => $shipping,
            'totals' => $totals,
        ];
    }

    private function makeSqliteDatabase(): Database
    {
        $settings = new class extends SettingsManager {
            public function __construct()
            {
            }

            public function getAllSettings(string $fileName): array
            {
                return [
                    'DRIVER' => 'sqlite',
                    'CONNECTION' => 'sqlite',
                    'DATABASE' => ':memory:',
                ];
            }
        };

        return new Database(
            $settings,
            new ErrorManager(new ExceptionProvider()),
            new PDO('sqlite::memory:')
        );
    }

    private function makeConfig(): TestFrameworkConfig
    {
        $sessionPath = sys_get_temp_dir() . '/langelermvc-sessions-' . bin2hex(random_bytes(4));
        $this->tempPaths[] = $sessionPath;

        return new TestFrameworkConfig([
            'app' => [
                'NAME' => 'LangelerMVC',
                'URL' => 'https://langelermvc.test',
            ],
            'auth' => [
                'GUARD' => 'session',
                'DEFAULT_ROLE' => 'customer',
                'USER_REPOSITORY' => UserRepository::class,
                'PASSWORD_HASHER' => 'default',
                'OTP' => [
                    'DIGITS' => 6,
                    'PERIOD' => 30,
                    'ALGORITHM' => 'sha1',
                    'RECOVERY_CODES' => 8,
                ],
            ],
            'mail' => [
                'MAILER' => 'array',
                'FROM' => 'LangelerMVC <no-reply@langelermvc.test>',
            ],
            'notifications' => [
                'QUEUE' => false,
                'QUEUE_NAME' => 'notifications',
                'DEFAULT_CHANNELS' => ['database', 'mail'],
            ],
            'payment' => [
                'DRIVER' => 'testing',
                'CURRENCY' => 'SEK',
                'DEFAULT_METHOD' => 'card',
                'DEFAULT_FLOW' => 'authorize_capture',
                'WEBHOOKS' => [
                    'ENABLED' => true,
                    'REQUIRE_SIGNATURE' => true,
                    'SIGNATURE_HEADER' => 'X-Langeler-Signature',
                    'EVENT_ID_HEADER' => 'X-Langeler-Event',
                    'TIMESTAMP_HEADER' => 'X-Langeler-Timestamp',
                    'TOLERANCE_SECONDS' => 300,
                    'SECRETS' => [
                        'testing' => 'testing-webhook-secret',
                    ],
                ],
                'DRIVERS' => [
                    'testing' => [
                        'ENABLED' => true,
                        'LABEL' => 'Testing Reference Driver',
                        'MODE' => 'reference',
                        'METHODS' => ['card', 'wallet', 'bank_transfer', 'bnpl', 'local_instant', 'manual', 'crypto'],
                        'FLOWS' => ['authorize_capture', 'purchase', 'redirect', 'async', 'manual_review'],
                    ],
                    'card' => [
                        'ENABLED' => true,
                        'LABEL' => 'Credit / Debit Card',
                        'MODE' => 'reference',
                        'METHODS' => ['card'],
                        'FLOWS' => ['authorize_capture', 'purchase', 'redirect'],
                    ],
                    'crypto' => [
                        'ENABLED' => true,
                        'LABEL' => 'Crypto',
                        'MODE' => 'reference',
                        'METHODS' => ['crypto'],
                        'FLOWS' => ['async', 'redirect', 'manual_review'],
                    ],
                    'paypal' => [
                        'ENABLED' => true,
                        'LABEL' => 'PayPal',
                        'MODE' => 'reference',
                        'METHODS' => ['wallet', 'card'],
                        'FLOWS' => ['authorize_capture', 'purchase', 'redirect'],
                    ],
                    'klarna' => [
                        'ENABLED' => true,
                        'LABEL' => 'Klarna',
                        'MODE' => 'reference',
                        'METHODS' => ['bnpl'],
                        'FLOWS' => ['redirect', 'authorize_capture'],
                    ],
                    'swish' => [
                        'ENABLED' => true,
                        'LABEL' => 'Swish',
                        'MODE' => 'reference',
                        'METHODS' => ['local_instant'],
                        'FLOWS' => ['redirect', 'async'],
                    ],
                    'qliro' => [
                        'ENABLED' => true,
                        'LABEL' => 'Qliro',
                        'MODE' => 'reference',
                        'METHODS' => ['card', 'bnpl', 'local_instant', 'bank_transfer'],
                        'FLOWS' => ['redirect', 'authorize_capture'],
                    ],
                    'walley' => [
                        'ENABLED' => true,
                        'LABEL' => 'Walley',
                        'MODE' => 'reference',
                        'METHODS' => ['bnpl'],
                        'FLOWS' => ['redirect', 'authorize_capture'],
                    ],
                ],
            ],
            'commerce' => [
                'CURRENCY' => 'SEK',
                'TAX' => [
                    'RATE_BPS' => 2500,
                ],
                'SHIPPING' => [
                    'FLAT_RATE_MINOR' => 1490,
                    'FREE_OVER_MINOR' => 50000,
                ],
                'DISCOUNT' => [
                    'RATE_BPS' => 0,
                    'MAX_MINOR' => 0,
                ],
                'PROMOTIONS' => [
                    'VALKOMMEN10' => [
                        'LABEL' => 'Valkommen 10%',
                        'TYPE' => 'percentage',
                        'RATE_BPS' => 1000,
                        'MAX_DISCOUNT_MINOR' => 3000,
                        'MIN_SUBTOTAL_MINOR' => 5000,
                        'ALLOWED_COUNTRIES' => ['SE', 'NO', 'DK', 'FI'],
                        'ALLOWED_ZONES' => ['SE', 'NORDIC'],
                    ],
                    'FRIFRAKT' => [
                        'LABEL' => 'Fri Frakt',
                        'TYPE' => 'free_shipping',
                        'MIN_SUBTOTAL_MINOR' => 5000,
                        'FREE_SHIPPING_ELIGIBLE_ONLY' => true,
                        'ALLOWED_COUNTRIES' => ['SE'],
                        'ALLOWED_ZONES' => ['SE'],
                    ],
                    'LOCKER49' => [
                        'LABEL' => 'Locker 49 kr',
                        'TYPE' => 'shipping_fixed',
                        'SHIPPING_RATE_MINOR' => 490,
                        'ALLOWED_COUNTRIES' => ['SE'],
                        'ALLOWED_ZONES' => ['SE'],
                        'ALLOWED_CARRIERS' => ['instabox', 'budbee', 'postnord'],
                        'ALLOWED_SHIPPING_OPTIONS' => ['instabox-locker', 'budbee-box', 'postnord-service-point'],
                    ],
                    'DIGITAL25' => [
                        'LABEL' => 'Digital 25%',
                        'TYPE' => 'percentage',
                        'APPLIES_TO' => 'qualified_items',
                        'RATE_BPS' => 2500,
                        'MAX_DISCOUNT_MINOR_BY_CURRENCY' => [
                            'SEK' => 5000,
                        ],
                        'ALLOWED_FULFILLMENT_TYPES' => ['digital_download', 'virtual_access'],
                        'ALLOWED_CURRENCIES' => ['SEK'],
                    ],
                ],
                'INVENTORY' => [
                    'RESERVE_ON_CHECKOUT' => true,
                    'RELEASE_ON_CANCEL' => true,
                ],
                'FULFILLMENT' => [
                    'DEFAULT_TYPE' => 'physical_shipping',
                    'AUTO_READY_ON_CAPTURE' => true,
                    'NO_SHIPPING_TYPES' => ['digital_download', 'virtual_access', 'subscription'],
                    'SHIPPING_REQUIRED_TYPES' => ['physical_shipping', 'preorder'],
                    'PICKUP_TYPES' => ['store_pickup', 'scheduled_pickup'],
                    'STOCK_MANAGED_TYPES' => ['physical_shipping', 'store_pickup', 'scheduled_pickup'],
                    'DIGITAL_DELIVERY_OPTION' => [
                        'CODE' => 'digital-delivery',
                        'LABEL' => 'Digital / online delivery',
                        'SERVICE_LABEL' => 'Instant access after payment',
                    ],
                ],
            ],
            'queue' => [
                'DRIVER' => 'database',
                'DEFAULT_QUEUE' => 'default',
            ],
            'session' => [
                'DRIVER' => 'file',
                'NAME' => 'langelermvc_session',
                'LIFETIME' => 120,
                'COOKIE' => [
                    'PATH' => '/',
                    'DOMAIN' => '',
                    'SECURE' => false,
                    'HTTPONLY' => true,
                    'SAME_SITE' => 'Lax',
                ],
                'SAVE' => [
                    'PATH' => $sessionPath,
                ],
                'GC' => [
                    'PROBABILITY' => 1,
                    'DIVISOR' => 100,
                    'MAX_LIFETIME' => 1440,
                ],
                'NATIVE' => [
                    'HANDLER' => 'files',
                    'STRICT_MODE' => true,
                    'USE_COOKIES' => true,
                    'USE_ONLY_COOKIES' => true,
                    'SID_LENGTH' => 48,
                ],
                'DATABASE' => [
                    'TABLE' => 'framework_sessions',
                ],
            ],
            'http' => [
                'SIGNED_URL' => [
                    'KEY' => 'langelermvc-signed-url',
                ],
                'THROTTLE' => [
                    'MAX_ATTEMPTS' => 10,
                    'DECAY_SECONDS' => 60,
                ],
            ],
            'webmodule' => [
                'CONTENT_SOURCE' => 'database',
                'DEFAULT_LAYOUT' => 'WebShell',
            ],
        ]);
    }

    private function makeCryptoStub(): CryptoManager
    {
        $crypto = $this->createStub(CryptoManager::class);
        $crypto->method('generateRandom')->willReturnCallback(function (string $type, mixed ...$arguments): string {
            if ($type === 'generateRandomIv') {
                return random_bytes(16);
            }

            $length = isset($arguments[0]) && is_int($arguments[0]) ? $arguments[0] : 32;

            return random_bytes($length);
        });
        $crypto->method('getDriverName')->willReturn('openssl');
        $crypto->method('resolveConfiguredKey')->willReturn(str_repeat('k', 32));
        $crypto->method('resolveConfiguredCipher')->willReturn('AES-256-CBC');
        $crypto->method('ivLength')->willReturn(16);
        $crypto->method('encrypt')->willReturnCallback(
            static fn(string $type, string $value, mixed ...$arguments): string => base64_encode($value)
        );
        $crypto->method('decrypt')->willReturnCallback(
            static fn(string $type, string $value, mixed ...$arguments): string => (string) (base64_decode($value, true) ?: '')
        );
        $crypto->method('passwordHash')->willReturnCallback(
            static fn(string $algorithm, string $value): string => (string) password_hash($value, PASSWORD_DEFAULT)
        );
        $crypto->method('passwordVerify')->willReturnCallback(
            static fn(string $hash, string $password, string $action = 'verify'): bool => password_verify($password, $hash)
        );
        $crypto->method('passwordNeedsRehash')->willReturn(false);

        return $crypto;
    }

    private function makeCacheDouble(): CacheManager
    {
        $cacheStore = [];
        $cache = $this->createStub(CacheManager::class);
        $cache->method('get')->willReturnCallback(
            static function (string $key, mixed $default = null) use (&$cacheStore): mixed {
                return $cacheStore[$key] ?? (is_callable($default) ? $default() : $default);
            }
        );
        $cache->method('put')->willReturnCallback(
            static function (string $key, mixed $value, ?int $ttl = null) use (&$cacheStore): bool {
                $cacheStore[$key] = $value;

                return true;
            }
        );
        $cache->method('forget')->willReturnCallback(
            static function (string $key) use (&$cacheStore): bool {
                unset($cacheStore[$key]);

                return true;
            }
        );

        return $cache;
    }

    private function makeRouterDouble(): \App\Core\Router
    {
        $router = $this->createStub(\App\Core\Router::class);
        $router->method('listRoutes')->willReturn([
            ['method' => 'GET', 'path' => '/admin', 'action' => 'AdminController@dashboard', 'name' => 'admin.dashboard', 'middleware' => []],
            ['method' => 'GET', 'path' => '/shop', 'action' => 'ShopController@index', 'name' => 'shop.index', 'middleware' => []],
            ['method' => 'GET', 'path' => '/cart', 'action' => 'CartController@show', 'name' => 'cart.show', 'middleware' => []],
            ['method' => 'GET', 'path' => '/orders', 'action' => 'OrderController@index', 'name' => 'orders.index', 'middleware' => []],
        ]);

        return $router;
    }
}
