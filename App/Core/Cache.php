<?php

namespace App\Core;

use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Finders\FileFinder;
use App\Utilities\Handlers\DataHandler;
use App\Utilities\Handlers\CryptoHandler;
use App\Utilities\Handlers\DataStructureHandler;
use App\Utilities\Handlers\DateTimeHandler;
use App\Utilities\Managers\FileManager;
use App\Helpers\TypeChecker;
use App\Utilities\Sanitation\GeneralSanitizer;
use App\Exceptions\CacheException;

class Cache
{
    private FileManager $fileManager;
    private GeneralSanitizer $sanitizer;
    private TypeChecker $typeChecker;
    private DataHandler $dataHandler;
    private CryptoHandler $cryptoHandler;
    private DataStructureHandler $dataStructureHandler;
    private DateTimeHandler $dateTimeHandler;
    private DirectoryFinder $directoryFinder;
    private FileFinder $fileFinder;

    private string $cacheDir;
    private int $defaultTTL;
    private \SplQueue $cacheQueue;
    private string $encryptionKey;
    private int $maxCacheSize;
    private string $encryptionKeyFile;
    private string $masterKey;
    private array $cacheData = ['timestamp' => null, 'ttl' => null, 'data' => null];
    private string $currentFormat = 'json';
    private array $formatMap = [];

    public function __construct(
        FileManager $fileManager,
        GeneralSanitizer $sanitizer,
        TypeChecker $typeChecker,
        DataHandler $dataHandler,
        CryptoHandler $cryptoHandler,
        DataStructureHandler $dataStructureHandler,
        DateTimeHandler $dateTimeHandler,
        DirectoryFinder $directoryFinder,
        FileFinder $fileFinder,
        string $masterKey = 'default_master_key',
        int $defaultTTL = 600,
        int $maxCacheSize = 100
    ) {
        $this->fileManager = $fileManager;
        $this->sanitizer = $sanitizer;
        $this->typeChecker = $typeChecker;
        $this->dataHandler = $dataHandler;
        $this->cryptoHandler = $cryptoHandler;
        $this->dataStructureHandler = $dataStructureHandler;
        $this->dateTimeHandler = $dateTimeHandler;
        $this->directoryFinder = $directoryFinder;
        $this->fileFinder = $fileFinder;

        $this->defaultTTL = $defaultTTL;
        $this->maxCacheSize = $maxCacheSize;
        $this->masterKey = $masterKey;

        // Setup cache directory, queue, and encryption key
        $this->cacheDir = $this->initializeCacheDirectory();
        $this->cacheQueue = $this->dataStructureHandler->createQueue();
        $this->encryptionKeyFile = $this->initializeSecureDirectory() . DIRECTORY_SEPARATOR . 'cache_key';
        $this->encryptionKey = $this->getOrGenerateEncryptionKey();
        $this->initializeFormatMap();
    }

    public function set(string $key, $data, ?int $ttl = null): bool
    {
        $this->typeChecker->isString($key);
        $this->typeChecker->isIntegerOrNull($ttl);

        $ttl = $ttl ?? $this->defaultTTL;

        $this->cacheData = [
            'timestamp' => $this->dateTimeHandler->getCurrentTimestamp(),
            'ttl' => $ttl,
            'data' => $this->encryptData($data, $key)
        ];

        $this->dataStructureHandler->enqueue($this->cacheQueue, $key);
        $this->manageCacheEviction();

        return $this->writeCacheFile($key);
    }

    public function get(string $key)
    {
        $this->typeChecker->isString($key);

        try {
            if (!$this->fileExists($key)) {
                return null;
            }

            $this->cacheData = $this->readCacheFile($key);

            if ($this->isCacheExpired()) {
                $this->delete($key);
                return null;
            }

            return $this->decryptData($this->cacheData['data'], $key);
        } catch (CacheException $e) {
            return null;
        }
    }

    public function delete(string $key): bool
    {
        $this->typeChecker->isString($key);

        try {
            if (!$this->fileExists($key)) {
                return false;
            }
            return $this->fileManager->deleteFile($this->getCacheFilePath($key));
        } catch (CacheException $e) {
            throw new CacheException("Error deleting cache for key: $key - " . $e->getMessage());
        }
    }

    public function clear(): bool
    {
        try {
            $cacheFiles = $this->fileFinder->find(['extension' => ['json', 'cache', 'xml', 'yaml']], $this->cacheDir);

            foreach ($cacheFiles as $file) {
                $this->fileManager->deleteFile($file->getPathname());
            }

            $this->cacheQueue = $this->dataStructureHandler->createQueue();
            return true;
        } catch (CacheException $e) {
            throw new CacheException("Error clearing cache: " . $e->getMessage());
        }
    }

    private function writeCacheFile(string $key): bool
    {
        try {
            if (empty($this->cacheData['data'])) {
                throw new CacheException("Cache data is empty for key: $key");
            }

            // Validate the cache directory before writing
            $this->validateCacheDirectory();

            $filePath = $this->getCacheFilePath($key);

            // Write the encrypted cache data to the cache file
            $written = $this->fileManager->writeFileContents(
                $filePath,
                $this->formatMap[$this->currentFormat]['encode']($this->cacheData)
            );

            if (!$written) {
                throw new CacheException("Failed to write cache file: $filePath");
            }

            return true;
        } catch (CacheException $e) {
            throw new CacheException("Error writing cache for key: $key - " . $e->getMessage());
        }
    }

