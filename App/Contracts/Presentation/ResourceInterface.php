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
}
