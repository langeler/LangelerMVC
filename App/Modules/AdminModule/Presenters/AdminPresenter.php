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
            'modules' => is_array($data['modules'] ?? null) ? $data['modules'] : [],
            'system' => is_array($data['system'] ?? null) ? $data['system'] : [],
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
