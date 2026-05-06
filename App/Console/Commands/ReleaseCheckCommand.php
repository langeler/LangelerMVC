<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Core\Config;
use App\Core\Router;
use App\Contracts\Support\FrameworkLayerManagerInterface;
use App\Utilities\Managers\Support\PaymentManager;
use App\Utilities\Managers\System\SettingsManager;
use App\Utilities\Traits\ApplicationPathTrait;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ReleaseCheckCommand extends Command
{
    use ApplicationPathTrait;

    /**
     * @var list<string>
     */
    private const REQUIRED_DOCS = [
        'readme.md',
        'CHANGELOG.md',
        'RELEASE.md',
        'Docs/FrameworkStatus.md',
        'Docs/ReleaseReadinessPlan.md',
        'Docs/DeploymentAndUpgrade.md',
        'Docs/OperationsGuide.md',
        'Docs/DatabaseMatrixTesting.md',
        'Docs/FrameworkWideLayerEvaluation.md',
        'Docs/InstallationWizard.md',
        'Docs/ThemeManagement.md',
        'Docs/PaymentDrivers.md',
        'Docs/ShippingAdapters.md',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_ENV_KEYS = [
        'APP_NAME',
        'APP_ENV',
        'APP_URL',
        'APP_INSTALLED',
        'THEME_DEFAULT',
        'THEME_MODE',
        'THEME_ALLOW_USER_SELECTION',
        'THEME_ASSET_CSS',
        'THEME_ASSET_JS',
        'DB_CONNECTION',
        'SESSION_DRIVER',
        'CACHE_DRIVER',
        'QUEUE_DRIVER',
        'MAIL_MAILER',
        'PAYMENT_DRIVER',
        'PAYMENT_CURRENCY',
        'PAYMENT_DEFAULT_METHOD',
        'PAYMENT_DEFAULT_FLOW',
        'PAYMENT_WEBHOOKS_ENABLED',
        'PAYMENT_WEBHOOKS_REQUIRE_SIGNATURE',
        'PAYMENT_WEBHOOKS_SIGNATURE_HEADER',
        'PAYMENT_WEBHOOKS_EVENT_ID_HEADER',
        'PAYMENT_WEBHOOKS_TIMESTAMP_HEADER',
        'PAYMENT_WEBHOOKS_TOLERANCE_SECONDS',
        'PAYMENT_WEBHOOK_SECRET_TESTING',
        'PAYMENT_WEBHOOK_SECRET_CARD',
        'PAYMENT_WEBHOOK_SECRET_CRYPTO',
        'PAYMENT_WEBHOOK_SECRET_PAYPAL',
        'PAYMENT_WEBHOOK_SECRET_KLARNA',
        'PAYMENT_WEBHOOK_SECRET_SWISH',
        'PAYMENT_WEBHOOK_SECRET_QLIRO',
        'PAYMENT_WEBHOOK_SECRET_WALLEY',
        'PAYMENT_CARD_MODE',
        'PAYMENT_CARD_API_BASE',
        'PAYMENT_CARD_API_KEY',
        'PAYMENT_CARD_AUTH_SCHEME',
        'PAYMENT_CARD_CREATE_URL',
        'PAYMENT_CARD_CAPTURE_URL',
        'PAYMENT_CARD_REFUND_URL',
        'PAYMENT_CARD_CANCEL_URL',
        'PAYMENT_CARD_RECONCILE_URL',
        'PAYMENT_PAYPAL_MODE',
        'PAYMENT_PAYPAL_API_BASE',
        'PAYMENT_PAYPAL_CLIENT_ID',
        'PAYMENT_PAYPAL_CLIENT_SECRET',
        'PAYMENT_PAYPAL_RETURN_URL',
        'PAYMENT_PAYPAL_CANCEL_URL',
        'PAYMENT_KLARNA_MODE',
        'PAYMENT_KLARNA_API_BASE',
        'PAYMENT_KLARNA_USERNAME',
        'PAYMENT_KLARNA_PASSWORD',
        'PAYMENT_KLARNA_PURCHASE_COUNTRY',
        'PAYMENT_KLARNA_PURCHASE_CURRENCY',
        'PAYMENT_KLARNA_LOCALE',
        'PAYMENT_SWISH_MODE',
        'PAYMENT_SWISH_API_BASE',
        'PAYMENT_SWISH_PAYEE_ALIAS',
        'PAYMENT_SWISH_CERTIFICATE_PATH',
        'PAYMENT_SWISH_PRIVATE_KEY_PATH',
        'PAYMENT_SWISH_PASSPHRASE',
        'PAYMENT_SWISH_CALLBACK_URL',
        'PAYMENT_QLIRO_MODE',
        'PAYMENT_QLIRO_API_BASE',
        'PAYMENT_QLIRO_API_KEY',
        'PAYMENT_QLIRO_MERCHANT_API_KEY',
        'PAYMENT_QLIRO_MERCHANT_API_SECRET',
        'PAYMENT_QLIRO_MERCHANT_CONFIRMATION_URL',
        'PAYMENT_QLIRO_MERCHANT_TERMS_URL',
        'PAYMENT_QLIRO_MERCHANT_CHECKOUT_STATUS_PUSH_URL',
        'PAYMENT_QLIRO_MERCHANT_ORDER_MANAGEMENT_STATUS_PUSH_URL',
        'PAYMENT_QLIRO_CAPTURE_URL',
        'PAYMENT_QLIRO_REFUND_URL',
        'PAYMENT_QLIRO_CANCEL_URL',
        'PAYMENT_WALLEY_MODE',
        'PAYMENT_WALLEY_API_BASE',
        'PAYMENT_WALLEY_API_KEY',
        'PAYMENT_WALLEY_WSDL_URL',
        'PAYMENT_WALLEY_USERNAME',
        'PAYMENT_WALLEY_PASSWORD',
        'PAYMENT_WALLEY_MERCHANT_ID',
        'PAYMENT_WALLEY_RETURN_URL',
        'PAYMENT_WALLEY_CALLBACK_URL',
        'PAYMENT_WALLEY_CREATE_URL',
        'PAYMENT_WALLEY_CAPTURE_URL',
        'PAYMENT_WALLEY_REFUND_URL',
        'PAYMENT_WALLEY_CANCEL_URL',
        'PAYMENT_WALLEY_RECONCILE_URL',
        'PAYMENT_CRYPTO_MODE',
        'PAYMENT_CRYPTO_DEFAULT_ASSET',
        'PAYMENT_CRYPTO_DEFAULT_NETWORK',
        'PAYMENT_CRYPTO_CONFIRMATIONS_REQUIRED',
        'COMMERCE_CURRENCY',
        'COMMERCE_SHIPPING_INTEGRATION_MODE',
        'COMMERCE_SHIPPING_ACTIVE_CARRIER',
        'COMMERCE_SHIPPING_AUTO_BOOK_LABELS',
        'COMMERCE_SHIPPING_API_BASE',
        'COMMERCE_SHIPPING_API_KEY',
        'COMMERCE_SHIPPING_SERVICE_POINTS_URL',
        'COMMERCE_SHIPPING_BOOKING_URL',
        'COMMERCE_SHIPPING_TRACKING_URL',
        'COMMERCE_SHIPPING_CANCELLATION_URL',
        'COMMERCE_SHIPPING_TIMEOUT',
        'COMMERCE_SUBSCRIPTION_DEFAULT_INTERVAL',
        'COMMERCE_SUBSCRIPTION_MAX_RETRIES',
        'COMMERCE_INVENTORY_RESERVE_ON_CHECKOUT',
        'COMMERCE_INVENTORY_RELEASE_ON_CANCEL',
        'COMMERCE_INVENTORY_RESERVATION_TTL_MINUTES',
        'COMMERCE_DOCUMENTS_VAT_RATE_BPS',
        'COMMERCE_DOCUMENTS_SELLER_NAME',
        'COMMERCE_DOCUMENTS_SELLER_VAT_ID',
        'COMMERCE_DOCUMENTS_SELLER_ADDRESS',
        'COMMERCE_RETURNS_WINDOW_DAYS',
        'COMMERCE_RETURNS_ALLOW_EXCHANGES',
        'COMMERCE_RETURNS_AUTO_RESTOCK',
    ];

    /**
     * @var array<string, list<string>>
     */
    private const REQUIRED_DATA_SQL = [
        'Data/Framework.sql' => [
            'framework_migrations',
            'framework_migration_locks',
            'framework_jobs',
            'framework_failed_jobs',
            'framework_audit_log',
        ],
        'Data/Web.sql' => [
            'pages',
        ],
        'Data/Users.sql' => [
            'users',
            'roles',
            'permissions',
            'user_roles',
            'role_permissions',
            'user_auth_tokens',
            'user_passkeys',
        ],
        'Data/Products.sql' => [
            'categories',
            'products',
        ],
        'Data/Carts.sql' => [
            'carts',
            'cart_items',
            'promotions',
            'promotion_usages',
        ],
        'Data/Orders.sql' => [
            'orders',
            'order_items',
            'order_addresses',
            'order_entitlements',
            'order_subscriptions',
            'inventory_reservations',
            'order_returns',
            'order_documents',
            'payment_webhook_events',
        ],
    ];

    /**
     * @var list<string>
     */
    private const STALE_DATA_SQL_TABLES = [
        'coupons',
        'product_variations',
        'product_images',
        'shipment_tracking',
        'order_details',
        'user_details',
        'user_addresses',
        'user_security',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_ROUTES = [
        'GET /admin/operations',
        'GET /admin/promotions',
        'POST /admin/promotions/bulk',
        'POST /admin/orders/{order:\d+}/capture',
        'POST /admin/orders/{order:\d+}/refund',
        'POST /admin/orders/{order:\d+}/returns',
        'POST /admin/orders/{order:\d+}/returns/{return:\d+}/approve',
        'POST /admin/orders/{order:\d+}/returns/{return:\d+}/complete',
        'POST /admin/orders/{order:\d+}/documents/{type:[a-z-]+}',
        'POST /api/orders/webhooks/payments/{driver}',
        'POST /api/orders/webhooks/subscriptions/{driver}',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_FULFILLMENT_TYPES = [
        'physical_shipping',
        'digital_download',
        'virtual_access',
        'store_pickup',
        'scheduled_pickup',
        'preorder',
        'subscription',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_MODULES = [
        'WebModule',
        'UserModule',
        'ShopModule',
        'CartModule',
        'OrderModule',
        'AdminModule',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_SWEDISH_CARRIERS = [
        'postnord',
        'instabox',
        'budbee',
        'bring',
        'dhl',
        'schenker',
        'earlybird',
        'airmee',
        'ups',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_PAYMENT_DRIVERS = [
        'testing',
        'card',
        'paypal',
        'klarna',
        'swish',
        'qliro',
        'walley',
        'crypto',
    ];

    public function __construct(
        private readonly Config $config,
        private readonly Router $router,
        private readonly SettingsManager $settings,
        private readonly PaymentManager $payments,
        private readonly FrameworkLayerManagerInterface $frameworkLayers
    ) {
    }

    public function name(): string
    {
        return 'release:check';
    }

    public function description(): string
    {
        return 'Run release hygiene, docs, route, template, provider, and environment readiness checks.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        $strict = $this->optionBool($options['strict'] ?? false);
        $quiet = $this->optionBool($options['quiet'] ?? false);
        $payload = $this->inspect($strict);

        if (!$quiet) {
            $this->dumpJson($payload);
        }

        return (bool) ($payload['healthy'] ?? false) ? 0 : 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function inspect(bool $strict = false): array
    {
        $checks = [
            'release_docs' => $this->releaseDocsCheck(),
            'environment_template' => $this->environmentTemplateCheck(),
            'data_sql_reference' => $this->dataSqlReferenceCheck(),
            'framework_layers' => $this->frameworkLayers->inspect(),
            'framework_routes' => $this->routeCheck(),
            'module_surface' => $this->moduleSurfaceCheck(),
            'payment_surface' => $this->paymentSurfaceCheck(),
            'commerce_surface' => $this->commerceSurfaceCheck(),
            'theme_surface' => $this->themeSurfaceCheck(),
            'template_accessibility' => $this->templateAccessibilityCheck(),
            'external_matrix' => $this->externalMatrixCheck(),
            'live_integrations' => $this->liveIntegrationCheck(),
        ];

        $errors = [];
        $warnings = [];

        foreach ($checks as $check) {
            $errors = array_merge($errors, array_map('strval', (array) ($check['errors'] ?? [])));
            $warnings = array_merge($warnings, array_map('strval', (array) ($check['warnings'] ?? [])));
        }

        $errors = array_values(array_unique($errors));
        $warnings = array_values(array_unique($warnings));
        $healthy = $errors === [] && (!$strict || $warnings === []);

        return [
            'status' => $healthy ? 200 : 503,
            'healthy' => $healthy,
            'strict' => $strict,
            'timestamp' => gmdate('c'),
            'checks' => $checks,
            'errors' => $errors,
            'warnings' => $warnings,
            'external_required' => [
                'database_cache_session_matrix' => ['mysql', 'pgsql', 'sqlsrv', 'redis', 'memcached'],
                'live_carriers' => self::REQUIRED_SWEDISH_CARRIERS,
                'live_payment_or_subscription_credentials' => ['card', 'paypal', 'klarna', 'swish', 'qliro', 'walley', 'crypto'],
                'browser_accessibility_pass' => ['public-light', 'public-dark', 'installer', 'admin'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function releaseDocsCheck(): array
    {
        $missing = [];
        $warnings = [];

        foreach (self::REQUIRED_DOCS as $doc) {
            if (!is_file($this->path($doc))) {
                $missing[] = $doc;
            }
        }

        $requiredPhrases = [
            'readme.md' => ['Verification Snapshot', 'DeploymentAndUpgrade.md'],
            'RELEASE.md' => ['Latest Verified Snapshot', 'Deployment Recipe', 'Upgrade Recipe'],
            'Docs/ReleaseReadinessPlan.md' => ['Exact Remaining Work', 'P2 - Production Hardening'],
            'Docs/DeploymentAndUpgrade.md' => ['Production Deployment Recipe', 'Release Smoke Matrix'],
            'Docs/FrameworkStatus.md' => ['Verification result', 'Remaining Hardening / Environment Work'],
        ];

        foreach ($requiredPhrases as $doc => $phrases) {
            $contents = $this->read($doc);

            foreach ($phrases as $phrase) {
                if ($contents !== '' && !str_contains($contents, $phrase)) {
                    $warnings[] = sprintf('Release doc [%s] does not mention [%s].', $doc, $phrase);
                }
            }
        }

        return [
            'ok' => $missing === [],
            'required' => self::REQUIRED_DOCS,
            'missing' => $missing,
            'errors' => $missing === [] ? [] : ['Missing release documentation: ' . implode(', ', $missing)],
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function environmentTemplateCheck(): array
    {
        $contents = $this->read('.env.example');
        $missing = [];
        $duplicates = [];

        foreach (self::REQUIRED_ENV_KEYS as $key) {
            if (!preg_match('/^' . preg_quote($key, '/') . '=/m', $contents)) {
                $missing[] = $key;
            }
        }

        if (preg_match_all('/^([A-Z0-9_]+)=/m', $contents, $matches)) {
            $counts = array_count_values($matches[1]);
            $duplicates = array_keys(array_filter($counts, static fn(int $count): bool => $count > 1));
        }

        $report = method_exists($this->settings, 'environmentReport')
            ? $this->settings->environmentReport()
            : ['unknown' => [], 'unknown_count' => 0];
        $unknown = array_values(array_map('strval', (array) ($report['unknown'] ?? [])));

        return [
            'ok' => $missing === [] && $duplicates === [],
            'required_count' => count(self::REQUIRED_ENV_KEYS),
            'missing' => $missing,
            'duplicates' => $duplicates,
            'runtime_unknown_env_keys' => $unknown,
            'errors' => array_values(array_filter([
                $missing === [] ? null : 'Missing .env.example release keys: ' . implode(', ', $missing),
                $duplicates === [] ? null : 'Duplicate .env.example keys: ' . implode(', ', $duplicates),
            ])),
            'warnings' => $unknown === [] ? [] : ['Runtime .env has unknown keys: ' . implode(', ', $unknown)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dataSqlReferenceCheck(): array
    {
        $missingFiles = [];
        $missingTables = [];
        $staleTables = [];

        foreach (self::REQUIRED_DATA_SQL as $file => $tables) {
            $contents = $this->read($file);

            if ($contents === '') {
                $missingFiles[] = $file;
                continue;
            }

            foreach ($tables as $table) {
                if (!preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?["`\[]?' . preg_quote($table, '/') . '["`\]]?/i', $contents)) {
                    $missingTables[] = $file . ':' . $table;
                }
            }

            foreach (self::STALE_DATA_SQL_TABLES as $table) {
                if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?["`\[]?' . preg_quote($table, '/') . '["`\]]?/i', $contents)) {
                    $staleTables[] = $file . ':' . $table;
                }
            }
        }

        $readme = $this->read('Data/README.md');

        if ($readme === '') {
            $missingFiles[] = 'Data/README.md';
        }

        $errors = [];

        if ($missingFiles !== []) {
            $errors[] = 'Missing Data SQL release references: ' . implode(', ', $missingFiles);
        }

        if ($missingTables !== []) {
            $errors[] = 'Data SQL references are missing release tables: ' . implode(', ', $missingTables);
        }

        if ($staleTables !== []) {
            $errors[] = 'Data SQL references still contain stale pre-release tables: ' . implode(', ', $staleTables);
        }

        return [
            'ok' => $errors === [],
            'required_files' => array_values(array_keys(self::REQUIRED_DATA_SQL)),
            'required_table_count' => array_sum(array_map('count', self::REQUIRED_DATA_SQL)),
            'missing_files' => array_values(array_unique($missingFiles)),
            'missing_tables' => array_values(array_unique($missingTables)),
            'stale_tables' => array_values(array_unique($staleTables)),
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function routeCheck(): array
    {
        $routes = [];

        foreach ($this->router->listRoutes() as $route) {
            $routes[] = trim((string) ($route['method'] ?? '') . ' ' . (string) ($route['path'] ?? ''));
        }

        $missing = array_values(array_diff(self::REQUIRED_ROUTES, $routes));

        return [
            'ok' => $missing === [],
            'route_count' => count($routes),
            'required' => self::REQUIRED_ROUTES,
            'missing' => $missing,
            'errors' => $missing === [] ? [] : ['Missing release-critical routes: ' . implode(', ', $missing)],
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function moduleSurfaceCheck(): array
    {
        $requiredComponents = ['Controllers', 'Routes', 'Requests', 'Responses', 'Views', 'Presenters', 'Services'];
        $modules = [];
        $errors = [];

        foreach (self::REQUIRED_MODULES as $module) {
            $moduleRoot = $this->path('App/Modules/' . $module);
            $components = [];

            if (!is_dir($moduleRoot)) {
                $errors[] = sprintf('Missing first-party module [%s].', $module);
                $modules[$module] = ['present' => false, 'components' => []];
                continue;
            }

            foreach ($requiredComponents as $component) {
                $componentRoot = $moduleRoot . DIRECTORY_SEPARATOR . $component;
                $files = is_dir($componentRoot)
                    ? array_values(array_filter(glob($componentRoot . DIRECTORY_SEPARATOR . '*.php') ?: [], 'is_file'))
                    : [];
                $components[$component] = count($files);

                if ($files === []) {
                    $errors[] = sprintf('Module [%s] is missing release component [%s].', $module, $component);
                }
            }

            $modules[$module] = [
                'present' => true,
                'components' => $components,
            ];
        }

        return [
            'ok' => $errors === [],
            'required' => self::REQUIRED_MODULES,
            'component_requirements' => $requiredComponents,
            'modules' => $modules,
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentSurfaceCheck(): array
    {
        $configured = array_keys((array) $this->config->get('payment', 'DRIVERS', []));
        $configured = array_values(array_map('strtolower', array_map('strval', $configured)));
        $catalog = $this->payments->driverCatalog();
        $catalogDrivers = array_keys($catalog);
        $missingConfigured = array_values(array_diff(self::REQUIRED_PAYMENT_DRIVERS, $configured));
        $missingCatalog = array_values(array_diff(self::REQUIRED_PAYMENT_DRIVERS, $catalogDrivers));
        $errors = [];

        if ($missingConfigured !== []) {
            $errors[] = 'Missing payment driver configuration: ' . implode(', ', $missingConfigured);
        }

        if ($missingCatalog !== []) {
            $errors[] = 'Missing payment provider catalog entries: ' . implode(', ', $missingCatalog);
        }

        foreach ($catalog as $driver => $definition) {
            $methods = (array) ($definition['methods'] ?? []);
            $flows = (array) ($definition['flows'] ?? []);

            if ($methods === []) {
                $errors[] = sprintf('Payment driver [%s] does not expose supported methods.', $driver);
            }

            if ($flows === []) {
                $errors[] = sprintf('Payment driver [%s] does not expose supported flows.', $driver);
            }

            if (!array_key_exists('live_ready', $definition)) {
                $errors[] = sprintf('Payment driver [%s] does not expose live readiness metadata.', $driver);
            }
        }

        return [
            'ok' => $errors === [],
            'required' => self::REQUIRED_PAYMENT_DRIVERS,
            'configured' => $configured,
            'catalog' => $catalogDrivers,
            'drivers' => $catalog,
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function commerceSurfaceCheck(): array
    {
        $fulfillmentTypes = array_keys((array) $this->config->get('commerce', 'FULFILLMENT.TYPES', []));
        $carriers = array_keys((array) $this->config->get('commerce', 'SHIPPING.CARRIERS', []));
        $carrierAdapters = array_keys((array) $this->config->get('commerce', 'SHIPPING.ADAPTERS', []));
        $trackingApps = array_keys((array) $this->config->get('commerce', 'SHIPPING.TRACKING_APPS', []));
        $missingTypes = array_values(array_diff(self::REQUIRED_FULFILLMENT_TYPES, $fulfillmentTypes));
        $missingCarriers = array_values(array_diff(self::REQUIRED_SWEDISH_CARRIERS, $carriers));
        $missingCarrierAdapters = array_values(array_diff(self::REQUIRED_SWEDISH_CARRIERS, $carrierAdapters));
        $errors = [];

        if ($missingTypes !== []) {
            $errors[] = 'Missing commerce fulfillment types: ' . implode(', ', $missingTypes);
        }

        if ($missingCarriers !== []) {
            $errors[] = 'Missing Swedish carrier definitions: ' . implode(', ', $missingCarriers);
        }

        if ($missingCarrierAdapters !== []) {
            $errors[] = 'Missing Swedish carrier adapter definitions: ' . implode(', ', $missingCarrierAdapters);
        }

        if (!in_array('mina_paket', $trackingApps, true)) {
            $errors[] = 'Missing Mina Paket tracking-app handoff definition.';
        }

        foreach ([
            'INVENTORY.RESERVATION_TTL_MINUTES' => (int) $this->config->get('commerce', 'INVENTORY.RESERVATION_TTL_MINUTES', 0),
            'RETURNS.WINDOW_DAYS' => (int) $this->config->get('commerce', 'RETURNS.WINDOW_DAYS', -1),
            'DOCUMENTS.VAT_RATE_BPS' => (int) $this->config->get('commerce', 'DOCUMENTS.VAT_RATE_BPS', -1),
        ] as $key => $value) {
            if ($value < 0 || ($key === 'INVENTORY.RESERVATION_TTL_MINUTES' && $value < 1)) {
                $errors[] = sprintf('Invalid commerce setting [%s].', $key);
            }
        }

        return [
            'ok' => $errors === [],
            'fulfillment_types' => $fulfillmentTypes,
            'carriers' => $carriers,
            'carrier_adapters' => $carrierAdapters,
            'tracking_apps' => $trackingApps,
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function themeSurfaceCheck(): array
    {
        $requiredThemes = ['bootstrap-light', 'bootstrap-dark', 'bootstrap-system'];
        $allowedModes = ['light', 'dark', 'system'];
        $themes = array_keys((array) $this->config->get('theme', 'THEMES', []));
        $themes = array_values(array_map('strtolower', array_map('strval', $themes)));
        $defaultTheme = strtolower((string) $this->config->get('theme', 'DEFAULT', ''));
        $mode = strtolower((string) $this->config->get('theme', 'MODE', ''));
        $cssAsset = (string) $this->config->get('theme', 'ASSETS.CSS', '/assets/css/langelermvc-theme.css');
        $jsAsset = (string) $this->config->get('theme', 'ASSETS.JS', '/assets/js/langelermvc-theme.js');
        $assetFiles = [
            'source_css' => 'App/Resources/css/langelermvc-theme.css',
            'source_js' => 'App/Resources/js/langelermvc-theme.js',
            'public_css' => 'Public/' . ltrim($cssAsset, '/'),
            'public_js' => 'Public/' . ltrim($jsAsset, '/'),
        ];
        $missingThemes = array_values(array_diff($requiredThemes, $themes));
        $missingAssets = [];
        $errors = [];

        foreach ($assetFiles as $label => $relative) {
            if ($relative === '' || !is_file($this->path($relative))) {
                $missingAssets[] = $label . ':' . $relative;
            }
        }

        if ($missingThemes !== []) {
            $errors[] = 'Missing framework theme definitions: ' . implode(', ', $missingThemes);
        }

        if (!in_array($defaultTheme, $themes, true)) {
            $errors[] = sprintf('Default theme [%s] is not present in the configured theme catalog.', $defaultTheme);
        }

        if (!in_array($mode, $allowedModes, true)) {
            $errors[] = sprintf('Theme mode [%s] is not one of: %s.', $mode, implode(', ', $allowedModes));
        }

        if ($missingAssets !== []) {
            $errors[] = 'Missing theme assets: ' . implode(', ', $missingAssets);
        }

        foreach ([
            'css_asset_path' => $cssAsset,
            'js_asset_path' => $jsAsset,
        ] as $label => $asset) {
            if (!str_starts_with($asset, '/assets/')) {
                $errors[] = sprintf('Theme %s must point at /assets/.', $label);
            }
        }

        return [
            'ok' => $errors === [],
            'required' => $requiredThemes,
            'catalog' => $themes,
            'default' => $defaultTheme,
            'mode' => $mode,
            'assets' => $assetFiles,
            'missing_themes' => $missingThemes,
            'missing_assets' => $missingAssets,
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function templateAccessibilityCheck(): array
    {
        $files = $this->templateFiles();
        $rawPhp = [];
        $imagesWithoutAlt = [];
        $unlabelledControls = [];

        foreach ($files as $file) {
            $contents = (string) file_get_contents($file);
            $relative = $this->relativePath($file);

            if (str_contains($contents, '<?php') || str_contains($contents, '<?=') || str_contains($contents, '<? ')) {
                $rawPhp[] = $relative;
            }

            if (preg_match_all('/<img\b(?![^>]*\balt\s*=)[^>]*>/i', $contents, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $imagesWithoutAlt[] = $relative . ':' . $this->lineNumber($contents, (int) $match[1]);
                }
            }

            foreach ($this->findUnlabelledControls($contents) as $line) {
                $unlabelledControls[] = $relative . ':' . $line;
            }
        }

        $errors = [];

        if ($rawPhp !== []) {
            $errors[] = 'Raw PHP tags remain in native .vide templates: ' . implode(', ', $rawPhp);
        }

        if ($imagesWithoutAlt !== []) {
            $errors[] = 'Images without alt attributes found: ' . implode(', ', $imagesWithoutAlt);
        }

        return [
            'ok' => $errors === [],
            'template_count' => count($files),
            'raw_php_templates' => $rawPhp,
            'images_without_alt' => $imagesWithoutAlt,
            'unlabelled_controls' => $unlabelledControls,
            'errors' => $errors,
            'warnings' => $unlabelledControls === []
                ? []
                : ['Potentially unlabelled form controls found: ' . implode(', ', $unlabelledControls)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function externalMatrixCheck(): array
    {
        $compose = $this->read('docker-compose.verify.yml');
        $requiredServices = ['mysql', 'pgsql', 'sqlsrv', 'redis', 'memcached'];
        $missingServices = [];

        foreach ($requiredServices as $service) {
            if (!str_contains($compose, $service)) {
                $missingServices[] = $service;
            }
        }

        $extensions = [
            'pdo_sqlite' => extension_loaded('pdo_sqlite'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'pdo_pgsql' => extension_loaded('pdo_pgsql'),
            'sqlsrv' => extension_loaded('sqlsrv') || extension_loaded('pdo_sqlsrv'),
            'redis' => extension_loaded('redis'),
            'memcached' => extension_loaded('memcached') || extension_loaded('memcache'),
            'imagick' => extension_loaded('imagick'),
        ];

        $warnings = [];
        $missingOptional = array_keys(array_filter($extensions, static fn(bool $loaded): bool => !$loaded));

        if ($missingOptional !== []) {
            $warnings[] = 'Optional release-matrix PHP extensions are not loaded here: ' . implode(', ', $missingOptional);
        }

        return [
            'ok' => $missingServices === [],
            'compose_services' => $requiredServices,
            'missing_compose_services' => $missingServices,
            'loaded_extensions' => $extensions,
            'errors' => $missingServices === [] ? [] : ['Missing docker-compose.verify.yml services: ' . implode(', ', $missingServices)],
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function liveIntegrationCheck(): array
    {
        $driver = strtolower((string) $this->config->get('payment', 'DRIVER', 'testing'));
        $driverConfig = (array) $this->config->get('payment', 'DRIVERS.' . $driver, []);
        $mode = strtolower((string) ($driverConfig['MODE'] ?? 'reference'));
        $webhookSecret = trim((string) $this->config->get('payment', 'WEBHOOKS.SECRETS.' . $driver, ''));
        $shippingMode = strtolower((string) $this->config->get('commerce', 'SHIPPING.INTEGRATION.MODE', 'reference'));
        $sellerVatId = trim((string) $this->config->get('commerce', 'DOCUMENTS.SELLER_VAT_ID', ''));
        $sellerAddress = trim((string) $this->config->get('commerce', 'DOCUMENTS.SELLER_ADDRESS', ''));
        $warnings = [];
        $errors = [];

        if ($driver === 'testing' || $mode === 'reference') {
            $warnings[] = sprintf('Payment driver [%s] is still in reference/testing mode.', $driver);
        }

        if ($webhookSecret === '') {
            $warnings[] = sprintf('Payment webhook secret for active driver [%s] is empty.', $driver);
        }

        if ($mode === 'live') {
            foreach ($this->liveRequiredPaymentFields($driver) as $field) {
                if (trim((string) ($driverConfig[$field] ?? '')) === '') {
                    $errors[] = sprintf('Live payment driver [%s] is missing [%s].', $driver, $field);
                }
            }
        }

        if ($shippingMode === 'reference') {
            $warnings[] = 'Shipping integration mode is reference; live carrier credentials/adapters still need target-environment validation.';
        }

        if ($shippingMode === 'live') {
            foreach ($this->liveRequiredShippingFields() as $field) {
                if (trim((string) $this->config->get('commerce', 'SHIPPING.INTEGRATION.' . $field, '')) === '') {
                    $errors[] = sprintf('Live shipping integration is missing [%s].', $field);
                }
            }
        }

        if ($sellerVatId === '' || $sellerAddress === '') {
            $warnings[] = 'Order document seller VAT/address fields should be filled before issuing production VAT documents.';
        }

        return [
            'ok' => $errors === [],
            'payment_driver' => $driver,
            'payment_mode' => $mode,
            'payment_webhook_secret_configured' => $webhookSecret !== '',
            'shipping_mode' => $shippingMode,
            'shipping_active_carrier' => strtolower((string) $this->config->get('commerce', 'SHIPPING.INTEGRATION.ACTIVE_CARRIER', 'postnord')),
            'seller_vat_configured' => $sellerVatId !== '',
            'seller_address_configured' => $sellerAddress !== '',
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return list<string>
     */
    private function liveRequiredPaymentFields(string $driver): array
    {
        return match ($driver) {
            'card' => ['API_KEY', 'CREATE_URL', 'CAPTURE_URL', 'REFUND_URL', 'CANCEL_URL'],
            'paypal' => ['API_BASE', 'CLIENT_ID', 'CLIENT_SECRET', 'RETURN_URL', 'CANCEL_URL'],
            'klarna' => ['API_BASE', 'USERNAME', 'PASSWORD', 'PURCHASE_COUNTRY', 'PURCHASE_CURRENCY'],
            'swish' => ['API_BASE', 'PAYEE_ALIAS', 'CERTIFICATE_PATH', 'PRIVATE_KEY_PATH', 'CALLBACK_URL'],
            'qliro' => ['API_BASE', 'API_KEY', 'MERCHANT_CONFIRMATION_URL', 'MERCHANT_TERMS_URL'],
            'walley' => ['CREATE_URL', 'CAPTURE_URL', 'REFUND_URL', 'CANCEL_URL', 'RECONCILE_URL'],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private function liveRequiredShippingFields(): array
    {
        return ['ACTIVE_CARRIER', 'API_BASE', 'API_KEY', 'BOOKING_URL', 'TRACKING_URL'];
    }

    /**
     * @return list<string>
     */
    private function templateFiles(): array
    {
        $root = $this->path('App/Templates');
        $files = [];

        if (!is_dir($root)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        foreach ($iterator as $entry) {
            if (!$entry instanceof SplFileInfo || !$entry->isFile() || $entry->getExtension() !== 'vide') {
                continue;
            }

            $files[] = $entry->getPathname();
        }

        sort($files);

        return $files;
    }

    /**
     * @return list<int>
     */
    private function findUnlabelledControls(string $contents): array
    {
        if (!preg_match_all('/<(input|select|textarea)\b[^>]*>/i', $contents, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $lines = [];

        foreach ($matches[0] as $match) {
            $tag = (string) $match[0];
            $offset = (int) $match[1];

            if (preg_match('/\btype\s*=\s*["\']hidden["\']/i', $tag)) {
                continue;
            }

            if ($this->controlHasAccessibleName($contents, $tag, $offset)) {
                continue;
            }

            $lines[] = $this->lineNumber($contents, $offset);
        }

        return array_values(array_unique($lines));
    }

    private function controlHasAccessibleName(string $contents, string $tag, int $offset): bool
    {
        if (preg_match('/\baria-label\s*=|\baria-labelledby\s*=/i', $tag)) {
            return true;
        }

        if (preg_match('/\bid\s*=\s*["\']([^"\']+)["\']/i', $tag, $idMatch)) {
            $id = preg_quote((string) $idMatch[1], '/');

            if (preg_match('/<label\b[^>]*\bfor\s*=\s*["\']' . $id . '["\']/i', $contents)) {
                return true;
            }
        }

        $before = substr($contents, max(0, $offset - 300), min(300, $offset));
        $lastOpen = strripos($before, '<label');
        $lastClose = strripos($before, '</label>');

        if ($lastOpen === false || ($lastClose !== false && $lastClose > $lastOpen)) {
            return false;
        }

        $after = substr($contents, $offset, 600);

        return stripos($after, '</label>') !== false;
    }

    private function lineNumber(string $contents, int $offset): int
    {
        return substr_count(substr($contents, 0, $offset), "\n") + 1;
    }

    private function path(string $relative): string
    {
        return $this->frameworkBasePath() . DIRECTORY_SEPARATOR . ltrim($relative, '\\/');
    }

    private function relativePath(string $path): string
    {
        $base = rtrim($this->frameworkBasePath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    }

    private function read(string $relative): string
    {
        $path = $this->path($relative);

        return is_file($path) ? (string) file_get_contents($path) : '';
    }

    private function optionBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
        }

        return (bool) $value;
    }
}
