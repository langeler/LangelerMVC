<?php

namespace App\Abstracts\Data;

use App\Managers\CryptoManager;                        // Manages cryptographic operations.
use App\Helpers\TypeChecker;                           // Provides utility methods for type validation.

use App\Utilities\Finders\{
    DirectoryFinder, // Handles searching and managing directories.
    FileFinder       // Handles searching and managing files.
};

use App\Utilities\Handlers\{
    DataHandler,             // Processes and manipulates data structures.
    DataStructureHandler     // Manages complex data structures and transformations.
};

use App\Utilities\Managers\{
    CompressionManager,      // Handles data compression tasks.
    FileManager,             // Manages file operations and configurations.
    SettingsManager,         // Handles configuration and application settings.
    System\ErrorManager,     // Manages errors and exceptions system-wide.
    DateTimeManager          // Provides utilities for handling and manipulating date and time.
};

use App\Utilities\Sanitation\{
    GeneralSanitizer,        // Provides general data sanitation utilities.
    PatternSanitizer         // Facilitates pattern-based data sanitation.
};

use App\Utilities\Traits\{
    ArrayTrait,              // Provides utility methods for array operations.
    CheckerTrait,            // Offers validation methods for data integrity.
    ConversionTrait,         // Facilitates data type and format conversions.
    ErrorTrait,              // Handles exception wrapping and error transformations.
    ExistenceCheckerTrait,   // Verifies existence of classes, methods, and properties.
    LoopTrait,               // Provides utilities for iterating over data structures.
    ManipulationTrait,       // Adds support for data manipulation tasks.
    MetricsTrait,            // Provides methods for calculating and analyzing data metrics.
    TypeCheckerTrait         // Validates and ensures correct data types.
};

use App\Utilities\Validation\PatternValidator;         // Facilitates pattern-based data validation.

/**
 * Abstract Cache Class
 *
 * Provides a base caching system that can optionally encrypt/decrypt data using CryptoManager
 * (which unifies both SodiumCrypto and OpenSSLCrypto drivers). Configuration is loaded from
 * SettingsManager under:
 *   'CACHE' => [ 'FILE' => '/path/to/cacheDir', 'MEMCACHED' => 100, ... ]
 *   'ENCRYPTION' => [ 'TYPE' => 'openssl'|'sodium', 'CIPHER' => 'aes256gcm', 'KEY' => <base64>, 'SODIUM' => <base64>, ... ]
 */
abstract class Cache
{
    use ErrorTrait,
        CheckerTrait,
        TypeCheckerTrait,
        ConversionTrait,
        ExistenceCheckerTrait,
        ManipulationTrait,
        MetricsTrait,
        ArrayTrait,
        LoopTrait;

    protected string $encryptionKey = '';
    protected array $settings = [];
    protected array $cacheData = ['timestamp' => null, 'ttl' => null, 'data' => null];
    protected string $cacheDir;
    protected mixed $cacheQueue;

    public function __construct(
        protected TypeChecker $typeChecker,
        protected FileManager $fileManager,
        protected CompressionManager $compressionManager,
        protected DataHandler $dataHandler,
        protected CryptoManager $cryptoManager, // Use CryptoManager instead of old CryptoHandler
        protected DataStructureHandler $dataStructureHandler,
        protected DateTimeManager $dateTimeManager,
        protected SettingsManager $settingsManager,
        protected DirectoryFinder $directoryFinder,
        protected FileFinder $fileFinder,
        protected GeneralSanitizer $sanitizer,
        protected ?PatternSanitizer $patternSanitizer = null,
        protected ?PatternValidator $patternValidator = null,
        protected ErrorManager $errorManager
    ) {
        $this->wrapInTry(
            fn() => (
                $this->settings = [
                    'cache'      => $this->settingsManager->getAllSettings('CACHE'),
                    'encryption' => $this->settingsManager->getAllSettings('ENCRYPTION')
                ],
                $this->encryptionKey = $this->initEncryptionKey(),
                $this->cacheQueue = $this->dataStructureHandler->createQueue(),
                $this->cacheDir = $this->locateCacheDirectory()
            ),
            'cache'
        );
    }

