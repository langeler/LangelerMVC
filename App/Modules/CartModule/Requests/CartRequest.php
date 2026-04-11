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

        return $data;
    }
}
