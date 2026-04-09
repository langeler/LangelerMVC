<?php

declare(strict_types=1);

namespace App\Drivers\Caching;

use App\Abstracts\Data\Cache;
use App\Utilities\Finders\FileFinder;
use App\Utilities\Handlers\DataHandler;
use App\Utilities\Managers\Data\CryptoManager;
use App\Utilities\Managers\DateTimeManager;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\SettingsManager;
use App\Utilities\Managers\System\ErrorManager;
use SplFileInfo;

class FileCache extends Cache
{
    public function __construct(
        private readonly FileFinder $fileFinder,
        FileManager $fileManager,
        DataHandler $dataHandler,
        CryptoManager $cryptoManager,
        DateTimeManager $dateTimeManager,
        SettingsManager $settingsManager,
        ErrorManager $errorManager
    ) {
        parent::__construct(
            $fileManager,
            $dataHandler,
            $cryptoManager,
            $dateTimeManager,
            $settingsManager,
            $errorManager
        );
    }

    public function driverName(): string
    {
        return 'file';
    }

    /**
     * @return array<string, mixed>
     */
    public function capabilities(): array
    {
        return [
            'extension' => true,
            'persistent' => true,
            'shared_store' => false,
            'prefix_scoped_clear' => true,
            'filesystem' => true,
            'compression' => true,
            'encryption' => true,
            'pruning' => true,
        ];
    }

    protected function usesFilesystem(): bool
    {
        return true;
    }

    protected function putRaw(string $storageKey, string $payload, ?int $ttl = null): bool
    {
        return $this->fileManager->writeContents($this->pathForStorageKey($storageKey), $payload) !== false;
    }

    protected function getRaw(string $storageKey): ?string
    {
        $path = $this->pathForStorageKey($storageKey);

        return $this->fileManager->fileExists($path)
            ? $this->fileManager->readContents($path)
            : null;
    }

    protected function deleteRaw(string $storageKey): bool
    {
        $path = $this->pathForStorageKey($storageKey);

        return !$this->fileManager->fileExists($path) || $this->fileManager->deleteFile($path);
    }

    /**
     * @return array<string, int>
     */
    protected function discoverStoredEntries(): array
    {
        $entries = [];

        foreach ($this->fileFinder->find(['extension' => 'cache'], $this->cacheDir) as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
                continue;
            }

            $payload = $this->fileManager->readContents($fileInfo->getPathname());

            if (!$this->isString($payload) || $payload === '') {
                continue;
            }

            try {
                $record = $this->dataHandler->jsonDecode($payload, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                continue;
            }

            if (!$this->isArray($record)) {
                continue;
            }

            $storageKey = (string) ($record['key'] ?? '');

            if ($storageKey === '') {
                continue;
            }

            $entries[$storageKey] = $this->toInt($record['timestamp'] ?? 0);
        }

        return $entries;
    }

    private function pathForStorageKey(string $storageKey): string
    {
        return $this->fileManager->normalizePath(
            $this->cacheDir . DIRECTORY_SEPARATOR . hash('sha256', $storageKey) . '.cache'
        );
    }
}