    /**
     * Store an item in the cache.
     */
    abstract public function set(string $key, mixed $data, ?int $ttl = null): bool;

    /**
     * Retrieve an item from the cache.
     */
    abstract public function get(string $key): mixed;

    /**
     * Delete an item from the cache.
     */
    abstract public function delete(string $key): bool;

    /**
     * Clear the entire cache.
     */
    abstract public function clear(): bool;

    /**
     * Encrypt data depending on 'TYPE' => 'openssl' or 'sodium'.
     * For OpenSSL, a random IV is prepended to the final ciphertext.
     * For Sodium, a random nonce is prepended to the final ciphertext.
     */
    protected function encryptData(string $raw): string
    {
        return $this->wrapInTry(
            fn() => match ($this->settings['encryption']['TYPE'] ?? 'openssl') {
                'openssl' => (
                    $iv = $this->cryptoManager->generateRandom(
                        'generateRandomIv',
                        $this->settings['encryption']['CIPHER'] ?? 'aes256gcm'
                    ),
                    $cipher = $this->cryptoManager->encrypt(
                        'symmetric',
                        $raw,
                        $this->settings['encryption']['CIPHER'] ?? 'aes256gcm',
                        $this->encryptionKey,
                        $iv
                    ),
                    $iv . $cipher // Prepend IV
                ),
                'sodium' => (
                    $nonceSize = $this->nonceLenSodium(),
                    $nonce = $this->cryptoManager->generateRandom('custom', $nonceSize),
                    $cipher = $this->cryptoManager->encrypt('secretBox', $raw, $nonce, $this->encryptionKey),
                    $nonce . $cipher // Prepend nonce
                ),
                default => (
                    $this->errorManager->logErrorMessage("Unsupported encryption type.", __FILE__, __LINE__, 'userError', 'cache'),
                    throw $this->errorManager->resolveException('cache', "Unsupported encryption type.")
                )
            },
            'cache'
        );
    }

    /**
     * Decrypt data depending on 'TYPE' => 'openssl' or 'sodium'.
     * For OpenSSL, parse out the IV from the front of the cipher.
     * For Sodium, parse out the nonce from the front of the cipher.
     */
    protected function decryptData(string $cipher): string
    {
        return $this->wrapInTry(
            fn() => match ($this->settings['encryption']['TYPE'] ?? 'openssl') {
                'openssl' => (
                    $ivLen = $this->ivLenOpenssl(),
                    $iv = $this->substring($cipher, 0, $ivLen),
                    $encPart = $this->substring($cipher, $ivLen),
                    $this->cryptoManager->decrypt(
                        'symmetric',
                        $encPart,
                        $this->settings['encryption']['CIPHER'] ?? 'aes256gcm',
                        $this->encryptionKey,
                        $iv
                    )
                ),
                'sodium' => (
                    $nonceSize = $this->nonceLenSodium(),
                    $nonce = $this->substring($cipher, 0, $nonceSize),
                    $encPart = $this->substring($cipher, $nonceSize),
                    $this->cryptoManager->decrypt('secretBox', $encPart, $nonce, $this->encryptionKey)
                ),
                default => (
                    $this->errorManager->logErrorMessage("Unsupported decryption type.", __FILE__, __LINE__, 'userError', 'cache'),
                    throw $this->errorManager->resolveException('cache', "Unsupported decryption type.")
                )
            },
            'cache'
        );
    }

