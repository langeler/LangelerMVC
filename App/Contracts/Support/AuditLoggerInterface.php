<?php

declare(strict_types=1);

namespace App\Contracts\Support;

interface AuditLoggerInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function record(string $event, array $context = [], string $category = 'framework', string $severity = 'info'): bool;

    /**
     * @param array<string, mixed> $criteria
     * @return list<array<string, mixed>>
     */
    public function recent(int $limit = 50, array $criteria = []): array;

    /**
     * @return array<string, mixed>
     */
    public function summary(int $windowSeconds = 86400): array;

    /**
     * @return array<string, mixed>
     */
    public function capabilities(): array;
}
