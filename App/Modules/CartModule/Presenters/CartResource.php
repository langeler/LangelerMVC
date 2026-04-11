<?php

declare(strict_types=1);

namespace App\Modules\CartModule\Presenters;

use App\Abstracts\Presentation\Resource;

class CartResource extends Resource
{
    protected function resolveData(): array
    {
        $payload = is_array($this->resource) ? $this->resource : [];

        return [
            'status' => (int) ($payload['status'] ?? 200),
            'title' => (string) ($payload['title'] ?? 'Cart'),
            'message' => (string) ($payload['message'] ?? ''),
            'cart' => is_array($payload['cart'] ?? null) ? $payload['cart'] : [],
        ];
    }

    protected function defaultMeta(): array
    {
        $payload = is_array($this->resource) ? $this->resource : [];

        return [
            'module' => 'CartModule',
            'template' => (string) ($payload['template'] ?? 'CartPage'),
        ];
    }
}
