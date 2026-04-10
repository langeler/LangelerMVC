<?php

declare(strict_types=1);

namespace App\Abstracts\Presentation;

use App\Contracts\Presentation\ResourceInterface;
use App\Utilities\Traits\ArrayTrait;

abstract class Resource implements ResourceInterface
{
    use ArrayTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $meta = [];

    public function __construct(protected mixed $resource)
    {
    }

    /**
     * @param array<string, mixed> $meta
     */
    public function withMeta(array $meta): static
    {
        $this->meta = $this->merge($this->meta, $meta);

        return $this;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    protected function metaPayload(): array
    {
        return $this->meta;
    }
}
