<?php

declare(strict_types=1);

namespace App\Drivers\Session;

use App\Contracts\Session\SessionDriverInterface;
use App\Exceptions\SessionException;
use App\Utilities\Managers\Data\CryptoManager;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\EncodingTrait;
use App\Utilities\Traits\ManipulationTrait;
use SessionHandler;

/**
 * Wraps any session handler and encrypts payloads before they are persisted.
 *
 * The wrapper stores encrypted payloads with a versioned prefix so the framework can:
 * - distinguish encrypted payloads from legacy plaintext sessions
 * - keep backward compatibility for existing unencrypted sessions
 * - invalidate unreadable/corrupt encrypted payloads safely without crashing session start
 */
class EncryptedSessionDriver extends SessionHandler implements SessionDriverInterface
{
    use CheckerTrait {
        CheckerTrait::startsWith as private startsWithString;
    }
    use EncodingTrait;
    use ManipulationTrait {
        ManipulationTrait::substring as private substringString;
        ManipulationTrait::toLower as private toLowerString;
    }

    private const PAYLOAD_PREFIX = 'lgx:v1:';

    public function __construct(
        private readonly SessionHandler $handler,
        private readonly CryptoManager $cryptoManager
    ) {
    }

    public function driverName(): string
    {
        return $this->handler instanceof SessionDriverInterface
            ? $this->handler->driverName()
            : 'native';
    }

    public function capabilities(): array
    {
        $base = $this->handler instanceof SessionDriverInterface
            ? $this->handler->capabilities()
            : [
                'extension' => true,
                'persistent' => true,
            ];

        $base['encrypted'] = true;
        $base['payload_version'] = 'v1';

        return $base;
    }

    public function supports(string $feature): bool
    {
        if ($feature === 'encrypted') {
            return true;
        }

        return $this->handler instanceof SessionDriverInterface
            ? $this->handler->supports($feature)
            : (($this->capabilities()[$feature] ?? null) === true);
    }

    public function open(string $savePath, string $sessionName): bool
    {
        return $this->handler->open($savePath, $sessionName);
    }

    public function close(): bool
    {
        return $this->handler->close();
    }

    public function read(string $id): string|false
    {
        $payload = $this->handler->read($id);

        if ($payload === false || $payload === '') {
            return '';
        }

        return $this->decryptPayload((string) $payload);
    }

    public function write(string $id, string $data): bool
    {
        return $this->handler->write($id, $this->encryptPayload($data));
    }

    public function destroy(string $id): bool
    {
        return $this->handler->destroy($id);
    }

    public function gc(int $max_lifetime): int|false
    {
        return $this->handler->gc($max_lifetime);
    }

    private function encryptPayload(string $payload): string
    {
        if ($payload === '') {
            return '';
        }

        $driver = $this->toLowerString($this->cryptoManager->getDriverName());
        $key = $this->cryptoManager->resolveConfiguredKey($driver);

        if ($key === '') {
            throw new SessionException('Session encryption requires a configured crypto key.');
        }

        if ($driver === 'sodium') {
            $nonceLength = $this->cryptoManager->nonceLength('secretBox');
            $nonce = $this->cryptoManager->generateRandom('custom', $nonceLength);
            $cipher = $this->cryptoManager->encrypt('secretBox', $payload, $nonce, $key);

            return self::PAYLOAD_PREFIX . $this->base64EncodeString($nonce . $cipher);
        }

        $cipherMethod = $this->cryptoManager->resolveConfiguredCipher('openssl');
        $iv = $this->cryptoManager->generateRandom('generateRandomIv', $cipherMethod);
        $cipher = $this->cryptoManager->encrypt('symmetric', $payload, $cipherMethod, $key, $iv);

        return self::PAYLOAD_PREFIX . $this->base64EncodeString($iv . $cipher);
    }

    private function decryptPayload(string $payload): string
    {
        if (!$this->startsWithString($payload, self::PAYLOAD_PREFIX)) {
            return $payload;
        }

        $encoded = $this->substringString($payload, strlen(self::PAYLOAD_PREFIX));
        $raw = $this->base64DecodeString($encoded, true);

        if ($raw === false || $raw === '') {
            return '';
        }

        $driver = $this->toLowerString($this->cryptoManager->getDriverName());
        $key = $this->cryptoManager->resolveConfiguredKey($driver);

        if ($key === '') {
            return '';
        }

        try {
            if ($driver === 'sodium') {
                $nonceLength = $this->cryptoManager->nonceLength('secretBox');
                $nonce = $this->substringString($raw, 0, $nonceLength);
                $cipher = $this->substringString($raw, $nonceLength);

                return $this->cryptoManager->decrypt('secretBox', $cipher, $nonce, $key);
            }

            $cipherMethod = $this->cryptoManager->resolveConfiguredCipher('openssl');
            $ivLength = $this->cryptoManager->ivLength($cipherMethod);
            $iv = $this->substringString($raw, 0, $ivLength);
            $cipher = $this->substringString($raw, $ivLength);

            return $this->cryptoManager->decrypt('symmetric', $cipher, $cipherMethod, $key, $iv);
        } catch (\Throwable) {
            return '';
        }
    }
}
