<?php

declare(strict_types=1);

namespace App\Modules\AdminModule\Presenters;

use App\Abstracts\Presentation\Resource;

class AdminResource extends Resource
{
    public function toArray(): array
    {
        $payload = is_array($this->resource) ? $this->resource : [];

        return [
            'data' => [
                'status' => (int) ($payload['status'] ?? 200),
                'title' => (string) ($payload['title'] ?? 'Admin'),
                'message' => (string) ($payload['message'] ?? ''),
                'metrics' => is_array($payload['metrics'] ?? null) ? $payload['metrics'] : [],
                'users' => is_array($payload['users'] ?? null) ? $payload['users'] : [],
                'roles' => is_array($payload['roles'] ?? null) ? $payload['roles'] : [],
                'permissions' => is_array($payload['permissions'] ?? null) ? $payload['permissions'] : [],
                'modules' => is_array($payload['modules'] ?? null) ? $payload['modules'] : [],
                'system' => is_array($payload['system'] ?? null) ? $payload['system'] : [],
            ],
            'meta' => [
                'module' => 'AdminModule',
                'template' => (string) ($payload['template'] ?? 'AdminDashboard'),
            ],
        ];
    }
}
