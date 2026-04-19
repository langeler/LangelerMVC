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
                'payment_method' => ['methods' => 'string', 'required' => false],
                'payment_flow' => ['methods' => 'string', 'required' => false],
                'idempotency_key' => ['methods' => 'string', 'required' => false],
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
                'payment_method' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^(card|wallet|bank_transfer|bnpl|local_instant|manual|crypto)$/']],
                'payment_flow' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^(authorize_capture|purchase|redirect|async|manual_review)$/']],
                'idempotency_key' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^[A-Za-z0-9._:-]{8,191}$/']],
            ],
            default => [],
        };
    }
}
