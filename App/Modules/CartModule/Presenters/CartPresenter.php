<?php

declare(strict_types=1);

namespace App\Modules\CartModule\Presenters;

use App\Abstracts\Presentation\Presenter;

class CartPresenter extends Presenter
{
    protected function transformData(array $data): array
    {
        return [
            'template' => (string) ($data['template'] ?? 'CartPage'),
            'status' => (int) ($data['status'] ?? 200),
            'title' => (string) ($data['title'] ?? 'Cart'),
            'headline' => (string) ($data['headline'] ?? $data['title'] ?? 'Cart'),
            'summary' => (string) ($data['summary'] ?? ''),
            'message' => (string) ($data['message'] ?? ''),
            'cart' => is_array($data['cart'] ?? null) ? $data['cart'] : [],
        ];
    }

    protected function computeProperties(array $data): array
    {
        $cart = is_array($data['cart'] ?? null) ? $data['cart'] : [];

        return [
            'itemCount' => is_array($cart['items'] ?? null) ? count($cart['items']) : 0,
            'metaDescription' => (string) ($data['summary'] ?? ''),
        ];
    }

    protected function buildMetadata(array $data): array
    {
        return [
            'module' => 'CartModule',
            'status' => $data['status'] ?? 200,
            'generatedAt' => $this->dateTimeManager->formatDateTime(
                $this->dateTimeManager->createDateTime('now'),
                \DateTime::RFC3339
            ),
        ];
    }
}
