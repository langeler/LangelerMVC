<?php

namespace App\Contracts\Data;

interface CacheDriverInterface
{
	public function set(string $key, $data, int $ttl): bool;
	public function get(string $key): ?string;
	public function delete(string $key): bool;
	public function clear(): bool;
}
