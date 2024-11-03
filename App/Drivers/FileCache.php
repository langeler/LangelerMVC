<?php

namespace App\Drivers;

use App\Abstracts\Data\Cache;
use App\Exceptions\Data\CacheException;
use Throwable;

class FileCache extends Cache
{
	public function set(string $key, $data, ?int $ttl = null): bool
	{
		return $this->wrapInTry(function () use ($key, $data, $ttl) {
			$cachePath = $this->getCacheFilePath($key);
			$cacheData = json_encode([
				'timestamp' => $this->dateTimeHandler->getCurrentTimestamp(),
				'ttl' => $ttl ?? (int)$this->settings['cache']['TTL'],
				'data' => $this->compressData($this->encryptData($this->serializeData($data))),
			]);

			if ($this->fileManager->writeContents($cachePath, $cacheData) === false) {
				throw new CacheException("Failed to write cache data for key: $key");
			}

			$this->postSetActions($key);
			return true;
		});
	}

	private function postSetActions(string $key): void
	{
		$this->dataStructureHandler->enqueue($this->cacheQueue, $key);
		$this->evictIfNeeded();
	}

	public function get(string $key)
	{
		return $this->wrapInTry(function () use ($key) {
			$cachePath = $this->getCacheFilePath($key);

			if (!$this->fileManager->fileExists($cachePath)) {
				return null;
			}

			$cacheData = json_decode($this->fileManager->readContents($cachePath), true);
			return $cacheData ? $this->validateAndReturnData($cacheData, $key) : null;
		});
	}

	private function validateAndReturnData(array $cacheData, string $key)
	{
		if ($this->isExpired($cacheData['timestamp'], $cacheData['ttl'])) {
			$this->delete($key);
			return null;
		}

		return $this->unserializeData($this->decryptData($this->decompressData($cacheData['data'])));
	}

	public function delete(string $key): bool
	{
		return $this->fileManager->deleteFile($this->getCacheFilePath($key));
	}

	public function clear(): bool
	{
		return $this->wrapInTry(function () {
			foreach ($this->fileFinder->find(['extension' => 'cache'], $this->cacheDir) as $file) {
				$this->fileManager->deleteFile($file->getPathname());
			}
			$this->cacheQueue = $this->dataStructureHandler->createQueue();
			return true;
		});
	}

	private function getCacheFilePath(string $key): string
	{
		return $this->cacheDir . DIRECTORY_SEPARATOR . $this->sanitizer->sanitizeString($key) . '.cache';
	}

	private function wrapInTry(callable $callback)
	{
		try {
			return $callback();
		} catch (Throwable $e) {
			throw new CacheException("Cache operation failed: " . $e->getMessage(), 0, $e);
		}
	}
}
