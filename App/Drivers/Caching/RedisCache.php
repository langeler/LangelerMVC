<?php

namespace App\Drivers\Caching;

use App\Abstracts\Data\Cache;
use App\Exceptions\Data\CacheException;
use Redis;

class RedisCache extends Cache
{
	protected Redis $redis;

	public function __construct(Redis $redis)
	{
		$this->redis = $redis;
		parent::__construct();
	}

	public function set(string $key, mixed $data, ?int $ttl = null): bool
	{
		return $this->wrapInTry(function () use ($key, $data, $ttl) {
			$ttl = $ttl ?? (int)$this->settings['cache']['TTL'];
			$result = $this->redis->set(
				$key,
				json_encode([
					'timestamp' => $this->dateTimeManager->getCurrentTimestamp(),
					'ttl' => $ttl,
					'data' => base64_encode(
						$this->compressData(
							$this->encryptData(
								$this->serializeData($data)
							)
						)
					)
				])
			);

			if (!$result) {
				throw new CacheException("Failed to set cache data for key: $key");
			}

			if ($ttl) {
				$this->redis->expire($key, $ttl);
			}

			$this->dataStructureHandler->enqueue($this->cacheQueue, $key);
			$this->evictIfNeeded();
			return true;
		});
	}

	public function get(string $key): mixed
	{
		return $this->wrapInTry(function () use ($key) {
			$result = $this->redis->get($key);
			if ($result === false) {
				return null;
			}

			$cacheData = json_decode($result, true);
				return $this->isExpired($cacheData['timestamp'], $cacheData['ttl']) ? null : $this->unserializeData(
					$this->decryptData(
						$this->decompressData(
							base64_decode((string) ($cacheData['data'] ?? ''), true) ?: ''
						)
					)
				);
			});
		}

	public function delete(string $key): bool
	{
		return $this->wrapInTry(fn() => $this->redis->del($key) > 0);
	}

	public function clear(): bool
	{
		return $this->wrapInTry(fn() => $this->redis->flushDB());
	}
}
