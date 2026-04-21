<?php

declare(strict_types=1);

namespace App\Modules\ShopModule\Presenters;

use App\Abstracts\Presentation\Presenter;

class ShopPresenter extends Presenter
{
    protected function transformData(array $data): array
    {
        return [
            'template' => (string) ($data['template'] ?? 'ShopCatalog'),
            'status' => (int) ($data['status'] ?? 200),
            'title' => (string) ($data['title'] ?? 'Shop'),
            'headline' => (string) ($data['headline'] ?? $data['title'] ?? 'Shop'),
            'summary' => (string) ($data['summary'] ?? ''),
            'message' => (string) ($data['message'] ?? ''),
            'products' => is_array($data['products'] ?? null) ? $data['products'] : [],
            'product' => is_array($data['product'] ?? null) ? $data['product'] : [],
            'categories' => is_array($data['categories'] ?? null) ? $data['categories'] : [],
            'category' => is_array($data['category'] ?? null) ? $data['category'] : [],
            'filters' => is_array($data['filters'] ?? null) ? $data['filters'] : [],
            'related' => is_array($data['related'] ?? null) ? $data['related'] : [],
            'pagination' => is_array($data['pagination'] ?? null) ? $data['pagination'] : [],
        ];
    }

    protected function computeProperties(array $data): array
    {
        return [
            'hasProduct' => is_array($data['product'] ?? null) && $data['product'] !== [],
            'metaDescription' => (string) ($data['summary'] ?? ''),
        ];
    }

    protected function buildMetadata(array $data): array
    {
        return [
            'module' => 'ShopModule',
            'status' => $data['status'] ?? 200,
            'generatedAt' => $this->dateTimeManager->formatDateTime(
                $this->dateTimeManager->createDateTime('now'),
                \DateTime::RFC3339
            ),
        ];
    }
}
