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

	public function set(string $key, mixed $data, ?int $ttl = null): bool
	{
		return $this->wrapInTry(function () use ($key, $data, $ttl) {
			$ttl = $ttl ?? (int)$this->settings['cache']['TTL'];
				$result = $this->memcached->set($key, $this->toJson([
					'timestamp' => $this->dateTimeManager->getCurrentTimestamp(),
					'ttl' => $ttl,
					'data' => $this->base64EncodeString(
						$this->compressData(
							$this->encryptData(
								$this->serializeData($data)
							)
						)
					)
				], JSON_THROW_ON_ERROR), $ttl);

			if (!$result) {
				throw new CacheException("Failed to set cache data for key: $key");
			}

			$this->dataStructureHandler->enqueue($this->cacheQueue, $key);
			$this->evictIfNeeded();
			return true;
		});
	}

	public function get(string $key): mixed
	{
		return $this->wrapInTry(function () use ($key) {
			$result = $this->memcached->get($key);
			if ($result === false) {
				return null;
			}

				$cacheData = $this->fromJson($result, true, 512, JSON_THROW_ON_ERROR);
					return $this->isExpired($cacheData['timestamp'], $cacheData['ttl']) ? null : $this->unserializeData(
						$this->decryptData(
							$this->decompressData(
								$this->base64DecodeString((string) ($cacheData['data'] ?? ''), true) ?: ''
							)
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
