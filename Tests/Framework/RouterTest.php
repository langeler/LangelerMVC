<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Contracts\Http\ResponseInterface;
use App\Core\Database;
use App\Core\MigrationRunner;
use App\Core\Router;
use App\Core\SeedRunner;
use App\Providers\CoreProvider;
use App\Providers\ExceptionProvider;
use App\Utilities\Managers\Data\ModuleManager;
use App\Utilities\Managers\System\ErrorManager;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    private array $postBackup = [];
    private array $getBackup = [];
    private array $envBackup = [];
    private array $serverBackup = [];
    private ?string $sqliteTestDatabasePath = null;

    protected function setUp(): void
    {
        $this->postBackup = $_POST;
        $this->getBackup = $_GET;
        $this->serverBackup = [
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
            'HTTP_ACCEPT' => $_SERVER['HTTP_ACCEPT'] ?? null,
        ];
        $this->envBackup = [
            'DB_CONNECTION' => getenv('DB_CONNECTION') !== false ? (string) getenv('DB_CONNECTION') : null,
            'DB_DATABASE' => getenv('DB_DATABASE') !== false ? (string) getenv('DB_DATABASE') : null,
            'DB_TIMEOUT' => getenv('DB_TIMEOUT') !== false ? (string) getenv('DB_TIMEOUT') : null,
            'WEBMODULE_CONTENT_SOURCE' => getenv('WEBMODULE_CONTENT_SOURCE') !== false ? (string) getenv('WEBMODULE_CONTENT_SOURCE') : null,
        ];

        $databasePath = sys_get_temp_dir() . '/langelermvc-router-test.sqlite';

        if (file_exists($databasePath)) {
            unlink($databasePath);
        }

        $databasePath = tempnam(sys_get_temp_dir(), 'langelermvc-router-');

        if ($databasePath === false) {
            throw new \RuntimeException('Failed to allocate temporary SQLite database path for router test.');
        }

        $this->sqliteTestDatabasePath = $databasePath;

        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=' . $databasePath);
        putenv('DB_TIMEOUT=1');
        putenv('WEBMODULE_CONTENT_SOURCE=memory');
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $databasePath;
        $_ENV['DB_TIMEOUT'] = '1';
        $_ENV['WEBMODULE_CONTENT_SOURCE'] = 'memory';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = $databasePath;
        $_SERVER['DB_TIMEOUT'] = '1';
        $_SERVER['WEBMODULE_CONTENT_SOURCE'] = 'memory';
    }

    protected function tearDown(): void
    {
        $_POST = $this->postBackup;
        $_GET = $this->getBackup;

        foreach ($this->envBackup as $key => $value) {
            if ($value === null) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
                continue;
            }

            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        foreach ($this->serverBackup as $key => $value) {
            if ($value === null) {
                unset($_SERVER[$key]);
                continue;
            }

            $_SERVER[$key] = $value;
        }

        if ($this->sqliteTestDatabasePath !== null && file_exists($this->sqliteTestDatabasePath)) {
            @unlink($this->sqliteTestDatabasePath);
        }

        $this->sqliteTestDatabasePath = null;
        $this->serverBackup = [];
    }

    public function testRouterDispatchesHomeRouteFromModuleRouteFile(): void
    {
        $router = $this->resolveRouter();
        $response = $router->dispatch('/', 'GET');

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(200, $response->getStatus());
        self::assertStringContainsString('LangelerMVC is running.', $response->toArray()['content']);
        self::assertStringContainsString('<html', $response->toArray()['content']);
    }

    public function testRouterResolvesNamedRoutesAndFallback(): void
    {
        $router = $this->resolveRouter();
        $response = $router->dispatch('/missing', 'GET');

        self::assertSame('/', $router->route('home'));
        self::assertSame('/shop/categories/framework-tools', $router->route('shop.category', ['slug' => 'framework-tools']));
        self::assertSame('/orders/complete/demo-reference', $router->route('orders.complete.reference', ['reference' => 'demo-reference']));
        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(404, $response->getStatus());
        self::assertStringContainsString('<html', $response->toArray()['content']);
        self::assertStringContainsString('Return Home', $response->toArray()['content']);
    }

    public function testRouterUsesProvidedDispatchMethodInsteadOfSuperglobalOverride(): void
    {
        $_POST['_method'] = 'DELETE';
        $_GET['_method'] = 'DELETE';

        $router = $this->resolveRouter();
        $response = $router->dispatch('/', 'GET');

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(200, $response->getStatus());
    }

    public function testRouterRegistersUserAndAdminPlatformRoutes(): void
    {
        $router = $this->resolveRouter();
        $names = array_values(array_filter(array_map(
            static fn(array $route): ?string => $route['name'],
            $router->listRoutes()
        )));

        self::assertContains('user.login', $names);
        self::assertContains('user.profile', $names);
        self::assertContains('admin.dashboard', $names);
        self::assertContains('shop.index', $names);
        self::assertContains('shop.category', $names);
        self::assertContains('api.shop.category', $names);
        self::assertContains('admin.catalog.categories.store', $names);
        self::assertContains('admin.catalog.products.store', $names);
        self::assertContains('admin.orders.show', $names);
        self::assertContains('admin.orders.capture', $names);
        self::assertContains('admin.orders.cancel', $names);
        self::assertContains('admin.orders.refund', $names);
        self::assertContains('admin.orders.reconcile', $names);
        self::assertContains('api.admin.catalog.categories.store', $names);
        self::assertContains('api.admin.catalog.products.store', $names);
        self::assertContains('api.admin.orders.show', $names);
        self::assertContains('api.admin.orders.capture', $names);
        self::assertContains('api.admin.orders.cancel', $names);
        self::assertContains('api.admin.orders.refund', $names);
        self::assertContains('api.admin.orders.reconcile', $names);
        self::assertContains('cart.show', $names);
        self::assertContains('orders.checkout.form', $names);
        self::assertContains('orders.complete', $names);
        self::assertContains('orders.cancelled', $names);
        self::assertContains('api.orders.complete', $names);
        self::assertContains('api.orders.cancelled', $names);
        self::assertContains('web.page', $names);
        self::assertContains('api.web.page', $names);
    }

    public function testRouterDispatchesDynamicWebModulePagesForHtmlAndApi(): void
    {
        putenv('WEBMODULE_CONTENT_SOURCE=database');
        $_ENV['WEBMODULE_CONTENT_SOURCE'] = 'database';
        $_SERVER['WEBMODULE_CONTENT_SOURCE'] = 'database';

        $router = $this->resolveRouter();

        self::assertSame('/pages/about', $router->route('web.page', ['slug' => 'about']));

        $html = $router->dispatch('/pages/about', 'GET');

        self::assertInstanceOf(ResponseInterface::class, $html);
        self::assertSame(200, $html->getStatus());
        self::assertStringContainsString('About LangelerMVC', $html->toArray()['content']);

        $_SERVER['REQUEST_URI'] = '/api/pages/about';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $api = $router->dispatch('/api/pages/about', 'GET');

        self::assertInstanceOf(ResponseInterface::class, $api);
        self::assertSame(200, $api->getStatus());
        self::assertSame('application/json; charset=UTF-8', $api->getHeaders()['content-type']);
        self::assertStringContainsString('"about"', $api->toArray()['content']);
        self::assertStringContainsString('"About LangelerMVC"', $api->toArray()['content']);
    }

    public function testRouterDispatchesPublicUserFacingHtmlRoutes(): void
    {
        $router = $this->resolveRouter();

        foreach (['/users/login', '/users/register', '/users/password/forgot'] as $path) {
            $response = $router->dispatch($path, 'GET');

            self::assertInstanceOf(ResponseInterface::class, $response);
            self::assertSame(200, $response->getStatus(), sprintf('Expected [%s] to return 200.', $path));
            self::assertStringContainsString('<html', $response->toArray()['content'], sprintf('Expected [%s] to render HTML.', $path));
        }
    }

    public function testRouterDispatchesStorefrontCartAndCheckoutHtmlRoutes(): void
    {
        $provider = new CoreProvider();
        $provider->registerServices();

        $database = $provider->getCoreService('database');
        $modules = $provider->resolveClass(ModuleManager::class);

        self::assertInstanceOf(Database::class, $database);
        self::assertInstanceOf(ModuleManager::class, $modules);

        $errors = new ErrorManager(new ExceptionProvider());

        $database->query('DROP TABLE IF EXISTS pages');

        (new MigrationRunner($database, $modules, $errors))->migrate();
        (new SeedRunner($database, $modules, $errors))->run();

        $router = $provider->getCoreService('router');

        self::assertInstanceOf(Router::class, $router);

        $catalog = $router->dispatch('/shop', 'GET');
        self::assertInstanceOf(ResponseInterface::class, $catalog);
        self::assertSame(200, $catalog->getStatus());
        self::assertStringContainsString('<html', $catalog->toArray()['content']);
        self::assertStringContainsString('/shop/categories/framework-tools', $catalog->toArray()['content']);
        self::assertStringContainsString('name="availability"', $catalog->toArray()['content']);

        $category = $router->dispatch('/shop/categories/framework-tools', 'GET');
        self::assertInstanceOf(ResponseInterface::class, $category);
        self::assertSame(200, $category->getStatus());
        self::assertStringContainsString('<html', $category->toArray()['content']);
        self::assertStringContainsString('Framework Tools', $category->toArray()['content']);
        self::assertStringContainsString('action="/cart/items"', $category->toArray()['content']);

        $product = $router->dispatch('/shop/products/starter-platform-license', 'GET');
        self::assertInstanceOf(ResponseInterface::class, $product);
        self::assertSame(200, $product->getStatus());
        self::assertStringContainsString('<html', $product->toArray()['content']);
        self::assertStringContainsString('action="/cart/items"', $product->toArray()['content']);

        $cart = $router->dispatch('/cart', 'GET');
        self::assertInstanceOf(ResponseInterface::class, $cart);
        self::assertSame(200, $cart->getStatus());
        self::assertStringContainsString('<html', $cart->toArray()['content']);
        self::assertStringContainsString('Browse the storefront catalog', $cart->toArray()['content']);

        $checkout = $router->dispatch('/orders/checkout', 'GET');
        self::assertInstanceOf(ResponseInterface::class, $checkout);
        self::assertSame(200, $checkout->getStatus());
        self::assertStringContainsString('<html', $checkout->toArray()['content']);
        self::assertStringContainsString('Checkout unavailable', $checkout->toArray()['content']);

        $complete = $router->dispatch('/orders/complete', 'GET');
        self::assertInstanceOf(ResponseInterface::class, $complete);
        self::assertSame(200, $complete->getStatus());
        self::assertStringContainsString('<html', $complete->toArray()['content']);
        self::assertStringContainsString('Payment return received', $complete->toArray()['content']);

        $cancelled = $router->dispatch('/orders/cancelled/demo-reference', 'GET');
        self::assertInstanceOf(ResponseInterface::class, $cancelled);
        self::assertSame(200, $cancelled->getStatus());
        self::assertStringContainsString('<html', $cancelled->toArray()['content']);
        self::assertStringContainsString('Payment flow cancelled', $cancelled->toArray()['content']);
    }

    public function testRouterDispatchesOrderReturnPagesForSeededReferences(): void
    {
        $provider = new CoreProvider();
        $provider->registerServices();

        $database = $provider->getCoreService('database');
        $modules = $provider->resolveClass(ModuleManager::class);

        self::assertInstanceOf(Database::class, $database);
        self::assertInstanceOf(ModuleManager::class, $modules);

        $errors = new ErrorManager(new ExceptionProvider());

        (new MigrationRunner($database, $modules, $errors))->migrate();
        (new SeedRunner($database, $modules, $errors))->run();

        $router = $provider->getCoreService('router');

        self::assertInstanceOf(Router::class, $router);

        $complete = $router->dispatch('/orders/complete/demo-seed-order', 'GET');
        self::assertInstanceOf(ResponseInterface::class, $complete);
        self::assertSame(200, $complete->getStatus());
        self::assertStringContainsString('<html', $complete->toArray()['content']);
        self::assertStringContainsString('Order', $complete->toArray()['content']);
        self::assertStringContainsString('Payment return received', $complete->toArray()['content']);

        $cancelled = $router->dispatch('/orders/cancelled/demo-seed-order', 'GET');
        self::assertInstanceOf(ResponseInterface::class, $cancelled);
        self::assertSame(200, $cancelled->getStatus());
        self::assertStringContainsString('<html', $cancelled->toArray()['content']);
        self::assertStringContainsString('Order', $cancelled->toArray()['content']);
        self::assertStringContainsString('Payment flow cancelled', $cancelled->toArray()['content']);
    }

    public function testRouterDispatchesPublicStorefrontApisAsJson(): void
    {
        $provider = new CoreProvider();
        $provider->registerServices();

        $database = $provider->getCoreService('database');
        $modules = $provider->resolveClass(ModuleManager::class);

        self::assertInstanceOf(Database::class, $database);
        self::assertInstanceOf(ModuleManager::class, $modules);

        $errors = new ErrorManager(new ExceptionProvider());

        (new MigrationRunner($database, $modules, $errors))->migrate();
        (new SeedRunner($database, $modules, $errors))->run();

        $router = $provider->getCoreService('router');

        self::assertInstanceOf(Router::class, $router);

        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $_SERVER['REQUEST_URI'] = '/api/shop';
        $catalog = $router->dispatch('/api/shop', 'GET');
        self::assertInstanceOf(ResponseInterface::class, $catalog);
        self::assertSame(200, $catalog->getStatus());
        self::assertSame('application/json; charset=UTF-8', $catalog->getHeaders()['content-type']);
        self::assertStringContainsString('"products"', $catalog->toArray()['content']);

        $_SERVER['REQUEST_URI'] = '/api/cart';
        $cart = $router->dispatch('/api/cart', 'GET');
        self::assertInstanceOf(ResponseInterface::class, $cart);
        self::assertSame(200, $cart->getStatus());
        self::assertSame('application/json; charset=UTF-8', $cart->getHeaders()['content-type']);
        self::assertStringContainsString('"cart"', $cart->toArray()['content']);

        $_SERVER['REQUEST_URI'] = '/api/orders/complete/demo-seed-order';
        $orderReturn = $router->dispatch('/api/orders/complete/demo-seed-order', 'GET');
        self::assertInstanceOf(ResponseInterface::class, $orderReturn);
        self::assertSame(200, $orderReturn->getStatus());
        self::assertSame('application/json; charset=UTF-8', $orderReturn->getHeaders()['content-type']);
        self::assertStringContainsString('"Payment return received"', $orderReturn->toArray()['content']);
    }

    private function resolveRouter(): Router
    {
        $provider = new CoreProvider();
        $provider->registerServices();
        $database = $provider->getCoreService('database');

        if ($database instanceof Database) {
            $database->query('CREATE TABLE IF NOT EXISTS pages (id INTEGER PRIMARY KEY AUTOINCREMENT, slug TEXT NOT NULL, title TEXT NOT NULL, content TEXT, is_published INTEGER NOT NULL DEFAULT 1, created_at TEXT NULL, updated_at TEXT NULL)');
            $database->execute('INSERT INTO pages (slug, title, content, is_published) VALUES (?, ?, ?, ?)', ['home', 'LangelerMVC is running.', 'The starter WebModule page is now stored in the framework database layer.', 1]);
            $database->execute('INSERT INTO pages (slug, title, content, is_published) VALUES (?, ?, ?, ?)', ['not-found', 'Route not found.', 'The requested route could not be resolved by the framework router.', 1]);
            $database->execute('INSERT INTO pages (slug, title, content, is_published) VALUES (?, ?, ?, ?)', ['about', 'About LangelerMVC', 'The framework ships with concrete modules and native presentation layers for production extension work.', 1]);
        }

        return $provider->getCoreService('router');
    }
}
