<?php

declare(strict_types=1);

namespace App\Modules\ShopModule\Requests;

use App\Abstracts\Http\InboundRequest;

class ShopRequest extends InboundRequest
{
    private string $scenario = 'catalog';

    public function forScenario(string $scenario): static
    {
        $this->scenario = $scenario;

        return $this;
    }

    protected function sanitizationRules(): array
    {
        return match ($this->scenario) {
            'catalog' => [
                'q' => ['methods' => 'string', 'required' => false],
                'availability' => ['methods' => 'string', 'required' => false],
                'sort' => ['methods' => 'string', 'required' => false],
                'page' => ['methods' => 'integer', 'required' => false],
            ],
            default => [],
        };
    }

    protected function validationRules(): array
    {
        return match ($this->scenario) {
            'catalog' => [
                'availability' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^(all|in_stock|out_of_stock)$/']],
                'sort' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^(newest|oldest|name|price_low|price_high)$/']],
                'page' => ['methods' => 'integer', 'required' => false, 'rules' => ['min' => [1]]],
            ],
            default => [],
        };
    }

    protected function transformInput(array $data): array
    {
        if (isset($data['q']) && is_string($data['q'])) {
            $data['q'] = trim($data['q']);
        }

        if (isset($data['availability']) && is_string($data['availability'])) {
            $data['availability'] = strtolower(trim($data['availability']));
        }

        if (isset($data['sort']) && is_string($data['sort'])) {
            $data['sort'] = strtolower(trim($data['sort']));
        }

        if (isset($data['page'])) {
            $data['page'] = max(1, (int) $data['page']);
        }

        return $data;
    }
}
