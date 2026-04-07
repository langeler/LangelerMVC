<?php

namespace App\Abstracts\Data;

use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Finders\FileFinder;
use App\Utilities\Handlers\DataHandler;
use App\Utilities\Handlers\DataStructureHandler;
use App\Utilities\Managers\CompressionManager;
use App\Utilities\Managers\Data\CryptoManager;
use App\Utilities\Managers\DateTimeManager;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\SettingsManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Sanitation\GeneralSanitizer;
use App\Utilities\Sanitation\PatternSanitizer;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ConversionTrait;
use App\Utilities\Traits\ErrorTrait;
use App\Utilities\Traits\LoopTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\MetricsTrait;
use App\Utilities\Validation\PatternValidator;

abstract class Cache
{
    use ErrorTrait,
        ConversionTrait,
        MetricsTrait,
        LoopTrait,
        ManipulationTrait,
        ArrayTrait,
        CheckerTrait {
        ErrorTrait::isNumeric insteadof CheckerTrait;
        ManipulationTrait::repeat insteadof LoopTrait;
        ManipulationTrait::pad insteadof ArrayTrait;
        ManipulationTrait::replace insteadof ArrayTrait;
        ManipulationTrait::reverse insteadof ArrayTrait;
        ManipulationTrait::shuffle insteadof ArrayTrait;
        CheckerTrait::isNumeric as isStringNumeric;
        LoopTrait::repeat as loopRepeat;
        ArrayTrait::pad as arrayPad;
        ArrayTrait::replace as arrayReplace;
        ArrayTrait::reverse as arrayReverse;
        ArrayTrait::shuffle as arrayShuffle;
    }

    protected string $encryptionKey = '';
    protected array $settings = [];
    protected array $cacheData = ['timestamp' => null, 'ttl' => null, 'data' => null];
    protected string $cacheDir;
    protected mixed $cacheQueue;

    public function __construct(
        protected FileManager $fileManager,
        protected CompressionManager $compressionManager,
        protected DataHandler $dataHandler,
        protected CryptoManager $cryptoManager,
        protected DataStructureHandler $dataStructureHandler,
        protected DateTimeManager $dateTimeManager,
        protected SettingsManager $settingsManager,
        protected DirectoryFinder $directoryFinder,
        protected FileFinder $fileFinder,
        protected GeneralSanitizer $sanitizer,
        protected ErrorManager $errorManager,
        protected ?PatternSanitizer $patternSanitizer = null,
        protected ?PatternValidator $patternValidator = null
    ) {
        $this->wrapInTry(function () {
            $this->settings = [
                'cache' => $this->settingsManager->getAllSettings('CACHE'),
                'encryption' => $this->settingsManager->getAllSettings('ENCRYPTION'),
            ];
            $this->encryptionKey = $this->initEncryptionKey();
            $this->cacheQueue = $this->dataStructureHandler->createQueue();
            $this->cacheDir = $this->locateCacheDirectory();
        }, 'cache');
    }

    abstract public function set(string $key, mixed $data, ?int $ttl = null): bool;

    abstract public function get(string $key): mixed;

    abstract public function delete(string $key): bool;

    abstract public function clear(): bool;

    protected function encryptData(string $raw): string
    {
        return $this->wrapInTry(function () use ($raw) {
            return match ($this->settings['encryption']['TYPE'] ?? 'openssl') {
                'openssl' => $this->encryptWithOpenSsl($raw),
                'sodium' => $this->encryptWithSodium($raw),
                default => $this->throwCacheException('Unsupported encryption type.'),
            };
        }, 'cache');
    }

    protected function decryptData(string $cipher): string
    {
        return $this->wrapInTry(function () use ($cipher) {
            return match ($this->settings['encryption']['TYPE'] ?? 'openssl') {
                'openssl' => $this->decryptWithOpenSsl($cipher),
                'sodium' => $this->decryptWithSodium($cipher),
                default => $this->throwCacheException('Unsupported decryption type.'),
            };
        }, 'cache');
    }

    protected function initEncryptionKey(): string
    {
        return $this->wrapInTry(function () {
            return match ($this->settings['encryption']['TYPE'] ?? 'openssl') {
                'openssl' => base64_decode($this->settings['encryption']['KEY'] ?? '', true) ?: '',
                'sodium' => base64_decode($this->settings['encryption']['SODIUM'] ?? '', true) ?: '',
                default => $this->throwCacheException('Invalid encryption key type.'),
            };
        }, 'cache');
    }

    protected function isExpired(int $timestamp, int $ttl): bool
    {
        return $this->wrapInTry(
            fn() => $this->dateTimeManager->getCurrentTimestamp() > ($timestamp + $ttl),
            'cache'
        );
    }