    /**
     * Initialize the encryption key from SettingsManager config.
     */
    protected function initEncryptionKey(): string
    {
        return $this->wrapInTry(
            fn() => match ($this->settings['encryption']['TYPE'] ?? 'openssl') {
                'openssl' => base64_decode($this->settings['encryption']['KEY'] ?? ''),
                'sodium'  => base64_decode($this->settings['encryption']['SODIUM'] ?? ''),
                default   => (
                    $this->errorManager->logErrorMessage("Invalid encryption key type.", __FILE__, __LINE__, 'userError', 'cache'),
                    throw $this->errorManager->resolveException('cache', "Invalid encryption key type.")
                )
            },
            'cache'
        );
    }

    /**
     * Check if a cache entry is expired given a timestamp + TTL.
     */
    protected function isExpired(int $ts, int $ttl): bool
    {
        return $this->wrapInTry(
            fn() => $this->dateTimeManager->getCurrentTimestamp() > ($ts + $ttl),
            'cache'
        );
    }

    /**
     * Locate or create the cache directory, optionally validating via PatternSanitizer + PatternValidator.
     */
    protected function locateCacheDirectory(): string
    {
        return $this->wrapInTry(
            fn() => (
                $dirs = $this->directoryFinder->find(['name' => 'Cache']),
                !$this->isEmpty($dirs) && $this->typeChecker->isDirectory($dirs[0])
                    ? $dirs[0]
                    : (
                        $fallback = $this->settings['cache']['FILE'] ?? 'cache',
                        $this->patternSanitizer && $this->patternValidator
                            ? (
                                $cleaned = $this->patternSanitizer->clean(['p' => ['pathUnix']], ['p' => $fallback])['p'],
                                $valid = $this->patternValidator->verify(['pp' => ['pathUnix']], ['pp' => $cleaned])['pp'],
                                $this->fileManager->createDirectory($valid, 0777, true),
                                $valid
                            )
                            : (
                                $this->fileManager->createDirectory($fallback, 0777, true),
                                $fallback
                            )
                    )
            ),
            'cache'
        );
    }

    /**
     * Evict items from the queue if over the limit specified in 'MEMCACHED' => 100, etc.
     */
    protected function evictIfNeeded(): void
    {
        $this->wrapInTry(
            fn() => (
                $limit = $this->settings['cache']['MEMCACHED'] ?? 100,
                while ($this->dataStructureHandler->count($this->cacheQueue) > $limit) {
                    $this->delete($this->dataStructureHandler->dequeue($this->cacheQueue));
                }
            ),
            'cache'
        );
    }

    /**
     * Write encrypted payload to disk in $this->cacheDir/$key.cache.
     */
    protected function saveCacheData(string $key, string $payload): bool
    {
        return $this->wrapInTry(
            fn() => $this->fileManager->writeContents(
                $this->join('/', [$this->cacheDir, "$key.cache"]),
                $payload
            ) !== false,
            'cache'
        );
    }

    /**
     * Read encrypted payload from disk if exists.
     */
    protected function loadCacheData(string $key): ?string
    {
        return $this->wrapInTry(
            fn() => $this->fileManager->readContents(
                $this->join('/', [$this->cacheDir, "$key.cache"])
            ),
            'cache'
        );
    }

    /**
     * Delete file from disk for a given key.
     */
    protected function deleteCacheData(string $key): bool
    {
        return $this->wrapInTry(
            fn() => $this->fileManager->deleteFile(
                $this->join('/', [$this->cacheDir, "$key.cache"])
            ),
            'cache'
        );
    }

    /**
     * Helper to get IV length for configured OpenSSL cipher.
     */
    private function ivLenOpenssl(): int
    {
        return $this->wrapInTry(
            fn() => $this->cryptoManager->cryptoDriver->CipherHandler('getIvLength')(
                $this->settings['encryption']['CIPHER'] ?? 'aes256gcm'
            ),
            'cache'
        );
    }

    /**
     * Helper to get nonce length for typical Sodium secretBox usage.
     */
    private function nonceLenSodium(): int
    {
        return $this->wrapInTry(
            fn() => $this->cryptoManager->cryptoDriver->config['secretBox']['nonceBytes'] ?? 24,
            'cache'
        );
    }
}
