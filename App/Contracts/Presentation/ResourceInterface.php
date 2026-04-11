<?php

declare(strict_types=1);

namespace App\Contracts\Presentation;

use JsonSerializable;

interface ResourceInterface extends JsonSerializable
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * @param array<string, mixed> $meta
     */
    public function withMeta(array $meta): static;

    /**
     * @param array<string, mixed> $links
     */
    public function withLinks(array $links): static;

    /**
     * @param array<string, mixed> $payload
     */
    public function additional(array $payload): static;

    public function wrap(string $key = 'data'): static;

    public function withoutWrap(): static;
}
