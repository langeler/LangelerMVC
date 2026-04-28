<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Presenters;

use App\Abstracts\Presentation\Presenter;

class OrderPresenter extends Presenter
{
    protected function transformData(array $data): array
    {
        return [
            'template' => (string) ($data['template'] ?? 'OrderCheckout'),
            'status' => (int) ($data['status'] ?? 200),
            'title' => (string) ($data['title'] ?? 'Orders'),
            'headline' => (string) ($data['headline'] ?? $data['title'] ?? 'Orders'),
            'summary' => (string) ($data['summary'] ?? ''),
            'message' => (string) ($data['message'] ?? ''),
            'cart' => is_array($data['cart'] ?? null) ? $data['cart'] : [],
            'payment' => is_array($data['payment'] ?? null) ? $data['payment'] : [],
            'checkout' => is_array($data['checkout'] ?? null) ? $data['checkout'] : [],
            'shipping' => is_array($data['shipping'] ?? null) ? $data['shipping'] : [],
            'lookup' => is_array($data['lookup'] ?? null) ? $data['lookup'] : [],
            'order' => is_array($data['order'] ?? null) ? $data['order'] : [],
            'orders' => is_array($data['orders'] ?? null) ? $data['orders'] : [],
            'webhook' => is_array($data['webhook'] ?? null) ? $data['webhook'] : [],
        ];
    }

    protected function computeProperties(array $data): array
    {
        return [
            'orderCount' => is_array($data['orders'] ?? null) ? count($data['orders']) : 0,
            'metaDescription' => (string) ($data['summary'] ?? ''),
        ];
    }

    protected function buildMetadata(array $data): array
    {
        return [
            'module' => 'OrderModule',
            'status' => $data['status'] ?? 200,
            'generatedAt' => $this->dateTimeManager->formatDateTime(
                $this->dateTimeManager->createDateTime('now'),
                \DateTime::RFC3339
            ),
        ];
    }
}
