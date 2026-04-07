<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Contracts\Http\ResponseInterface;
use App\Core\Router;
use App\Providers\CoreProvider;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    private array $postBackup = [];
    private array $getBackup = [];

    protected function setUp(): void
    {
        $this->postBackup = $_POST;
        $this->getBackup = $_GET;
    }

    protected function tearDown(): void
    {
        $_POST = $this->postBackup;
        $_GET = $this->getBackup;
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

    private function resolveRouter(): Router
    {
        $provider = new CoreProvider();
        $provider->registerServices();

        return $provider->getCoreService('router');
    }
}
