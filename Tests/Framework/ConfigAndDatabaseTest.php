<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Core\Config;
use App\Core\Database;
use App\Providers\CoreProvider;
use PHPUnit\Framework\TestCase;

class ConfigAndDatabaseTest extends TestCase
{
    /**
     * Restores environment overrides after each test.
     *
     * @var array<string, string|false>
     */
    private array $originalEnvironment = [];

    protected function tearDown(): void
    {
        foreach ($this->originalEnvironment as $key => $value) {
            if ($value === false) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
                continue;
            }

            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        $this->originalEnvironment = [];
    }

    public function testConfigLoadsTrackedFilesAndSupportsCaseInsensitiveLookup(): void
    {
        $config = $this->resolveConfig();
        $all = $config->all();

        self::assertArrayHasKey('app', $all);
        self::assertArrayHasKey('cache', $all);
        self::assertArrayHasKey('webmodule', $all);
        self::assertSame('file', $config->get('CACHE', 'DRIVER'));
        self::assertSame('file', $config->get('cache', 'DRIVER'));
        self::assertSame('file', $config->get('cache', 'driver'));
        self::assertSame('Storage/Sessions', $config->get('session', 'save.path'));
    }

    public function testConfigMergesEnvironmentOverridesAtRuntime(): void
    {
        $this->setEnvironmentOverride('APP_NAME', 'LangelerMVC Test Suite');
        $config = $this->resolveConfig();

        self::assertSame('LangelerMVC Test Suite', $config->get('app', 'NAME'));
    }

    public function testConfigSupportsInstallerEnvironmentAliasesAndTypedBooleans(): void
    {
        $this->setEnvironmentOverride('SESSION_SECURE_COOKIE', 'false');
        $this->setEnvironmentOverride('SESSION_HTTPONLY_COOKIE', 'false');
        $this->setEnvironmentOverride('CACHE_FILE_PATH', 'Storage/Cache/Installer');
        $this->setEnvironmentOverride('AUTH_VERIFY_EMAIL', 'false');
        $this->setEnvironmentOverride('AUTH_PASSKEY_RP_ID', 'example.test');
        $this->setEnvironmentOverride('MAIL_REPLY_TO', 'support@example.test');
        $this->setEnvironmentOverride('HTTP_SIGNED_URL_KEY', 'signed-url-secret');
        $this->setEnvironmentOverride('PAYMENT_DEFAULT_METHOD', 'wallet');
        $this->setEnvironmentOverride('PAYMENT_DEFAULT_FLOW', 'redirect');
        $this->setEnvironmentOverride('WEBMODULE_CONTENT_SOURCE', 'memory');
        $this->setEnvironmentOverride('QUEUE_DEFAULT_QUEUE', 'framework');
        $this->setEnvironmentOverride('QUEUE_MAX_ATTEMPTS', '5');
        $this->setEnvironmentOverride('QUEUE_BACKOFF_STRATEGY', 'linear');
        $this->setEnvironmentOverride('QUEUE_BACKOFF_SECONDS', '7');
        $this->setEnvironmentOverride('QUEUE_BACKOFF_MAX_SECONDS', '70');
        $this->setEnvironmentOverride('QUEUE_WORKER_SLEEP', '2');
        $this->setEnvironmentOverride('QUEUE_WORKER_MAX_RUNTIME', '600');
        $this->setEnvironmentOverride('QUEUE_WORKER_MAX_MEMORY_MB', '384');
        $this->setEnvironmentOverride('QUEUE_WORKER_CONTROL_PATH', 'Storage/Framework/QueueControl');
        $this->setEnvironmentOverride('QUEUE_FAILED_PRUNE_AFTER_HOURS', '48');
        $this->setEnvironmentOverride('OPERATIONS_AUDIT_ENABLED', 'false');
        $this->setEnvironmentOverride('OPERATIONS_AUDIT_SUMMARY_LIMIT', '42');
        $this->setEnvironmentOverride('OPERATIONS_AUDIT_RETENTION_HOURS', '96');

        $config = $this->resolveConfig();

        self::assertFalse($config->get('session', 'COOKIE.SECURE'));
        self::assertFalse($config->get('session', 'COOKIE.HTTPONLY'));
        self::assertSame('Storage/Cache/Installer', $config->get('cache', 'FILE'));
        self::assertFalse($config->get('auth', 'VERIFY_EMAIL'));
        self::assertSame('example.test', $config->get('auth', 'PASSKEY.RP_ID'));
        self::assertSame('support@example.test', $config->get('mail', 'REPLY'));
        self::assertSame('signed-url-secret', $config->get('http', 'SIGNED_URL.KEY'));
        self::assertSame('wallet', $config->get('payment', 'DEFAULT_METHOD'));
        self::assertSame('redirect', $config->get('payment', 'DEFAULT_FLOW'));
        self::assertSame('memory', $config->get('webmodule', 'CONTENT_SOURCE'));
        self::assertSame('framework', $config->get('queue', 'DEFAULT_QUEUE'));
        self::assertSame(5, $config->get('queue', 'MAX_ATTEMPTS'));
        self::assertSame('linear', $config->get('queue', 'BACKOFF.STRATEGY'));
        self::assertSame(7, $config->get('queue', 'BACKOFF.SECONDS'));
        self::assertSame(70, $config->get('queue', 'BACKOFF.MAX_SECONDS'));
        self::assertSame(2, $config->get('queue', 'WORKER.SLEEP'));
        self::assertSame(600, $config->get('queue', 'WORKER.MAX_RUNTIME'));
        self::assertSame(384, $config->get('queue', 'WORKER.MAX_MEMORY_MB'));
        self::assertSame('Storage/Framework/QueueControl', $config->get('queue', 'WORKER.CONTROL_PATH'));
        self::assertSame(48, $config->get('queue', 'FAILED.PRUNE_AFTER_HOURS'));
        self::assertFalse($config->get('operations', 'AUDIT.ENABLED'));
        self::assertSame(42, $config->get('operations', 'AUDIT.SUMMARY_LIMIT'));
        self::assertSame(96, $config->get('operations', 'AUDIT.RETENTION_HOURS'));
    }

    public function testDatabaseServiceResolvesWithoutConnecting(): void
    {
        $provider = new CoreProvider();
        $provider->registerServices();

        $database = $provider->getCoreService('database');

        self::assertInstanceOf(Database::class, $database);
        self::assertFalse($database->isConnected());
    }

    private function resolveConfig(): Config
    {
        $provider = new CoreProvider();
        $provider->registerServices();

        return $provider->getCoreService('config');
    }

    private function setEnvironmentOverride(string $key, string $value): void
    {
        if (!array_key_exists($key, $this->originalEnvironment)) {
            $current = getenv($key);
            $this->originalEnvironment[$key] = $current === false ? false : $current;
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
