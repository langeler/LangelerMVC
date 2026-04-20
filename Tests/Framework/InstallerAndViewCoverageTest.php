<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Core\Config;
use App\Installer\InstallerView;
use App\Installer\InstallerWizard;
use App\Modules\AdminModule\Views\AdminView;
use App\Modules\CartModule\Views\CartView;
use App\Modules\OrderModule\Views\OrderView;
use App\Modules\ShopModule\Views\ShopView;
use App\Modules\UserModule\Views\UserView;
use App\Modules\WebModule\Views\WebView;
use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Finders\FileFinder;
use App\Utilities\Managers\CacheManager;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\IteratorManager;
use App\Utilities\Sanitation\PatternSanitizer;
use App\Utilities\Validation\PatternValidator;
use PHPUnit\Framework\TestCase;

final class InstallerAndViewCoverageTest extends TestCase
{
    private array $pathsToDelete = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->pathsToDelete) as $path) {
            if (is_file($path)) {
                @unlink($path);
                continue;
            }

            if (is_dir($path)) {
                @rmdir($path);
            }
        }

        $this->pathsToDelete = [];
    }

    public function testInstallerWizardReportsCapabilitiesForFreshProjectRoot(): void
    {
        $temporaryRoot = sys_get_temp_dir() . '/langelermvc-installer-' . bin2hex(random_bytes(4));

        foreach ([
            $temporaryRoot,
            $temporaryRoot . '/Storage',
            $temporaryRoot . '/Storage/Database',
        ] as $path) {
            mkdir($path, 0777, true);
            $this->pathsToDelete[] = $path;
        }

        copy(dirname(__DIR__, 2) . '/.env.example', $temporaryRoot . '/.env.example');
        $this->pathsToDelete[] = $temporaryRoot . '/.env.example';

        $wizard = new InstallerWizard(new FileManager(), $temporaryRoot);
        $status = $wizard->status();

        self::assertFalse($status['installed']);
        self::assertTrue($status['storageWritable']);
        self::assertTrue($status['databaseWritable']);
        self::assertTrue($status['environmentWritable']);
        self::assertContains('testing', $status['paymentDrivers']);
        self::assertContains('klarna', $status['paymentDrivers']);
        self::assertContains('WebModule', $status['modules']);
        self::assertContains('database', $status['contentSources']);
    }

    public function testInstallerWizardGeneratesProductionReadySecretsAndDerivedDefaults(): void
    {
        $wizard = new InstallerWizard(new FileManager());
        $defaults = $wizard->defaults();

        self::assertStringStartsWith('base64:', $defaults['ENCRYPTION_KEY']);
        self::assertStringStartsWith('base64:', $defaults['ENCRYPTION_OPENSSL_KEY']);
        self::assertStringStartsWith('base64:', $defaults['ENCRYPTION_SODIUM_KEY']);
        self::assertNotSame('langelermvc-signed-url', $defaults['HTTP_SIGNED_URL_KEY']);
        self::assertSame('LangelerMVC', $defaults['AUTH_PASSKEY_RP_NAME']);
        self::assertSame('localhost', $defaults['AUTH_PASSKEY_RP_ID']);
        self::assertSame('http://localhost', $defaults['AUTH_PASSKEY_ORIGINS']);
        self::assertSame('no-reply@langelermvc.test', $defaults['MAIL_FROM_ADDRESS']);
    }

    public function testInstallerAndModuleViewsExposeProductionTemplates(): void
    {
        $iterator = new IteratorManager();
        $files = new FileFinder($iterator);
        $directories = new DirectoryFinder($iterator);
        $cache = $this->createStub(CacheManager::class);
        $fileManager = new FileManager();
        $sanitizer = new PatternSanitizer();
        $validator = new PatternValidator();
        $config = $this->makeConfig();

        $installerView = new InstallerView($files, $directories, $cache, $fileManager, $sanitizer, $validator, $config);

        self::assertTrue($installerView->templateExists('layout', 'InstallerShell'));
        self::assertTrue($installerView->templateExists('page', 'InstallerWizard'));

        $installerOutput = $installerView->renderPage('InstallerWizard', [
            'pageTitle' => 'Install LangelerMVC',
            'form' => (new InstallerWizard(new FileManager()))->defaults(),
            'status' => (new InstallerWizard(new FileManager()))->status(),
            'errors' => [],
            'result' => null,
        ]);

        self::assertStringContainsString('Install LangelerMVC', $installerOutput);
        self::assertStringContainsString('Security & Identity', $installerOutput);
        self::assertStringContainsString('Installation Plan', $installerOutput);
        self::assertStringContainsString('Payment Compatibility', $installerOutput);

        $matrix = [
            [new WebView($files, $directories, $cache, $fileManager, $sanitizer, $validator, $config), ['Home', 'NotFound']],
            [new UserView($files, $directories, $cache, $fileManager, $sanitizer, $validator, $config), ['UserLogin', 'UserRegister', 'UserPasswordForgot', 'UserPasswordReset', 'UserProfile', 'UserStatus']],
            [new AdminView($files, $directories, $cache, $fileManager, $sanitizer, $validator, $config), ['AdminDashboard', 'AdminUsers', 'AdminRoles', 'AdminCatalog', 'AdminCarts', 'AdminOrders', 'AdminSystem', 'AdminOperations']],
            [new ShopView($files, $directories, $cache, $fileManager, $sanitizer, $validator, $config), ['ShopCatalog', 'ShopProduct']],
            [new CartView($files, $directories, $cache, $fileManager, $sanitizer, $validator, $config), ['CartPage']],
            [new OrderView($files, $directories, $cache, $fileManager, $sanitizer, $validator, $config), ['OrderCheckout', 'OrderList', 'OrderDetail']],
        ];

        foreach ($matrix as [$view, $pages]) {
            foreach ($pages as $page) {
                self::assertTrue($view->templateExists('page', $page), sprintf('Expected template [%s] to exist for [%s].', $page, $view::class));
            }
        }
    }

    public function testProductionTemplatesProvideNativeVideCounterparts(): void
    {
        $templateDirectories = [
            dirname(__DIR__, 2) . '/App/Templates/Layouts',
            dirname(__DIR__, 2) . '/App/Templates/Pages',
            dirname(__DIR__, 2) . '/App/Templates/Components',
            dirname(__DIR__, 2) . '/App/Templates/Partials',
        ];

        foreach ($templateDirectories as $directory) {
            foreach (glob($directory . '/*.php') ?: [] as $phpTemplate) {
                self::assertFileExists(
                    substr($phpTemplate, 0, -4) . '.vide',
                    sprintf('Expected a native .vide counterpart for [%s].', basename($phpTemplate))
                );
            }
        }
    }

    private function makeConfig(): Config
    {
        return new class extends Config {
            public function __construct()
            {
            }

            public function get(string $file, ?string $key = null, mixed $default = null): mixed
            {
                return match (strtolower($file)) {
                    'app' => match ($key) {
                        'NAME' => 'LangelerMVC',
                        'VERSION' => '1.0.0',
                        default => $default,
                    },
                    'webmodule' => match ($key) {
                        'DEFAULT_LAYOUT' => 'WebShell',
                        default => $default,
                    },
                    default => $default,
                };
            }
        };
    }
}
