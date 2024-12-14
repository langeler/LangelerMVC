<?php

namespace App\Drivers\Caching;

use App\Abstracts\Data\Cache;
use App\Core\Database;
use App\Exceptions\Data\CacheException;

class DatabaseCache extends Cache
{
	protected Database $database;

	public function __construct(Database $database)
	{
		$this->database = $database;
		parent::__construct();
	}

	public function set(string $key, $data, ?int $ttl = null): bool
	{
		return $this->wrapInTry(function () use ($key, $data, $ttl) {
			$ttl = $ttl ?? (int)$this->settings['cache']['TTL'];
			$cacheData = [
				'timestamp' => $this->dateTimeHandler->getCurrentTimestamp(),
				'ttl' => $ttl,
				'data' => $this->compressData(
					$this->encryptData(
						$this->serializeData($data)
					)
				),
			];

			$this->delete($key);
			$result = $this->database->query(
				'INSERT INTO cache (cache_key, cache_data, timestamp, ttl) VALUES (?, ?, ?, ?)',
				[$key, json_encode($cacheData), $cacheData['timestamp'], $ttl]
			);

			if ($result === false) {
				throw new CacheException("Failed to insert cache data for key: $key");
			}

			$this->dataStructureHandler->enqueue($this->cacheQueue, $key);
			$this->evictIfNeeded();
			return true;
		});
	}

	public function get(string $key)
	{
		return $this->wrapInTry(function () use ($key) {
			$result = $this->database->fetchOne(
				'SELECT cache_data, timestamp, ttl FROM cache WHERE cache_key = ?',
				[$key]
			);

			if (!$result) {
				return null;
			}

			$cacheData = json_decode($result['cache_data'], true);
			return $this->isExpired($cacheData['timestamp'], $cacheData['ttl']) ? $this->delete($key) : $this->unserializeData(
				$this->decryptData(
					$this->decompressData($cacheData['data'])
				)
			);
		});
	}

	public function delete(string $key): bool
	{
		return $this->wrapInTry(fn() => $this->database->query('DELETE FROM cache WHERE cache_key = ?', [$key]) !== false);
	}

	public function clear(): bool
	{
		return $this->wrapInTry(fn() => $this->database->query('DELETE FROM cache') !== false);
	}
}
