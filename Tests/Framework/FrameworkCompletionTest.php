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
use App\Modules\CartModule\Migrations\CreateCartTables;
use App\Modules\CartModule\Presenters\CartResource;
use App\Modules\CartModule\Repositories\CartItemRepository;
use App\Modules\CartModule\Repositories\CartRepository;
use App\Modules\CartModule\Seeds\CartSeed;
use App\Modules\CartModule\Services\CartService;
use App\Modules\OrderModule\Listeners\OrderLifecycleNotificationListener;
use App\Modules\OrderModule\Migrations\CreateOrderTables;
use App\Modules\OrderModule\Presenters\OrderResource;
use App\Modules\OrderModule\Repositories\OrderAddressRepository;
use App\Modules\OrderModule\Repositories\OrderItemRepository;
use App\Modules\OrderModule\Repositories\OrderRepository;
use App\Modules\OrderModule\Seeds\OrderSeed;
use App\Modules\OrderModule\Services\OrderService;
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
            OrderSeed::class,
            CreatePagesTable::class,
            PageSeed::class,
            CreateCartTables::class,
            CartSeed::class,
            CreateShopTables::class,
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

        self::assertSame(1, count($stack['notifications']->databaseNotifications()));
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
        self::assertArrayHasKey('lookup', $formJson['data']);

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
        $catalogAdmin = $stack['adminService']->forAction('catalog')->execute();
        $cartsAdmin = $stack['adminService']->forAction('carts')->execute();
        $operations = $stack['adminService']->forAction('operations')->execute();
        $adminJson = (new AdminResource($operations))->toArray();

        self::assertSame(200, $catalogAdmin['status']);
        self::assertNotEmpty($catalogAdmin['catalog']);
        self::assertSame(200, $cartsAdmin['status']);
        self::assertNotEmpty($cartsAdmin['carts']);
        self::assertSame(200, $orders['status']);
        self::assertNotEmpty($orders['orders']);
        self::assertSame(200, $operations['status']);
        self::assertArrayHasKey('queue', $operations['operations']);
        self::assertArrayHasKey('health', $operations['operations']);
        self::assertArrayHasKey('audit', $operations['operations']);
        self::assertArrayHasKey('payments', $adminJson['data']['operations']);
        self::assertArrayHasKey('methods', $operations['operations']['payments']);
        self::assertArrayHasKey('flows', $operations['operations']['payments']);
        self::assertArrayHasKey('catalog', $operations['operations']['payments']);
        self::assertArrayHasKey('paypal', $operations['operations']['payments']['catalog']);
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
            ShopSeed::class,
            CreateCartTables::class,
            CreateOrderTables::class,
            MergeCartOnLoginListener::class,
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
        $products = new ProductRepository($database);
        $categories = new CategoryRepository($database);
        $carts = new CartRepository($database);
        $cartItems = new CartItemRepository($database);
        $orders = new OrderRepository($database);
        $orderItems = new OrderItemRepository($database);
        $addresses = new OrderAddressRepository($database);

        $catalogService = new CatalogService($products, $categories);
        $cartService = new CartService($carts, $cartItems, $products, $session, $auth);
        $orderService = new OrderService(
            $orders,
            $orderItems,
            $addresses,
            $carts,
            $cartItems,
            $payments,
            $events,
            $auth,
            $session,
            $httpSecurity,
            $audit
        );
        $adminService = new AdminAccessService(
            $auth,
            $users,
            $roles,
            $permissions,
            $products,
            $categories,
            $carts,
            $cartItems,
            $orders,
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
            MergeCartOnLoginListener::class => new MergeCartOnLoginListener($cartService),
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
            'catalogService' => $catalogService,
            'database' => $database,
            'events' => $events,
            'mail' => $mail,
            'notifications' => $notifications,
            'orderService' => $orderService,
            'orders' => $orders,
            'payments' => $payments,
            'queue' => $queue,
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
