<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Contracts\Data\CryptoInterface;
use App\Drivers\Cryptography\OpenSSLCrypto;
use App\Drivers\Cryptography\SodiumCrypto;
use App\Exceptions\ContainerException;
use App\Exceptions\Data\CryptoException;
use App\Providers\CryptoProvider;
use App\Providers\ExceptionProvider;
use App\Utilities\Managers\Data\CryptoManager;
use App\Utilities\Managers\SettingsManager;
use PHPUnit\Framework\TestCase;

class CryptoSubsystemTest extends TestCase
{
    public function testExceptionProviderAndCryptoProviderExposeFrameworkCryptoBoundary(): void
    {
        $exceptionProvider = new ExceptionProvider();
        $exceptionProvider->registerServices();

        $resolvedException = $exceptionProvider->getException('crypto', 'boom');
        $cryptoProvider = new CryptoProvider();
        $cryptoProvider->registerServices();
        $driver = $cryptoProvider->getCryptoDriver(['DRIVER' => 'openssl']);

        self::assertInstanceOf(CryptoException::class, $resolvedException);
        self::assertContains('openssl', $cryptoProvider->getSupportedDrivers());
        self::assertContains('sodium', $cryptoProvider->getSupportedDrivers());
        self::assertInstanceOf(CryptoInterface::class, $driver);
        self::assertSame('openssl', $driver->driverName());
        self::assertTrue($driver->supports('extension'));
    }

    public function testCryptoManagerNormalizesSettingsSecretsAndCapabilities(): void
    {
        $provider = $this->createMock(CryptoProvider::class);
        $settings = $this->createMock(SettingsManager::class);
        $driver = new OpenSSLCrypto();

        $settings
            ->expects($this->once())
            ->method('getAllSettings')
            ->with('ENCRYPTION')
            ->willReturn([
                'DRIVER' => ' OPENSSL # active driver ',
                'OPENSSL_CIPHER' => 'AES-256-CBC',
                'OPENSSL_KEY' => 'base64:S0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0s=',
            ]);

        $provider
            ->expects($this->once())
            ->method('registerServices');

        $provider
            ->expects($this->once())
            ->method('getCryptoDriver')
            ->with(['DRIVER' => 'openssl'])
            ->willReturn($driver);

        $manager = new CryptoManager($provider, $settings);

        self::assertSame('openssl', $manager->getDriverName());
        self::assertSame('AES-256-CBC', $manager->resolveConfiguredCipher('openssl'));
        self::assertSame(str_repeat('K', 32), $manager->resolveConfiguredKey('openssl'));
        self::assertSame('ABC', $manager->decodeConfiguredSecret('hex:414243'));
        self::assertTrue($manager->supports('encrypt.symmetric'));
        self::assertSame(openssl_cipher_iv_length('aes-256-cbc'), $manager->ivLength());
    }

    public function testCryptoManagerRejectsInvalidConfiguredSecrets(): void
    {
        $provider = $this->createStub(CryptoProvider::class);
        $settings = $this->createStub(SettingsManager::class);

        $settings
            ->method('getAllSettings')
            ->willReturn(['DRIVER' => 'openssl']);

        $provider
            ->method('getCryptoDriver')
            ->willReturn(new OpenSSLCrypto());

        $manager = new CryptoManager($provider, $settings);

        $this->expectException(CryptoException::class);
        $manager->decodeConfiguredSecret('base64:not-valid');
    }

    public function testOpenSslDriverSupportsEmptyStringRoundTripsAndKeyExchangeResolution(): void
    {
        $driver = new OpenSSLCrypto();
        $key = str_repeat('K', 32);
        $cipher = 'AES-256-CBC';
        $iv = ($driver->RandomGenerator('generateRandomIv'))($cipher);
        $encrypt = $driver->Encryptor('symmetric');
        $decrypt = $driver->Decryptor('symmetric');

        $encrypted = $encrypt('', $cipher, $key, $iv);
        $decrypted = $decrypt($encrypted, $cipher, $key, $iv);

        self::assertIsString($encrypted);
        self::assertSame('', $decrypted);
        self::assertIsCallable($driver->KeyExchanger('deriveSharedKey'));
        self::assertTrue($driver->supports('keyexchange.derivesharedkey'));
    }

    public function testSodiumDriverUsesBooleanVerificationAndValidatedKeyDerivation(): void
    {
        $driver = new SodiumCrypto();
        $argonHash = ($driver->PasswordHasher('argon2id'))('password');
        $scryptHash = ($driver->PasswordHasher('scrypt'))('password');
        $kdfKey = ($driver->KeyGenerator('kdf'))();
        $deriveKey = $driver->KeyDerivation();

        self::assertTrue(($driver->PasswordVerifier('verify'))($argonHash, 'password'));
        self::assertFalse(($driver->PasswordVerifier('verify'))($argonHash, 'wrong'));
        self::assertTrue(($driver->PasswordVerifier('scryptVerify'))($scryptHash, 'password'));
        self::assertFalse(($driver->PasswordVerifier('scryptVerify'))($scryptHash, 'wrong'));
        self::assertSame(32, strlen($deriveKey(32, 1, 'CTXTEST1', $kdfKey)));
        self::assertIsBool($driver->supports('encrypt.aead.aes256gcm'));
    }

    public function testCryptoProviderRejectsUnsupportedDrivers(): void
    {
        $provider = new CryptoProvider();
        $provider->registerServices();

        $this->expectException(ContainerException::class);
        $provider->getCryptoDriver(['DRIVER' => 'unsupported']);
    }
}
