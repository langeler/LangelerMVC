<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Core\Router;
use App\Providers\CoreProvider;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testRouterDispatchesHomeRouteFromModuleRouteFile(): void
    {
        $router = $this->resolveRouter();

        self::assertSame('LangelerMVC is running.', $router->dispatch('/', 'GET'));
    }

    public function testRouterResolvesNamedRoutesAndFallback(): void
    {
        $router = $this->resolveRouter();

        self::assertSame('/', $router->route('home'));
        self::assertSame('Route not found.', $router->dispatch('/missing', 'GET'));
    }

    private function resolveRouter(): Router
    {
        $provider = new CoreProvider();
        $provider->registerServices();

        return $provider->getCoreService('router');
    }
}
