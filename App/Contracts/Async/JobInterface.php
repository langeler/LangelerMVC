<?php

declare(strict_types=1);

namespace App\Contracts\Async;

interface JobInterface
{
    public function name(): string;

    /**
     * @param array<string, mixed> $payload
     */
    public function withPayload(array $payload): static;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array;

    public function handle(): mixed;
}
