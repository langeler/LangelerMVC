<?php

namespace App\Abstracts\Data;

use App\Exceptions\Data\CacheException;
use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Finders\FileFinder;
use App\Utilities\Handlers\CryptoHandler;
use App\Utilities\Handlers\DataHandler;
use App\Utilities\Handlers\DataStructureHandler;
use App\Utilities\Handlers\DateTimeHandler;
use App\Utilities\Managers\CompressionManager;
use App\Utilities\Managers\FileManager;
use App\Helpers\TypeChecker;
use App\Utilities\Managers\SettingsManager;
use App\Utilities\Sanitation\GeneralSanitizer;
use SplQueue;
use Throwable;

abstract class Cache
{
    protected string $encryptionKey = '';
    protected array $settings;
    protected array $cacheData = ['timestamp' => null, 'ttl' => null, 'data' => null];
    protected string $cacheDir;
    protected SplQueue $cacheQueue;

    public function __construct(
        protected TypeChecker $typeChecker,
        protected FileManager $fileManager,
        protected CompressionManager $compressionManager,
        protected DataHandler $dataHandler,
        protected CryptoHandler $cryptoHandler,
        protected DataStructureHandler $dataStructureHandler,
        protected DateTimeHandler $dateTimeHandler,
        protected SettingsManager $settingsManager,
        protected DirectoryFinder $directoryFinder,
        protected FileFinder $fileFinder,
        protected GeneralSanitizer $sanitizer
    ) {
        $this->initializeCache();
    }

    protected function initializeCache(): void
    {
        try {
            $this->settings = [
                'cache' => $this->settingsManager->getAllSettings('CACHE'),
                'encryption' => $this->settingsManager->getAllSettings('ENCRYPTION'),
            ];
            $this->encryptionKey = $this->getOrGenerateEncryptionKey();
            $this->cacheQueue = $this->dataStructureHandler->createQueue();
            $this->cacheDir = $this->findCacheDirectory();
        } catch (Throwable $e) {
            throw new CacheException("Error initializing cache: " . $e->getMessage(), 0, $e);
        }
    }

    abstract public function set(string $key, $data, ?int $ttl = null): bool;
    abstract public function get(string $key);
    abstract public function delete(string $key): bool;
    abstract public function clear(): bool;

    protected function encryptData(string $data): string
    {
        return match ($this->settings['encryption']['TYPE'] ?? 'openssl') {
            'openssl' => $this->opensslEncrypt($data),
            'sodium' => $this->sodiumEncrypt($data),
            default => throw new CacheException("Unsupported encryption type."),
        };
    }

    protected function opensslEncrypt(string $data): string
    {
        $iv = $this->cryptoHandler->generateNonce($this->cryptoHandler->getIvLength($this->settings['encryption']['CIPHER']));
        return $iv . $this->cryptoHandler->encryptData($data, $this->settings['encryption']['CIPHER'], $this->encryptionKey, 0, $iv);
    }

    protected function sodiumEncrypt(string $data): string
    {
        return $this->cryptoHandler->sodiumEncrypt($data, $this->encryptionKey);
    }

    protected function decryptData(string $data): string
    {
        return match ($this->settings['encryption']['TYPE'] ?? 'openssl') {
            'openssl' => $this->opensslDecrypt($data),
            'sodium' => $this->sodiumDecrypt($data),
            default => throw new CacheException("Unsupported decryption type."),
        };
    }

    protected function opensslDecrypt(string $data): string
    {
        $ivLength = $this->cryptoHandler->getIvLength($this->settings['encryption']['CIPHER']);
        $iv = substr($data, 0, $ivLength);
        return $this->cryptoHandler->decryptData(substr($data, $ivLength), $this->settings['encryption']['CIPHER'], $this->encryptionKey, 0, $iv);
    }

    protected function sodiumDecrypt(string $data): string
    {
        return $this->cryptoHandler->sodiumDecrypt($data, $this->encryptionKey);
    }

    protected function getOrGenerateEncryptionKey(): string
    {
        return match ($this->settings['encryption']['TYPE'] ?? 'openssl') {
            'openssl' => base64_decode($this->settings['encryption']['KEY']),
            'sodium' => base64_decode($this->settings['encryption']['SODIUM']),
            default => throw new CacheException("Unsupported encryption key type."),
        };
    }

    protected function isExpired(int $timestamp, int $ttl): bool
    {
        return $this->dateTimeHandler->getCurrentTimestamp() > ($timestamp + $ttl);
    }

    protected function findCacheDirectory(): string
    {
        $dirs = $this->directoryFinder->find(['name' => 'Cache']);
        if (!empty($dirs) && $this->typeChecker->isDirectory($dirs[0])) {
            return $dirs[0];
        }
        $cacheDir = $this->settings['cache']['FILE'];
        $this->fileManager->createDirectory($cacheDir, 0777, true);
        return $cacheDir;
    }

    protected function evictIfNeeded(): void
    {
        while ($this->dataStructureHandler->count($this->cacheQueue) > $this->settings['cache']['MEMCACHED']) {
            $this->delete($this->dataStructureHandler->dequeue($this->cacheQueue));
        }
    }

    protected function saveCacheData(string $key, string $data): bool
    {
        return $this->fileManager->writeContents("{$this->cacheDir}/$key.cache", $data) !== false;
    }

    protected function loadCacheData(string $key): ?string
    {
        return $this->fileManager->readContents("{$this->cacheDir}/$key.cache");
    }

    protected function deleteCacheData(string $key): bool
    {
        return $this->fileManager->deleteFile("{$this->cacheDir}/$key.cache");
    }
}
