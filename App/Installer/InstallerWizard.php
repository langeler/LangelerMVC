<?php

declare(strict_types=1);

namespace App\Installer;

use App\Core\MigrationRunner;
use App\Core\SeedRunner;
use App\Modules\UserModule\Repositories\RoleRepository;
use App\Modules\UserModule\Repositories\UserRepository;
use App\Providers\CoreProvider;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\Security\DatabaseUserProvider;
use App\Utilities\Traits\ApplicationPathTrait;

final class InstallerWizard
{
    use ApplicationPathTrait;

    private const SUPPORTED_DATABASE_DRIVERS = ['sqlite', 'mysql', 'pgsql', 'sqlsrv'];
    private const SUPPORTED_SESSION_DRIVERS = ['native', 'file', 'database', 'redis'];
    private const SUPPORTED_CACHE_DRIVERS = ['array', 'file', 'database', 'redis', 'memcache'];
    private const SUPPORTED_MAIL_DRIVERS = ['array', 'log', 'smtp', 'sendmail', 'mail'];
    private const SUPPORTED_QUEUE_DRIVERS = ['sync', 'database'];
    private const SUPPORTED_CONTENT_SOURCES = ['database', 'memory'];
    private const SUPPORTED_PAYMENT_DRIVERS = ['testing', 'card', 'paypal', 'klarna', 'swish', 'qliro', 'walley', 'crypto'];
    private const SUPPORTED_PAYMENT_METHODS = ['card', 'wallet', 'bank_transfer', 'bnpl', 'local_instant', 'manual', 'crypto'];
    private const SUPPORTED_PAYMENT_FLOWS = ['authorize_capture', 'purchase', 'redirect', 'async', 'manual_review'];
    private const SUPPORTED_CARRIER_ADAPTERS = ['postnord', 'instabox', 'budbee', 'bring', 'dhl', 'schenker', 'earlybird', 'airmee', 'ups'];
    private const SUPPORTED_FULFILLMENT_TYPES = ['physical_shipping', 'digital_download', 'virtual_access', 'store_pickup', 'scheduled_pickup', 'preorder', 'subscription'];
    private const SUPPORTED_SUBSCRIPTION_INTERVALS = ['weekly', 'monthly', 'quarterly', 'yearly'];

    /**
     * @var list<string>
     */
    private const BOOLEAN_FIELDS = [
        'APP_DEBUG',
        'AUTH_VERIFY_EMAIL',
        'CACHE_ENABLED',
        'COMMERCE_INVENTORY_RESERVE_ON_CHECKOUT',
        'COMMERCE_INVENTORY_RELEASE_ON_CANCEL',
        'COMMERCE_RETURNS_ALLOW_EXCHANGES',
        'COMMERCE_RETURNS_AUTO_RESTOCK',
        'MAIL_LOG_ENABLED',
        'MAIL_QUEUE',
        'NOTIFICATIONS_QUEUE',
        'OPERATIONS_HEALTH_ENABLED',
        'OPERATIONS_AUDIT_ENABLED',
        'PAYMENT_WEBHOOKS_ENABLED',
        'PAYMENT_WEBHOOKS_REQUIRE_SIGNATURE',
        'SESSION_EXPIRE_ON_CLOSE',
        'SESSION_SECURE_COOKIE',
        'SESSION_HTTPONLY_COOKIE',
        'COMMERCE_FULFILLMENT_AUTO_READY_ON_CAPTURE',
    ];

    private string $basePath;
    /**
     * @var (callable(): CoreProvider)|null
     */
    private $providerFactory;

    public function __construct(
        private readonly FileManager $files,
        ?string $basePath = null,
        ?callable $providerFactory = null
    ) {
        $this->basePath = $basePath ?? $this->frameworkBasePath();
        $this->providerFactory = $providerFactory;
    }

    /**
     * @return array<string, string>
     */
    public function defaults(): array
    {
        $defaults = array_replace($this->templateEnvironment(), $this->baseDefaults());
        $defaults = $this->synchronizeDefaults($defaults);

        $defaults['ADMIN_NAME'] = 'Platform Administrator';
        $defaults['ADMIN_EMAIL'] = 'admin@langelermvc.test';
        $defaults['ADMIN_PASSWORD'] = 'admin12345';

        return $defaults;
    }

    public function isInstalled(): bool
    {
        $environment = $this->readEnvironment($this->envPath());

        return filter_var($environment['APP_INSTALLED'] ?? false, FILTER_VALIDATE_BOOL);
    }

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $storage = $this->storagePath();
        $databaseDirectory = $this->storagePath('Database');
        $environmentDirectory = dirname($this->envPath());
        $appUrl = (string) ($this->defaults()['APP_URL'] ?? 'http://localhost');
        $installState = $this->readInstallState();

