<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Contracts\Data\CacheDriverInterface;
use App\Core\Database;
use App\Drivers\Cryptography\OpenSSLCrypto;
use App\Drivers\Cryptography\SodiumCrypto;
use App\Providers\CacheProvider;
use App\Providers\ExceptionProvider;
use App\Utilities\Handlers\CryptoHandler;
use App\Utilities\Handlers\LocaleHandler;
use App\Utilities\Handlers\MessageFormatterHandler;
use App\Utilities\Handlers\NormalizeHandler;
use App\Utilities\Managers\Data\CacheManager;
use App\Utilities\Managers\SettingsManager;
use App\Utilities\Managers\System\DateTimeManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Managers\System\FileManager;
use App\Utilities\Managers\System\ReflectionManager;
use App\Utilities\Handlers\NumberFormatterHandler;
use PDO;
use PHPUnit\Framework\TestCase;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
class ReflectionMarker
{
    public function __construct(public string $name)
    {
    }
}

class UtilityLayerHardeningTest extends TestCase
{
    public function testLocaleAndNormalizationHandlersConstructAndOperate(): void
    {
        $localeHandler = new LocaleHandler();
        $normalizeHandler = new NormalizeHandler();

        self::assertSame('en', $localeHandler->getPrimaryLanguage('en_US'));
        self::assertSame("\u{00C5}", $normalizeHandler->normalize("A\u{030A}"));
    }

    public function testDateTimeManagerAndReflectionManagerExposeWorkingConvenienceHelpers(): void
    {
        $dateTimeManager = new DateTimeManager();
        $reflectionManager = new ReflectionManager();

        self::assertArrayHasKey('dateFormats', $dateTimeManager->traitConstants);
        self::assertSame(
            '2024-01-01',
            DateTimeManager::setState([
                'date' => '2024-01-01 00:00:00.000000',
                'timezone_type' => 3,
                'timezone' => 'UTC',
            ])->format('Y-m-d')
        );

        $subject = new class {
            #[ReflectionMarker('property')]
            public string $name = 'LangelerMVC';
            public static string $kind = 'framework';
            private string $secret = 'hidden';

            #[ReflectionMarker('method')]
            public function marker(): void
            {
            }
        };

        $subject->name = 'Runtime value';

        $properties = $reflectionManager->getPublicPropertiesWithValues($subject);
        $visibilities = $reflectionManager->getMethodVisibilities(FileManager::class);
        $propertyAttributes = $reflectionManager->getAttributeData($subject, 'name');
        $methodAttributes = $reflectionManager->getAttributeData($subject, 'marker');
        $indexedVisibilities = [];

        foreach ($visibilities as $method) {
            $indexedVisibilities[$method['name']] = $method['visibility'];
        }

        self::assertSame('Runtime value', $properties['name']);
        self::assertSame('framework', $properties['kind']);
        self::assertSame('private', $indexedVisibilities['getFileInfo']);
        self::assertCount(1, $propertyAttributes);
        self::assertCount(1, $methodAttributes);
        self::assertTrue($reflectionManager->methodHasVisibility($subject, 'marker', 'public'));
        self::assertSame([], $reflectionManager->listUsedTraits($subject));
        self::assertSame([], $reflectionManager->getConstantsWithMetadata($subject));
    }

    public function testFormatterHandlersKeepUpdatedRuntimeState(): void
    {
        $messageFormatter = new MessageFormatterHandler('en_US', '{0} apples');
        $numberFormatter = new NumberFormatterHandler();

        self::assertTrue($messageFormatter->setPattern('{0} pears'));
        self::assertSame('3 pears', $messageFormatter->format([3]));

        self::assertTrue($numberFormatter->setPattern('#,##0.00'));
        self::assertSame('#,##0.00', $numberFormatter->getPattern());
    }

    public function testCryptoHandlersAndDriversUseRuntimeSafeDefaults(): void
    {
        $cryptoHandler = new CryptoHandler();
        $openSsl = new OpenSSLCrypto();
        $sodium = new SodiumCrypto();

        self::assertSame(hash('sha256', 'abc'), $cryptoHandler->hashData('sha256', 'abc'));
        self::assertSame(openssl_digest('abc', 'sha256'), ($openSsl->Hasher('hashDigest'))('abc'));

        $scrypt = $sodium->PasswordHasher('scrypt');
        $verifyScrypt = $sodium->PasswordVerifier('scryptVerify');
        $hash = $scrypt('password');

        self::assertIsString($hash);
        self::assertTrue($verifyScrypt($hash, 'password'));
    }

