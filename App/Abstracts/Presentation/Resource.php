<?php

declare(strict_types=1);

namespace App\Abstracts\Presentation;

use App\Contracts\Presentation\ResourceInterface;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\TypeCheckerTrait;

abstract class Resource implements ResourceInterface
{
    use ArrayTrait, TypeCheckerTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $meta = [];

    /**
     * @var array<string, mixed>
     */
    protected array $links = [];

    /**
     * @var array<string, mixed>
     */
    protected array $additional = [];

    protected string $wrapKey = 'data';

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

    public function withLinks(array $links): static
    {
        $this->links = $this->merge($this->links, $links);

        return $this;
    }

    public function additional(array $payload): static
    {
        $this->additional = $this->merge($this->additional, $payload);

        return $this;
    }

    public function wrap(string $key = 'data'): static
    {
        $this->wrapKey = $key;

        return $this;
    }

    public function withoutWrap(): static
    {
        $this->wrapKey = '';

        return $this;
    }

    public function toArray(): array
    {
        $data = $this->resolveData();
        $payload = [];

        if ($this->wrapKey === '') {
            $payload = $this->isArray($data) ? $data : ['value' => $data];
        } else {
            $payload[$this->wrapKey] = $data;
        }

        if ($this->linksPayload() !== []) {
            $payload['links'] = $this->linksPayload();
        }

        if ($this->metaPayload() !== []) {
            $payload['meta'] = $this->metaPayload();
        }

        return $this->additional !== []
            ? $this->merge($payload, $this->additional)
            : $payload;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    protected function defaultMeta(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function linksPayload(): array
    {
        return $this->links;
    }

    /**
     * @return array<string, mixed>
     */
    protected function metaPayload(): array
    {
        return $this->merge($this->defaultMeta(), $this->meta);
    }

    abstract protected function resolveData(): mixed;
}
