<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Core\Config;
use App\Core\Router;
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
        'Docs/InstallationWizard.md',
        'Docs/PaymentDrivers.md',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_ENV_KEYS = [
        'APP_NAME',
        'APP_ENV',
        'APP_URL',
        'APP_INSTALLED',
        'DB_CONNECTION',
        'SESSION_DRIVER',
        'CACHE_DRIVER',
        'QUEUE_DRIVER',
        'MAIL_MAILER',
        'PAYMENT_DRIVER',
        'PAYMENT_WEBHOOKS_REQUIRE_SIGNATURE',
        'PAYMENT_WEBHOOK_SECRET_TESTING',
        'COMMERCE_CURRENCY',
        'COMMERCE_SHIPPING_INTEGRATION_MODE',
        'COMMERCE_SHIPPING_AUTO_BOOK_LABELS',
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

    public function __construct(
        private readonly Config $config,
        private readonly Router $router,
        private readonly SettingsManager $settings
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
            'framework_routes' => $this->routeCheck(),
            'commerce_surface' => $this->commerceSurfaceCheck(),
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
                'browser_accessibility_pass' => ['public', 'installer', 'admin'],
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

        foreach (self::REQUIRED_ENV_KEYS as $key) {
            if (!preg_match('/^' . preg_quote($key, '/') . '=/m', $contents)) {
                $missing[] = $key;
            }
        }

        $report = method_exists($this->settings, 'environmentReport')
            ? $this->settings->environmentReport()
            : ['unknown' => [], 'unknown_count' => 0];
        $unknown = array_values(array_map('strval', (array) ($report['unknown'] ?? [])));

        return [
            'ok' => $missing === [],
            'required_count' => count(self::REQUIRED_ENV_KEYS),
            'missing' => $missing,
            'runtime_unknown_env_keys' => $unknown,
            'errors' => $missing === [] ? [] : ['Missing .env.example release keys: ' . implode(', ', $missing)],
            'warnings' => $unknown === [] ? [] : ['Runtime .env has unknown keys: ' . implode(', ', $unknown)],
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
    private function commerceSurfaceCheck(): array
    {
        $fulfillmentTypes = array_keys((array) $this->config->get('commerce', 'FULFILLMENT.TYPES', []));
        $carriers = array_keys((array) $this->config->get('commerce', 'SHIPPING.CARRIERS', []));
        $trackingApps = array_keys((array) $this->config->get('commerce', 'SHIPPING.TRACKING_APPS', []));
        $missingTypes = array_values(array_diff(self::REQUIRED_FULFILLMENT_TYPES, $fulfillmentTypes));
        $missingCarriers = array_values(array_diff(self::REQUIRED_SWEDISH_CARRIERS, $carriers));
        $errors = [];

        if ($missingTypes !== []) {
            $errors[] = 'Missing commerce fulfillment types: ' . implode(', ', $missingTypes);
        }

        if ($missingCarriers !== []) {
            $errors[] = 'Missing Swedish carrier definitions: ' . implode(', ', $missingCarriers);
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
            'tracking_apps' => $trackingApps,
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

        if ($sellerVatId === '' || $sellerAddress === '') {
            $warnings[] = 'Order document seller VAT/address fields should be filled before issuing production VAT documents.';
        }

        return [
            'ok' => $errors === [],
            'payment_driver' => $driver,
            'payment_mode' => $mode,
            'payment_webhook_secret_configured' => $webhookSecret !== '',
            'shipping_mode' => $shippingMode,
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
            'paypal' => ['CLIENT_ID', 'CLIENT_SECRET', 'RETURN_URL', 'CANCEL_URL'],
            'klarna' => ['USERNAME', 'PASSWORD', 'PURCHASE_COUNTRY', 'PURCHASE_CURRENCY'],
            'swish' => ['PAYEE_ALIAS', 'CERTIFICATE_PATH', 'PRIVATE_KEY_PATH', 'CALLBACK_URL'],
            'qliro' => ['API_KEY', 'MERCHANT_API_KEY', 'MERCHANT_API_SECRET', 'RETURN_URL'],
            'walley' => ['WSDL_URL', 'USERNAME', 'PASSWORD', 'MERCHANT_ID', 'RETURN_URL'],
            default => [],
        };
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
