<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Requests;

use App\Abstracts\Http\InboundRequest;

class OrderRequest extends InboundRequest
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
            'checkout' => [
                'name' => ['methods' => 'string'],
                'email' => ['methods' => 'email'],
                'line_one' => ['methods' => 'string'],
                'line_two' => ['methods' => 'string', 'required' => false],
                'postal_code' => ['methods' => 'string'],
                'city' => ['methods' => 'string'],
                'country' => ['methods' => 'string'],
                'phone' => ['methods' => 'string', 'required' => false],
            ],
            default => [],
        };
    }

    protected function validationRules(): array
    {
        return match ($this->scenario) {
            'checkout' => [
                'name' => ['methods' => 'string', 'rules' => ['notEmpty', 'minLength' => [2]]],
                'email' => ['methods' => 'email'],
                'line_one' => ['methods' => 'string', 'rules' => ['notEmpty']],
                'postal_code' => ['methods' => 'string', 'rules' => ['notEmpty']],
                'city' => ['methods' => 'string', 'rules' => ['notEmpty']],
                'country' => ['methods' => 'string', 'rules' => ['notEmpty']],
            ],
            default => [],
        };
    }
}
