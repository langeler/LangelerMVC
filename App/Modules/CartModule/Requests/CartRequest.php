<?php

declare(strict_types=1);

namespace App\Modules\CartModule\Requests;

use App\Abstracts\Http\InboundRequest;

class CartRequest extends InboundRequest
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
            'addItem', 'updateItem' => [
                'product_id' => ['methods' => 'string', 'required' => false],
                'slug' => ['methods' => 'string', 'required' => false],
                'quantity' => ['methods' => 'integer', 'required' => false],
            ],
            'applyDiscount' => [
                'coupon_code' => ['methods' => 'string'],
                'discount_code' => ['methods' => 'string', 'required' => false],
            ],
            default => [],
        };
    }

    protected function validationRules(): array
    {
        return match ($this->scenario) {
            'addItem' => [
                'quantity' => ['methods' => 'integer', 'required' => false, 'rules' => ['min' => [1]]],
            ],
            'updateItem' => [
                'quantity' => ['methods' => 'integer', 'rules' => ['min' => [1]]],
            ],
            'applyDiscount' => [
                'coupon_code' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^[A-Za-z0-9-]{4,64}$/']],
                'discount_code' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^[A-Za-z0-9-]{4,64}$/']],
            ],
            default => [],
        };
    }

    protected function transformInput(array $data): array
    {
        if (isset($data['quantity'])) {
            $data['quantity'] = max(1, (int) $data['quantity']);
        }

        if (isset($data['product_id']) && $data['product_id'] !== '') {
            $data['product_id'] = (int) $data['product_id'];
        }

        if (isset($data['slug']) && is_string($data['slug'])) {
            $data['slug'] = trim($data['slug']);
        }

        foreach (['coupon_code', 'discount_code'] as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $data[$key] = strtoupper(trim($data[$key]));
            }
        }

        if (($data['coupon_code'] ?? '') === '' && ($data['discount_code'] ?? '') !== '') {
            $data['coupon_code'] = $data['discount_code'];
        }

        return $data;
    }
}
