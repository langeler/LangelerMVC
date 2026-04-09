<?php

namespace App\Utilities\Managers\Data;

use App\Contracts\Data\CryptoInterface;
use App\Exceptions\Data\CryptoException;
use App\Providers\CryptoProvider;
use App\Utilities\Managers\SettingsManager;
use App\Utilities\Traits\{
	ArrayTrait,
    CheckerTrait,
    EncodingTrait,
    ErrorTrait,
	ManipulationTrait,
	TypeCheckerTrait
};
use App\Utilities\Traits\Patterns\PatternTrait;

class CryptoManager
{
    use ArrayTrait, CheckerTrait, EncodingTrait, ErrorTrait, ManipulationTrait, PatternTrait, TypeCheckerTrait;

    public readonly array $cryptoSettings;
    public readonly CryptoInterface $cryptoDriver;

    public function __construct(
        protected CryptoProvider $cryptoProvider,
        protected SettingsManager $settingsManager,
        ?CryptoInterface $cryptoDriver = null
    ) {
        $this->cryptoProvider->registerServices();
        $this->cryptoSettings = $this->normalizeCryptoSettings(
            $this->settingsManager->getAllSettings('ENCRYPTION')
        );
        $this->cryptoDriver = $cryptoDriver ?? $this->resolveCryptoDriver();
    }

    public function encrypt(string $type, mixed ...$args): mixed
    {
        return $this->dispatchFactory('Encryptor', $type, $args);
    }

    public function decrypt(string $type, mixed ...$args): mixed
    {
        return $this->dispatchFactory('Decryptor', $type, $args);
    }

    public function generateRandom(string $type, mixed ...$args): mixed
    {
        if ($this->countElements($args) === 1 && $this->isInt($args[0])) {
            return ($this->cryptoDriver->RandomGenerator($type, $args[0]))();
        }

        return $this->dispatchFactory('RandomGenerator', $type, $args);
    }

    public function hash(string $type, mixed ...$args): mixed
    {
        return $this->dispatchFactory('Hasher', $type, $args);
    }

    public function memory(string $action, mixed ...$args): mixed
    {
        return $this->dispatchFactory('MemoryHandler', $action, $args);
    }

    public function convert(string $type, mixed ...$args): mixed
    {
        return $this->dispatchFactory('DataConverter', $type, $args);
    }

    public function sign(string $type, mixed ...$args): mixed
    {
        return $this->dispatchOptionalFactory('Signer', $type, $args);
    }

    public function verify(string $type, mixed ...$args): mixed
    {
        return $this->dispatchOptionalFactory('Verifier', $type, $args);
    }

    public function key(string $action, mixed ...$args): mixed
    {
        return $this->dispatchOptionalFactory('KeyHandler', $action, $args);
    }

    public function keyExchange(string $action, mixed ...$args): mixed
    {
        if ($this->methodExists($this->cryptoDriver, 'KeyExchangeHandler')) {
            return $this->dispatchOptionalFactory('KeyExchangeHandler', $action, $args);
        }

        return $this->dispatchFactory('KeyExchanger', $action, $args);
    }

    public function passwordHash(string $type, mixed ...$args): mixed
    {
        return $this->dispatchOptionalFactory('PasswordHasher', $type, $args);
    }

    public function passwordVerify(string $hash, string $password, string $action = 'verify'): bool
    {
        return (bool) $this->dispatchOptionalFactory('PasswordVerifier', $action, [$hash, $password]);
    }

    public function passwordNeedsRehash(string $hash, mixed ...$args): bool
    {
        return (bool) $this->dispatchOptionalFactory('PasswordVerifier', 'rehash', [$hash, ...$args]);
    }

    public function cipher(string $action, mixed ...$args): mixed
    {
        return $this->dispatchOptionalFactory('CipherHandler', $action, $args);
    }

    public function certificate(string $action, mixed ...$args): mixed
    {
        return $this->dispatchOptionalFactory('CertificateHandler', $action, $args);
    }

    public function pki(string $action, mixed ...$args): mixed
    {
        return $this->dispatchOptionalFactory('PKIHandler', $action, $args);
    }

    public function system(string $action, mixed ...$args): mixed
    {
        return $this->dispatchOptionalFactory('SystemHandler', $action, $args);
    }

    public function deriveKey(mixed ...$args): mixed
    {
        if (!$this->methodExists($this->cryptoDriver, 'KeyDerivation')) {
            throw new CryptoException("Crypto driver [{$this->getDriverName()}] does not support key derivation.");
        }

        $callable = $this->cryptoDriver->KeyDerivation();

        if (!$this->isCallable($callable)) {
            throw new CryptoException("Crypto driver [{$this->getDriverName()}] returned an invalid key derivation handler.");
        }

        return $callable(...$args);
    }

    public function getDriver(): CryptoInterface
    {
        return $this->cryptoDriver;
    }

    public function getDriverName(): string
    {
        return $this->cryptoDriver->driverName();
    }

    /**
     * @return array<string, mixed>
     */
    public function capabilities(): array
    {
        return $this->cryptoDriver->capabilities();
    }

    public function supports(string $feature): bool
    {
        return $this->cryptoDriver->supports($feature);
    }

