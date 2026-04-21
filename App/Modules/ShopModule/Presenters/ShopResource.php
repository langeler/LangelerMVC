<?php

declare(strict_types=1);

namespace App\Modules\ShopModule\Presenters;

use App\Abstracts\Presentation\Resource;

class ShopResource extends Resource
{
    protected function resolveData(): array
    {
        $payload = is_array($this->resource) ? $this->resource : [];

        return [
            'status' => (int) ($payload['status'] ?? 200),
            'title' => (string) ($payload['title'] ?? 'Shop'),
            'message' => (string) ($payload['message'] ?? ''),
            'products' => is_array($payload['products'] ?? null) ? $payload['products'] : [],
            'product' => is_array($payload['product'] ?? null) ? $payload['product'] : [],
            'categories' => is_array($payload['categories'] ?? null) ? $payload['categories'] : [],
            'category' => is_array($payload['category'] ?? null) ? $payload['category'] : [],
            'filters' => is_array($payload['filters'] ?? null) ? $payload['filters'] : [],
            'related' => is_array($payload['related'] ?? null) ? $payload['related'] : [],
            'pagination' => is_array($payload['pagination'] ?? null) ? $payload['pagination'] : [],
        ];
    }

    protected function defaultMeta(): array
    {
        $payload = is_array($this->resource) ? $this->resource : [];

        return array_filter([
            'module' => 'ShopModule',
            'template' => $payload['template'] ?? 'ShopCatalog',
        ], static fn(mixed $value): bool => $value !== null && $value !== '');
    }
}
