<?php

declare(strict_types=1);

namespace App\Contracts\Async;

interface FailedJobStoreInterface
{
    /**
     * @param array<string, mixed> $envelope
     */
    public function record(array $envelope, \Throwable $exception): string;

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array;

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array;

    public function delete(string $id): bool;

    public function prune(?int $failedBefore = null): int;
}