        return [
            'php' => PHP_VERSION,
            'installed' => $this->isInstalled(),
            'storageWritable' => $this->isWritablePath($storage),
            'databaseWritable' => $this->isWritablePath($databaseDirectory),
            'environmentWritable' => $this->isWritablePath($environmentDirectory),
            'httpsRecommended' => str_starts_with(strtolower($appUrl), 'https://'),
            'extensions' => [
                'pdo' => extension_loaded('pdo'),
                'openssl' => extension_loaded('openssl'),
                'sodium' => extension_loaded('sodium'),
                'intl' => extension_loaded('intl'),
                'mbstring' => extension_loaded('mbstring'),
                'redis' => extension_loaded('redis'),
                'memcached' => extension_loaded('memcached'),
            ],
            'modules' => $this->moduleInstallOrder(),
            'databaseDrivers' => self::SUPPORTED_DATABASE_DRIVERS,
            'sessionDrivers' => self::SUPPORTED_SESSION_DRIVERS,
            'cacheDrivers' => self::SUPPORTED_CACHE_DRIVERS,
            'mailDrivers' => self::SUPPORTED_MAIL_DRIVERS,
            'queueDrivers' => self::SUPPORTED_QUEUE_DRIVERS,
            'contentSources' => self::SUPPORTED_CONTENT_SOURCES,
            'paymentDrivers' => self::SUPPORTED_PAYMENT_DRIVERS,
            'carrierAdapters' => self::SUPPORTED_CARRIER_ADAPTERS,
            'fulfillmentTypes' => self::SUPPORTED_FULFILLMENT_TYPES,
            'subscriptionIntervals' => self::SUPPORTED_SUBSCRIPTION_INTERVALS,
            'installState' => [
                'status' => (string) ($installState['status'] ?? 'idle'),
                'stage' => (string) ($installState['stage'] ?? 'idle'),
                'resumeAvailable' => in_array((string) ($installState['status'] ?? ''), ['failed', 'rolled_back'], true),
                'updatedAt' => $installState['updated_at'] ?? null,
                'error' => is_array($installState['error'] ?? null)
                    ? (string) ($installState['error']['message'] ?? '')
                    : '',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function install(array $payload): array
    {
        $data = $this->normalizePayload($payload);
        $this->validate($data);
        $hadEnvironmentFile = $this->files->fileExists($this->envPath());
        $previousEnvironmentContents = $hadEnvironmentFile
            ? ($this->files->readContents($this->envPath()) ?? '')
            : null;
        $environment = [];
        $migrated = [];
        $seeded = [];
        $stage = 'bootstrap';
        $migrationRunner = null;

        $this->updateInstallState('running', $stage, [
            'migrated' => [],
            'seeded' => [],
            'rolled_back' => [],
        ]);

        try {
            $stage = 'filesystem';
            $this->updateInstallState('running', $stage);
            $this->prepareFilesystem($data);

            $stage = 'database';
            $this->updateInstallState('running', $stage);
            $this->verifyDatabaseConnection($data);

            $environment = $this->buildEnvironment($data);

            $stage = 'environment';
            $this->updateInstallState('running', $stage);
            $this->writeEnvironment($environment);
            $this->reloadEnvironment($environment);
            $this->ensurePathConstants();

            $provider = $this->makeCoreProvider();
            $provider->registerServices();

            $migrationRunner = $provider->getCoreService('migrationRunner');
            $seedRunner = $provider->getCoreService('seedRunner');

            if (!$migrationRunner instanceof MigrationRunner || !$seedRunner instanceof SeedRunner) {
                throw new \RuntimeException('Unable to resolve schema lifecycle services during installation.');
            }

            $stage = 'migrations.framework';
            $migrated['Framework'] = $migrationRunner->migrate('Framework');
            $this->updateInstallState('running', $stage, ['migrated' => $migrated]);

            foreach ($this->moduleInstallOrder() as $module) {
                $stage = 'migrations.' . $module;
                $migrated[$module] = $migrationRunner->migrate($module);
                $this->updateInstallState('running', $stage, ['migrated' => $migrated]);
            }

            foreach ($this->moduleInstallOrder() as $module) {
                $stage = 'seeds.' . $module;
                $seeded[$module] = $seedRunner->run($module);
                $this->updateInstallState('running', $stage, ['migrated' => $migrated, 'seeded' => $seeded]);
            }

            $stage = 'administrator';
            $admin = $this->provisionAdministrator($provider, $data);

            $this->clearInstallState();

            return [
                'environment' => $environment,
                'migrated' => $migrated,
                'seeded' => $seeded,
                'modules' => $this->moduleInstallOrder(),
                'admin' => $admin,
                'login' => [
                    'html' => '/users/login',
                    'api' => '/api/users/login',
                ],
            ];
        } catch (\Throwable $exception) {
            $rolledBack = $migrationRunner instanceof MigrationRunner
                ? $this->rollbackInstallMigrations($migrationRunner, $migrated)
                : [];

            $this->restoreEnvironmentSnapshot($previousEnvironmentContents, $hadEnvironmentFile, $environment);
            $this->updateInstallState($rolledBack !== [] ? 'rolled_back' : 'failed', $stage, [
                'migrated' => $migrated,
                'seeded' => $seeded,
                'rolled_back' => $rolledBack,
                'error' => [
                    'type' => $exception::class,
                    'message' => $exception->getMessage(),
                ],
            ]);

            throw new \RuntimeException(
                $rolledBack !== []
                    ? 'Installation failed and rollback was applied: ' . $exception->getMessage()
                    : 'Installation failed: ' . $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, string>
     */
    private function normalizePayload(array $payload): array
    {
        $data = array_replace($this->defaults(), array_map(
            static fn(mixed $value): string => is_string($value) ? trim($value) : (is_scalar($value) ? (string) $value : ''),
            $payload
        ));

        foreach (self::BOOLEAN_FIELDS as $key) {
            $data[$key] = $this->booleanValue($payload[$key] ?? $data[$key] ?? false) ? 'true' : 'false';
        }

        $data['APP_URL'] = rtrim((string) ($data['APP_URL'] ?? ''), '/');
        $data['APP_LOCALE'] = strtolower((string) ($data['APP_LOCALE'] ?? 'en'));
        $data['APP_FALLBACK_LOCALE'] = strtolower((string) ($data['APP_FALLBACK_LOCALE'] ?? 'en'));
        $data['PAYMENT_CURRENCY'] = strtoupper((string) ($data['PAYMENT_CURRENCY'] ?? 'SEK'));
        $data['COMMERCE_CURRENCY'] = strtoupper((string) ($data['COMMERCE_CURRENCY'] ?? $data['PAYMENT_CURRENCY'] ?? 'SEK'));
        $data['COMMERCE_FULFILLMENT_DEFAULT_TYPE'] = strtolower((string) ($data['COMMERCE_FULFILLMENT_DEFAULT_TYPE'] ?? 'physical_shipping'));
        $data['COMMERCE_SHIPPING_ACTIVE_CARRIER'] = strtolower((string) ($data['COMMERCE_SHIPPING_ACTIVE_CARRIER'] ?? 'postnord'));
        $data['COMMERCE_SUBSCRIPTION_DEFAULT_INTERVAL'] = strtolower((string) ($data['COMMERCE_SUBSCRIPTION_DEFAULT_INTERVAL'] ?? 'monthly'));
        $data['MAIL_FROM_NAME'] = trim((string) ($data['MAIL_FROM_NAME'] ?? ''));

        if (($data['DB_CONNECTION'] ?? '') === 'sqlite' && ($data['DB_DATABASE'] ?? '') === '') {
            $data['DB_DATABASE'] = 'Storage/Database/langelermvc.sqlite';
        }

        return $this->synchronizeDefaults($data);
    }

    /**
     * @param array<string, string> $data
     */
    private function validate(array $data): void
    {
        $required = [
            'APP_NAME' => 'Application name',
            'APP_URL' => 'Application URL',
            'APP_TIMEZONE' => 'Timezone',
            'DB_CONNECTION' => 'Database driver',
            'ADMIN_NAME' => 'Administrator name',
            'ADMIN_EMAIL' => 'Administrator email',
            'ADMIN_PASSWORD' => 'Administrator password',
        ];

        foreach ($required as $key => $label) {
            if (($data[$key] ?? '') === '') {
                throw new \InvalidArgumentException(sprintf('%s is required.', $label));
            }
        }

        if (filter_var($data['APP_URL'], FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Application URL must be a valid absolute URL.');
        }

        if (!in_array($data['APP_TIMEZONE'], timezone_identifiers_list(), true)) {
            throw new \InvalidArgumentException('Timezone must be a valid PHP timezone identifier.');
        }

        if (filter_var($data['ADMIN_EMAIL'], FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('Administrator email must be a valid email address.');
        }

        if (strlen($data['ADMIN_PASSWORD']) < 8) {
            throw new \InvalidArgumentException('Administrator password must be at least 8 characters long.');
        }

        if (!in_array($data['DB_CONNECTION'], self::SUPPORTED_DATABASE_DRIVERS, true)) {
            throw new \InvalidArgumentException('Unsupported database driver selected.');
        }

        if (!in_array($data['SESSION_DRIVER'], self::SUPPORTED_SESSION_DRIVERS, true)) {
            throw new \InvalidArgumentException('Unsupported session driver selected.');
        }

        if (!in_array($data['CACHE_DRIVER'], self::SUPPORTED_CACHE_DRIVERS, true)) {
            throw new \InvalidArgumentException('Unsupported cache driver selected.');
        }

        if (!in_array($data['MAIL_MAILER'], self::SUPPORTED_MAIL_DRIVERS, true)) {
            throw new \InvalidArgumentException('Unsupported mail driver selected.');
        }

        if (!in_array($data['QUEUE_DRIVER'], self::SUPPORTED_QUEUE_DRIVERS, true)) {
            throw new \InvalidArgumentException('Unsupported queue driver selected.');
        }

        if (!in_array($data['WEBMODULE_CONTENT_SOURCE'], self::SUPPORTED_CONTENT_SOURCES, true)) {
            throw new \InvalidArgumentException('Unsupported WebModule content source selected.');
        }

        if (!in_array($data['PAYMENT_DRIVER'], self::SUPPORTED_PAYMENT_DRIVERS, true)) {
            throw new \InvalidArgumentException('Unsupported payment driver selected.');
        }

        if (!in_array($data['PAYMENT_DEFAULT_METHOD'], self::SUPPORTED_PAYMENT_METHODS, true)) {
            throw new \InvalidArgumentException('Unsupported payment method selected.');
        }

        if (!in_array($data['PAYMENT_DEFAULT_FLOW'], self::SUPPORTED_PAYMENT_FLOWS, true)) {
            throw new \InvalidArgumentException('Unsupported payment flow selected.');
        }

        if (!in_array($data['COMMERCE_FULFILLMENT_DEFAULT_TYPE'], self::SUPPORTED_FULFILLMENT_TYPES, true)) {
            throw new \InvalidArgumentException('Unsupported default fulfillment type selected.');
        }

        if (!in_array($data['COMMERCE_SHIPPING_ACTIVE_CARRIER'], self::SUPPORTED_CARRIER_ADAPTERS, true)) {
            throw new \InvalidArgumentException('Unsupported active carrier adapter selected.');
        }

        if (!in_array($data['COMMERCE_SUBSCRIPTION_DEFAULT_INTERVAL'], self::SUPPORTED_SUBSCRIPTION_INTERVALS, true)) {
            throw new \InvalidArgumentException('Unsupported default subscription interval selected.');
        }

        if (!in_array($data['ENCRYPTION_TYPE'], ['openssl', 'sodium'], true)) {
            throw new \InvalidArgumentException('Unsupported encryption driver selected.');
        }

        foreach ([
            'MAIL_FROM_ADDRESS' => 'mail from address',
            'MAIL_REPLY_TO' => 'mail reply-to address',
        ] as $key => $label) {
            if (($data[$key] ?? '') !== '' && filter_var($data[$key], FILTER_VALIDATE_EMAIL) === false) {
                throw new \InvalidArgumentException(sprintf('The %s must be a valid email address.', $label));
            }
        }

        foreach ([
            'DB_PORT' => 'database port',
            'DB_TIMEOUT' => 'database timeout',
            'DB_POOL_SIZE' => 'database pool size',
            'MAIL_PORT' => 'mail port',
            'SESSION_LIFETIME' => 'session lifetime',
            'CACHE_TTL' => 'cache TTL',
            'QUEUE_RETRY_AFTER' => 'queue retry-after',
            'QUEUE_MAX_ATTEMPTS' => 'queue max attempts',
            'QUEUE_BACKOFF_SECONDS' => 'queue backoff seconds',
            'QUEUE_BACKOFF_MAX_SECONDS' => 'queue max backoff seconds',
            'QUEUE_WORKER_SLEEP' => 'queue worker sleep',
            'QUEUE_WORKER_MAX_RUNTIME' => 'queue worker max runtime',
            'QUEUE_WORKER_MAX_MEMORY_MB' => 'queue worker max memory',
            'QUEUE_FAILED_PRUNE_AFTER_HOURS' => 'failed queue prune window',
            'HTTP_THROTTLE_MAX_ATTEMPTS' => 'throttle max attempts',
            'HTTP_THROTTLE_DECAY_SECONDS' => 'throttle decay seconds',
            'AUTH_REMEMBER_ME_DAYS' => 'remember me days',
            'AUTH_OTP_TRUSTED_DEVICE_DAYS' => 'trusted device days',
            'ENCRYPTION_PBKDF2_ITERATIONS' => 'PBKDF2 iterations',
            'OPERATIONS_AUDIT_SUMMARY_LIMIT' => 'audit summary limit',
            'OPERATIONS_AUDIT_RETENTION_HOURS' => 'audit retention hours',
            'PAYMENT_WEBHOOKS_TOLERANCE_SECONDS' => 'payment webhook timestamp tolerance',
            'COMMERCE_TAX_RATE_BPS' => 'commerce tax rate basis points',
            'COMMERCE_DOCUMENTS_VAT_RATE_BPS' => 'commerce document VAT rate basis points',
            'COMMERCE_RETURNS_WINDOW_DAYS' => 'commerce returns window days',
            'COMMERCE_SHIPPING_FLAT_RATE_MINOR' => 'commerce shipping flat rate',
            'COMMERCE_SHIPPING_FREE_OVER_MINOR' => 'commerce free shipping threshold',
            'COMMERCE_SHIPPING_TIMEOUT' => 'commerce shipping adapter timeout',
            'COMMERCE_DISCOUNT_RATE_BPS' => 'commerce default discount rate basis points',
            'COMMERCE_DISCOUNT_MAX_MINOR' => 'commerce default discount cap',
            'COMMERCE_ACCESS_DEFAULT_DOWNLOAD_LIMIT' => 'commerce default download limit',
            'COMMERCE_ACCESS_DEFAULT_ACCESS_DAYS' => 'commerce default access days',
            'COMMERCE_SUBSCRIPTION_TRIAL_DAYS' => 'commerce subscription trial days',
            'COMMERCE_SUBSCRIPTION_MAX_RETRIES' => 'commerce subscription max retries',
            'COMMERCE_INVENTORY_RESERVATION_TTL_MINUTES' => 'commerce inventory reservation TTL',
        ] as $key => $label) {
            if (($data[$key] ?? '') !== '' && (!ctype_digit((string) $data[$key]) || (int) $data[$key] < 0)) {
                throw new \InvalidArgumentException(sprintf('The %s must be a non-negative integer.', $label));
            }
        }

        if (($data['PAYMENT_CURRENCY'] ?? '') === '' || preg_match('/^[A-Z]{3}$/', $data['PAYMENT_CURRENCY']) !== 1) {
            throw new \InvalidArgumentException('Payment currency must be a 3-letter ISO currency code.');
        }

        if (($data['COMMERCE_CURRENCY'] ?? '') === '' || preg_match('/^[A-Z]{3}$/', $data['COMMERCE_CURRENCY']) !== 1) {
            throw new \InvalidArgumentException('Commerce currency must be a 3-letter ISO currency code.');
        }

        if (($data['AUTH_PASSKEY_ORIGINS'] ?? '') !== '' && filter_var($data['AUTH_PASSKEY_ORIGINS'], FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Passkey origin must be a valid absolute URL.');
        }

        if (in_array($data['CACHE_DRIVER'], ['redis'], true) && !extension_loaded('redis')) {
            throw new \InvalidArgumentException('The Redis cache driver requires the redis PHP extension.');
        }

        if (in_array($data['CACHE_DRIVER'], ['memcache'], true) && !extension_loaded('memcached')) {
            throw new \InvalidArgumentException('The Memcache cache driver requires the memcached PHP extension.');
        }

        if ($data['SESSION_DRIVER'] === 'redis' && !extension_loaded('redis')) {
            throw new \InvalidArgumentException('The Redis session driver requires the redis PHP extension.');
        }

        if ($data['ENCRYPTION_TYPE'] === 'openssl' && !extension_loaded('openssl')) {
            throw new \InvalidArgumentException('The OpenSSL encryption driver requires the openssl PHP extension.');
        }

        if ($data['ENCRYPTION_TYPE'] === 'sodium' && !extension_loaded('sodium')) {
            throw new \InvalidArgumentException('The Sodium encryption driver requires the sodium PHP extension.');
        }
    }

    /**
     * @param array<string, string> $data
     */
    private function prepareFilesystem(array $data): void
    {
        $paths = [
            $this->storagePath(),
            $this->storagePath('Cache'),
            $this->storagePath('Sessions'),
            $this->storagePath('Database'),
            $this->storagePath('Logs'),
        ];

        if (($data['SESSION_SAVE_PATH'] ?? '') !== '') {
            $paths[] = $this->resolveProjectPath($data['SESSION_SAVE_PATH'], $this->storagePath('Sessions'));
        }

        if (($data['CACHE_FILE_PATH'] ?? '') !== '') {
            $paths[] = $this->resolveProjectPath($data['CACHE_FILE_PATH'], $this->storagePath('Cache'));
        }

        foreach (array_unique($paths) as $path) {
            if (!$this->files->isDirectory($path) && !$this->files->createDirectory($path, 0777, true)) {
                throw new \RuntimeException(sprintf('Unable to create required storage directory [%s].', $path));
            }
        }

        if ($data['DB_CONNECTION'] === 'sqlite') {
            $databasePath = $this->resolveSqlitePath($data['DB_DATABASE']);
            $directory = dirname($databasePath);

            if (!$this->files->isDirectory($directory) && !$this->files->createDirectory($directory, 0777, true)) {
                throw new \RuntimeException(sprintf('Unable to create the SQLite database directory [%s].', $directory));
            }

            if (!$this->files->fileExists($databasePath) && $this->files->writeContents($databasePath, '') === false) {
                throw new \RuntimeException(sprintf('Unable to create the SQLite database file [%s].', $databasePath));
            }
        }
    }

    /**
     * @param array<string, string> $data
     */
    private function verifyDatabaseConnection(array $data): void
    {
        $dsn = match ($data['DB_CONNECTION']) {
            'sqlite' => 'sqlite:' . $this->resolveSqlitePath($data['DB_DATABASE']),
            'pgsql' => sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $data['DB_HOST'],
                $data['DB_PORT'] !== '' ? $data['DB_PORT'] : '5432',
                $data['DB_DATABASE']
            ),
            'sqlsrv' => sprintf(
                'sqlsrv:Server=%s,%s;Database=%s',
                $data['DB_HOST'],
                $data['DB_PORT'] !== '' ? $data['DB_PORT'] : '1433',
                $data['DB_DATABASE']
            ),
            default => sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                $data['DB_CONNECTION'],
                $data['DB_HOST'],
                $data['DB_PORT'] !== '' ? $data['DB_PORT'] : '3306',
                $data['DB_DATABASE'],
                'utf8mb4'
            ),
        };

        try {
            new \PDO(
                $dsn,
                $data['DB_USERNAME'] !== '' ? $data['DB_USERNAME'] : null,
                $data['DB_PASSWORD'] !== '' ? $data['DB_PASSWORD'] : null
            );
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Unable to connect using the provided database settings: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @param array<string, string> $data
     * @return array<string, string>
     */
    private function buildEnvironment(array $data): array
    {
        $environment = array_replace($this->templateEnvironment(), $this->baseDefaults(), $data);

        foreach (array_keys($environment) as $key) {
            if (str_starts_with($key, 'ADMIN_')) {
                unset($environment[$key]);
            }
        }

        $environment = $this->synchronizeDefaults($environment);
        $environment['APP_INSTALLED'] = 'true';
        $environment['APP_MAINTENANCE'] = 'false';
        $environment['DB_DATABASE'] = $data['DB_CONNECTION'] === 'sqlite'
            ? $this->resolveSqlitePath($data['DB_DATABASE'])
            : $data['DB_DATABASE'];
        $environment['FEATURE_VERIFY_EMAIL'] = $environment['AUTH_VERIFY_EMAIL'];

        return $environment;
    }

    /**
     * @param array<string, string> $environment
     */
    private function writeEnvironment(array $environment): void
    {
        $contents = [];
        $groups = [
            'APPLICATION' => [
                'APP_NAME',
                'APP_ENV',
                'APP_DEBUG',
                'APP_INSTALLED',
                'APP_URL',
                'APP_TIMEZONE',
                'APP_LOCALE',
                'APP_FALLBACK_LOCALE',
                'APP_VERSION',
                'APP_MAINTENANCE',
                'APP_LOG_LEVEL',
                'APP_LOG_CHANNEL',
            ],
            'DATABASE' => [
                'DB_CONNECTION',
                'DB_HOST',
                'DB_PORT',
                'DB_DATABASE',
                'DB_USERNAME',
                'DB_PASSWORD',
                'DB_CHARSET',
                'DB_COLLATION',
                'DB_POOLING',
                'DB_POOL_SIZE',
                'DB_FAILOVER',
                'DB_TIMEOUT',
                'DB_RETRY_DELAY',
                'DB_SSL_MODE',
                'DB_REPLICATION',
            ],
            'QUEUE' => [
                'QUEUE_DRIVER',
                'QUEUE_DEFAULT_QUEUE',
                'QUEUE_RETRY_AFTER',
                'QUEUE_MAX_ATTEMPTS',
                'QUEUE_BACKOFF_STRATEGY',
                'QUEUE_BACKOFF_SECONDS',
                'QUEUE_BACKOFF_MAX_SECONDS',
                'QUEUE_WORKER_SLEEP',
                'QUEUE_WORKER_MAX_RUNTIME',
                'QUEUE_WORKER_MAX_MEMORY_MB',
                'QUEUE_WORKER_CONTROL_PATH',
                'QUEUE_FAILED_PRUNE_AFTER_HOURS',
            ],
            'MAIL' => [
                'MAIL_MAILER',
                'MAIL_HOST',
                'MAIL_PORT',
                'MAIL_USERNAME',
                'MAIL_PASSWORD',
                'MAIL_ENCRYPTION',
                'MAIL_FROM_ADDRESS',
                'MAIL_FROM_NAME',
                'MAIL_REPLY_TO',
                'MAIL_CC',
                'MAIL_BCC',
                'MAIL_QUEUE',
                'MAIL_LOG_ENABLED',
            ],
            'NOTIFICATIONS' => [
                'NOTIFICATIONS_QUEUE',
                'NOTIFICATIONS_QUEUE_NAME',
                'NOTIFICATIONS_DEFAULT_CHANNELS',
            ],
            'SESSION' => [
                'SESSION_DRIVER',
                'SESSION_NAME',
                'SESSION_LIFETIME',
                'SESSION_EXPIRE_ON_CLOSE',
                'SESSION_SECURE_COOKIE',
                'SESSION_HTTPONLY_COOKIE',
                'SESSION_SAME_SITE',
                'SESSION_SAVE_PATH',
            ],
            'CACHE' => [
                'CACHE_ENABLED',
                'CACHE_DRIVER',
                'CACHE_PREFIX',
                'CACHE_TTL',
                'CACHE_COMPRESSION',
                'CACHE_SERIALIZATION',
                'CACHE_ENCRYPT',
                'CACHE_MAX_ITEMS',
                'CACHE_REDIS_HOST',
                'CACHE_REDIS_PORT',
                'CACHE_REDIS_PASSWORD',
                'CACHE_REDIS_DATABASE',
                'CACHE_MEMCACHED_HOST',
                'CACHE_MEMCACHED_PORT',
                'CACHE_FILE_PATH',
            ],
            'ENCRYPTION' => [
                'ENCRYPTION_ENABLED',
                'ENCRYPTION_TYPE',
                'ENCRYPTION_KEY',
                'ENCRYPTION_CIPHER',
                'ENCRYPTION_HASH_ALGO',
                'ENCRYPTION_PBKDF2_ITERATIONS',
                'ENCRYPTION_OPENSSL_KEY',
                'ENCRYPTION_OPENSSL_CIPHER',
                'ENCRYPTION_SODIUM_KEY',
            ],
            'HTTP' => [
                'HTTP_SIGNED_URL_KEY',
                'HTTP_THROTTLE_MAX_ATTEMPTS',
                'HTTP_THROTTLE_DECAY_SECONDS',
            ],
            'AUTH' => [
                'AUTH_VERIFY_EMAIL',
                'AUTH_EMAIL_VERIFY_EXPIRES',
                'AUTH_PASSWORD_RESET_EXPIRES',
                'AUTH_REMEMBER_ME_DAYS',
                'AUTH_OTP_TRUSTED_DEVICE_DAYS',
                'AUTH_PASSKEY_RP_ID',
                'AUTH_PASSKEY_RP_NAME',
                'AUTH_PASSKEY_ORIGINS',
            ],
            'FEATURES' => [
                'FEATURE_VERIFY_EMAIL',
                'FEATURE_2FA',
            ],
            'PAYMENTS' => [
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
            ],
            'COMMERCE' => [
                'COMMERCE_CURRENCY',
                'COMMERCE_TAX_RATE_BPS',
                'COMMERCE_DOCUMENTS_VAT_RATE_BPS',
                'COMMERCE_DOCUMENTS_SELLER_NAME',
                'COMMERCE_DOCUMENTS_SELLER_VAT_ID',
                'COMMERCE_DOCUMENTS_SELLER_ADDRESS',
                'COMMERCE_RETURNS_WINDOW_DAYS',
                'COMMERCE_RETURNS_ALLOW_EXCHANGES',
                'COMMERCE_RETURNS_AUTO_RESTOCK',
                'COMMERCE_DISCOUNT_RATE_BPS',
                'COMMERCE_DISCOUNT_MAX_MINOR',
                'COMMERCE_SHIPPING_DEFAULT_COUNTRY',
                'COMMERCE_SHIPPING_DEFAULT_OPTION',
                'COMMERCE_SHIPPING_FLAT_RATE_MINOR',
                'COMMERCE_SHIPPING_FREE_OVER_MINOR',
                'COMMERCE_SHIPPING_INTEGRATION_MODE',
                'COMMERCE_SHIPPING_ACTIVE_CARRIER',
                'COMMERCE_SHIPPING_AUTO_BOOK_LABELS',
                'COMMERCE_SHIPPING_LABEL_FORMAT',
                'COMMERCE_SHIPPING_LABEL_BASE_URL',
                'COMMERCE_SHIPPING_API_BASE',
                'COMMERCE_SHIPPING_API_KEY',
                'COMMERCE_SHIPPING_USERNAME',
                'COMMERCE_SHIPPING_PASSWORD',
                'COMMERCE_SHIPPING_AUTH_SCHEME',
                'COMMERCE_SHIPPING_SERVICE_POINTS_URL',
                'COMMERCE_SHIPPING_BOOKING_URL',
                'COMMERCE_SHIPPING_TRACKING_URL',
                'COMMERCE_SHIPPING_CANCELLATION_URL',
                'COMMERCE_SHIPPING_TIMEOUT',
                'COMMERCE_FULFILLMENT_DEFAULT_TYPE',
                'COMMERCE_FULFILLMENT_AUTO_READY_ON_CAPTURE',
                'COMMERCE_ACCESS_DEFAULT_DOWNLOAD_LIMIT',
                'COMMERCE_ACCESS_DEFAULT_ACCESS_DAYS',
                'COMMERCE_ACCESS_KEY_PREFIX',
                'COMMERCE_SUBSCRIPTION_DEFAULT_INTERVAL',
                'COMMERCE_SUBSCRIPTION_TRIAL_DAYS',
                'COMMERCE_SUBSCRIPTION_MAX_RETRIES',
                'COMMERCE_SUBSCRIPTION_DUNNING_RETRY_DAYS',
                'COMMERCE_INVENTORY_RESERVE_ON_CHECKOUT',
                'COMMERCE_INVENTORY_RELEASE_ON_CANCEL',
                'COMMERCE_INVENTORY_RESERVATION_TTL_MINUTES',
            ],
            'WEBMODULE' => [
                'WEBMODULE_CONTENT_SOURCE',
            ],
            'OPERATIONS' => [
                'OPERATIONS_HEALTH_ENABLED',
                'OPERATIONS_AUDIT_ENABLED',
                'OPERATIONS_AUDIT_SUMMARY_LIMIT',
                'OPERATIONS_AUDIT_RETENTION_HOURS',
            ],
        ];

        foreach ($groups as $title => $keys) {
            $contents[] = '# ' . $title;

            foreach ($keys as $key) {
                if (!array_key_exists($key, $environment)) {
                    continue;
                }

                $contents[] = $key . '=' . $this->quoteEnvironmentValue((string) $environment[$key]);
            }

            $contents[] = '';
        }

        if ($this->files->writeContents($this->envPath(), implode(PHP_EOL, $contents)) === false) {
            throw new \RuntimeException('Unable to write the environment file.');
        }
    }

    /**
     * @param array<string, string> $environment
     */
    private function reloadEnvironment(array $environment): void
    {
        foreach ($environment as $key => $value) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    private function ensurePathConstants(): void
    {
        $constants = [
            'BASE_PATH' => $this->basePath,
            'APP_PATH' => $this->basePath . DIRECTORY_SEPARATOR . 'App',
            'CONFIG_PATH' => $this->basePath . DIRECTORY_SEPARATOR . 'Config',
            'PUBLIC_PATH' => $this->basePath . DIRECTORY_SEPARATOR . 'Public',
            'STORAGE_PATH' => $this->basePath . DIRECTORY_SEPARATOR . 'Storage',
        ];

        foreach ($constants as $constant => $value) {
            if (!defined($constant)) {
                define($constant, $value);
            }
        }
    }

    /**
     * @param array<string, string> $data
     * @return array<string, string|int>
     */
    private function provisionAdministrator(CoreProvider $provider, array $data): array
    {
        $userRepository = $provider->resolveClass(UserRepository::class);
        $roleRepository = $provider->resolveClass(RoleRepository::class);
        $userProvider = $provider->resolveClass(DatabaseUserProvider::class);

        if (
            !$userRepository instanceof UserRepository
            || !$roleRepository instanceof RoleRepository
            || !$userProvider instanceof DatabaseUserProvider
        ) {
            throw new \RuntimeException('Unable to resolve user platform services during installation.');
        }

        $administratorRole = $roleRepository->findByName('administrator');

        if ($administratorRole === null) {
            throw new \RuntimeException('The administrator role was not created during the seed phase.');
        }

        $user = $userRepository->findByEmail($data['ADMIN_EMAIL']);
        $demoAdmin = $userRepository->findByEmail('admin@langelermvc.test');

        if ($user === null && $demoAdmin !== null) {
            $user = $userRepository->updateProfile((int) $demoAdmin->getKey(), [
                'name' => $data['ADMIN_NAME'],
                'email' => $data['ADMIN_EMAIL'],
                'status' => 'active',
                'email_verified_at' => gmdate('Y-m-d H:i:s'),
            ]);
        }

        if ($user === null) {
            $user = $userRepository->create([
                'name' => $data['ADMIN_NAME'],
                'email' => $data['ADMIN_EMAIL'],
                'password' => $userProvider->hashValue($data['ADMIN_PASSWORD']),
                'email_verified_at' => gmdate('Y-m-d H:i:s'),
                'status' => 'active',
            ]);
        } else {
            $userRepository->updateProfile((int) $user->getKey(), [
                'name' => $data['ADMIN_NAME'],
                'email' => $data['ADMIN_EMAIL'],
                'status' => 'active',
                'email_verified_at' => gmdate('Y-m-d H:i:s'),
            ]);
            $userRepository->updatePassword((int) $user->getKey(), $userProvider->hashValue($data['ADMIN_PASSWORD']));
            $user = $userRepository->find((int) $user->getKey());
        }

        $userRepository->syncRoles((int) $user->getKey(), [(int) $administratorRole->getKey()]);

        return [
            'id' => (int) $user->getKey(),
            'name' => $data['ADMIN_NAME'],
            'email' => $data['ADMIN_EMAIL'],
            'password' => $data['ADMIN_PASSWORD'],
        ];
    }

    private function makeCoreProvider(): CoreProvider
    {
        $provider = $this->providerFactory !== null
            ? ($this->providerFactory)()
            : new CoreProvider();

        if (!$provider instanceof CoreProvider) {
            throw new \RuntimeException('Installer provider factory must resolve to CoreProvider.');
        }

        return $provider;
    }

    /**
     * @param array<string, array<int, string>> $migrated
     * @return array<string, array<int, string>>
     */
    private function rollbackInstallMigrations(MigrationRunner $migrationRunner, array $migrated): array
    {
        $rolledBack = [];

        foreach (array_reverse(array_keys($migrated)) as $module) {
            $executed = array_values(array_filter(
                array_map('strval', (array) ($migrated[$module] ?? [])),
                static fn(string $migration): bool => $migration !== ''
            ));

            if ($executed === []) {
                continue;
            }

            $rolledBack[$module] = $migrationRunner->rollbackNamed($executed);
        }

        return array_filter(
            $rolledBack,
            static fn(array $executed): bool => $executed !== []
        );
    }

    /**
     * @param array<string, string> $writtenEnvironment
     */
    private function restoreEnvironmentSnapshot(?string $previousContents, bool $hadEnvironmentFile, array $writtenEnvironment): void
    {
        $writtenKeys = array_keys($writtenEnvironment);

        if ($hadEnvironmentFile) {
            if ($this->files->writeContents($this->envPath(), $previousContents ?? '') === false) {
                return;
            }

            $restored = $this->readEnvironment($this->envPath());
            $this->unsetEnvironmentKeys(array_diff($writtenKeys, array_keys($restored)));
            $this->reloadEnvironment($restored);
            return;
        }

        if ($this->files->fileExists($this->envPath())) {
            $this->files->deleteFile($this->envPath());
        }

        $this->unsetEnvironmentKeys($writtenKeys);
    }

    /**
     * @param array<int, string> $keys
     */
    private function unsetEnvironmentKeys(array $keys): void
    {
        foreach ($keys as $key) {
            if ($key === '') {
                continue;
            }

            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function updateInstallState(string $status, string $stage, array $context = []): void
    {
        $state = $this->readInstallState();
        $state['status'] = $status;
        $state['stage'] = $stage;
        $state['updated_at'] = gmdate('c');
        $state['started_at'] = (string) ($state['started_at'] ?? gmdate('c'));

        foreach ($context as $key => $value) {
            $state[$key] = $value;
        }

        $this->writeInstallState($state);
    }

    /**
     * @return array<string, mixed>
     */
    private function readInstallState(): array
    {
        $contents = $this->files->readContents($this->installStatePath());

        if (!is_string($contents) || trim($contents) === '') {
            return [];
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : [];
        } catch (\JsonException) {
            return [];
        }
    }

    /**
     * @param array<string, mixed> $state
     */
    private function writeInstallState(array $state): void
    {
        $directory = dirname($this->installStatePath());

        if (!$this->files->isDirectory($directory)) {
            $this->files->createDirectory($directory, 0777, true);
        }

        $payload = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if ($this->files->writeContents($this->installStatePath(), $payload) === false) {
            throw new \RuntimeException('Unable to write the installer state journal.');
        }
    }

    private function clearInstallState(): void
    {
        if ($this->files->fileExists($this->installStatePath())) {
            $this->files->deleteFile($this->installStatePath());
        }
    }

    /**
     * @return array<int, string>
     */
    private function moduleInstallOrder(): array
    {
        return ['WebModule', 'UserModule', 'ShopModule', 'CartModule', 'OrderModule'];
    }

    /**
     * @return array<string, string>
     */
    private function templateEnvironment(): array
    {
        $template = $this->readEnvironment($this->basePath . DIRECTORY_SEPARATOR . '.env.example');

        return $template !== [] ? $template : [];
    }

    /**
     * @return array<string, string>
     */
    private function baseDefaults(): array
    {
        return [
            'APP_NAME' => 'LangelerMVC',
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_INSTALLED' => 'false',
            'APP_URL' => 'http://localhost',
            'APP_TIMEZONE' => 'Europe/Stockholm',
            'APP_LOCALE' => 'en',
            'APP_FALLBACK_LOCALE' => 'en',
            'APP_VERSION' => '1.0.0',
            'APP_MAINTENANCE' => 'false',
            'APP_LOG_LEVEL' => 'info',
            'APP_LOG_CHANNEL' => 'daily',
            'DB_CONNECTION' => 'sqlite',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'Storage/Database/langelermvc.sqlite',
            'DB_USERNAME' => '',
            'DB_PASSWORD' => '',
            'DB_CHARSET' => 'utf8mb4',
            'DB_COLLATION' => 'utf8mb4_unicode_ci',
            'DB_POOLING' => 'false',
            'DB_POOL_SIZE' => '10',
            'DB_FAILOVER' => '',
            'DB_TIMEOUT' => '30',
            'DB_RETRY_DELAY' => '2000',
            'DB_SSL_MODE' => 'prefer',
            'DB_REPLICATION' => 'false',
            'QUEUE_DRIVER' => 'sync',
            'QUEUE_DEFAULT_QUEUE' => 'default',
            'QUEUE_RETRY_AFTER' => '60',
            'QUEUE_MAX_ATTEMPTS' => '3',
            'QUEUE_BACKOFF_STRATEGY' => 'exponential',
            'QUEUE_BACKOFF_SECONDS' => '5',
            'QUEUE_BACKOFF_MAX_SECONDS' => '300',
            'QUEUE_WORKER_SLEEP' => '1',
            'QUEUE_WORKER_MAX_RUNTIME' => '0',
            'QUEUE_WORKER_MAX_MEMORY_MB' => '256',
            'QUEUE_WORKER_CONTROL_PATH' => 'Storage/Framework/Queue',
            'QUEUE_FAILED_PRUNE_AFTER_HOURS' => '168',
            'MAIL_MAILER' => 'array',
            'MAIL_HOST' => 'smtp.mailtrap.io',
            'MAIL_PORT' => '2525',
            'MAIL_USERNAME' => '',
            'MAIL_PASSWORD' => '',
            'MAIL_ENCRYPTION' => 'tls',
            'MAIL_FROM_ADDRESS' => '',
            'MAIL_FROM_NAME' => '',
            'MAIL_REPLY_TO' => '',
            'MAIL_CC' => '',
            'MAIL_BCC' => '',
            'MAIL_QUEUE' => 'false',
            'MAIL_LOG_ENABLED' => 'true',
            'NOTIFICATIONS_QUEUE' => 'false',
            'NOTIFICATIONS_QUEUE_NAME' => 'notifications',
            'NOTIFICATIONS_DEFAULT_CHANNELS' => 'database,mail',
            'SESSION_DRIVER' => 'database',
            'SESSION_NAME' => 'langelermvc_session',
            'SESSION_LIFETIME' => '120',
            'SESSION_EXPIRE_ON_CLOSE' => 'false',
            'SESSION_SECURE_COOKIE' => 'false',
            'SESSION_HTTPONLY_COOKIE' => 'true',
            'SESSION_SAME_SITE' => 'lax',
            'SESSION_SAVE_PATH' => 'Storage/Sessions',
            'CACHE_ENABLED' => 'true',
            'CACHE_DRIVER' => 'file',
            'CACHE_PREFIX' => 'langelermvc_cache',
            'CACHE_TTL' => '3600',
            'CACHE_COMPRESSION' => 'true',
            'CACHE_SERIALIZATION' => 'php',
            'CACHE_ENCRYPT' => 'false',
            'CACHE_MAX_ITEMS' => '0',
            'CACHE_REDIS_HOST' => '127.0.0.1',
            'CACHE_REDIS_PORT' => '6379',
            'CACHE_REDIS_PASSWORD' => '',
            'CACHE_REDIS_DATABASE' => '0',
            'CACHE_MEMCACHED_HOST' => '127.0.0.1',
            'CACHE_MEMCACHED_PORT' => '11211',
            'CACHE_FILE_PATH' => 'Storage/Cache',
            'ENCRYPTION_ENABLED' => 'true',
            'ENCRYPTION_TYPE' => 'openssl',
            'ENCRYPTION_KEY' => '',
            'ENCRYPTION_CIPHER' => 'AES-256-CBC',
            'ENCRYPTION_HASH_ALGO' => 'sha256',
            'ENCRYPTION_PBKDF2_ITERATIONS' => '100000',
            'ENCRYPTION_OPENSSL_KEY' => '',
            'ENCRYPTION_OPENSSL_CIPHER' => 'AES-256-CBC',
            'ENCRYPTION_SODIUM_KEY' => '',
            'HTTP_SIGNED_URL_KEY' => '',
            'HTTP_THROTTLE_MAX_ATTEMPTS' => '5',
            'HTTP_THROTTLE_DECAY_SECONDS' => '60',
            'AUTH_VERIFY_EMAIL' => 'true',
            'AUTH_EMAIL_VERIFY_EXPIRES' => '1440',
            'AUTH_PASSWORD_RESET_EXPIRES' => '60',
            'AUTH_REMEMBER_ME_DAYS' => '30',
            'AUTH_OTP_TRUSTED_DEVICE_DAYS' => '30',
            'AUTH_PASSKEY_RP_ID' => '',
            'AUTH_PASSKEY_RP_NAME' => '',
            'AUTH_PASSKEY_ORIGINS' => '',
            'FEATURE_VERIFY_EMAIL' => 'true',
            'FEATURE_2FA' => 'true',
            'PAYMENT_DRIVER' => 'testing',
            'PAYMENT_CURRENCY' => 'SEK',
            'PAYMENT_DEFAULT_METHOD' => 'card',
            'PAYMENT_DEFAULT_FLOW' => 'authorize_capture',
            'PAYMENT_WEBHOOKS_ENABLED' => 'true',
            'PAYMENT_WEBHOOKS_REQUIRE_SIGNATURE' => 'true',
            'PAYMENT_WEBHOOKS_SIGNATURE_HEADER' => 'X-Langeler-Signature',
            'PAYMENT_WEBHOOKS_EVENT_ID_HEADER' => 'X-Langeler-Event',
            'PAYMENT_WEBHOOKS_TIMESTAMP_HEADER' => 'X-Langeler-Timestamp',
            'PAYMENT_WEBHOOKS_TOLERANCE_SECONDS' => '300',
            'PAYMENT_WEBHOOK_SECRET_TESTING' => '',
            'PAYMENT_WEBHOOK_SECRET_CARD' => '',
            'PAYMENT_WEBHOOK_SECRET_CRYPTO' => '',
            'PAYMENT_WEBHOOK_SECRET_PAYPAL' => '',
            'PAYMENT_WEBHOOK_SECRET_KLARNA' => '',
            'PAYMENT_WEBHOOK_SECRET_SWISH' => '',
            'PAYMENT_WEBHOOK_SECRET_QLIRO' => '',
            'PAYMENT_WEBHOOK_SECRET_WALLEY' => '',
            'PAYMENT_CARD_MODE' => 'reference',
            'PAYMENT_CARD_API_BASE' => '',
            'PAYMENT_CARD_API_KEY' => '',
            'PAYMENT_CARD_AUTH_SCHEME' => 'Bearer',
            'PAYMENT_CARD_CREATE_URL' => '',
            'PAYMENT_CARD_CAPTURE_URL' => '',
            'PAYMENT_CARD_REFUND_URL' => '',
            'PAYMENT_CARD_CANCEL_URL' => '',
            'PAYMENT_CARD_RECONCILE_URL' => '',
            'PAYMENT_PAYPAL_MODE' => 'reference',
            'PAYMENT_PAYPAL_API_BASE' => 'https://api-m.sandbox.paypal.com',
            'PAYMENT_PAYPAL_CLIENT_ID' => '',
            'PAYMENT_PAYPAL_CLIENT_SECRET' => '',
            'PAYMENT_PAYPAL_RETURN_URL' => 'https://langelermvc.test/orders/complete',
            'PAYMENT_PAYPAL_CANCEL_URL' => 'https://langelermvc.test/orders/cancelled',
            'PAYMENT_KLARNA_MODE' => 'reference',
            'PAYMENT_KLARNA_API_BASE' => 'https://api.playground.klarna.com',
            'PAYMENT_KLARNA_USERNAME' => '',
            'PAYMENT_KLARNA_PASSWORD' => '',
            'PAYMENT_KLARNA_PURCHASE_COUNTRY' => 'SE',
            'PAYMENT_KLARNA_PURCHASE_CURRENCY' => 'SEK',
            'PAYMENT_KLARNA_LOCALE' => 'sv-SE',
            'PAYMENT_SWISH_MODE' => 'reference',
            'PAYMENT_SWISH_API_BASE' => 'https://mss.cpc.getswish.net/swish-cpcapi/api/v2',
            'PAYMENT_SWISH_PAYEE_ALIAS' => '',
            'PAYMENT_SWISH_CERTIFICATE_PATH' => '',
            'PAYMENT_SWISH_PRIVATE_KEY_PATH' => '',
            'PAYMENT_SWISH_PASSPHRASE' => '',
            'PAYMENT_SWISH_CALLBACK_URL' => 'https://langelermvc.test/payments/swish/callback',
            'PAYMENT_QLIRO_MODE' => 'reference',
            'PAYMENT_QLIRO_API_BASE' => 'https://api.qit.nu',
            'PAYMENT_QLIRO_API_KEY' => '',
            'PAYMENT_QLIRO_MERCHANT_API_KEY' => '',
            'PAYMENT_QLIRO_MERCHANT_API_SECRET' => '',
            'PAYMENT_QLIRO_MERCHANT_CONFIRMATION_URL' => 'https://langelermvc.test/orders/complete',
            'PAYMENT_QLIRO_MERCHANT_TERMS_URL' => 'https://langelermvc.test/terms',
            'PAYMENT_QLIRO_MERCHANT_CHECKOUT_STATUS_PUSH_URL' => '',
            'PAYMENT_QLIRO_MERCHANT_ORDER_MANAGEMENT_STATUS_PUSH_URL' => '',
            'PAYMENT_QLIRO_CAPTURE_URL' => '',
            'PAYMENT_QLIRO_REFUND_URL' => '',
            'PAYMENT_QLIRO_CANCEL_URL' => '',
            'PAYMENT_WALLEY_MODE' => 'reference',
            'PAYMENT_WALLEY_API_BASE' => '',
            'PAYMENT_WALLEY_API_KEY' => '',
            'PAYMENT_WALLEY_WSDL_URL' => '',
            'PAYMENT_WALLEY_USERNAME' => '',
            'PAYMENT_WALLEY_PASSWORD' => '',
            'PAYMENT_WALLEY_MERCHANT_ID' => '',
            'PAYMENT_WALLEY_RETURN_URL' => 'https://langelermvc.test/orders/complete',
            'PAYMENT_WALLEY_CALLBACK_URL' => '',
            'PAYMENT_WALLEY_CREATE_URL' => '',
            'PAYMENT_WALLEY_CAPTURE_URL' => '',
            'PAYMENT_WALLEY_REFUND_URL' => '',
            'PAYMENT_WALLEY_CANCEL_URL' => '',
            'PAYMENT_WALLEY_RECONCILE_URL' => '',
            'PAYMENT_CRYPTO_MODE' => 'reference',
            'PAYMENT_CRYPTO_DEFAULT_ASSET' => 'BTC',
            'PAYMENT_CRYPTO_DEFAULT_NETWORK' => 'bitcoin',
            'PAYMENT_CRYPTO_CONFIRMATIONS_REQUIRED' => '1',
            'COMMERCE_CURRENCY' => 'SEK',
            'COMMERCE_TAX_RATE_BPS' => '2500',
            'COMMERCE_DOCUMENTS_VAT_RATE_BPS' => '2500',
            'COMMERCE_DOCUMENTS_SELLER_NAME' => 'LangelerMVC',
            'COMMERCE_DOCUMENTS_SELLER_VAT_ID' => '',
            'COMMERCE_DOCUMENTS_SELLER_ADDRESS' => '',
            'COMMERCE_RETURNS_WINDOW_DAYS' => '30',
            'COMMERCE_RETURNS_ALLOW_EXCHANGES' => 'true',
            'COMMERCE_RETURNS_AUTO_RESTOCK' => 'true',
            'COMMERCE_DISCOUNT_RATE_BPS' => '0',
            'COMMERCE_DISCOUNT_MAX_MINOR' => '0',
            'COMMERCE_SHIPPING_DEFAULT_COUNTRY' => 'SE',
            'COMMERCE_SHIPPING_DEFAULT_OPTION' => 'postnord-service-point',
            'COMMERCE_SHIPPING_FLAT_RATE_MINOR' => '1490',
            'COMMERCE_SHIPPING_FREE_OVER_MINOR' => '50000',
            'COMMERCE_SHIPPING_INTEGRATION_MODE' => 'reference',
            'COMMERCE_SHIPPING_ACTIVE_CARRIER' => 'postnord',
            'COMMERCE_SHIPPING_AUTO_BOOK_LABELS' => 'true',
            'COMMERCE_SHIPPING_LABEL_FORMAT' => 'pdf',
            'COMMERCE_SHIPPING_LABEL_BASE_URL' => 'https://shipments.langelermvc.test/labels',
            'COMMERCE_SHIPPING_API_BASE' => '',
            'COMMERCE_SHIPPING_API_KEY' => '',
            'COMMERCE_SHIPPING_USERNAME' => '',
            'COMMERCE_SHIPPING_PASSWORD' => '',
            'COMMERCE_SHIPPING_AUTH_SCHEME' => 'Bearer',
            'COMMERCE_SHIPPING_SERVICE_POINTS_URL' => '',
            'COMMERCE_SHIPPING_BOOKING_URL' => '',
            'COMMERCE_SHIPPING_TRACKING_URL' => '',
            'COMMERCE_SHIPPING_CANCELLATION_URL' => '',
            'COMMERCE_SHIPPING_TIMEOUT' => '30',
            'COMMERCE_FULFILLMENT_DEFAULT_TYPE' => 'physical_shipping',
            'COMMERCE_FULFILLMENT_AUTO_READY_ON_CAPTURE' => 'true',
            'COMMERCE_ACCESS_DEFAULT_DOWNLOAD_LIMIT' => '0',
            'COMMERCE_ACCESS_DEFAULT_ACCESS_DAYS' => '0',
            'COMMERCE_ACCESS_KEY_PREFIX' => 'ent',
            'COMMERCE_SUBSCRIPTION_DEFAULT_INTERVAL' => 'monthly',
            'COMMERCE_SUBSCRIPTION_TRIAL_DAYS' => '0',
            'COMMERCE_SUBSCRIPTION_MAX_RETRIES' => '3',
            'COMMERCE_SUBSCRIPTION_DUNNING_RETRY_DAYS' => '1,3,7',
            'COMMERCE_INVENTORY_RESERVE_ON_CHECKOUT' => 'true',
            'COMMERCE_INVENTORY_RELEASE_ON_CANCEL' => 'true',
            'COMMERCE_INVENTORY_RESERVATION_TTL_MINUTES' => '60',
            'OPERATIONS_HEALTH_ENABLED' => 'true',
            'OPERATIONS_AUDIT_ENABLED' => 'true',
            'OPERATIONS_AUDIT_SUMMARY_LIMIT' => '250',
            'OPERATIONS_AUDIT_RETENTION_HOURS' => '720',
            'WEBMODULE_CONTENT_SOURCE' => 'database',
        ];
    }

    /**
     * @param array<string, string> $environment
     * @return array<string, string>
     */
    private function synchronizeDefaults(array $environment): array
    {
        $appName = trim((string) ($environment['APP_NAME'] ?? 'LangelerMVC'));
        $appUrl = trim((string) ($environment['APP_URL'] ?? 'http://localhost'));
        $host = $this->resolveHostFromUrl($appUrl);
        $secureByUrl = str_starts_with(strtolower($appUrl), 'https://');

        if (($environment['MAIL_FROM_NAME'] ?? '') === '') {
            $environment['MAIL_FROM_NAME'] = $appName;
        }

        if (($environment['MAIL_FROM_ADDRESS'] ?? '') === '') {
            $environment['MAIL_FROM_ADDRESS'] = 'no-reply@' . $this->defaultMailDomain($host);
        }

        if (($environment['MAIL_REPLY_TO'] ?? '') === '') {
            $environment['MAIL_REPLY_TO'] = $environment['MAIL_FROM_ADDRESS'];
        }

        if (($environment['SESSION_SECURE_COOKIE'] ?? '') === '' || $this->isPlaceholderValue($environment['SESSION_SECURE_COOKIE'] ?? '')) {
            $environment['SESSION_SECURE_COOKIE'] = $secureByUrl ? 'true' : 'false';
        }

        if (($environment['AUTH_PASSKEY_RP_ID'] ?? '') === '') {
            $environment['AUTH_PASSKEY_RP_ID'] = $host;
        }

        if (($environment['AUTH_PASSKEY_RP_NAME'] ?? '') === '') {
            $environment['AUTH_PASSKEY_RP_NAME'] = $appName;
        }

        if (($environment['AUTH_PASSKEY_ORIGINS'] ?? '') === '') {
            $environment['AUTH_PASSKEY_ORIGINS'] = $appUrl;
        }

        foreach ([
            'PAYMENT_PAYPAL_RETURN_URL' => '/orders/complete',
            'PAYMENT_PAYPAL_CANCEL_URL' => '/orders/cancelled',
            'PAYMENT_SWISH_CALLBACK_URL' => '/payments/swish/callback',
            'PAYMENT_QLIRO_MERCHANT_CONFIRMATION_URL' => '/orders/complete',
            'PAYMENT_QLIRO_MERCHANT_TERMS_URL' => '/terms',
            'PAYMENT_WALLEY_RETURN_URL' => '/orders/complete',
        ] as $key => $path) {
            $value = trim((string) ($environment[$key] ?? ''));

            if ($value === '' || str_contains($value, 'langelermvc.test')) {
                $environment[$key] = rtrim($appUrl, '/') . $path;
            }
        }

        $environment['ENCRYPTION_KEY'] = $this->resolveGeneratedSecret(
            (string) ($environment['ENCRYPTION_KEY'] ?? ''),
            32,
            ['YourGlobalEncryptionKeyHere', 'S0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0s=']
        );
        $environment['ENCRYPTION_OPENSSL_KEY'] = $this->resolveGeneratedSecret(
            (string) ($environment['ENCRYPTION_OPENSSL_KEY'] ?? ''),
            32,
            ['YourOpenSSLEncryptionKeyHere', 'S0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0s=']
        );
        $environment['ENCRYPTION_SODIUM_KEY'] = $this->resolveGeneratedSecret(
            (string) ($environment['ENCRYPTION_SODIUM_KEY'] ?? ''),
            32,
            ['YourSodiumKeyHere', 'U1NTU1NTU1NTU1NTU1NTU1NTU1NTU1NTU1NTU1NTU1M=']
        );
        $environment['HTTP_SIGNED_URL_KEY'] = $this->resolveGeneratedHexSecret(
            (string) ($environment['HTTP_SIGNED_URL_KEY'] ?? ''),
            32,
            ['langelermvc-signed-url']
        );

        return $environment;
    }

    /**
     * @return array<string, string>
     */
    private function readEnvironment(string $path): array
    {
        $contents = $this->files->readContents($path);

        if (!is_string($contents) || $contents === '') {
            return [];
        }

        $variables = [];

        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $variables[trim($key)] = trim(trim($value), "\"'");
        }

        return $variables;
    }

    private function envPath(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . '.env';
    }

    private function installStatePath(): string
    {
        return $this->storagePath('Installer/install-state.json');
    }

    private function storagePath(string $path = ''): string
    {
        $base = $this->basePath . DIRECTORY_SEPARATOR . 'Storage';

        if ($path === '') {
            return $base;
        }

        return $base . DIRECTORY_SEPARATOR . ltrim($path, '\\/');
    }

    private function isWritablePath(string $path): bool
    {
        if ($this->files->isDirectory($path)) {
            return is_writable($path);
        }

        $parent = dirname($path);

        if ($this->files->isDirectory($parent)) {
            return is_writable($parent);
        }

        return $this->files->createDirectory($path, 0777, true);
    }

    private function resolveSqlitePath(string $path): string
    {
        if ($path === ':memory:') {
            return $path;
        }

        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
            return $path;
        }

        return $this->basePath . DIRECTORY_SEPARATOR . ltrim($path, '\\/');
    }

    private function resolveProjectPath(string $path, string $fallback): string
    {
        $trimmed = trim($path);

        if ($trimmed === '') {
            return $fallback;
        }

        if (str_starts_with($trimmed, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $trimmed) === 1) {
            return $trimmed;
        }

        return $this->basePath . DIRECTORY_SEPARATOR . ltrim($trimmed, '\\/');
    }

    private function resolveHostFromUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (is_string($host) && trim($host) !== '') {
            return trim($host);
        }

        return 'localhost';
    }

    private function defaultMailDomain(string $host): string
    {
        $normalized = strtolower(trim($host));

        return match (true) {
            $normalized === '',
            $normalized === 'localhost',
            $normalized === '127.0.0.1' => 'langelermvc.test',
            default => $normalized,
        };
    }

    /**
     * @param list<string> $placeholderFragments
     */
    private function resolveGeneratedSecret(string $value, int $bytes, array $placeholderFragments): string
    {
        return $this->shouldGenerateSecret($value, $placeholderFragments)
            ? 'base64:' . base64_encode(random_bytes($bytes))
            : $value;
    }

    /**
     * @param list<string> $placeholderFragments
     */
    private function resolveGeneratedHexSecret(string $value, int $bytes, array $placeholderFragments): string
    {
        return $this->shouldGenerateSecret($value, $placeholderFragments)
            ? bin2hex(random_bytes($bytes))
            : $value;
    }

    /**
     * @param list<string> $placeholderFragments
     */
    private function shouldGenerateSecret(string $value, array $placeholderFragments): bool
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return true;
        }

        foreach ($placeholderFragments as $fragment) {
            if ($fragment !== '' && str_contains($trimmed, $fragment)) {
                return true;
            }
        }

        return false;
    }

    private function isPlaceholderValue(string $value): bool
    {
        return trim($value) === '';
    }

    private function booleanValue(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
    }

    private function quoteEnvironmentValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/\s/', $value) === 1 || str_contains($value, '#')) {
            return '"' . addcslashes($value, "\"\\") . '"';
        }

        return $value;
    }
}
