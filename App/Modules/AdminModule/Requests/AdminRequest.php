<?php

declare(strict_types=1);

namespace App\Modules\AdminModule\Requests;

use App\Abstracts\Http\InboundRequest;

class AdminRequest extends InboundRequest
{
    private string $scenario = 'default';

    public function forScenario(string $scenario): static
    {
        $this->scenario = $scenario;

        return $this;
    }

    protected function validationRules(): array
    {
        return match ($this->scenario) {
            'assignRoles' => [
                'roles' => [
                    'required' => false,
                    'each' => ['methods' => 'string', 'rules' => ['notEmpty']],
                ],
            ],
            'syncPermissions' => [
                'permissions' => [
                    'required' => false,
                    'each' => ['methods' => 'string', 'rules' => ['notEmpty']],
                ],
            ],
            default => [],
        };
    }

    protected function transformInput(array $data): array
    {
        foreach (['roles', 'permissions'] as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $parts = array_map('trim', explode(',', $data[$key]));
                $data[$key] = array_values(array_filter($parts, static fn(string $value): bool => $value !== ''));
            }
        }

        return $data;
    }
}