    public function testCacheManagerReloadsDriverFromMergedOverridesInsteadOfDiscardingThem(): void
    {
        $initialSettings = ['DRIVER' => 'file', 'TTL' => 60];
        $overrideSettings = ['DRIVER' => 'redis', 'TTL' => 120];
        $seenSettings = [];

        $fileDriver = new class implements CacheDriverInterface {
            public function driverName(): string
            {
                return 'file';
            }

            public function capabilities(): array
            {
                return ['extension' => true];
            }

            public function supports(string $feature): bool
            {
                return $feature === 'extension';
            }

            public function set(string $key, mixed $data, ?int $ttl = null): bool
            {
                return true;
            }

            public function get(string $key): string
            {
                return 'file';
            }

            public function delete(string $key): bool
            {
                return true;
            }

            public function clear(): bool
            {
                return true;
            }

            public function has(string $key): bool
            {
                return true;
            }
        };

        $redisDriver = new class implements CacheDriverInterface {
            public function driverName(): string
            {
                return 'redis';
            }

            public function capabilities(): array
            {
                return ['extension' => true];
            }

            public function supports(string $feature): bool
            {
                return $feature === 'extension';
            }

            public function set(string $key, mixed $data, ?int $ttl = null): bool
            {
                return true;
            }

            public function get(string $key): string
            {
                return 'redis';
            }

            public function delete(string $key): bool
            {
                return true;
            }

            public function clear(): bool
            {
                return true;
            }

            public function has(string $key): bool
            {
                return true;
            }
        };

        $cacheProvider = $this->createMock(CacheProvider::class);
        $settingsManager = $this->createMock(SettingsManager::class);

        $settingsManager
            ->expects($this->once())
            ->method('getAllSettings')
            ->with('CACHE')
            ->willReturn($initialSettings);

        $cacheProvider
            ->expects($this->once())
            ->method('registerServices');

        $cacheProvider
            ->expects($this->exactly(2))
            ->method('getCacheDriver')
            ->willReturnCallback(function (array $settings) use (&$seenSettings, $fileDriver, $redisDriver): CacheDriverInterface {
                $seenSettings[] = $settings;

                return count($seenSettings) === 1 ? $fileDriver : $redisDriver;
            });

        $manager = new CacheManager($cacheProvider, $settingsManager);
        $manager->updateCacheDriver($overrideSettings);

        self::assertSame('file', $seenSettings[0]['DRIVER']);
        self::assertSame(60, $seenSettings[0]['TTL']);
        self::assertSame('langelermvc_cache', $seenSettings[0]['PREFIX']);
        self::assertTrue($seenSettings[0]['ENABLED']);
        self::assertSame('redis', $seenSettings[1]['DRIVER']);
        self::assertSame(120, $seenSettings[1]['TTL']);
        self::assertSame('langelermvc_cache', $seenSettings[1]['PREFIX']);
        self::assertTrue($seenSettings[1]['ENABLED']);
        self::assertSame('redis', $manager->get('example'));
    }

    public function testDatabaseTruncateUsesSqliteSafeStatement(): void
    {
        $settingsManager = $this->createStub(SettingsManager::class);
        $settingsManager
            ->method('getAllSettings')
            ->willReturnCallback(
                fn(string $name): array => match (strtolower($name)) {
                    'db' => [
                        'DRIVER' => 'sqlite',
                        'DATABASE' => ':memory:',
                    ],
                    default => [],
                }
            );

        $database = new Database(
            $settingsManager,
            new ErrorManager(new ExceptionProvider()),
            new PDO('sqlite::memory:')
        );

        $database->query('CREATE TABLE items (id INTEGER PRIMARY KEY, name TEXT)');
        $database->execute('INSERT INTO items (name) VALUES (?)', ['first']);

        self::assertSame(1, (int) $database->fetchColumn('SELECT COUNT(*) FROM items'));

        $database->truncate('items');

        self::assertSame(0, (int) $database->fetchColumn('SELECT COUNT(*) FROM items'));
    }
}
