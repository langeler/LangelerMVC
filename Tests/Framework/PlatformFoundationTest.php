<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Abstracts\Http\Response;
use App\Abstracts\Presentation\Resource;
use App\Abstracts\Presentation\ResourceCollection;
use App\Console\ConsoleKernel;
use App\Contracts\Http\RequestInterface;
use App\Contracts\Http\ServiceInterface;
use App\Contracts\Presentation\PresenterInterface;
use App\Contracts\Presentation\ViewInterface;
use App\Core\Config;
use App\Core\Database;
use App\Core\MigrationRunner;
use App\Core\SeedRunner;
use App\Drivers\Session\DatabaseSessionDriver;
use App\Drivers\Session\FileSessionDriver;
use App\Modules\WebModule\Migrations\CreatePagesTable;
use App\Modules\WebModule\Seeds\PageSeed;
use App\Providers\CoreProvider;
use App\Providers\ExceptionProvider;
use App\Providers\ShippingProvider;
use App\Utilities\Managers\Commerce\ShippingManager;
use App\Utilities\Managers\DateTimeManager;
use App\Utilities\Managers\Data\SessionManager;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\Support\MailManager;
use App\Utilities\Managers\Support\OtpManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Managers\SettingsManager;
use App\Utilities\Handlers\DataHandler;
use App\Utilities\Managers\Data\ModuleManager;
use PHPUnit\Framework\TestCase;
use PDO;

class PlatformFoundationTest extends TestCase
{
    private array $pathsToDelete = [];
    private array $directoriesToDelete = [];