    protected function locateCacheDirectory(): string
    {
        return $this->wrapInTry(function () {
            $directories = $this->directoryFinder->find(['name' => 'Cache']);
            $cacheDirectory = !$this->isEmpty($directories)
                ? array_key_first($directories)
                : null;

            if ($this->isString($cacheDirectory) && $this->isDirectory($cacheDirectory)) {
                return $cacheDirectory;
            }

            $fallback = $this->settings['cache']['FILE'] ?? 'cache';

            if ($this->patternSanitizer && $this->patternValidator) {
                $cleaned = $this->patternSanitizer->sanitizePathUnix((string) $fallback) ?? (string) $fallback;
                $validated = $this->patternValidator->validatePathUnix($cleaned)
                    ? $cleaned
                    : (string) $fallback;

                if (
                    $this->fileManager->isDirectory($validated)
                    || $this->fileManager->createDirectory($validated, 0777, true)
                ) {
                    return $validated;
                }
            }

            $localFallback = (realpath(dirname(__DIR__, 3)) ?: dirname(__DIR__, 3)) . '/Storage/Cache';

            if (
                $this->fileManager->isDirectory($localFallback)
                || $this->fileManager->createDirectory($localFallback, 0777, true)
            ) {
                return $localFallback;
            }

            $tempFallback = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . '/LangelerMVC/Cache';

            if (
                $this->fileManager->isDirectory($tempFallback)
                || $this->fileManager->createDirectory($tempFallback, 0777, true)
            ) {
                return $tempFallback;
            }

            if (
                $this->fileManager->isDirectory((string) $fallback)
                || $this->fileManager->createDirectory((string) $fallback, 0777, true)
            ) {
                return (string) $fallback;
            }

            $this->throwCacheException('Unable to locate or create a writable cache directory.');
        }, 'cache');
    }

    protected function evictIfNeeded(): void
    {
        $this->wrapInTry(function () {
            $limit = $this->settings['cache']['MEMCACHED'] ?? 100;

            while ($this->dataStructureHandler->count($this->cacheQueue) > $limit) {
                $this->delete($this->dataStructureHandler->dequeue($this->cacheQueue));
            }
        }, 'cache');
    }

    protected function saveCacheData(string $key, string $payload): bool
    {
        return $this->wrapInTry(
            fn() => $this->fileManager->writeContents(
                $this->join('/', [$this->cacheDir, "{$key}.cache"]),
                $payload
            ) !== false,
            'cache'
        );
    }

    protected function loadCacheData(string $key): ?string
    {
        return $this->wrapInTry(
            fn() => $this->fileManager->readContents(
                $this->join('/', [$this->cacheDir, "{$key}.cache"])
            ),
            'cache'
        );
    }

    protected function deleteCacheData(string $key): bool
    {
        return $this->wrapInTry(
            fn() => $this->fileManager->deleteFile(
                $this->join('/', [$this->cacheDir, "{$key}.cache"])
            ),
            'cache'
        );
    }

    protected function compressData(string $data): string
    {
        return $this->wrapInTry(function () use ($data) {
            $compressionEnabled = strtolower((string) ($this->settings['cache']['COMPRESSION'] ?? 'false')) === 'true';

            if (!$compressionEnabled || !function_exists('gzcompress')) {
                return $data;
            }

            $compressed = gzcompress($data);

            return $compressed === false ? $data : $compressed;
        }, 'cache');
    }

    protected function decompressData(string $data): string
    {
        return $this->wrapInTry(function () use ($data) {
            $compressionEnabled = strtolower((string) ($this->settings['cache']['COMPRESSION'] ?? 'false')) === 'true';

            if (!$compressionEnabled || !function_exists('gzuncompress')) {
                return $data;
            }

            $decompressed = @gzuncompress($data);

            return $decompressed === false ? $data : $decompressed;
        }, 'cache');
    }

    private function encryptWithOpenSsl(string $raw): string
    {
        $iv = $this->cryptoManager->generateRandom(
            'generateRandomIv',
            $this->settings['encryption']['CIPHER'] ?? 'aes256gcm'
        );
        $cipher = $this->cryptoManager->encrypt(
            'symmetric',
            $raw,
            $this->settings['encryption']['CIPHER'] ?? 'aes256gcm',
            $this->encryptionKey,
            $iv
        );

        return $iv . $cipher;
    }

    private function encryptWithSodium(string $raw): string
    {
        $nonceSize = $this->nonceLenSodium();
        $nonce = $this->cryptoManager->generateRandom('custom', $nonceSize);
        $cipher = $this->cryptoManager->encrypt('secretBox', $raw, $nonce, $this->encryptionKey);

        return $nonce . $cipher;
    }

    private function decryptWithOpenSsl(string $cipher): string
    {
        $ivLen = $this->ivLenOpenssl();
        $iv = $this->substring($cipher, 0, $ivLen);
        $encryptedPart = $this->substring($cipher, $ivLen);

        return $this->cryptoManager->decrypt(
            'symmetric',
            $encryptedPart,
            $this->settings['encryption']['CIPHER'] ?? 'aes256gcm',
            $this->encryptionKey,
            $iv
        );
    }

    private function decryptWithSodium(string $cipher): string
    {
        $nonceSize = $this->nonceLenSodium();
        $nonce = $this->substring($cipher, 0, $nonceSize);
        $encryptedPart = $this->substring($cipher, $nonceSize);

        return $this->cryptoManager->decrypt('secretBox', $encryptedPart, $nonce, $this->encryptionKey);
    }

    private function ivLenOpenssl(): int
    {
        return $this->wrapInTry(
            fn() => $this->cryptoManager->cryptoDriver->CipherHandler('getIvLength')(
                $this->settings['encryption']['CIPHER'] ?? 'aes256gcm'
            ),
            'cache'
        );
    }

    private function nonceLenSodium(): int
    {
        return 24;
    }

    private function throwCacheException(string $message): never
    {
        $this->errorManager->logErrorMessage($message, __FILE__, __LINE__, 'userError', 'cache');

        throw $this->errorManager->resolveException('cache', $message);
    }
}
