<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Core\Database;
use App\Drivers\Caching\ArrayCache;
use App\Drivers\Caching\DatabaseCache;
use App\Drivers\Caching\FileCache;
use App\Providers\CacheProvider;
use App\Providers\CryptoProvider;
use App\Providers\ExceptionProvider;
use App\Utilities\Finders\FileFinder;
use App\Utilities\Handlers\DataHandler;
use App\Utilities\Managers\DateTimeManager;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\IteratorManager;
use App\Utilities\Managers\Data\CacheManager;
use App\Utilities\Managers\Data\CryptoManager;
use App\Utilities\Managers\SettingsManager;
use App\Utilities\Managers\System\ErrorManager;
use PDO;
use PHPUnit\Framework\TestCase;

class CacheSubsystemTest extends TestCase
{
    public function testCacheManagerProvidesRememberPullAndDriverMetadataHelpers(): void
    {
        $settings = $this->makeSettingsManager([
            'DRIVER' => 'array',
            'PREFIX' => 'manager-cache-test',
            'COMPRESSION' => 'false',
            'ENCRYPT' => 'false',
        ]);
        $provider = $this->createMock(CacheProvider::class);
        $provider
            ->expects($this->once())
            ->method('registerServices');

        $manager = new CacheManager(
            $provider,
            $settings,
            $this->makeArrayCache($settings)
        );

        $calls = 0;

        self::assertSame('value', $manager->remember('example', function () use (&$calls): string {
            $calls++;

            return 'value';
        }, 60));
        self::assertSame('value', $manager->remember('example', function () use (&$calls): string {
            $calls++;

            return 'other';
        }, 60));
        self::assertSame(1, $calls);
        self::assertTrue($manager->has('example'));
        self::assertSame('value', $manager->pull('example'));
        self::assertTrue($manager->missing('example'));
        self::assertSame('array', $manager->getDriverName());
        self::assertTrue($manager->supports('extension'));
        self::assertSame('manager-cache-test', $manager->prefix());
    }

    public function testFileCacheClearOnlyRemovesTheActivePrefixNamespace(): void
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'langelermvc-cache-file-' . uniqid('', true);
        $alphaCache = $this->makeFileCache($this->makeSettingsManager([
            'DRIVER' => 'file',
            'FILE' => $directory,
            'PREFIX' => 'alpha-cache',
            'COMPRESSION' => 'false',
            'ENCRYPT' => 'false',
        ]));
        $betaCache = $this->makeFileCache($this->makeSettingsManager([
            'DRIVER' => 'file',
            'FILE' => $directory,
            'PREFIX' => 'beta-cache',
            'COMPRESSION' => 'false',
            'ENCRYPT' => 'false',
        ]));

        self::assertTrue($alphaCache->set('page.home', ['title' => 'Alpha'], 120));
        self::assertTrue($betaCache->set('page.home', ['title' => 'Beta'], 120));
        self::assertSame(['title' => 'Alpha'], $alphaCache->get('page.home'));
        self::assertSame(['title' => 'Beta'], $betaCache->get('page.home'));

        self::assertTrue($alphaCache->clear());

