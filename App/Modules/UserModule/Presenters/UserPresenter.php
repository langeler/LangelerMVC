<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Presenters;

use App\Abstracts\Presentation\Presenter;

class UserPresenter extends Presenter
{
    protected function transformData(array $data): array
    {
        return [
            'template' => (string) ($data['template'] ?? 'UserStatus'),
            'status' => (int) ($data['status'] ?? 200),
            'title' => (string) ($data['title'] ?? 'User Platform'),
            'headline' => (string) ($data['headline'] ?? $data['title'] ?? 'User Platform'),
            'summary' => (string) ($data['summary'] ?? ''),
            'message' => (string) ($data['message'] ?? ''),
            'user' => is_array($data['user'] ?? null) ? $data['user'] : null,
            'roles' => is_array($data['roles'] ?? null) ? $data['roles'] : [],
            'permissions' => is_array($data['permissions'] ?? null) ? $data['permissions'] : [],
            'users' => is_array($data['users'] ?? null) ? $data['users'] : [],
            'form' => is_array($data['form'] ?? null) ? $data['form'] : [],
            'otp' => is_array($data['otp'] ?? null) ? $data['otp'] : [],
            'recoveryCodes' => is_array($data['recoveryCodes'] ?? null) ? $data['recoveryCodes'] : [],
            'link' => is_array($data['link'] ?? null) ? $data['link'] : [],
            'passkey' => is_array($data['passkey'] ?? null) ? $data['passkey'] : [],
            'passkeys' => is_array($data['passkeys'] ?? null) ? $data['passkeys'] : [],
            'passkeySupport' => is_array($data['passkeySupport'] ?? null) ? $data['passkeySupport'] : [],
        ];
    }

    protected function computeProperties(array $data): array
    {
        return [
            'hasUser' => is_array($data['user'] ?? null),
            'hasRecoveryCodes' => is_array($data['recoveryCodes'] ?? null) && $data['recoveryCodes'] !== [],
            'metaDescription' => (string) ($data['summary'] ?? $data['message'] ?? ''),
        ];
    }

    protected function buildMetadata(array $data): array
    {
        return [
            'status' => $data['status'] ?? 200,
            'module' => 'UserModule',
            'generatedAt' => $this->dateTimeManager->formatDateTime(
                $this->dateTimeManager->createDateTime('now'),
                \DateTime::RFC3339
            ),
        ];
    }
}
