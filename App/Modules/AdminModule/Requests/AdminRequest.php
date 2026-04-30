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
            'savePage' => [
                'title' => ['methods' => 'string'],
                'slug' => ['methods' => 'string', 'required' => false],
                'content' => ['methods' => 'string'],
                'is_published' => ['methods' => 'string', 'required' => false],
            ],
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
                'fulfillment_type' => ['methods' => 'string', 'required' => false],
                'fulfillment_policy' => ['methods' => 'string', 'required' => false],
                'available_at' => ['methods' => 'string', 'required' => false],
            ],
            'savePromotion' => [
                'code' => ['methods' => 'string'],
                'label' => ['methods' => 'string'],
                'description' => ['methods' => 'string', 'required' => false],
                'type' => ['methods' => 'string', 'required' => false],
                'applies_to' => ['methods' => 'string', 'required' => false],
                'active' => ['methods' => 'string', 'required' => false],
                'rate_bps' => ['methods' => 'integer', 'required' => false],
                'amount_minor' => ['methods' => 'integer', 'required' => false],
                'shipping_rate_minor' => ['methods' => 'integer', 'required' => false],
                'min_subtotal_minor' => ['methods' => 'integer', 'required' => false],
                'max_subtotal_minor' => ['methods' => 'integer', 'required' => false],
                'max_discount_minor' => ['methods' => 'integer', 'required' => false],
                'min_items' => ['methods' => 'integer', 'required' => false],
                'max_items' => ['methods' => 'integer', 'required' => false],
                'usage_limit' => ['methods' => 'integer', 'required' => false],
                'starts_at' => ['methods' => 'string', 'required' => false],
                'ends_at' => ['methods' => 'string', 'required' => false],
                'criteria_json' => ['methods' => 'string', 'required' => false],
                'allowed_currencies' => ['methods' => 'string', 'required' => false],
                'allowed_countries' => ['methods' => 'string', 'required' => false],
                'allowed_zones' => ['methods' => 'string', 'required' => false],
                'allowed_carriers' => ['methods' => 'string', 'required' => false],
                'allowed_shipping_options' => ['methods' => 'string', 'required' => false],
                'allowed_product_ids' => ['methods' => 'string', 'required' => false],
                'allowed_product_slugs' => ['methods' => 'string', 'required' => false],
                'allowed_category_ids' => ['methods' => 'string', 'required' => false],
                'allowed_fulfillment_types' => ['methods' => 'string', 'required' => false],
                'allowed_user_ids' => ['methods' => 'string', 'required' => false],
                'allowed_customer_emails' => ['methods' => 'string', 'required' => false],
                'allowed_customer_segments' => ['methods' => 'string', 'required' => false],
                'excluded_product_ids' => ['methods' => 'string', 'required' => false],
                'excluded_product_slugs' => ['methods' => 'string', 'required' => false],
                'excluded_fulfillment_types' => ['methods' => 'string', 'required' => false],
                'excluded_user_ids' => ['methods' => 'string', 'required' => false],
                'excluded_customer_emails' => ['methods' => 'string', 'required' => false],
                'excluded_customer_segments' => ['methods' => 'string', 'required' => false],
                'required_fulfillment_types' => ['methods' => 'string', 'required' => false],
                'required_customer_segments' => ['methods' => 'string', 'required' => false],
                'per_customer_limit' => ['methods' => 'integer', 'required' => false],
                'per_segment_limit' => ['methods' => 'integer', 'required' => false],
                'free_shipping_eligible_only' => ['methods' => 'string', 'required' => false],
            ],
            'bulkPromotions' => [
                'bulk_action' => ['methods' => 'string'],
                'promotion_ids' => [
                    'required' => false,
                    'each' => ['methods' => 'integer'],
                ],
                'ids' => ['methods' => 'string', 'required' => false],
            ],
            'createOrderReturn' => [
                'type' => ['methods' => 'string'],
                'order_item_id' => ['methods' => 'integer'],
                'quantity' => ['methods' => 'integer'],
                'refund_minor' => ['methods' => 'integer', 'required' => false],
                'exchange_product_id' => ['methods' => 'integer', 'required' => false],
                'reason' => ['methods' => 'string', 'required' => false],
                'resolution' => ['methods' => 'string', 'required' => false],
                'restock' => ['methods' => 'string', 'required' => false],
                'process_refund' => ['methods' => 'string', 'required' => false],
            ],
            'completeOrderReturn' => [
                'refund_amount_minor' => ['methods' => 'integer', 'required' => false],
                'reason' => ['methods' => 'string', 'required' => false],
                'resolution' => ['methods' => 'string', 'required' => false],
                'process_refund' => ['methods' => 'string', 'required' => false],
            ],
            'issueOrderDocument' => [
                'amount_minor' => ['methods' => 'integer', 'required' => false],
                'refund_minor' => ['methods' => 'integer', 'required' => false],
                'return_id' => ['methods' => 'integer', 'required' => false],
                'vat_rate_bps' => ['methods' => 'integer', 'required' => false],
                'billing_country' => ['methods' => 'string', 'required' => false],
                'seller_name' => ['methods' => 'string', 'required' => false],
                'seller_vat_id' => ['methods' => 'string', 'required' => false],
                'seller_address' => ['methods' => 'string', 'required' => false],
                'notes' => ['methods' => 'string', 'required' => false],
            ],
            default => [],
        };
    }

    protected function validationRules(): array
    {
        return match ($this->scenario) {
            'savePage' => [
                'title' => ['methods' => 'string', 'rules' => ['notEmpty', 'minLength' => [2]]],
                'slug' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^[a-z0-9-]{2,191}$/']],
                'content' => ['methods' => 'string', 'rules' => ['notEmpty', 'minLength' => [2]]],
            ],
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
                'fulfillment_type' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^(physical_shipping|digital_download|virtual_access|store_pickup|scheduled_pickup|preorder|subscription)$/']],
            ],
            'savePromotion' => [
                'code' => ['methods' => 'regexp', 'rules' => ['notEmpty'], 'options' => ['pattern' => '/^[A-Za-z0-9_-]{2,64}$/']],
                'label' => ['methods' => 'string', 'rules' => ['notEmpty', 'minLength' => [2]]],
                'type' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^(percentage|fixed_amount|free_shipping|shipping_fixed|shipping_percentage)$/']],
                'applies_to' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^(cart_subtotal|qualified_items)$/']],
                'rate_bps' => ['methods' => 'integer', 'required' => false, 'rules' => ['min' => [0]]],
                'amount_minor' => ['methods' => 'integer', 'required' => false, 'rules' => ['min' => [0]]],
                'shipping_rate_minor' => ['methods' => 'integer', 'required' => false, 'rules' => ['min' => [0]]],
                'min_subtotal_minor' => ['methods' => 'integer', 'required' => false, 'rules' => ['min' => [0]]],
                'max_subtotal_minor' => ['methods' => 'integer', 'required' => false, 'rules' => ['min' => [0]]],
                'max_discount_minor' => ['methods' => 'integer', 'required' => false, 'rules' => ['min' => [0]]],
                'min_items' => ['methods' => 'integer', 'required' => false, 'rules' => ['min' => [0]]],
                'max_items' => ['methods' => 'integer', 'required' => false, 'rules' => ['min' => [0]]],
                'usage_limit' => ['methods' => 'integer', 'required' => false, 'rules' => ['min' => [0]]],
                'per_customer_limit' => ['methods' => 'integer', 'required' => false, 'rules' => ['min' => [0]]],
                'per_segment_limit' => ['methods' => 'integer', 'required' => false, 'rules' => ['min' => [0]]],
            ],
            'bulkPromotions' => [
                'bulk_action' => ['methods' => 'regexp', 'rules' => ['notEmpty'], 'options' => ['pattern' => '/^(activate|deactivate|delete)$/']],
                'promotion_ids' => [
                    'required' => false,
                    'each' => ['methods' => 'integer', 'rules' => ['min' => [1]]],
                ],
                'ids' => ['methods' => 'string', 'required' => false],
            ],
            'createOrderReturn' => [
                'type' => ['methods' => 'regexp', 'rules' => ['notEmpty'], 'options' => ['pattern' => '/^(return|exchange)$/']],
                'order_item_id' => ['methods' => 'integer', 'rules' => ['min' => [1]]],
                'quantity' => ['methods' => 'integer', 'rules' => ['min' => [1]]],
                'refund_minor' => ['methods' => 'integer', 'required' => false, 'rules' => ['min' => [0]]],
                'exchange_product_id' => ['methods' => 'integer', 'required' => false, 'rules' => ['min' => [0]]],
            ],
            'completeOrderReturn' => [
                'refund_amount_minor' => ['methods' => 'integer', 'required' => false, 'rules' => ['min' => [0]]],
            ],
            'issueOrderDocument' => [
                'amount_minor' => ['methods' => 'integer', 'required' => false, 'rules' => ['min' => [0]]],
                'refund_minor' => ['methods' => 'integer', 'required' => false, 'rules' => ['min' => [0]]],
                'return_id' => ['methods' => 'integer', 'required' => false, 'rules' => ['min' => [0]]],
                'vat_rate_bps' => ['methods' => 'integer', 'required' => false, 'rules' => ['min' => [0]]],
                'billing_country' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^[A-Za-z]{2,8}$/']],
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

        foreach ([
            'name',
            'slug',
            'title',
            'content',
            'description',
            'currency',
            'visibility',
            'media',
            'fulfillment_type',
            'fulfillment_policy',
            'available_at',
            'code',
            'label',
            'type',
            'applies_to',
            'bulk_action',
            'starts_at',
            'ends_at',
            'criteria_json',
            'allowed_currencies',
            'allowed_countries',
            'allowed_zones',
            'allowed_carriers',
            'allowed_shipping_options',
            'allowed_product_ids',
            'allowed_product_slugs',
            'allowed_category_ids',
            'allowed_fulfillment_types',
            'allowed_user_ids',
            'allowed_customer_emails',
            'allowed_customer_segments',
            'excluded_product_ids',
            'excluded_product_slugs',
            'excluded_fulfillment_types',
            'excluded_user_ids',
            'excluded_customer_emails',
            'excluded_customer_segments',
            'required_fulfillment_types',
            'required_customer_segments',
            'reason',
            'resolution',
            'restock',
            'process_refund',
            'billing_country',
            'seller_name',
            'seller_vat_id',
            'seller_address',
            'notes',
        ] as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $data[$key] = trim($data[$key]);
            }
        }

        if (isset($data['category_id'])) {
            $data['category_id'] = (int) $data['category_id'];
        }

        foreach (['order_item_id', 'quantity', 'exchange_product_id', 'return_id'] as $key) {
            if (isset($data[$key])) {
                $data[$key] = max(0, (int) $data[$key]);
            }
        }

        if (isset($data['price_minor'])) {
            $data['price_minor'] = max(0, (int) $data['price_minor']);
        }

        if (isset($data['stock'])) {
            $data['stock'] = max(0, (int) $data['stock']);
        }

        foreach ([
            'rate_bps',
            'amount_minor',
            'shipping_rate_minor',
            'min_subtotal_minor',
            'max_subtotal_minor',
            'max_discount_minor',
            'min_items',
            'max_items',
            'usage_limit',
            'per_customer_limit',
            'per_segment_limit',
            'refund_minor',
            'refund_amount_minor',
            'amount_minor',
            'vat_rate_bps',
        ] as $key) {
            if (isset($data[$key])) {
                $data[$key] = max(0, (int) $data[$key]);
            }
        }

        if (isset($data['promotion_ids']) && is_array($data['promotion_ids'])) {
            $data['promotion_ids'] = array_values(array_filter(array_map(
                static fn(mixed $value): int => max(0, (int) $value),
                $data['promotion_ids']
            ), static fn(int $value): bool => $value > 0));
        }

        if (isset($data['currency']) && is_string($data['currency'])) {
            $data['currency'] = strtoupper($data['currency']);
        }

        if (isset($data['code']) && is_string($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        if (isset($data['visibility']) && is_string($data['visibility'])) {
            $data['visibility'] = strtolower($data['visibility']);
        }

        if (isset($data['fulfillment_type']) && is_string($data['fulfillment_type'])) {
            $data['fulfillment_type'] = strtolower($data['fulfillment_type']);
        }

        foreach (['type', 'applies_to', 'bulk_action'] as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $data[$key] = strtolower($data[$key]);
            }
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

        foreach (['active', 'free_shipping_eligible_only'] as $key) {
            if (array_key_exists($key, $data)) {
                $value = $data[$key];
                $data[$key] = is_bool($value) ? $value : in_array(
                    strtolower(trim((string) $value)),
                    ['1', 'true', 'yes', 'on', 'active'],
                    true
                );
            }
        }

        return $data;
    }
}
