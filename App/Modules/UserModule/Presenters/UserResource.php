<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Presenters;

use App\Abstracts\Presentation\Resource;

class UserResource extends Resource
{
    protected function resolveData(): array
    {
        $payload = is_array($this->resource) ? $this->resource : [];

        return [
            'status' => (int) ($payload['status'] ?? 200),
            'title' => (string) ($payload['title'] ?? 'User Platform'),
            'message' => (string) ($payload['message'] ?? ''),
            'user' => is_array($payload['user'] ?? null) ? $payload['user'] : null,
            'roles' => is_array($payload['roles'] ?? null) ? $payload['roles'] : [],
            'permissions' => is_array($payload['permissions'] ?? null) ? $payload['permissions'] : [],
            'otp' => is_array($payload['otp'] ?? null) ? $payload['otp'] : [],
            'recoveryCodes' => is_array($payload['recoveryCodes'] ?? null) ? $payload['recoveryCodes'] : [],
            'passkey' => is_array($payload['passkey'] ?? null) ? $payload['passkey'] : [],
            'passkeys' => is_array($payload['passkeys'] ?? null) ? $payload['passkeys'] : [],
            'passkeySupport' => is_array($payload['passkeySupport'] ?? null) ? $payload['passkeySupport'] : [],
            'requiresOtp' => (bool) ($payload['requiresOtp'] ?? false),
        ];
    }

    protected function defaultMeta(): array
    {
        $payload = is_array($this->resource) ? $this->resource : [];

        return array_filter([
            'template' => $payload['template'] ?? null,
            'module' => 'UserModule',
            'redirect' => $payload['redirect'] ?? null,
        ], static fn(mixed $value): bool => $value !== null && $value !== '');
    }
}
