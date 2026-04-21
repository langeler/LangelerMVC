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

    private string $basePath;

    public function __construct(
        private readonly FileManager $files,
        ?string $basePath = null
    ) {
        $this->basePath = $basePath ?? $this->frameworkBasePath();
    }

    /**
     * @return array<string, string>
     */
    public function defaults(): array
    {
        $defaults = array_replace($this->templateEnvironment(), [
            'APP_NAME' => 'LangelerMVC',
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_INSTALLED' => 'false',
            'APP_URL' => 'http://localhost',
            'APP_TIMEZONE' => 'Europe/Stockholm',
            'APP_LOCALE' => 'en',
            'APP_FALLBACK_LOCALE' => 'en',
            'DB_CONNECTION' => 'sqlite',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'Storage/Database/langelermvc.sqlite',
            'DB_USERNAME' => '',
            'DB_PASSWORD' => '',
            'SESSION_DRIVER' => 'database',
            'CACHE_DRIVER' => 'file',
            'MAIL_MAILER' => 'array',
            'MAIL_HOST' => 'smtp.mailtrap.io',
            'MAIL_PORT' => '2525',
            'MAIL_USERNAME' => '',
            'MAIL_PASSWORD' => '',
            'MAIL_ENCRYPTION' => 'tls',
            'MAIL_FROM_ADDRESS' => 'no-reply@langelermvc.test',
            'MAIL_REPLY_TO' => 'no-reply@langelermvc.test',
            'WEBMODULE_CONTENT_SOURCE' => 'database',
            'FEATURE_VERIFY_EMAIL' => 'true',
            'FEATURE_2FA' => 'true',
        ]);

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

        return [
            'php' => PHP_VERSION,
            'installed' => $this->isInstalled(),
            'storageWritable' => $this->isWritablePath($storage),
            'databaseWritable' => $this->isWritablePath($databaseDirectory),
            'extensions' => [
                'pdo' => extension_loaded('pdo'),
                'openssl' => extension_loaded('openssl'),
                'intl' => extension_loaded('intl'),
                'mbstring' => extension_loaded('mbstring'),
                'redis' => extension_loaded('redis'),
                'memcached' => extension_loaded('memcached'),
            ],
            'paymentDrivers' => ['testing', 'card', 'paypal', 'klarna', 'swish', 'qliro', 'walley', 'crypto'],
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
        $this->prepareFilesystem($data);
        $this->verifyDatabaseConnection($data);

        $environment = $this->buildEnvironment($data);
        $this->writeEnvironment($environment);
        $this->reloadEnvironment($environment);
        $this->ensurePathConstants();

        $provider = new CoreProvider();
        $provider->registerServices();

        $migrationRunner = $provider->getCoreService('migrationRunner');
        $seedRunner = $provider->getCoreService('seedRunner');

        if (!$migrationRunner instanceof MigrationRunner || !$seedRunner instanceof SeedRunner) {
            throw new \RuntimeException('Unable to resolve schema lifecycle services during installation.');
        }

        $migrated = [];
        $seeded = [];

        foreach ($this->moduleInstallOrder() as $module) {
            $migrated[$module] = $migrationRunner->migrate($module);
        }

        foreach ($this->moduleInstallOrder() as $module) {
            $seeded[$module] = $seedRunner->run($module);
        }

        $admin = $this->provisionAdministrator($provider, $data);

        return [
            'environment' => $environment,
            'migrated' => $migrated,
            'seeded' => $seeded,
            'admin' => $admin,
            'login' => [
                'html' => '/users/login',
                'api' => '/api/users/login',
            ],
        ];
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

        $data['APP_DEBUG'] = $this->booleanValue($payload['APP_DEBUG'] ?? $data['APP_DEBUG']) ? 'true' : 'false';
        $data['FEATURE_VERIFY_EMAIL'] = $this->booleanValue($payload['FEATURE_VERIFY_EMAIL'] ?? $data['FEATURE_VERIFY_EMAIL']) ? 'true' : 'false';
        $data['FEATURE_2FA'] = $this->booleanValue($payload['FEATURE_2FA'] ?? $data['FEATURE_2FA']) ? 'true' : 'false';

        if ($data['DB_CONNECTION'] === 'sqlite' && $data['DB_DATABASE'] === '') {
            $data['DB_DATABASE'] = 'Storage/Database/langelermvc.sqlite';
        }

        return $data;
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

        if (filter_var($data['ADMIN_EMAIL'], FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('Administrator email must be a valid email address.');
        }

        if (strlen($data['ADMIN_PASSWORD']) < 8) {
            throw new \InvalidArgumentException('Administrator password must be at least 8 characters long.');
        }

        if (!in_array($data['DB_CONNECTION'], ['sqlite', 'mysql', 'pgsql', 'sqlsrv'], true)) {
            throw new \InvalidArgumentException('Unsupported database driver selected.');
        }

        if (!in_array($data['SESSION_DRIVER'], ['native', 'file', 'database', 'redis'], true)) {
            throw new \InvalidArgumentException('Unsupported session driver selected.');
        }

        if (!in_array($data['CACHE_DRIVER'], ['array', 'file', 'database', 'redis', 'memcache'], true)) {
            throw new \InvalidArgumentException('Unsupported cache driver selected.');
        }

        if (!in_array($data['MAIL_MAILER'], ['array', 'smtp'], true)) {
            throw new \InvalidArgumentException('Unsupported mail driver selected.');
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
        ];

        foreach ($paths as $path) {
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
            new \PDO($dsn, $data['DB_USERNAME'] !== '' ? $data['DB_USERNAME'] : null, $data['DB_PASSWORD'] !== '' ? $data['DB_PASSWORD'] : null);
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
        $environment = $this->templateEnvironment();

        foreach ($data as $key => $value) {
            if (!str_starts_with($key, 'ADMIN_')) {
                $environment[$key] = $value;
            }
        }

        $environment['APP_INSTALLED'] = 'true';
        $environment['APP_MAINTENANCE'] = 'false';
        $environment['CACHE_ENABLED'] = 'true';
        $environment['PAYMENT_DRIVER'] = $environment['PAYMENT_DRIVER'] ?? 'testing';
        $environment['WEBMODULE_CONTENT_SOURCE'] = 'database';
        $environment['DB_DATABASE'] = $data['DB_CONNECTION'] === 'sqlite'
            ? $this->resolveSqlitePath($data['DB_DATABASE'])
            : $data['DB_DATABASE'];

        if (($environment['MAIL_FROM_ADDRESS'] ?? '') === '') {
            $environment['MAIL_FROM_ADDRESS'] = 'no-reply@langelermvc.test';
        }

        if (($environment['MAIL_REPLY_TO'] ?? '') === '') {
            $environment['MAIL_REPLY_TO'] = $environment['MAIL_FROM_ADDRESS'];
        }

        return $environment;
    }

    /**
     * @param array<string, string> $environment
     */
    private function writeEnvironment(array $environment): void
    {
        $contents = [];
        $groups = [
            'APPLICATION' => ['APP_NAME', 'APP_ENV', 'APP_DEBUG', 'APP_INSTALLED', 'APP_URL', 'APP_TIMEZONE', 'APP_LOCALE', 'APP_FALLBACK_LOCALE', 'APP_VERSION', 'APP_MAINTENANCE', 'APP_LOG_LEVEL', 'APP_LOG_CHANNEL'],
            'DATABASE' => ['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'DB_CHARSET', 'DB_COLLATION', 'DB_POOLING', 'DB_POOL_SIZE', 'DB_FAILOVER', 'DB_TIMEOUT', 'DB_RETRY_DELAY', 'DB_SSL_MODE', 'DB_REPLICATION'],
            'MAIL' => ['MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION', 'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME', 'MAIL_REPLY_TO', 'MAIL_CC', 'MAIL_BCC', 'MAIL_QUEUE', 'MAIL_LOG_ENABLED'],
            'SESSION' => ['SESSION_DRIVER', 'SESSION_NAME', 'SESSION_LIFETIME', 'SESSION_EXPIRE_ON_CLOSE', 'SESSION_SECURE_COOKIE', 'SESSION_HTTPONLY_COOKIE', 'SESSION_SAME_SITE', 'SESSION_SAVE_PATH'],
            'CACHE' => ['CACHE_ENABLED', 'CACHE_DRIVER', 'CACHE_PREFIX', 'CACHE_TTL', 'CACHE_COMPRESSION', 'CACHE_SERIALIZATION', 'CACHE_ENCRYPT', 'CACHE_REDIS_HOST', 'CACHE_REDIS_PORT', 'CACHE_REDIS_PASSWORD', 'CACHE_REDIS_DATABASE', 'CACHE_MEMCACHED_HOST', 'CACHE_MEMCACHED_PORT', 'CACHE_FILE_PATH'],
            'ENCRYPTION' => ['ENCRYPTION_ENABLED', 'ENCRYPTION_TYPE', 'ENCRYPTION_KEY', 'ENCRYPTION_CIPHER', 'ENCRYPTION_HASH_ALGO', 'ENCRYPTION_HASH_ROUNDS', 'ENCRYPTION_OPENSSL_KEY', 'ENCRYPTION_OPENSSL_CIPHER', 'ENCRYPTION_SODIUM_KEY'],
            'FEATURES' => ['FEATURE_VERIFY_EMAIL', 'FEATURE_2FA'],
            'PAYMENTS' => ['PAYMENT_DRIVER'],
            'WEBMODULE' => ['WEBMODULE_CONTENT_SOURCE'],
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
