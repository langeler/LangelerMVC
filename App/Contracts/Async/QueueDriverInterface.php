<?php

declare(strict_types=1);

namespace App\Contracts\Async;

interface QueueDriverInterface
{
    public function driverName(): string;

    /**
     * @return array<string, mixed>
     */
    public function capabilities(): array;

    public function supports(string $feature): bool;

    /**
     * @param array<string, mixed> $envelope
     */
    public function push(array $envelope, string $queue = 'default', int $delay = 0): string;

    /**
     * @return array<string, mixed>|null
     */
    public function pop(string $queue = 'default'): ?array;

    public function delete(string $id): bool;

    /**
     * @param array<string, mixed> $envelope
     */
    public function release(array $envelope, int $delay = 0): bool;

    /**
     * @return list<array<string, mixed>>
     */
    public function pending(string $queue = 'default'): array;
}
