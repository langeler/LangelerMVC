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
            'modules' => is_array($payload['modules'] ?? null) ? $payload['modules'] : [],
            'system' => is_array($payload['system'] ?? null) ? $payload['system'] : [],
            'catalog' => is_array($payload['catalog'] ?? null) ? $payload['catalog'] : [],
            'categories' => is_array($payload['categories'] ?? null) ? $payload['categories'] : [],
            'carts' => is_array($payload['carts'] ?? null) ? $payload['carts'] : [],
            'orders' => is_array($payload['orders'] ?? null) ? $payload['orders'] : [],
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