    private function readCacheFile(string $key): array
    {
        try {
            if (!$this->fileExists($key)) {
                return [];
            }

            $data = $this->fileManager->readFileContents($this->getCacheFilePath($key));

            if ($data === null || trim($data) === '') {
                return [];  // Return an empty array if the file is missing or empty
            }

            return $this->formatMap[$this->currentFormat]['decode']($data) ?: [];
        } catch (CacheException $e) {
            throw new CacheException("Error reading cache for key: $key - " . $e->getMessage());
        }
    }

    private function fileExists(string $key): bool
    {
        return $this->fileManager->fileExists($this->getCacheFilePath($key));
    }

    private function isCacheExpired(): bool
    {
        $timestamp = $this->cacheData['timestamp'] ?? 0;
        $ttl = $this->cacheData['ttl'] ?? $this->defaultTTL;

        return $this->dateTimeHandler->getCurrentTimestamp() - $timestamp > $ttl;
    }

    private function encryptData($data, string $key): string
    {
        if (empty($this->encryptionKey)) {
            throw new CacheException("Encryption key is empty. Ensure it is generated correctly.");
        }

        return $this->cryptoHandler->sodiumEncrypt(
            $this->dataHandler->serializeData($data),
            $this->encryptionKey
        );
    }

    private function decryptData(string $encryptedData, string $key)
    {
        if (empty($this->encryptionKey)) {
            throw new CacheException("Decryption failed: encryption key is empty.");
        }

        return $this->dataHandler->unserializeData(
            $this->cryptoHandler->sodiumDecrypt(
                $encryptedData,
                $this->encryptionKey
            )
        );
    }

    private function initializeCacheDirectory(): string
    {
        $cacheDirResults = $this->directoryFinder->find(['name' => 'Cache']);
        if ($this->typeChecker->isArray($cacheDirResults) && !$this->typeChecker->isEmpty($cacheDirResults)) {
            return $cacheDirResults[0]->getPathname();
        } else {
            // Create the Cache directory if it doesn't exist
            return $this->fileManager->createDir('Storage/Cache');
        }
    }

    private function initializeSecureDirectory(): string
    {
        $secureDirResults = $this->directoryFinder->find(['name' => 'Secure']);
        if ($this->typeChecker->isArray($secureDirResults) && !$this->typeChecker->isEmpty($secureDirResults)) {
            return $secureDirResults[0]->getPathname();
        } else {
            // Create the Secure directory if it doesn't exist
            return $this->fileManager->createDir('Storage/Secure');
        }
    }

    private function getCacheFilePath(string $key): string
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . $this->sanitizer->sanitizeString($key) . '.' . $this->getCurrentFileExtension();
    }

    private function manageCacheEviction(): void
    {
        while ($this->dataStructureHandler->count($this->cacheQueue) > $this->maxCacheSize) {
            $this->delete($this->dataStructureHandler->dequeue($this->cacheQueue));
        }
    }

    private function getOrGenerateEncryptionKey(): string
    {
        try {
            return $this->readEncryptionKey();
        } catch (CacheException $e) {
            return $this->generateNewEncryptionKey();
        }
    }

    private function readEncryptionKey(): string
    {
        if (!$this->fileManager->fileExists($this->encryptionKeyFile)) {
            return $this->generateNewEncryptionKey();
        }

        try {
            $encryptedKey = $this->fileManager->readFileContents($this->encryptionKeyFile);
            return $this->cryptoHandler->sodiumDecrypt($encryptedKey, $this->getHashedMasterKey());
        } catch (CacheException $e) {
            throw new CacheException("Error reading encryption key: " . $e->getMessage());
        }
    }

    private function generateNewEncryptionKey(): string
    {
        try {
            $newKey = $this->cryptoHandler->sodiumRandomBytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
            $encryptedKey = $this->cryptoHandler->sodiumEncrypt($newKey, $this->getHashedMasterKey());
            $this->fileManager->writeFileContents($this->encryptionKeyFile, $encryptedKey);
            return $newKey;
        } catch (CacheException $e) {
            throw new CacheException("Error generating encryption key: " . $e->getMessage());
        }
    }

    private function getHashedMasterKey(): string
    {
        return hash('sha256', $this->masterKey, true);
    }

    private function validateCacheDirectory(): void
    {
        if (!is_dir($this->cacheDir)) {
            throw new CacheException("Cache directory does not exist: " . $this->cacheDir);
        }

        if (!is_writable($this->cacheDir)) {
            throw new CacheException("Cache directory is not writable: " . $this->cacheDir);
        }
    }

    public function setFormat(string $format): void
    {
        $this->typeChecker->isString($format);

        if (!isset($this->formatMap[$format])) {
            throw new CacheException("Unsupported format: $format");
        }

        $this->currentFormat = $format;
    }

    public function getFormat(): string
    {
        return $this->currentFormat;
    }

    private function initializeFormatMap(): void
    {
        $this->formatMap = [
            'json' => [
                'encode' => fn($data) => $this->dataHandler->jsonEncode($data),
                'decode' => fn($data) => $this->dataHandler->jsonDecode($data, true)
            ],
            'xml' => [
                'encode' => fn($data) => $this->dataHandler->encodeXml($data),
                'decode' => fn($data) => $this->dataHandler->decodeXml($data)
            ],
            'yaml' => [
                'encode' => fn($data) => $this->dataHandler->encodeYaml($data),
                'decode' => fn($data) => $this->dataHandler->decodeYaml($data)
            ]
        ];
    }

    private function getCurrentFileExtension(): string
    {
        $extensionMap = [
            'json' => 'json',
            'xml' => 'xml',
            'yaml' => 'yaml',
        ];

        return $extensionMap[$this->currentFormat] ?? 'cache';
    }
}
