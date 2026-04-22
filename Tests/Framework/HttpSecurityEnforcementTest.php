<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Core\App;
use App\Core\Config;
use App\Core\FrameworkResponse;
use App\Core\Router;
use App\Core\Session;
use App\Providers\CoreProvider;
use App\Providers\ExceptionProvider;
use App\Utilities\Handlers\DataHandler;
use App\Utilities\Managers\CacheManager;
use App\Utilities\Managers\DateTimeManager;
use App\Utilities\Managers\Security\HttpSecurityManager;
use App\Utilities\Managers\System\ErrorManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class HttpSecurityEnforcementTest extends TestCase
{
    private array $postBackup = [];
    private array $serverBackup = [];
    private array $envBackup = [];
    private ?string $sqliteTestDatabasePath = null;

    protected function setUp(): void
    {
        $this->postBackup = $_POST;
        $this->serverBackup = $_SERVER;
        $this->envBackup = [
            'DB_CONNECTION' => getenv('DB_CONNECTION') !== false ? (string) getenv('DB_CONNECTION') : null,
            'DB_DATABASE' => getenv('DB_DATABASE') !== false ? (string) getenv('DB_DATABASE') : null,
            'DB_TIMEOUT' => getenv('DB_TIMEOUT') !== false ? (string) getenv('DB_TIMEOUT') : null,
            'WEBMODULE_CONTENT_SOURCE' => getenv('WEBMODULE_CONTENT_SOURCE') !== false ? (string) getenv('WEBMODULE_CONTENT_SOURCE') : null,
        ];

        $databasePath = tempnam(sys_get_temp_dir(), 'langelermvc-security-');

        if ($databasePath === false) {
            throw new RuntimeException('Failed to allocate temporary SQLite database path for security test.');
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
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
    }

    protected function tearDown(): void
    {
        $_POST = $this->postBackup;
        $_SERVER = $this->serverBackup;

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

        if ($this->sqliteTestDatabasePath !== null && file_exists($this->sqliteTestDatabasePath)) {
            @unlink($this->sqliteTestDatabasePath);
        }

        $this->sqliteTestDatabasePath = null;
    }

    public function testRouterRejectsMissingCsrfTokenForUnsafeWebRoutes(): void
    {
        $provider = new CoreProvider();
        $provider->registerServices();
        $router = $provider->getCoreService('router');
        $session = $provider->getCoreService('session');

        self::assertInstanceOf(Router::class, $router);
        self::assertInstanceOf(Session::class, $session);

        $_SERVER['REQUEST_URI'] = '/users/logout';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $_POST = [];

        $blocked = $router->dispatch('/users/logout', 'POST');

        self::assertSame(419, $blocked->getStatus());
        self::assertStringContainsString('Security Check Failed', $blocked->toArray()['content']);

        $_POST = ['_token' => $session->token()];

        $allowed = $router->dispatch('/users/logout', 'POST');

        self::assertNotSame(419, $allowed->getStatus());
    }

    public function testRouterRejectsMissingCsrfTokenForUnsafeJsonRoutes(): void
    {
        $provider = new CoreProvider();
        $provider->registerServices();
        $router = $provider->getCoreService('router');
        $session = $provider->getCoreService('session');

        self::assertInstanceOf(Router::class, $router);
        self::assertInstanceOf(Session::class, $session);

        $_SERVER['REQUEST_URI'] = '/api/users/logout';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $_POST = [];
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);

        $blocked = $router->dispatch('/api/users/logout', 'POST');

        self::assertSame(419, $blocked->getStatus());
        self::assertStringContainsString('csrf_token_mismatch', $blocked->toArray()['content']);

        $_SERVER['HTTP_X_CSRF_TOKEN'] = $session->token();

        $allowed = $router->dispatch('/api/users/logout', 'POST');

        self::assertNotSame(419, $allowed->getStatus());
    }

    public function testAppDecoratesHtmlResponsesWithCsrfSurface(): void
    {
        $_SERVER['REQUEST_URI'] = '/demo';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $config = $this->createConfig([
            'app' => [
                'DEBUG' => 'false',
                'TIMEZONE' => 'UTC',
                'MAINTENANCE' => 'false',
            ],
            'http' => [
                'CSRF' => [
                    'ENABLED' => true,
                    'FIELD' => '_token',
                    'HEADER' => 'X-CSRF-TOKEN',
                ],
            ],
        ]);
        $cache = new class extends CacheManager {
            public function __construct()
            {
            }
        };
        $httpSecurity = new HttpSecurityManager($config, $cache);
        $session = new class extends Session {
            public function __construct()
            {
            }

            public function token(): string
            {
                return 'known-token';
            }
        };

        $response = new FrameworkResponse(new DataHandler(), new DateTimeManager());
        $response->asHtml(
            '<!DOCTYPE html><html lang="en"><head><title>Demo</title></head><body><form method="post" action="/demo"><button type="submit">Submit</button></form></body></html>'
        );

        $router = new class($response) extends Router {
            public function __construct(private readonly FrameworkResponse $response)
            {
            }

            public function dispatch(string $uri, string $method): mixed
            {
                return $this->response;
            }
        };

        $app = new App(
            $this->createProvider($config, $router, $httpSecurity, $session),
            $this->createErrorManager()
        );

        ob_start();
        $app->run();
        $output = (string) ob_get_clean();

        self::assertStringContainsString('meta name="csrf-token" content="known-token"', $output);
        self::assertStringContainsString('data-langeler-security="csrf-bootstrap"', $output);
        self::assertStringContainsString('name="_token" value="known-token"', $output);
        self::assertStringContainsString('data-csrf-protected="1"', $output);
    }

    public function testHttpSecurityManagerProvidesProductionHeaderDefaults(): void
    {
        $config = $this->createConfig([
            'http' => [
                'HEADERS' => [
                    'CONTENT_SECURITY_POLICY' => "default-src 'self'",
                    'STRICT_TRANSPORT_SECURITY' => 'max-age=60',
                ],
            ],
        ]);
        $cache = new class extends CacheManager {
            public function __construct()
            {
            }
        };
        $httpSecurity = new HttpSecurityManager($config, $cache);

        $headers = $httpSecurity->defaultHeaders(true);

        self::assertSame("default-src 'self'", $headers['Content-Security-Policy']);
        self::assertSame('max-age=60', $headers['Strict-Transport-Security']);
        self::assertSame('nosniff', $headers['X-Content-Type-Options']);
        self::assertSame('SAMEORIGIN', $headers['X-Frame-Options']);
    }

    /**
     * @param array<string, array<string, mixed>> $buckets
     */
    private function createConfig(array $buckets): Config
    {
        return new class($buckets) extends Config {
            /**
             * @param array<string, array<string, mixed>> $buckets
             */
            public function __construct(private array $buckets)
            {
            }

            public function get(string $file, ?string $key = null, mixed $default = null): mixed
            {
                $bucket = $this->buckets[strtolower($file)] ?? null;

                if (!is_array($bucket)) {
                    return $default;
                }

                if ($key === null || $key === '') {
                    return $bucket;
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

                    $matched = false;

                    foreach ($current as $candidate => $value) {
                        if (strcasecmp((string) $candidate, $segment) === 0) {
                            $current = $value;
                            $matched = true;
                            break;
                        }
                    }

                    if (!$matched) {
                        return $default;
                    }
                }

                return $current;
            }
        };
    }

    private function createProvider(
        Config $config,
        Router $router,
        HttpSecurityManager $httpSecurity,
        Session $session
    ): CoreProvider {
        $errorManager = $this->createErrorManager();

        return new class($config, $router, $errorManager, $httpSecurity, $session) extends CoreProvider {
            public function __construct(
                private readonly Config $config,
                private readonly Router $router,
                private readonly ErrorManager $errorManager,
                private readonly HttpSecurityManager $httpSecurity,
                private readonly Session $session
            ) {
            }

            public function getCoreService(string $serviceAlias): object
            {
                return match ($serviceAlias) {
                    'config' => $this->config,
                    'router' => $this->router,
                    'errorManager' => $this->errorManager,
                    'httpSecurity' => $this->httpSecurity,
                    'session' => $this->session,
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
