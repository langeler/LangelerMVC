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

    protected function sanitizationRules(): array
    {
        return match ($this->scenario) {
            'saveCategory' => [
                'name' => ['methods' => 'string'],
                'slug' => ['methods' => 'string', 'required' => false],
                'description' => ['methods' => 'string', 'required' => false],
                'is_published' => ['methods' => 'string', 'required' => false],
            ],
            'saveProduct' => [
                'category_id' => ['methods' => 'integer'],
                'name' => ['methods' => 'string'],
                'slug' => ['methods' => 'string', 'required' => false],
                'description' => ['methods' => 'string', 'required' => false],
                'price_minor' => ['methods' => 'integer'],
                'currency' => ['methods' => 'string', 'required' => false],
                'visibility' => ['methods' => 'string', 'required' => false],
                'stock' => ['methods' => 'integer', 'required' => false],
                'media' => ['methods' => 'string', 'required' => false],
            ],
            default => [],
        };
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
            'saveCategory' => [
                'name' => ['methods' => 'string', 'rules' => ['notEmpty', 'minLength' => [2]]],
                'slug' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^[a-z0-9-]{2,191}$/']],
            ],
            'saveProduct' => [
                'category_id' => ['methods' => 'integer', 'rules' => ['min' => [1]]],
                'name' => ['methods' => 'string', 'rules' => ['notEmpty', 'minLength' => [2]]],
                'slug' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^[a-z0-9-]{2,191}$/']],
                'price_minor' => ['methods' => 'integer', 'rules' => ['min' => [0]]],
                'currency' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^[A-Za-z]{3,12}$/']],
                'visibility' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^(draft|published|archived)$/']],
                'stock' => ['methods' => 'integer', 'required' => false, 'rules' => ['min' => [0]]],
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

        foreach (['name', 'slug', 'description', 'currency', 'visibility', 'media'] as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $data[$key] = trim($data[$key]);
            }
        }

        if (isset($data['category_id'])) {
            $data['category_id'] = (int) $data['category_id'];
        }

        if (isset($data['price_minor'])) {
            $data['price_minor'] = max(0, (int) $data['price_minor']);
        }

        if (isset($data['stock'])) {
            $data['stock'] = max(0, (int) $data['stock']);
        }

        if (isset($data['currency']) && is_string($data['currency'])) {
            $data['currency'] = strtoupper($data['currency']);
        }

        if (isset($data['visibility']) && is_string($data['visibility'])) {
            $data['visibility'] = strtolower($data['visibility']);
        }

        if (array_key_exists('is_published', $data)) {
            $value = $data['is_published'];
            $normalized = is_bool($value) ? $value : in_array(
                strtolower(trim((string) $value)),
                ['1', 'true', 'yes', 'on', 'published'],
                true
            );
            $data['is_published'] = $normalized;
        }

        return $data;
    }
}
