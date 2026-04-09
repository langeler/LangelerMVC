<?php

declare(strict_types=1);

namespace App\Contracts\Data;

interface CacheDriverInterface
{
    /**
     * Returns the normalized runtime driver name.
     */
    public function driverName(): string;

    /**
     * Returns the runtime capability map for the driver.
     *
     * @return array<string, mixed>
     */
    public function capabilities(): array;

    /**
     * Checks whether the driver supports a capability or feature path.
     */
    public function supports(string $feature): bool;

    public function set(string $key, mixed $data, ?int $ttl = null): bool;

    public function get(string $key): mixed;

    public function has(string $key): bool;

    public function delete(string $key): bool;

    public function clear(): bool;
}
