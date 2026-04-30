<?php

declare(strict_types=1);

namespace App\Modules\AdminModule\Presenters;

use App\Abstracts\Presentation\Presenter;

class AdminPresenter extends Presenter
{
    protected function transformData(array $data): array
    {
        return [
            'template' => (string) ($data['template'] ?? 'AdminDashboard'),
            'status' => (int) ($data['status'] ?? 200),
            'title' => (string) ($data['title'] ?? 'Admin'),
            'headline' => (string) ($data['headline'] ?? $data['title'] ?? 'Admin'),
            'summary' => (string) ($data['summary'] ?? ''),
            'message' => (string) ($data['message'] ?? ''),
            'metrics' => is_array($data['metrics'] ?? null) ? $data['metrics'] : [],
            'users' => is_array($data['users'] ?? null) ? $data['users'] : [],
            'roles' => is_array($data['roles'] ?? null) ? $data['roles'] : [],
            'permissions' => is_array($data['permissions'] ?? null) ? $data['permissions'] : [],
            'pages' => is_array($data['pages'] ?? null) ? $data['pages'] : [],
            'page_form' => is_array($data['page_form'] ?? null) ? $data['page_form'] : [],
            'page_metrics' => is_array($data['page_metrics'] ?? null) ? $data['page_metrics'] : [],
            'modules' => is_array($data['modules'] ?? null) ? $data['modules'] : [],
            'system' => is_array($data['system'] ?? null) ? $data['system'] : [],
            'catalog' => is_array($data['catalog'] ?? null) ? $data['catalog'] : [],
            'catalog_metrics' => is_array($data['catalog_metrics'] ?? null) ? $data['catalog_metrics'] : [],
            'category_form' => is_array($data['category_form'] ?? null) ? $data['category_form'] : [],
            'product_form' => is_array($data['product_form'] ?? null) ? $data['product_form'] : [],
            'categories' => is_array($data['categories'] ?? null) ? $data['categories'] : [],
            'promotions' => is_array($data['promotions'] ?? null) ? $data['promotions'] : [],
            'configured_promotions' => is_array($data['configured_promotions'] ?? null) ? $data['configured_promotions'] : [],
            'promotion_usage' => is_array($data['promotion_usage'] ?? null) ? $data['promotion_usage'] : [],
            'promotion_analytics' => is_array($data['promotion_analytics'] ?? null) ? $data['promotion_analytics'] : [],
            'promotion_form' => is_array($data['promotion_form'] ?? null) ? $data['promotion_form'] : [],
            'promotion_metrics' => is_array($data['promotion_metrics'] ?? null) ? $data['promotion_metrics'] : [],
            'carts' => is_array($data['carts'] ?? null) ? $data['carts'] : [],
            'orders' => is_array($data['orders'] ?? null) ? $data['orders'] : [],
            'order' => is_array($data['order'] ?? null) ? $data['order'] : [],
            'operations' => is_array($data['operations'] ?? null) ? $data['operations'] : [],
        ];
    }

    protected function computeProperties(array $data): array
    {
        return [
            'metaDescription' => (string) ($data['summary'] ?? 'Administrative overview of the framework platform'),
        ];
    }

    protected function buildMetadata(array $data): array
    {
        return [
            'status' => $data['status'] ?? 200,
            'module' => 'AdminModule',
            'generatedAt' => $this->dateTimeManager->formatDateTime(
                $this->dateTimeManager->createDateTime('now'),
                \DateTime::RFC3339
            ),
        ];
    }
}