    public function isEnabled(): bool
    {
        $enabled = $this->toLower($this->trimString((string) ($this->cryptoSettings['ENABLED'] ?? 'true')));

        return match ($enabled) {
            '0', 'false', 'off', 'no' => false,
            default => true,
        };
    }

    public function resolveConfiguredCipher(?string $driver = null, ?string $fallback = null): string
    {
        $targetDriver = $this->normalizeDriverName($driver ?? $this->getDriverName());

        $cipher = match ($targetDriver) {
            'openssl' => $this->cryptoSettings['OPENSSL_CIPHER']
                ?? $this->cryptoSettings['OPENSSL']
                ?? $this->cryptoSettings['CIPHER']
                ?? $fallback
                ?? 'AES-256-CBC',
            'sodium' => $this->cryptoSettings['SODIUM_CIPHER']
                ?? $this->cryptoSettings['CIPHER']
                ?? $fallback
                ?? 'secretBox',
            default => $fallback ?? '',
        };

        return $this->trimString((string) $cipher);
    }

    public function resolveConfiguredKey(?string $driver = null): string
    {
        $targetDriver = $this->normalizeDriverName($driver ?? $this->getDriverName());

        $secret = match ($targetDriver) {
            'openssl' => $this->cryptoSettings['OPENSSL_KEY']
                ?? $this->cryptoSettings['KEY']
                ?? '',
            'sodium' => $this->cryptoSettings['SODIUM_KEY']
                ?? $this->cryptoSettings['SODIUM']
                ?? $this->cryptoSettings['KEY']
                ?? '',
            default => $this->cryptoSettings['KEY'] ?? '',
        };

        return $this->decodeConfiguredSecret((string) $secret);
    }

    public function decodeConfiguredSecret(string $secret): string
    {
        $normalized = $this->trimString($secret);

        if ($normalized === '') {
            return '';
        }

        $normalizedLower = $this->toLower($normalized);

        if ($this->startsWith($normalizedLower, 'base64:')) {
            $decoded = $this->base64DecodeString($this->substring($normalized, 7), true);

            if ($decoded === false) {
                throw new CryptoException('Invalid base64-encoded crypto secret.');
            }

            return $decoded;
        }

        if ($this->startsWith($normalizedLower, 'hex:')) {
            $hex = $this->substring($normalized, 4);

            if (!$this->isHexadecimal($hex) || ($this->length($hex) % 2) !== 0) {
                throw new CryptoException('Invalid hexadecimal crypto secret.');
            }

            $decoded = hex2bin($hex);

            if ($decoded === false) {
                throw new CryptoException('Failed to decode hexadecimal crypto secret.');
            }

            return $decoded;
        }

        return $normalized;
    }

    public function ivLength(?string $cipher = null): int
    {
        return (int) $this->cipher('getIvLength', $cipher ?? $this->resolveConfiguredCipher('openssl'));
    }

    public function nonceLength(string $type = 'secretBox'): int
    {
        return match ($this->normalizeDriverName($this->getDriverName())) {
            'sodium' => match ($this->normalizeDriverName($type)) {
                'secretbox', 'box' => 24,
                'stream', 'xchacha20', 'xchacha20streamxor', 'xchacha20streamxoric' => 24,
                default => throw new CryptoException("Unsupported nonce length request: {$type}."),
            },
            default => throw new CryptoException("Crypto driver [{$this->getDriverName()}] does not use nonce lengths."),
        };
    }

    protected function resolveCryptoDriver(): CryptoInterface
    {
        $driver = $this->normalizeDriverName((string) ($this->cryptoSettings['DRIVER'] ?? $this->cryptoSettings['TYPE'] ?? 'openssl'));

        return $this->cryptoProvider->getCryptoDriver([
            'DRIVER' => $driver,
        ]);
    }

    private function dispatchFactory(string $factoryMethod, string $type, array $arguments): mixed
    {
        if (!$this->methodExists($this->cryptoDriver, $factoryMethod)) {
            throw new CryptoException("Crypto driver [{$this->getDriverName()}] does not implement {$factoryMethod}.");
        }

        $callable = $this->cryptoDriver->{$factoryMethod}($type);

        if (!$this->isCallable($callable)) {
            throw new CryptoException("Crypto driver [{$this->getDriverName()}] returned an invalid handler for {$factoryMethod}: {$type}.");
        }

        return $callable(...$arguments);
    }

    private function dispatchOptionalFactory(string $factoryMethod, string $type, array $arguments): mixed
    {
        if (!$this->methodExists($this->cryptoDriver, $factoryMethod)) {
            throw new CryptoException("Crypto driver [{$this->getDriverName()}] does not support {$factoryMethod}.");
        }

        return $this->dispatchFactory($factoryMethod, $type, $arguments);
    }

    private function normalizeCryptoSettings(array $settings): array
    {
        $driver = $this->normalizeDriverName((string) ($settings['DRIVER'] ?? $settings['TYPE'] ?? 'openssl'));
        $settings['DRIVER'] = $driver;
        $settings['TYPE'] = $driver;

        return $settings;
    }

    private function normalizeDriverName(string $driver): string
    {
        return $this->toLower($this->trimString((string) ($this->replaceByPattern('/\s+#.*$/', '', $driver) ?? $driver)));
    }
}
