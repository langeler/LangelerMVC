<?php

declare(strict_types=1);

namespace App\Abstracts\Presentation;

abstract class ResourceCollection extends Resource
{
    public function withPagination(array $pagination): static
    {
        return $this->withMeta(['pagination' => $pagination]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function resolveData(): array
    {
        $data = [];

        if (!$this->isIterable($this->resource)) {
            return $data;
        }

        foreach ($this->resource as $item) {
            $data[] = $this->mapItem($item);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function mapItem(mixed $item): array;
}