    protected function tearDown(): void
    {
        foreach ($this->pathsToDelete as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        foreach ($this->directoriesToDelete as $path) {
            $this->removeDirectory($path);
        }

        $this->pathsToDelete = [];
        $this->directoriesToDelete = [];
    }

    public function testConsoleKernelExposesOperationalCommands(): void
    {
        $provider = new CoreProvider();
        $provider->registerServices();
        $kernel = $provider->createConsoleKernel();

        self::assertInstanceOf(ConsoleKernel::class, $kernel);
        self::assertArrayHasKey('audit:list', $kernel->commandDescriptions());
        self::assertArrayHasKey('audit:prune', $kernel->commandDescriptions());
        self::assertArrayHasKey('health:check', $kernel->commandDescriptions());
        self::assertArrayHasKey('migrate', $kernel->commandDescriptions());
        self::assertArrayHasKey('route:list', $kernel->commandDescriptions());
        self::assertArrayHasKey('module:list', $kernel->commandDescriptions());
        self::assertArrayHasKey('module:make', $kernel->commandDescriptions());
        self::assertArrayHasKey('framework:architecture', $kernel->commandDescriptions());
        self::assertArrayHasKey('framework:doctor', $kernel->commandDescriptions());
        self::assertArrayHasKey('framework:layers', $kernel->commandDescriptions());
        self::assertArrayHasKey('queue:work', $kernel->commandDescriptions());
        self::assertArrayHasKey('queue:failed', $kernel->commandDescriptions());
        self::assertArrayHasKey('queue:stop', $kernel->commandDescriptions());
        self::assertArrayHasKey('queue:drain', $kernel->commandDescriptions());
        self::assertArrayHasKey('queue:retry', $kernel->commandDescriptions());
        self::assertArrayHasKey('queue:prune-failed', $kernel->commandDescriptions());
        self::assertArrayHasKey('release:check', $kernel->commandDescriptions());
        self::assertInstanceOf(ShippingManager::class, $provider->getCoreService('shipping'));
        self::assertInstanceOf(ShippingManager::class, $provider->resolveClass(\App\Support\Commerce\ShippingManager::class));
        self::assertInstanceOf(ShippingProvider::class, $provider->getCoreService('shippingProvider'));
    }

    public function testConsoleKernelParsesOptionsForOperationalCommands(): void
    {
        $provider = new CoreProvider();
        $provider->registerServices();
        $kernel = $provider->createConsoleKernel();

        ob_start();
        $exitCode = $kernel->run(['console', 'config:show', 'app', '--unused=1']);
        ob_end_clean();

        self::assertSame(0, $exitCode);
    }

    public function testConsoleKernelEmitsFrameworkLayerInspectionJson(): void
    {
        $lines = [];
        $exitCode = 1;
        exec(escapeshellarg(PHP_BINARY) . ' console framework:layers', $lines, $exitCode);

        $payload = json_decode(implode("\n", $lines), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $exitCode);
        self::assertTrue((bool) $payload['ok']);
        self::assertArrayHasKey('presentation', $payload['layers']);
        self::assertSame([], $payload['missing_required_paths']);
    }

    public function testConsoleKernelEmitsArchitectureAlignmentInspectionJson(): void
    {
        $lines = [];
        $exitCode = 1;
        exec(escapeshellarg(PHP_BINARY) . ' console framework:architecture', $lines, $exitCode);

        $payload = json_decode(implode("\n", $lines), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $exitCode);
        self::assertTrue((bool) $payload['ok']);
        self::assertArrayHasKey('manager_placement', $payload['checks']);
        self::assertSame([], $payload['errors']);
    }

    public function testConsoleKernelScaffoldsNewModule(): void
    {
        $provider = new CoreProvider();
        $provider->registerServices();
        $kernel = $provider->createConsoleKernel();

        $moduleSeed = strtoupper(bin2hex(random_bytes(3)));
        $moduleName = 'Spec' . $moduleSeed;
        $moduleFolder = dirname(__DIR__, 2) . '/App/Modules/' . $moduleName . 'Module';
        $this->directoriesToDelete[] = $moduleFolder;

        ob_start();
        $makeExit = $kernel->run(['console', 'module:make', $moduleName, '--quiet=1']);
        ob_end_clean();

        self::assertSame(0, $makeExit);
        self::assertDirectoryExists($moduleFolder);
        self::assertFileExists($moduleFolder . '/Routes/web.php');
        self::assertFileExists($moduleFolder . '/Controllers/' . $moduleName . 'Controller.php');
    }

    public function testMigrationAndSeedRunnersManageWebModuleSchemaLifecycle(): void
    {
        $database = $this->makeSqliteDatabase();
        $modules = $this->makeModuleManagerStub([
            CreatePagesTable::class,
            PageSeed::class,
        ]);
        $errors = new ErrorManager(new ExceptionProvider());

        $migrations = new MigrationRunner($database, $modules, $errors);
        $seeds = new SeedRunner($database, $modules, $errors);

        self::assertSame(['CreatePagesTable'], $migrations->migrate('WebModule'));
        self::assertSame('up', $migrations->status('WebModule')[0]['batch'] > 0 ? 'up' : 'pending');

        self::assertSame(['PageSeed'], $seeds->run('WebModule'));
        self::assertSame(4, (int) $database->fetchColumn('SELECT COUNT(*) FROM pages'));

        self::assertSame(['CreatePagesTable'], $migrations->rollback(1, 'WebModule'));
        self::assertFalse($database->fetchColumn("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'pages'"));
    }

    public function testSessionDriversSupportFileAndDatabaseBackends(): void
    {
        $fileManager = new FileManager();
        $filePath = sys_get_temp_dir() . '/langelermvc-session-driver-' . bin2hex(random_bytes(4));
        $driver = new FileSessionDriver($fileManager, $filePath);
        $this->pathsToDelete[] = $filePath . '/abc123.session';

        self::assertTrue($driver->open($filePath, 'framework'));
        self::assertTrue($driver->write('abc123', 'payload'));
        self::assertSame('payload', $driver->read('abc123'));
        self::assertTrue($driver->destroy('abc123'));

        $database = $this->makeSqliteDatabase();
        $databaseDriver = new DatabaseSessionDriver($database, 'framework_sessions');

        self::assertTrue($databaseDriver->open('', 'framework'));
        self::assertTrue($databaseDriver->write('session-id', 'serialized'));
        self::assertSame('serialized', $databaseDriver->read('session-id'));
        self::assertTrue($databaseDriver->destroy('session-id'));

        $manager = new SessionManager(
            $fileManager,
            new ErrorManager(new ExceptionProvider()),
            $database
        );

        self::assertTrue($manager->supports('drivers.file'));
        self::assertTrue($manager->supports('drivers.database'));
    }

    public function testRequestNegotiationResourcesMailerAndOtpManagersWork(): void
    {
        $request = new class implements RequestInterface {
            public function sanitize(): void {}
            public function validate(): void {}
            public function transform(): void {}
            public function handle(): void {}
            public function input(string $key, mixed $default = null): mixed { return $default; }
            public function all(): array { return []; }
            public function file(?string $key = null): mixed { return null; }
            public function header(string $key, mixed $default = null): mixed
            {
                return match (strtolower($key)) {
                    'accept' => 'application/json',
                    default => $default,
                };
            }
            public function headers(): array { return ['accept' => 'application/json']; }
            public function method(): string { return 'GET'; }
            public function uri(): string { return '/api/demo'; }
            public function accepts(string $contentType): bool { return str_contains('application/json', strtolower($contentType)); }
            public function wantsJson(): bool { return true; }
            public function expectsJson(): bool { return true; }
        };

        $response = new class(new DataHandler(), new DateTimeManager()) extends Response {
            public function send(): void
            {
                $this->prepareForSend();
            }
        };

        $view = $this->createStub(ViewInterface::class);
        $view->method('renderPage')->willReturn('<p>html</p>');

        $controller = new class(
            $request,
            $response,
            $this->createStub(ServiceInterface::class),
            $this->createStub(PresenterInterface::class),
            $view
        ) extends \App\Abstracts\Http\Controller {
            public function apiPayload(array $data): \App\Contracts\Http\ResponseInterface
            {
                return $this->respondNegotiated('renderPage', 'Ignored', $data, 202);
            }
        };

        $resource = new class(['name' => 'LangelerMVC']) extends Resource {
            protected function resolveData(): array
            {
                return is_array($this->resource) ? $this->resource : [];
            }
        };

        $collection = new class([['id' => 1], ['id' => 2]]) extends ResourceCollection {
            protected function mapItem(mixed $item): array
            {
                return (array) $item;
            }
        };

        $jsonResponse = $controller->apiPayload($resource->withMeta(['api' => true])->toArray());

        self::assertSame(202, $jsonResponse->getStatus());
        self::assertSame('application/json; charset=UTF-8', $jsonResponse->getHeaders()['content-type']);
        self::assertSame([['id' => 1], ['id' => 2]], $collection->toArray()['data']);

        $config = new class extends Config {
            public function __construct() {}

            public function get(string $file, ?string $key = null, mixed $default = null): mixed
            {
                return match (strtolower($file)) {
                    'mail' => match ($key) {
                        'MAILER' => 'array',
                        'FROM' => 'framework@example.com',
                        default => $default,
                    },
                    'app' => match ($key) {
                        'NAME' => 'LangelerMVC',
                        default => $default,
                    },
                    'auth' => match ($key) {
                        'OTP.DIGITS' => 6,
                        'OTP.PERIOD' => 30,
                        'OTP.ALGORITHM' => 'sha1',
                        default => $default,
                    },
                    default => $default,
                };
            }
        };

        $mail = new MailManager($config, new FileManager(), new ErrorManager(new ExceptionProvider()));
        $mailable = new class extends \App\Abstracts\Support\Mailable {
            protected function build(): void
            {
                $this->to('user@example.com')
                    ->subject('Framework Test')
                    ->text('Mail subsystem working.');
            }
        };

        self::assertTrue($mail->send($mailable));
        self::assertCount(1, $mail->outbox());

        $otp = new OtpManager($config);
        $provisioned = $otp->provision('user@example.com', 'LangelerMVC');
        $code = \OTPHP\TOTP::create(
            $provisioned['secret'],
            $provisioned['period'],
            'sha1',
            $provisioned['digits']
        )->now();

        self::assertTrue($otp->verify($provisioned['secret'], $code));
        self::assertCount(8, $otp->recoveryCodes());
    }

    private function makeSqliteDatabase(): Database
    {
        $settings = new class extends SettingsManager {
            public function __construct() {}

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

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);

        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $target = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($target)) {
                $this->removeDirectory($target);
                continue;
            }

            @unlink($target);
        }

        @rmdir($path);
    }

    /**
     * @param array<int, class-string> $classes
     */
    private function makeModuleManagerStub(array $classes): ModuleManager
    {
        return new class($classes) extends ModuleManager {
            /**
             * @param array<int, class-string> $classes
             */
            public function __construct(private array $classes)
            {
            }

            public function getClasses(string $module, string $subDir, array $filter = ['extension' => 'php'], array $sort = []): array
            {
                return $this->filterClasses($module, $subDir);
            }

            public function collectClasses(string $subDir, array $filter = ['extension' => 'php'], array $sort = []): array
            {
                return $this->filterClasses(null, $subDir);
            }

            /**
             * @return array<int, array{class:string,shortName:string,file:string}>
             */
            private function filterClasses(?string $module, string $subDir): array
            {
                $results = [];

                foreach ($this->classes as $class) {
                    if ($subDir !== '' && !str_contains($class, '\\' . $subDir . '\\')) {
                        continue;
                    }

                    if ($module !== null && !str_contains($class, '\\' . $module . '\\')) {
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
        };
    }
}
