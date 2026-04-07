<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Core\App;
use App\Core\Config;
use App\Core\Router;
use App\Providers\CoreProvider;
use App\Providers\ExceptionProvider;
use App\Utilities\Managers\System\ErrorManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class BootstrapAndAppTest extends TestCase
{
    private array $serverBackup = [];
    private string $timezoneBackup = 'UTC';

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;
        $this->timezoneBackup = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        date_default_timezone_set($this->timezoneBackup);
    }

    public function testBootstrapScriptReturnsApplicationAndRegistersPaths(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $app = require $projectRoot . '/bootstrap/app.php';

        self::assertInstanceOf(App::class, $app);
        self::assertTrue(defined('BASE_PATH'));
        self::assertTrue(defined('APP_PATH'));
        self::assertTrue(defined('CONFIG_PATH'));
        self::assertTrue(defined('PUBLIC_PATH'));
        self::assertTrue(defined('STORAGE_PATH'));
        self::assertSame(realpath($projectRoot), BASE_PATH);
        self::assertSame(realpath($projectRoot . '/Public'), PUBLIC_PATH);
    }

    public function testAppRunsAndEmitsStringResponsesWithMethodOverride(): void
    {
        $_SERVER['REQUEST_URI'] = '/articles/1?preview=1';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'PATCH';

        $router = $this->createRouter('Framework ready.');
        $app = new App(
            $this->createProvider(
                $this->createConfig([
                    'DEBUG' => 'false',
                    'TIMEZONE' => 'Europe/Stockholm',
                    'MAINTENANCE' => 'false',
                ]),
                $router
            ),
            $this->createErrorManager()
        );

        ob_start();
        $app->run();
        $output = ob_get_clean();

        self::assertSame('Framework ready.', $output);
        self::assertSame('Europe/Stockholm', date_default_timezone_get());
        self::assertSame(
            [['uri' => '/articles/1?preview=1', 'method' => 'PATCH']],
            $router->dispatches
        );
    }

    public function testAppEmitsJsonForArrayPayloads(): void
    {
        $_SERVER['REQUEST_URI'] = '/health';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $router = $this->createRouter(['status' => 'ok']);
        $app = new App(
            $this->createProvider(
                $this->createConfig([
                    'DEBUG' => 'true',
                    'TIMEZONE' => 'UTC',
                    'MAINTENANCE' => 'false',
                ]),
                $router
            ),
            $this->createErrorManager()
        );

        ob_start();
        $app->run();
        $output = ob_get_clean();

        self::assertSame('{"status":"ok"}', $output);
        self::assertSame(
            [['uri' => '/health', 'method' => 'GET']],
            $router->dispatches
        );
    }

    private function createConfig(array $appConfig): Config
    {
        return new class($appConfig) extends Config {
            public function __construct(private array $appConfig)
            {
            }

            public function get(string $file, ?string $key = null, mixed $default = null): mixed
            {
                if (strtolower($file) !== 'app') {
                    return $default;
                }

                if ($key === null || $key === '') {
                    return $this->appConfig;
                }

                foreach ($this->appConfig as $candidate => $value) {
                    if (strtolower((string) $candidate) === strtolower($key)) {
                        return $value;
                    }
                }

                return $default;
            }
        };
    }

    private function createRouter(mixed $result): Router
    {
        return new class($result) extends Router {
            public array $dispatches = [];

            public function __construct(private mixed $result)
            {
            }

            public function dispatch(string $uri, string $method): mixed
            {
                $this->dispatches[] = [
                    'uri' => $uri,
                    'method' => $method,
                ];

                return $this->result;
            }
        };
    }

    private function createProvider(Config $config, Router $router): CoreProvider
    {
        $errorManager = $this->createErrorManager();

        return new class($config, $router, $errorManager) extends CoreProvider {
            public function __construct(
                private Config $config,
                private Router $router,
                private ErrorManager $errorManager
            ) {
            }

            public function getCoreService(string $serviceAlias): object
            {
                return match ($serviceAlias) {
                    'config' => $this->config,
                    'router' => $this->router,
                    'errorManager' => $this->errorManager,
                    default => throw new RuntimeException("Unsupported core service alias: {$serviceAlias}"),
                };
            }
        };
    }

    private function createErrorManager(): ErrorManager
    {
        return new ErrorManager(new ExceptionProvider());
    }
}
