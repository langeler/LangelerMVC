<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Presenters;

use App\Abstracts\Presentation\Resource;

class OrderResource extends Resource
{
    protected function resolveData(): array
    {
        $payload = is_array($this->resource) ? $this->resource : [];

        return [
            'status' => (int) ($payload['status'] ?? 200),
            'title' => (string) ($payload['title'] ?? 'Orders'),
            'message' => (string) ($payload['message'] ?? ''),
            'cart' => is_array($payload['cart'] ?? null) ? $payload['cart'] : [],
            'payment' => is_array($payload['payment'] ?? null) ? $payload['payment'] : [],
            'checkout' => is_array($payload['checkout'] ?? null) ? $payload['checkout'] : [],
            'lookup' => is_array($payload['lookup'] ?? null) ? $payload['lookup'] : [],
            'order' => is_array($payload['order'] ?? null) ? $payload['order'] : [],
            'orders' => is_array($payload['orders'] ?? null) ? $payload['orders'] : [],
        ];
    }

    protected function defaultMeta(): array
    {
        $payload = is_array($this->resource) ? $this->resource : [];

        return [
            'module' => 'OrderModule',
            'template' => (string) ($payload['template'] ?? 'OrderCheckout'),
        ];
    }
}
