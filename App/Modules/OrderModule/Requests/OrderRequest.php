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
                'shipping_option' => ['methods' => 'string', 'required' => false],
                'service_point_id' => ['methods' => 'string', 'required' => false],
                'service_point_name' => ['methods' => 'string', 'required' => false],
                'coupon_code' => ['methods' => 'string', 'required' => false],
                'payment_driver' => ['methods' => 'string', 'required' => false],
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
                'shipping_option' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^[a-z0-9-]{4,80}$/']],
                'service_point_id' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^[A-Za-z0-9._:-]{2,120}$/']],
                'coupon_code' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^[A-Za-z0-9-]{4,64}$/']],
                'payment_driver' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^(testing|card|crypto|paypal|klarna|swish|qliro|walley)$/']],
                'payment_method' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^(card|wallet|bank_transfer|bnpl|local_instant|manual|crypto)$/']],
                'payment_flow' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^(authorize_capture|purchase|redirect|async|manual_review)$/']],
                'idempotency_key' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^[A-Za-z0-9._:-]{8,191}$/']],
            ],
            default => [],
        };
    }

    protected function transformInput(array $data): array
    {
        if (isset($data['coupon_code']) && is_string($data['coupon_code'])) {
            $data['coupon_code'] = strtoupper(trim($data['coupon_code']));
        }

        return $data;
    }
}
