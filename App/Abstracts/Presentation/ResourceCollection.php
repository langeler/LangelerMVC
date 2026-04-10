<?php

declare(strict_types=1);

namespace App\Abstracts\Presentation;

abstract class ResourceCollection extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        foreach ((array) $this->resource as $item) {
            $data[] = $this->mapItem($item);
        }

        $payload = ['data' => $data];

        if ($this->metaPayload() !== []) {
            $payload['meta'] = $this->metaPayload();
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function mapItem(mixed $item): array;
}
