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
        self::assertContains('postnord', $status['carrierAdapters']);
        self::assertContains('ups', $status['carrierAdapters']);
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
        self::assertSame('3', $defaults['QUEUE_MAX_ATTEMPTS']);
        self::assertSame('database,mail', $defaults['NOTIFICATIONS_DEFAULT_CHANNELS']);
        self::assertSame('true', $defaults['OPERATIONS_HEALTH_ENABLED']);
        self::assertSame('true', $defaults['COMMERCE_INVENTORY_RESERVE_ON_CHECKOUT']);
        self::assertSame('postnord', $defaults['COMMERCE_SHIPPING_ACTIVE_CARRIER']);
        self::assertSame('30', $defaults['COMMERCE_SHIPPING_TIMEOUT']);
        self::assertSame('60', $defaults['COMMERCE_INVENTORY_RESERVATION_TTL_MINUTES']);
        self::assertSame('2500', $defaults['COMMERCE_DOCUMENTS_VAT_RATE_BPS']);
        self::assertSame('30', $defaults['COMMERCE_RETURNS_WINDOW_DAYS']);
        self::assertSame('true', $defaults['COMMERCE_RETURNS_ALLOW_EXCHANGES']);
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
        $adminView = new AdminView($files, $directories, $cache, $fileManager, $sanitizer, $validator, $config);

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
        self::assertStringContainsString('Carrier Adapter Compatibility', $installerOutput);
        self::assertStringContainsString('Seller VAT ID', $installerOutput);

        $matrix = [
            [new WebView($files, $directories, $cache, $fileManager, $sanitizer, $validator, $config), ['Home', 'NotFound']],
            [new UserView($files, $directories, $cache, $fileManager, $sanitizer, $validator, $config), ['UserLogin', 'UserRegister', 'UserPasswordForgot', 'UserPasswordReset', 'UserProfile', 'UserStatus']],
            [$adminView, ['AdminDashboard', 'AdminUsers', 'AdminRoles', 'AdminPages', 'AdminCatalog', 'AdminPromotions', 'AdminCarts', 'AdminOrders', 'AdminSystem', 'AdminOperations']],
            [new ShopView($files, $directories, $cache, $fileManager, $sanitizer, $validator, $config), ['ShopCatalog', 'ShopProduct']],
            [new CartView($files, $directories, $cache, $fileManager, $sanitizer, $validator, $config), ['CartPage']],
            [new OrderView($files, $directories, $cache, $fileManager, $sanitizer, $validator, $config), ['OrderCheckout', 'OrderList', 'OrderDetail']],
        ];

        foreach ($matrix as [$view, $pages]) {
            foreach ($pages as $page) {
                self::assertTrue($view->templateExists('page', $page), sprintf('Expected template [%s] to exist for [%s].', $page, $view::class));
            }
        }

        $promotionOutput = $adminView->renderPage('AdminPromotions', [
            'headline' => 'Promotion and coupon management',
            'summary' => 'Operator promotion surface.',
            'promotions' => [[
                'id' => 1,
                'code' => 'ADMIN250',
                'label' => 'Admin 250 SEK',
                'active' => true,
                'type' => 'fixed_amount',
                'applies_to' => 'cart_subtotal',
                'rate_percent' => 0,
                'amount' => 'SEK 250.00',
                'shipping_rate' => '',
                'max_discount' => '',
                'usage_count' => 1,
                'usage_limit' => 5,
                'criteria' => ['per_customer_limit' => 1, 'per_segment_limit' => 1],
                'source' => 'database',
                'update_path' => '/admin/promotions/1',
                'deactivate_path' => '/admin/promotions/1/deactivate',
                'delete_path' => '/admin/promotions/1/delete',
            ]],
            'configured_promotions' => [],
            'promotion_usage' => [],
            'promotion_analytics' => [
                'by_code' => [['key' => 'ADMIN250', 'uses' => 1, 'orders' => 1, 'users' => 1, 'discount_minor' => 2500]],
                'by_customer_segment' => [['key' => 'customer', 'uses' => 1, 'orders' => 1, 'users' => 1, 'discount_minor' => 2500]],
            ],
            'promotion_form' => [],
            'promotion_metrics' => ['database_promotions' => 1],
        ]);

        self::assertStringContainsString('Bulk lifecycle action', $promotionOutput);
        self::assertStringContainsString('Promotion analytics', $promotionOutput);

        $operationsOutput = $adminView->renderPage('AdminOperations', [
            'headline' => 'Async and platform operations',
            'summary' => 'Structured operations.',
            'operations' => [
                'overview' => ['queue_driver' => 'sync', 'queue_pending' => 0, 'health_ready' => 'ok'],
                'links' => [['href' => '/admin/system', 'label' => 'System snapshot']],
                'queue' => ['driver' => 'sync', 'drivers' => ['sync'], 'failed_jobs' => 0],
                'notifications' => ['channels' => ['database'], 'stored' => 0],
                'events' => ['rows' => [['event' => 'order.created', 'listeners' => 1, 'listener_refs' => 'OrderListener']]],
                'payments' => [
                    'driver' => 'testing',
                    'methods' => ['card'],
                    'flows' => ['purchase'],
                    'driver_rows' => [['driver' => 'testing', 'label' => 'Testing', 'methods' => 'card', 'flows' => 'purchase', 'regions' => 'test', 'mode' => 'reference']],
                ],
                'shipping' => [
                    'country' => 'SE',
                    'default_option' => 'postnord-service-point',
                    'carriers' => [['code' => 'postnord']],
                    'adapter_rows' => [['carrier' => 'postnord', 'label' => 'PostNord', 'service_levels' => 'service_point, home', 'regions' => 'SE', 'mode' => 'reference', 'live_ready' => 'yes', 'missing' => '']],
                ],
                'health' => ['rows' => [['section' => 'ready', 'status' => 'ok', 'available' => 'yes', 'details' => 'database']]],
                'inventory' => [
                    'metrics' => ['inventory_reservations' => 1, 'reserved_inventory' => 1],
                    'recent' => [['reservation_key' => 'invres-demo', 'order_id' => 1, 'cart_id' => 1, 'product_id' => 1, 'quantity' => 1, 'status' => 'reserved', 'expires_at' => '2026-04-29 01:00:00']],
                ],
                'returns' => [
                    'metrics' => ['order_returns' => 1, 'completed_returns' => 1, 'return_refund_minor' => 1000],
                    'recent' => [['return_number' => 'RET-20260429-DEMO', 'type' => 'return', 'status' => 'completed', 'order_id' => 1, 'quantity' => 1, 'refund' => 'SEK 10.00', 'created_at' => '2026-04-29 01:00:00']],
                ],
                'documents' => [
                    'metrics' => ['order_documents' => 1, 'invoices' => 1, 'credit_notes' => 0],
                ],
                'audit' => [
                    'summary' => ['stored' => 1],
                    'filters' => [],
                    'limit' => 25,
                    'recent' => [['id' => '1', 'category' => 'admin', 'event' => 'admin.promotion.saved', 'severity' => 'info', 'actor_id' => '1', 'created_at' => '2026-04-29 00:00:00', 'context' => '{}']],
                    'category_links' => [['href' => '/admin/operations?audit_category=admin', 'label' => 'admin (1)']],
                    'severity_links' => [['href' => '/admin/operations?audit_severity=info', 'label' => 'info (1)']],
                ],
            ],
        ]);

        self::assertStringContainsString('Operator overview', $operationsOutput);
        self::assertStringContainsString('Carrier adapters', $operationsOutput);
        self::assertStringContainsString('Inventory reservations', $operationsOutput);
        self::assertStringContainsString('Returns, exchanges, and documents', $operationsOutput);
        self::assertStringContainsString('Audit drilldown', $operationsOutput);

        $ordersOutput = $adminView->renderPage('AdminOrders', [
            'headline' => 'Order administration',
            'summary' => 'Order workspace.',
            'orders' => [],
            'order' => [
                'id' => 1,
                'order_number' => 'ORD-20260429-DEMO',
                'contact_email' => 'customer@example.test',
                'status' => 'processing',
                'payment_status' => 'partially_refunded',
                'fulfillment_status' => 'ready_to_fulfill',
                'inventory_status' => 'committed',
                'currency' => 'SEK',
                'total_minor' => 10000,
                'total' => 'SEK 100.00',
                'items' => [['id' => 1, 'name' => 'Starter License', 'quantity' => 1, 'unit_price' => 'SEK 100.00', 'line_total' => 'SEK 100.00']],
                'returns' => [['return_number' => 'RET-20260429-DEMO', 'type' => 'return', 'status' => 'completed', 'order_item_id' => 1, 'quantity' => 1, 'refund' => 'SEK 10.00', 'reason' => 'Demo']],
                'documents' => [['document_number' => 'INV-20260429-DEMO', 'type' => 'invoice', 'status' => 'issued', 'total' => 'SEK 100.00', 'tax' => 'SEK 20.00', 'seller_vat_id' => 'SE556000000001', 'issued_at' => '2026-04-29 01:00:00']],
                'inventory_reservations' => [],
                'entitlements' => [],
                'subscriptions' => [],
                'addresses' => [],
                'actions' => [
                    'create_return' => '/admin/orders/1/returns',
                    'document_invoice' => '/admin/orders/1/documents/invoice',
                    'document_credit_note' => '/admin/orders/1/documents/credit-note',
                    'document_packing_slip' => '/admin/orders/1/documents/packing-slip',
                    'document_return_authorization' => '/admin/orders/1/documents/return-authorization',
                ],
            ],
        ]);

        self::assertStringContainsString('Returns, exchanges, and partial refunds', $ordersOutput);
        self::assertStringContainsString('Return and exchange ledger', $ordersOutput);
        self::assertStringContainsString('Order document ledger', $ordersOutput);
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

            foreach (glob($directory . '/*.lmv') ?: [] as $legacyTemplate) {
                self::assertFileExists(
                    substr($legacyTemplate, 0, -4) . '.vide',
                    sprintf('Expected a native .vide counterpart for [%s].', basename($legacyTemplate))
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