        self::assertNull($alphaCache->get('page.home'));
        self::assertSame(['title' => 'Beta'], $betaCache->get('page.home'));
    }

    public function testDatabaseCacheUsesNamespacedRowsAndBuilderBackedPersistence(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $database = $this->makeSqliteDatabase($pdo);
        $database->query('CREATE TABLE cache (cache_key TEXT PRIMARY KEY, cache_data TEXT NOT NULL, timestamp INTEGER NOT NULL, ttl INTEGER NOT NULL)');

        $alphaSettings = $this->makeSettingsManager([
            'DRIVER' => 'database',
            'PREFIX' => 'alpha-db-cache',
            'TABLE' => 'cache',
            'COMPRESSION' => 'false',
            'ENCRYPT' => 'false',
        ]);
        $betaSettings = $this->makeSettingsManager([
            'DRIVER' => 'database',
            'PREFIX' => 'beta-db-cache',
            'TABLE' => 'cache',
            'COMPRESSION' => 'false',
            'ENCRYPT' => 'false',
        ]);

        $alphaCache = $this->makeDatabaseCache($database, $alphaSettings);
        $betaCache = $this->makeDatabaseCache($database, $betaSettings);

        self::assertTrue($alphaCache->set('page.home', 'alpha', 180));
        self::assertTrue($betaCache->set('page.home', 'beta', 180));
        self::assertSame('alpha', $alphaCache->get('page.home'));
        self::assertSame('beta', $betaCache->get('page.home'));
        self::assertSame(4, (int) $database->fetchColumn('SELECT COUNT(*) FROM cache'));

        self::assertTrue($alphaCache->clear());

        self::assertNull($alphaCache->get('page.home'));
        self::assertSame('beta', $betaCache->get('page.home'));
        self::assertSame(2, (int) $database->fetchColumn('SELECT COUNT(*) FROM cache'));
    }

    public function testCacheProviderRejectsUnavailableRuntimeDrivers(): void
    {
        $provider = new CacheProvider();
        $provider->registerServices();

        if (!class_exists(\Redis::class)) {
            $this->expectException(\App\Exceptions\ContainerException::class);
            $provider->getCacheDriver(['DRIVER' => 'redis']);
            return;
        }

        if (!class_exists(\Memcached::class)) {
            $this->expectException(\App\Exceptions\ContainerException::class);
            $provider->getCacheDriver(['DRIVER' => 'memcache']);
            return;
        }

        self::assertContains('redis', $provider->getSupportedDrivers());
        self::assertContains('memcache', $provider->getSupportedDrivers());
    }

    private function makeArrayCache(SettingsManager $settingsManager): ArrayCache
    {
        return new ArrayCache(
            new FileManager(),
            new DataHandler(),
            $this->makeCryptoManager($settingsManager),
            new DateTimeManager(),
            $settingsManager,
            $this->makeErrorManager()
        );
    }

    private function makeFileCache(SettingsManager $settingsManager): FileCache
    {
        return new FileCache(
            new FileFinder(new IteratorManager()),
            new FileManager(),
            new DataHandler(),
            $this->makeCryptoManager($settingsManager),
            new DateTimeManager(),
            $settingsManager,
            $this->makeErrorManager()
        );
    }

    private function makeDatabaseCache(Database $database, SettingsManager $settingsManager): DatabaseCache
    {
        return new DatabaseCache(
            $database,
            new FileManager(),
            new DataHandler(),
            $this->makeCryptoManager($settingsManager),
            new DateTimeManager(),
            $settingsManager,
            $this->makeErrorManager()
        );
    }

    private function makeCryptoManager(SettingsManager $settingsManager): CryptoManager
    {
        return new CryptoManager(new CryptoProvider(), $settingsManager);
    }

    private function makeErrorManager(): ErrorManager
    {
        return new ErrorManager(new ExceptionProvider());
    }

    private function makeSqliteDatabase(PDO $pdo): Database
    {
        $settingsManager = new class extends SettingsManager {
            public function __construct()
            {
            }

            public function getAllSettings(string $fileName): array
            {
                return match (strtolower($fileName)) {
                    'db' => [
                        'DRIVER' => 'sqlite',
                        'CONNECTION' => 'sqlite',
                        'DATABASE' => ':memory:',
                    ],
                    default => [],
                };
            }
        };

        return new Database(
            $settingsManager,
            $this->makeErrorManager(),
            $pdo
        );
    }

    private function makeSettingsManager(array $cacheSettings = [], array $encryptionSettings = []): SettingsManager
    {
        $cacheDefaults = [
            'ENABLED' => true,
            'DRIVER' => 'array',
            'PREFIX' => 'langelermvc_test',
            'TTL' => 3600,
            'COMPRESSION' => false,
            'SERIALIZATION' => 'php',
            'ENCRYPT' => false,
            'MAX_ITEMS' => 0,
            'FILE' => 'Storage/Cache',
            'TABLE' => 'cache',
        ];
        $encryptionDefaults = [
            'ENABLED' => true,
            'DRIVER' => 'openssl',
            'OPENSSL_CIPHER' => 'AES-256-CBC',
            'KEY' => 'base64:' . base64_encode(str_repeat('a', 32)),
            'OPENSSL_KEY' => 'base64:' . base64_encode(str_repeat('b', 32)),
        ];

        return new class(array_merge($cacheDefaults, $cacheSettings), array_merge($encryptionDefaults, $encryptionSettings)) extends SettingsManager {
            public function __construct(
                private readonly array $cacheConfig,
                private readonly array $encryptionConfig
            ) {
            }

            public function getAllSettings(string $fileName): array
            {
                return match (strtolower($fileName)) {
                    'cache' => $this->cacheConfig,
                    'encryption' => $this->encryptionConfig,
                    default => [],
                };
            }
        };
    }
}
