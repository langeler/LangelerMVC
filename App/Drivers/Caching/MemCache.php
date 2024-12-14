<?php

namespace App\Drivers\Caching;

use App\Abstracts\Data\Cache;
use App\Exceptions\Data\CacheException;
use Memcached;

class MemCache extends Cache
{
	protected Memcached $memcached;

	public function __construct(Memcached $memcached)
	{
		$this->memcached = $memcached;
		parent::__construct();
	}

	public function set(string $key, $data, ?int $ttl = null): bool
	{
		return $this->wrapInTry(function () use ($key, $data, $ttl) {
			$ttl = $ttl ?? (int)$this->settings['cache']['TTL'];
			$result = $this->memcached->set($key, json_encode([
				'timestamp' => $this->dateTimeHandler->getCurrentTimestamp(),
				'ttl' => $ttl,
				'data' => $this->compressData(
					$this->encryptData(
						$this->serializeData($data)
					)
				)
			]), $ttl);

			if (!$result) {
				throw new CacheException("Failed to set cache data for key: $key");
			}

			$this->dataStructureHandler->enqueue($this->cacheQueue, $key);
			$this->evictIfNeeded();
			return true;
		});
	}

	public function get(string $key)
	{
		return $this->wrapInTry(function () use ($key) {
			$result = $this->memcached->get($key);
			if ($result === false) {
				return null;
			}

			$cacheData = json_decode($result, true);
			return $this->isExpired($cacheData['timestamp'], $cacheData['ttl']) ? $this->delete($key) : $this->unserializeData(
				$this->decryptData(
					$this->decompressData($cacheData['data'])
				)
			);
		});
	}

	public function delete(string $key): bool
	{
		return $this->wrapInTry(fn() => $this->memcached->delete($key));
	}

	public function clear(): bool
	{
		return $this->wrapInTry(fn() => $this->memcached->flush());
	}
}
