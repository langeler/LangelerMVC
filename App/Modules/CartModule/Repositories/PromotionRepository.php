<?php

declare(strict_types=1);

namespace App\Modules\CartModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\CartModule\Models\Promotion;

class PromotionRepository extends Repository
{
    protected string $modelClass = Promotion::class;

    /**
     * @var list<string>
     */
    private const CRITERIA_LIST_FIELDS = [
        'allowed_currencies',
        'allowed_countries',
        'allowed_zones',
        'allowed_carriers',
        'allowed_shipping_options',
        'allowed_product_slugs',
        'allowed_fulfillment_types',
        'excluded_product_slugs',
        'excluded_fulfillment_types',
        'required_fulfillment_types',
    ];

    /**
     * @var list<string>
     */
    private const CRITERIA_INT_LIST_FIELDS = [
        'allowed_product_ids',
        'allowed_category_ids',
        'excluded_product_ids',
    ];

    public function findByCode(string $code): ?Promotion
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return null;
        }

        $promotion = $this->findOneBy(['code' => $code]);

        return $promotion instanceof Promotion ? $promotion : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function allSummary(string $currency = 'SEK'): array
    {
        return array_map(
            fn(Promotion $promotion): array => $this->mapSummary($promotion, $currency),
            array_values(array_filter($this->all(), static fn(mixed $promotion): bool => $promotion instanceof Promotion))
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function definitionCatalog(string $currency = 'SEK'): array
    {
        return array_map(
            fn(array $summary): array => $this->definitionFromSummary($summary, $currency),
            $this->allSummary($currency)
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function savePromotion(array $attributes, int $promotionId = 0): Promotion
    {
        if ($promotionId > 0 && !array_key_exists('usage_count', $attributes)) {
            $existing = $this->find($promotionId);

            if ($existing instanceof Promotion) {
                $attributes['usage_count'] = (int) ($existing->getAttribute('usage_count') ?? 0);
            }
        }

        $attributes = $this->normalizeAttributes($attributes);

        if ($promotionId > 0) {
            $this->update($promotionId, $attributes);
            $fresh = $this->find($promotionId);

            if ($fresh instanceof Promotion) {
                return $fresh;
            }
        }

        /** @var Promotion $promotion */
        $promotion = $this->create($attributes);

        return $promotion;
    }

    public function setActive(int $promotionId, bool $active): ?Promotion
    {
        $this->update($promotionId, ['active' => $active ? 1 : 0]);
        $fresh = $this->find($promotionId);

        return $fresh instanceof Promotion ? $fresh : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function mapSummary(Promotion $promotion, string $currency = 'SEK'): array
    {
        $criteria = $this->decodeCriteria((string) ($promotion->getAttribute('criteria') ?? '[]'));
        $code = strtoupper((string) ($promotion->getAttribute('code') ?? ''));
        $type = (string) ($promotion->getAttribute('type') ?? 'fixed_amount');
        $rateBps = max(0, (int) ($promotion->getAttribute('rate_bps') ?? 0));
        $amountMinor = max(0, (int) ($promotion->getAttribute('amount_minor') ?? 0));
        $shippingRateMinor = max(0, (int) ($promotion->getAttribute('shipping_rate_minor') ?? 0));
        $maxDiscountMinor = max(0, (int) ($promotion->getAttribute('max_discount_minor') ?? 0));
        $usageLimit = max(0, (int) ($promotion->getAttribute('usage_limit') ?? 0));
        $usageCount = max(0, (int) ($promotion->getAttribute('usage_count') ?? 0));

        return [
            'id' => (int) $promotion->getKey(),
            'code' => $code,
            'label' => (string) ($promotion->getAttribute('label') ?? $code),
            'description' => (string) ($promotion->getAttribute('description') ?? ''),
            'type' => $type,
            'applies_to' => (string) ($promotion->getAttribute('applies_to') ?? 'cart_subtotal'),
            'active' => (bool) ($promotion->getAttribute('active') ?? true),
            'rate_bps' => $rateBps,
            'rate_percent' => $rateBps / 100,
            'amount_minor' => $amountMinor,
            'amount' => $this->formatMoneyMinor($amountMinor, $currency),
            'shipping_rate_minor' => $shippingRateMinor,
            'shipping_rate' => $this->formatMoneyMinor($shippingRateMinor, $currency),
            'min_subtotal_minor' => max(0, (int) ($promotion->getAttribute('min_subtotal_minor') ?? 0)),
            'max_subtotal_minor' => max(0, (int) ($promotion->getAttribute('max_subtotal_minor') ?? 0)),
            'max_discount_minor' => $maxDiscountMinor,
            'max_discount' => $this->formatMoneyMinor($maxDiscountMinor, $currency),
            'min_items' => max(0, (int) ($promotion->getAttribute('min_items') ?? 0)),
            'max_items' => max(0, (int) ($promotion->getAttribute('max_items') ?? 0)),
            'usage_limit' => $usageLimit,
            'usage_count' => $usageCount,
            'usage_remaining' => $usageLimit > 0 ? max(0, $usageLimit - $usageCount) : null,
            'starts_at' => (string) ($promotion->getAttribute('starts_at') ?? ''),
            'ends_at' => (string) ($promotion->getAttribute('ends_at') ?? ''),
            'criteria' => $criteria,
            'criteria_input' => $this->criteriaInput($criteria),
            'source' => (string) ($promotion->getAttribute('source') ?? 'database'),
            'created_at' => (string) ($promotion->getAttribute('created_at') ?? ''),
            'updated_at' => (string) ($promotion->getAttribute('updated_at') ?? ''),
            'update_path' => '/admin/promotions/' . (int) $promotion->getKey() . '/update',
            'activate_path' => '/admin/promotions/' . (int) $promotion->getKey() . '/activate',
            'deactivate_path' => '/admin/promotions/' . (int) $promotion->getKey() . '/deactivate',
            'delete_path' => '/admin/promotions/' . (int) $promotion->getKey() . '/delete',
        ];
    }

    /**
     * @param array<string, mixed> $summary
     * @return array<string, mixed>
     */
    private function definitionFromSummary(array $summary, string $currency): array
    {
        $criteria = is_array($summary['criteria'] ?? null) ? $summary['criteria'] : [];

        return [
            'CODE' => (string) ($summary['code'] ?? ''),
            'LABEL' => (string) ($summary['label'] ?? ''),
            'DESCRIPTION' => (string) ($summary['description'] ?? ''),
            'TYPE' => (string) ($summary['type'] ?? 'fixed_amount'),
            'APPLIES_TO' => (string) ($summary['applies_to'] ?? 'cart_subtotal'),
            'ACTIVE' => (bool) ($summary['active'] ?? true),
            'RATE_BPS' => (int) ($summary['rate_bps'] ?? 0),
            'AMOUNT_MINOR' => (int) ($summary['amount_minor'] ?? 0),
            'SHIPPING_RATE_MINOR' => (int) ($summary['shipping_rate_minor'] ?? 0),
            'MIN_SUBTOTAL_MINOR' => (int) ($summary['min_subtotal_minor'] ?? 0),
            'MAX_SUBTOTAL_MINOR' => (int) ($summary['max_subtotal_minor'] ?? 0),
            'MAX_DISCOUNT_MINOR' => (int) ($summary['max_discount_minor'] ?? 0),
            'MIN_ITEMS' => (int) ($summary['min_items'] ?? 0),
            'MAX_ITEMS' => (int) ($summary['max_items'] ?? 0),
            'USAGE_LIMIT' => (int) ($summary['usage_limit'] ?? 0),
            'USAGE_COUNT' => (int) ($summary['usage_count'] ?? 0),
            'STARTS_AT' => (string) ($summary['starts_at'] ?? ''),
            'ENDS_AT' => (string) ($summary['ends_at'] ?? ''),
            'ALLOWED_CURRENCIES' => (array) ($criteria['allowed_currencies'] ?? [$currency]),
            'ALLOWED_COUNTRIES' => (array) ($criteria['allowed_countries'] ?? []),
            'ALLOWED_ZONES' => (array) ($criteria['allowed_zones'] ?? []),
            'ALLOWED_CARRIERS' => (array) ($criteria['allowed_carriers'] ?? []),
            'ALLOWED_SHIPPING_OPTIONS' => (array) ($criteria['allowed_shipping_options'] ?? []),
            'ALLOWED_PRODUCT_IDS' => (array) ($criteria['allowed_product_ids'] ?? []),
            'ALLOWED_PRODUCT_SLUGS' => (array) ($criteria['allowed_product_slugs'] ?? []),
            'ALLOWED_CATEGORY_IDS' => (array) ($criteria['allowed_category_ids'] ?? []),
            'ALLOWED_FULFILLMENT_TYPES' => (array) ($criteria['allowed_fulfillment_types'] ?? []),
            'EXCLUDED_PRODUCT_IDS' => (array) ($criteria['excluded_product_ids'] ?? []),
            'EXCLUDED_PRODUCT_SLUGS' => (array) ($criteria['excluded_product_slugs'] ?? []),
            'EXCLUDED_FULFILLMENT_TYPES' => (array) ($criteria['excluded_fulfillment_types'] ?? []),
            'REQUIRED_FULFILLMENT_TYPES' => (array) ($criteria['required_fulfillment_types'] ?? []),
            'FREE_SHIPPING_ELIGIBLE_ONLY' => (bool) ($criteria['free_shipping_eligible_only'] ?? false),
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function normalizeAttributes(array $attributes): array
    {
        $criteria = is_array($attributes['criteria'] ?? null) ? $attributes['criteria'] : [];

        return [
            'code' => strtoupper(trim((string) ($attributes['code'] ?? ''))),
            'label' => trim((string) ($attributes['label'] ?? '')),
            'description' => trim((string) ($attributes['description'] ?? '')),
            'type' => $this->normalizeType((string) ($attributes['type'] ?? 'fixed_amount')),
            'applies_to' => in_array((string) ($attributes['applies_to'] ?? 'cart_subtotal'), ['cart_subtotal', 'qualified_items'], true)
                ? (string) ($attributes['applies_to'] ?? 'cart_subtotal')
                : 'cart_subtotal',
            'active' => !empty($attributes['active']) ? 1 : 0,
            'rate_bps' => max(0, (int) ($attributes['rate_bps'] ?? 0)),
            'amount_minor' => max(0, (int) ($attributes['amount_minor'] ?? 0)),
            'shipping_rate_minor' => max(0, (int) ($attributes['shipping_rate_minor'] ?? 0)),
            'min_subtotal_minor' => max(0, (int) ($attributes['min_subtotal_minor'] ?? 0)),
            'max_subtotal_minor' => max(0, (int) ($attributes['max_subtotal_minor'] ?? 0)),
            'max_discount_minor' => max(0, (int) ($attributes['max_discount_minor'] ?? 0)),
            'min_items' => max(0, (int) ($attributes['min_items'] ?? 0)),
            'max_items' => max(0, (int) ($attributes['max_items'] ?? 0)),
            'usage_limit' => max(0, (int) ($attributes['usage_limit'] ?? 0)),
            'usage_count' => max(0, (int) ($attributes['usage_count'] ?? 0)),
            'starts_at' => $this->nullableString($attributes['starts_at'] ?? null),
            'ends_at' => $this->nullableString($attributes['ends_at'] ?? null),
            'criteria' => $this->toJson($this->normalizeCriteria($criteria), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'source' => trim((string) ($attributes['source'] ?? 'database')) ?: 'database',
        ];
    }

    /**
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>
     */
    private function normalizeCriteria(array $criteria): array
    {
        $normalized = [];

        foreach (self::CRITERIA_LIST_FIELDS as $field) {
            $values = array_values(array_filter(array_map(
                static fn(mixed $value): string => trim((string) $value),
                (array) ($criteria[$field] ?? [])
            ), static fn(string $value): bool => $value !== ''));

            if ($values !== []) {
                $normalized[$field] = $values;
            }
        }

        foreach (self::CRITERIA_INT_LIST_FIELDS as $field) {
            $values = array_values(array_filter(array_map(
                static fn(mixed $value): int => max(0, (int) $value),
                (array) ($criteria[$field] ?? [])
            ), static fn(int $value): bool => $value > 0));

            if ($values !== []) {
                $normalized[$field] = $values;
            }
        }

        if (array_key_exists('free_shipping_eligible_only', $criteria)) {
            $normalized['free_shipping_eligible_only'] = !empty($criteria['free_shipping_eligible_only']);
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeCriteria(string $payload): array
    {
        if (trim($payload) === '') {
            return [];
        }

        try {
            $decoded = $this->fromJson($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return $this->isArray($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $criteria
     */
    private function criteriaInput(array $criteria): string
    {
        return $criteria === []
            ? ''
            : json_encode($criteria, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));

        return in_array($type, ['percentage', 'fixed_amount', 'free_shipping', 'shipping_fixed', 'shipping_percentage'], true)
            ? $type
            : 'fixed_amount';
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
