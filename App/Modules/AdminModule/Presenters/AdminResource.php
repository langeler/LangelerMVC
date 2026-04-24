<?php

declare(strict_types=1);

namespace App\Modules\AdminModule\Presenters;

use App\Abstracts\Presentation\Resource;

class AdminResource extends Resource
{
    protected function resolveData(): array
    {
        $payload = is_array($this->resource) ? $this->resource : [];

        return [
            'status' => (int) ($payload['status'] ?? 200),
            'title' => (string) ($payload['title'] ?? 'Admin'),
            'message' => (string) ($payload['message'] ?? ''),
            'metrics' => is_array($payload['metrics'] ?? null) ? $payload['metrics'] : [],
            'users' => is_array($payload['users'] ?? null) ? $payload['users'] : [],
            'roles' => is_array($payload['roles'] ?? null) ? $payload['roles'] : [],
            'permissions' => is_array($payload['permissions'] ?? null) ? $payload['permissions'] : [],
            'pages' => is_array($payload['pages'] ?? null) ? $payload['pages'] : [],
            'page_form' => is_array($payload['page_form'] ?? null) ? $payload['page_form'] : [],
            'page_metrics' => is_array($payload['page_metrics'] ?? null) ? $payload['page_metrics'] : [],
            'modules' => is_array($payload['modules'] ?? null) ? $payload['modules'] : [],
            'system' => is_array($payload['system'] ?? null) ? $payload['system'] : [],
            'catalog' => is_array($payload['catalog'] ?? null) ? $payload['catalog'] : [],
            'catalog_metrics' => is_array($payload['catalog_metrics'] ?? null) ? $payload['catalog_metrics'] : [],
            'category_form' => is_array($payload['category_form'] ?? null) ? $payload['category_form'] : [],
            'product_form' => is_array($payload['product_form'] ?? null) ? $payload['product_form'] : [],
            'categories' => is_array($payload['categories'] ?? null) ? $payload['categories'] : [],
            'promotions' => is_array($payload['promotions'] ?? null) ? $payload['promotions'] : [],
            'configured_promotions' => is_array($payload['configured_promotions'] ?? null) ? $payload['configured_promotions'] : [],
            'promotion_usage' => is_array($payload['promotion_usage'] ?? null) ? $payload['promotion_usage'] : [],
            'promotion_form' => is_array($payload['promotion_form'] ?? null) ? $payload['promotion_form'] : [],
            'promotion_metrics' => is_array($payload['promotion_metrics'] ?? null) ? $payload['promotion_metrics'] : [],
            'carts' => is_array($payload['carts'] ?? null) ? $payload['carts'] : [],
            'orders' => is_array($payload['orders'] ?? null) ? $payload['orders'] : [],
            'order' => is_array($payload['order'] ?? null) ? $payload['order'] : [],
            'operations' => is_array($payload['operations'] ?? null) ? $payload['operations'] : [],
        ];
    }

    protected function defaultMeta(): array
    {
        $payload = is_array($this->resource) ? $this->resource : [];

        return [
            'module' => 'AdminModule',
            'template' => (string) ($payload['template'] ?? 'AdminDashboard'),
        ];
    }
}
