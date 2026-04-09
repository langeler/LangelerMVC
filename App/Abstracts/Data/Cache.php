<?php

declare(strict_types=1);

namespace App\Abstracts\Data;

use App\Contracts\Data\CacheDriverInterface;
use App\Utilities\Handlers\DataHandler;
use App\Utilities\Managers\Data\CryptoManager;
use App\Utilities\Managers\DateTimeManager;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\SettingsManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Traits\ApplicationPathTrait;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ConversionTrait;
use App\Utilities\Traits\EncodingTrait;
use App\Utilities\Traits\ErrorTrait;
use App\Utilities\Traits\ExistenceCheckerTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;
use App\Utilities\Traits\Patterns\PatternTrait;

abstract class Cache implements CacheDriverInterface
{
    use ErrorTrait;
    use ApplicationPathTrait;
    use ArrayTrait;
    use CheckerTrait;
    use ConversionTrait;
    use EncodingTrait;
    use ExistenceCheckerTrait;
    use ManipulationTrait;
    use PatternTrait {
        PatternTrait::match as private matchPattern;
    }
    use TypeCheckerTrait;

    protected array $settings = [];
    protected string $cacheDir = '';
    protected ?string $encryptionKey = null;

    public function __construct(
        protected FileManager $fileManager,
        protected DataHandler $dataHandler,
        protected CryptoManager $cryptoManager,
        protected DateTimeManager $dateTimeManager,
        protected SettingsManager $settingsManager,
        protected ErrorManager $errorManager
    ) {
        $this->wrapInTry(function (): void {
            $this->settings = [
                'cache' => $this->settingsManager->getAllSettings('CACHE'),
                'encryption' => $this->settingsManager->getAllSettings('ENCRYPTION'),
            ];

            if ($this->usesFilesystem()) {
                $this->cacheDir = $this->locateCacheDirectory();
            }
        }, 'cache');
    }

    abstract public function driverName(): string;

    /**
     * @return array<string, mixed>
     */
    abstract public function capabilities(): array;

    abstract protected function putRaw(string $storageKey, string $payload, ?int $ttl = null): bool;

    abstract protected function getRaw(string $storageKey): ?string;

    abstract protected function deleteRaw(string $storageKey): bool;

    /**
     * Discover known storage keys and their timestamps when the driver can do so
     * without relying on the internal index entry.
     *
     * @return array<string, int>
     */
    protected function discoverStoredEntries(): array
    {
        return [];
    }

    public function supports(string $feature): bool
    {
        $resolved = $this->resolveCapability($feature);

        return match (true) {
            $this->isBool($resolved) => $resolved,
            $this->isArray($resolved) => !$this->isEmpty($resolved),
            $this->isString($resolved) => $resolved !== '',
            default => $resolved !== null,
        };
    }

    public function set(string $key, mixed $data, ?int $ttl = null): bool
    {
        if (!$this->isCacheEnabled()) {
            return true;
        }

        return $this->wrapInTry(function () use ($key, $data, $ttl): bool {
            $normalizedKey = $this->normalizeKey($key);
            $storageKey = $this->storageKey($normalizedKey);
            $effectiveTtl = $this->normalizeTtl($ttl);
            $payload = $this->encodeCachePayload($data, $effectiveTtl, $storageKey);

            if (!$this->putRaw($storageKey, $payload, $effectiveTtl)) {
                $this->throwCacheException("Failed to write cache entry for key: {$key}");
            }

            $this->trackStoredKey(
                $storageKey,
                $this->extractPayloadTimestamp($payload) ?? $this->dateTimeManager->getCurrentTimestamp()
            );
            $this->pruneIfNeeded();

            return true;
        }, 'cache');
    }

