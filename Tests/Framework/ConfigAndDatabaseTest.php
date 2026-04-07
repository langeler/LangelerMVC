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

    public function testConfigSkipsInvalidFilesAndSupportsCaseInsensitiveLookup(): void
    {
        $config = $this->resolveConfig();
        $all = $config->all();

        self::assertArrayHasKey('app', $all);
        self::assertArrayHasKey('cache', $all);
        self::assertArrayNotHasKey('untitled', $all);
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
