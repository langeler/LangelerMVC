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
        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(404, $response->getStatus());
        self::assertStringContainsString('Route not found.', $response->toArray()['content']);
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
        self::assertContains('cart.show', $names);
        self::assertContains('orders.checkout.form', $names);
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

        foreach ([
            '/shop',
            '/shop/products/starter-platform-license',
            '/cart',
            '/orders/checkout',
        ] as $path) {
            $response = $router->dispatch($path, 'GET');

            self::assertInstanceOf(ResponseInterface::class, $response);
            self::assertSame(200, $response->getStatus(), sprintf('Expected [%s] to return 200.', $path));
            self::assertStringContainsString('<html', $response->toArray()['content'], sprintf('Expected [%s] to render HTML.', $path));
        }
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