    public function get(string $key): mixed
    {
        if (!$this->isCacheEnabled()) {
            return null;
        }

        return $this->wrapInTry(function () use ($key): mixed {
            $normalizedKey = $this->normalizeKey($key);
            $storageKey = $this->storageKey($normalizedKey);
            $payload = $this->getRaw($storageKey);

            if (!$this->isString($payload) || $payload === '') {
                $this->untrackStoredKey($storageKey);

                return null;
            }

            try {
                $timestamp = $this->extractPayloadTimestamp($payload);

                if ($timestamp !== null) {
                    $this->trackStoredKey($storageKey, $timestamp);
                }

                return $this->decodeCachePayload(
                    $payload,
                    $storageKey,
                    fn(string $expiredKey): bool => $this->purgeStorageKey($expiredKey)
                );
            } catch (\Throwable) {
                $this->purgeStorageKey($storageKey);

                return null;
            }
        }, 'cache');
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function delete(string $key): bool
    {
        if (!$this->isCacheEnabled()) {
            return true;
        }

        return $this->wrapInTry(
            fn(): bool => $this->purgeStorageKey($this->storageKey($this->normalizeKey($key))),
            'cache'
        );
    }

    public function clear(): bool
    {
        if (!$this->isCacheEnabled()) {
            return true;
        }

        return $this->wrapInTry(function (): bool {
            $success = true;

            foreach ($this->getKeys($this->storedEntries()) as $storageKey) {
                $resolvedKey = (string) $storageKey;

                if ($this->isReservedStorageKey($resolvedKey)) {
                    continue;
                }

                $success = $this->deleteRaw($resolvedKey) && $success;
            }

            $this->deleteRaw($this->indexStorageKey());

            return $success;
        }, 'cache');
    }

    protected function usesFilesystem(): bool
    {
        return false;
    }

    protected function isCacheEnabled(): bool
    {
        return $this->normalizeBoolSetting($this->cacheSetting('ENABLED', true), true);
    }

    protected function shouldEncrypt(): bool
    {
        return $this->normalizeBoolSetting($this->cacheSetting('ENCRYPT', false), false)
            && $this->cryptoManager->isEnabled();
    }

    protected function shouldCompress(): bool
    {
        return $this->normalizeBoolSetting($this->cacheSetting('COMPRESSION', true), true);
    }

    protected function defaultTtl(): int
    {
        $ttl = $this->toInt($this->cacheSetting('TTL', 3600));

        return $ttl > 0 ? $ttl : 0;
    }

    protected function maxItems(): int
    {
        $configured = $this->cacheSetting(
            'MAX_ITEMS',
            $this->cacheSetting('MEMCACHED', 0)
        );

        $value = $this->toInt($configured);

        return $value > 0 ? $value : 0;
    }

    protected function cacheTable(): string
    {
        $table = $this->cleanSettingString(
            $this->cacheSetting(
                'TABLE',
                $this->cacheSetting('DATABASE_TABLE', 'cache')
            )
        );

        if ($table === '' || $this->matchPattern('/^[A-Za-z_][A-Za-z0-9_]*$/', $table) !== 1) {
            $this->throwCacheException("Invalid cache table name: {$table}");
        }

        return $table;
    }

    protected function cachePrefix(): string
    {
        $prefix = $this->cleanSettingString($this->cacheSetting('PREFIX', 'langelermvc_cache'));
        $normalized = (string) ($this->replaceByPattern('/[^A-Za-z0-9_.:-]+/', '_', $prefix) ?? $prefix);
        $normalized = $this->trimString($normalized, '._:- ');

        return $normalized !== '' ? $normalized : 'langelermvc_cache';
    }

    protected function cacheSetting(string $key, mixed $default = null): mixed
    {
        return $this->keyExists($this->settings['cache'] ?? [], $key)
            ? $this->settings['cache'][$key]
            : $default;
    }

    protected function locateCacheDirectory(): string
    {
        return $this->wrapInTry(function (): string {
            $candidates = $this->unique([
                $this->cleanSettingString($this->cacheSetting('FILE', '')),
                $this->frameworkStoragePath('Cache'),
                $this->trimRight(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'LangelerMVC' . DIRECTORY_SEPARATOR . 'Cache',
            ]);

            foreach ($candidates as $candidate) {
                if (!$this->isString($candidate) || $candidate === '') {
                    continue;
                }

                $normalized = $this->fileManager->normalizePath($candidate);

                if (
                    $normalized !== ''
                    && (
                        $this->fileManager->isDirectory($normalized)
                        || $this->fileManager->createDirectory($normalized, 0777, true)
                    )
                ) {
                    return $normalized;
                }
            }

            $this->throwCacheException('Unable to locate or create a writable cache directory.');
        }, 'cache');
    }

    protected function encodeCachePayload(mixed $data, ?int $ttl = null, ?string $storageKey = null): string
    {
        return $this->wrapInTry(function () use ($data, $ttl, $storageKey): string {
            $serialized = $this->serializeCacheValue($data);

            if ($this->shouldCompress()) {
                $serialized = $this->compressData($serialized);
            }

            if ($this->shouldEncrypt()) {
                $serialized = $this->encryptData($serialized);
            }

            return $this->dataHandler->jsonEncode([
                'key' => $storageKey,
                'timestamp' => $this->dateTimeManager->getCurrentTimestamp(),
                'ttl' => $this->normalizeTtl($ttl),
                'serialization' => $this->serializer(),
                'compressed' => $this->shouldCompress(),
                'encrypted' => $this->shouldEncrypt(),
                'data' => $this->base64EncodeString($serialized),
            ], JSON_THROW_ON_ERROR);
        }, 'cache');
    }

    protected function decodeCachePayload(string $payload, string $key, ?callable $expireCallback = null): mixed
    {
        return $this->wrapInTry(function () use ($payload, $key, $expireCallback): mixed {
            $record = $this->decodePayloadRecord($payload, $key);
            $timestamp = $this->toInt($record['timestamp'] ?? 0);
            $ttl = $this->normalizeTtl($record['ttl'] ?? $this->defaultTtl());

            if ($this->isExpired($timestamp, $ttl)) {
                if ($this->isCallable($expireCallback)) {
                    $expireCallback($key);
                }

                return null;
            }

            $decoded = $this->decodePayloadData(
                (string) ($record['data'] ?? ''),
                $key,
                $this->normalizeBoolSetting($record['encrypted'] ?? $this->shouldEncrypt(), $this->shouldEncrypt()),
                $this->normalizeBoolSetting($record['compressed'] ?? $this->shouldCompress(), $this->shouldCompress())
            );
            $serializer = $this->normalizeSerializer((string) ($record['serialization'] ?? $this->serializer()));

            return $this->unserializeCacheValue($decoded, $serializer);
        }, 'cache');
    }

    protected function isExpired(int $timestamp, int $ttl): bool
    {
        if ($ttl <= 0) {
            return false;
        }

        return $this->dateTimeManager->getCurrentTimestamp() > ($timestamp + $ttl);
    }

    protected function compressData(string $data): string
    {
        return $this->wrapInTry(function () use ($data): string {
            $compressed = gzcompress($data);

            return $compressed === false ? $data : $compressed;
        }, 'cache');
    }

    protected function decompressData(string $data): string
    {
        return $this->wrapInTry(function () use ($data): string {
            $decompressed = @gzuncompress($data);

            return $decompressed === false ? $data : $decompressed;
        }, 'cache');
    }

    protected function encryptData(string $raw): string
    {
        return $this->wrapInTry(function () use ($raw): string {
            $encryptionKey = $this->resolveEncryptionKey();

            return match ($this->cryptoManager->getDriverName()) {
                'openssl' => $this->encryptWithOpenSsl($raw, $encryptionKey),
                'sodium' => $this->encryptWithSodium($raw, $encryptionKey),
                default => $this->throwCacheException('Unsupported encryption driver for cache.'),
            };
        }, 'cache');
    }

    protected function decryptData(string $cipher): string
    {
        return $this->wrapInTry(function () use ($cipher): string {
            $encryptionKey = $this->resolveEncryptionKey();

            return match ($this->cryptoManager->getDriverName()) {
                'openssl' => $this->decryptWithOpenSsl($cipher, $encryptionKey),
                'sodium' => $this->decryptWithSodium($cipher, $encryptionKey),
                default => $this->throwCacheException('Unsupported decryption driver for cache.'),
            };
        }, 'cache');
    }

    protected function serializeCacheValue(mixed $data): string
    {
        return match ($this->serializer()) {
            'php' => $this->dataHandler->serializeData($data),
            'json' => $this->dataHandler->jsonEncode($data, JSON_THROW_ON_ERROR),
            'igbinary' => $this->serializeWithIgbinary($data),
            default => $this->throwCacheException('Unsupported cache serialization strategy.'),
        };
    }

    protected function unserializeCacheValue(string $payload, string $serializer): mixed
    {
        return match ($serializer) {
            'php' => $this->dataHandler->unserializeData($payload, true),
            'json' => $this->dataHandler->jsonDecode($payload, true, 512, JSON_THROW_ON_ERROR),
            'igbinary' => $this->unserializeWithIgbinary($payload),
            default => $this->throwCacheException('Unsupported cache serialization strategy.'),
        };
    }

    protected function serializer(): string
    {
        return $this->normalizeSerializer((string) $this->cacheSetting('SERIALIZATION', 'php'));
    }

    protected function throwCacheException(string $message): never
    {
        $this->errorManager->logErrorMessage($message, __FILE__, __LINE__, 'userError', 'cache');

        throw $this->errorManager->resolveException('cache', $message);
    }

    protected function resolveEncryptionKey(): string
    {
        if ($this->encryptionKey !== null && $this->encryptionKey !== '') {
            return $this->encryptionKey;
        }

        $resolved = $this->wrapInTry(
            fn(): string => $this->cryptoManager->resolveConfiguredKey(),
            'cache'
        );

        if ($resolved === '') {
            $this->throwCacheException('Cache encryption is enabled, but no encryption key is configured.');
        }

        return $this->encryptionKey = $resolved;
    }

    /**
     * @return array<string, int>
     */
    protected function storedEntries(): array
    {
        $indexed = $this->readIndexEntries();

        return $indexed !== []
            ? $indexed
            : $this->discoverStoredEntries();
    }

    protected function trackStoredKey(string $storageKey, int $timestamp): void
    {
        if ($this->isReservedStorageKey($storageKey)) {
            return;
        }

        $entries = $this->readIndexEntries();
        $entries[$storageKey] = $timestamp;
        $this->writeIndexEntries($entries);
    }

    protected function untrackStoredKey(string $storageKey): void
    {
        if ($this->isReservedStorageKey($storageKey)) {
            return;
        }

        $entries = $this->readIndexEntries();

        if (!$this->keyExists($entries, $storageKey)) {
            return;
        }

        unset($entries[$storageKey]);
        $this->writeIndexEntries($entries);
    }

    protected function storageKey(string $key): string
    {
        return $this->cachePrefix() . ':' . hash('sha256', $this->cachePrefix() . '|' . $key);
    }

    protected function normalizeKey(string $key): string
    {
        $normalized = $this->trimString($key);

        if ($normalized === '') {
            $this->throwCacheException('Cache key must be a non-empty string.');
        }

        if ($this->matchPattern('/[\x00-\x1F\x7F]/', $normalized) === 1) {
            $this->throwCacheException("Cache key contains invalid control characters: {$key}");
        }

        return $normalized;
    }

    protected function purgeStorageKey(string $storageKey): bool
    {
        $deleted = $this->deleteRaw($storageKey);
        $this->untrackStoredKey($storageKey);

        return $deleted;
    }

    private function encodeIndexPayload(array $entries): string
    {
        return $this->dataHandler->jsonEncode([
            'version' => 1,
            'entries' => $entries,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, int>
     */
    private function readIndexEntries(): array
    {
        $payload = $this->getRaw($this->indexStorageKey());

        if (!$this->isString($payload) || $payload === '') {
            return [];
        }

        try {
            $decoded = $this->dataHandler->jsonDecode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }

        if (!$this->isArray($decoded) || !$this->isArray($decoded['entries'] ?? null)) {
            return [];
        }

        $entries = [];

        foreach ($decoded['entries'] as $storageKey => $timestamp) {
            if (!$this->isString($storageKey) || $storageKey === '' || $this->isReservedStorageKey($storageKey)) {
                continue;
            }

            $entries[$storageKey] = $this->toInt($timestamp);
        }

        return $entries;
    }

    /**
     * @param array<string, int> $entries
     */
    private function writeIndexEntries(array $entries): void
    {
        if ($entries === []) {
            $this->deleteRaw($this->indexStorageKey());

            return;
        }

        if (!$this->putRaw($this->indexStorageKey(), $this->encodeIndexPayload($entries), 0)) {
            $this->throwCacheException('Failed to update the cache index.');
        }
    }

    private function pruneIfNeeded(): void
    {
        $limit = $this->maxItems();

        if ($limit <= 0) {
            return;
        }

        $entries = $this->storedEntries();

        if ($this->countElements($entries) <= $limit) {
            return;
        }

        asort($entries);
        $overflow = $this->countElements($entries) - $limit;

        foreach ($this->slice($this->getKeys($entries), 0, $overflow) as $storageKey) {
            if ($this->isString($storageKey) && !$this->isReservedStorageKey($storageKey)) {
                $this->purgeStorageKey($storageKey);
            }
        }
    }

    private function decodePayloadRecord(string $payload, string $key): array
    {
        $record = $this->dataHandler->jsonDecode($payload, true, 512, JSON_THROW_ON_ERROR);

        if (!$this->isArray($record)) {
            $this->throwCacheException("Invalid cache payload for key: {$key}");
        }

        return $record;
    }

    private function decodePayloadData(string $encoded, string $key, bool $isEncrypted, bool $isCompressed): string
    {
        $decoded = $this->base64DecodeString($encoded, true);

        if ($decoded === false) {
            $this->throwCacheException("Invalid base64 cache payload for key: {$key}");
        }

        if ($isEncrypted) {
            $decoded = $this->decryptData($decoded);
        }

        if ($isCompressed) {
            $decoded = $this->decompressData($decoded);
        }

        return $decoded;
    }

    private function extractPayloadTimestamp(string $payload): ?int
    {
        try {
            $decoded = $this->dataHandler->jsonDecode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        if (!$this->isArray($decoded)) {
            return null;
        }

        return $this->keyExists($decoded, 'timestamp')
            ? $this->toInt($decoded['timestamp'])
            : null;
    }

    private function indexStorageKey(): string
    {
        return $this->cachePrefix() . ':__index__';
    }

    private function isReservedStorageKey(string $storageKey): bool
    {
        return $storageKey === $this->indexStorageKey();
    }

    private function encryptWithOpenSsl(string $raw, string $encryptionKey): string
    {
        $cipherMethod = $this->cryptoManager->resolveConfiguredCipher('openssl');
        $iv = $this->cryptoManager->generateRandom('generateRandomIv', $cipherMethod);
        $cipher = $this->cryptoManager->encrypt(
            'symmetric',
            $raw,
            $cipherMethod,
            $encryptionKey,
            $iv
        );

        return $iv . $cipher;
    }

    private function encryptWithSodium(string $raw, string $encryptionKey): string
    {
        $nonceSize = $this->cryptoManager->nonceLength('secretBox');
        $nonce = $this->cryptoManager->generateRandom('custom', $nonceSize);
        $cipher = $this->cryptoManager->encrypt('secretBox', $raw, $nonce, $encryptionKey);

        return $nonce . $cipher;
    }

    private function decryptWithOpenSsl(string $cipher, string $encryptionKey): string
    {
        $cipherMethod = $this->cryptoManager->resolveConfiguredCipher('openssl');
        $ivLen = $this->cryptoManager->ivLength($cipherMethod);
        $iv = $this->substring($cipher, 0, $ivLen);
        $encryptedPart = $this->substring($cipher, $ivLen);

        return $this->cryptoManager->decrypt(
            'symmetric',
            $encryptedPart,
            $cipherMethod,
            $encryptionKey,
            $iv
        );
    }

    private function decryptWithSodium(string $cipher, string $encryptionKey): string
    {
        $nonceSize = $this->cryptoManager->nonceLength('secretBox');
        $nonce = $this->substring($cipher, 0, $nonceSize);
        $encryptedPart = $this->substring($cipher, $nonceSize);

        return $this->cryptoManager->decrypt('secretBox', $encryptedPart, $nonce, $encryptionKey);
    }

    private function normalizeBoolSetting(mixed $value, bool $default): bool
    {
        if ($this->isBool($value)) {
            return $value;
        }

        if ($this->isInt($value) || $this->isFloat($value)) {
            return (int) $value !== 0;
        }

        if (!$this->isString($value)) {
            return $default;
        }

        return match ($this->toLower($this->cleanSettingString($value))) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off', '' => false,
            default => $default,
        };
    }

    private function normalizeTtl(mixed $ttl): int
    {
        if ($ttl === null || $ttl === '') {
            return $this->defaultTtl();
        }

        $normalized = $this->toInt($ttl);

        return $normalized > 0 ? $normalized : 0;
    }

    private function normalizeSerializer(string $serializer): string
    {
        return match ($this->toLower($this->cleanSettingString($serializer))) {
            '', 'php' => 'php',
            'json' => 'json',
            'igbinary' => 'igbinary',
            default => $this->cleanSettingString($serializer),
        };
    }

    private function cleanSettingString(mixed $value): string
    {
        if (!$this->isString($value) && !$this->isInt($value) && !$this->isFloat($value) && !$this->isBool($value)) {
            return '';
        }

        $stringValue = (string) $value;
        $withoutComment = (string) ($this->replaceByPattern('/\s+#.*$/', '', $stringValue) ?? $stringValue);

        return $this->trimString($withoutComment);
    }

    private function serializeWithIgbinary(mixed $data): string
    {
        if (!$this->functionExists('igbinary_serialize')) {
            $this->throwCacheException('igbinary serialization is unavailable on this runtime.');
        }

        return igbinary_serialize($data);
    }

    private function unserializeWithIgbinary(string $payload): mixed
    {
        if (!$this->functionExists('igbinary_unserialize')) {
            $this->throwCacheException('igbinary unserialization is unavailable on this runtime.');
        }

        return igbinary_unserialize($payload);
    }

    private function resolveCapability(string $feature): mixed
    {
        $normalized = $this->toLower($this->trimString($feature));

        if ($normalized === '') {
            return null;
        }

        $value = $this->capabilities();

        foreach ($this->splitString('.', $normalized) as $segment) {
            if (!$this->isArray($value) || !$this->keyExists($value, $segment)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
