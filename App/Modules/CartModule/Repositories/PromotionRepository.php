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
        'allowed_customer_emails',
        'allowed_customer_segments',
        'excluded_product_slugs',
        'excluded_fulfillment_types',
        'excluded_customer_emails',
        'excluded_customer_segments',
        'required_fulfillment_types',
        'required_customer_segments',
    ];

    /**
     * @var list<string>
     */
    private const CRITERIA_INT_LIST_FIELDS = [
        'allowed_product_ids',
        'allowed_category_ids',
        'allowed_user_ids',
        'excluded_product_ids',
        'excluded_user_ids',
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
     * @param array<string, mixed> $usage
     */
    public function recordUsage(array $usage): bool
    {
        $code = strtoupper(trim((string) ($usage['promotion_code'] ?? $usage['code'] ?? '')));

        if ($code === '') {
            return false;
        }

        try {
            $promotion = $this->findByCode($code);
            $promotionId = $promotion instanceof Promotion
                ? (int) $promotion->getKey()
                : max(0, (int) ($usage['promotion_id'] ?? 0));
            $createdAt = gmdate('Y-m-d H:i:s');
            $context = is_array($usage['context'] ?? null) ? $usage['context'] : [];

            $query = $this->db->dataQuery('promotion_usages')->insert('promotion_usages', [
                'promotion_id' => $promotionId > 0 ? $promotionId : null,
                'promotion_code' => $code,
                'order_id' => max(0, (int) ($usage['order_id'] ?? 0)) ?: null,
                'cart_id' => max(0, (int) ($usage['cart_id'] ?? 0)) ?: null,
                'user_id' => max(0, (int) ($usage['user_id'] ?? 0)) ?: null,
                'currency' => strtoupper(trim((string) ($usage['currency'] ?? 'SEK'))),
                'discount_minor' => max(0, (int) ($usage['discount_minor'] ?? 0)),
                'item_discount_minor' => max(0, (int) ($usage['item_discount_minor'] ?? 0)),
                'shipping_discount_minor' => max(0, (int) ($usage['shipping_discount_minor'] ?? 0)),
                'source' => trim((string) ($usage['source'] ?? ($promotionId > 0 ? 'database' : 'config'))) ?: 'database',
                'context' => $this->toJson($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'created_at' => $createdAt,
            ])->toExecutable();

            $this->db->execute($query['sql'], $query['bindings']);

            if ($promotionId > 0) {
                $this->incrementUsageCount($promotionId, $createdAt);
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function usageSummaries(int $limit = 25): array
    {
        try {
            $query = $this->db
                ->dataQuery('promotion_usages')
                ->select(['*'])
                ->orderBy('id', 'DESC')
                ->limit(max(1, $limit))
                ->toExecutable();

            return array_map(
                fn(array $row): array => $this->mapUsageSummary($row),
                $this->db->fetchAll($query['sql'], $query['bindings'])
            );
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, int>
     */
    public function usageMetrics(): array
    {
        $rows = $this->usageSummaries(1000);

        return [
            'usage_records' => count($rows),
            'database_promotion_usage' => count(array_filter($rows, static fn(array $row): bool => ($row['source'] ?? '') === 'database')),
            'configured_promotion_usage' => count(array_filter($rows, static fn(array $row): bool => ($row['source'] ?? '') === 'config')),
            'total_discount_minor' => array_reduce($rows, static fn(int $carry, array $row): int => $carry + max(0, (int) ($row['discount_minor'] ?? 0)), 0),
            'shipping_discount_minor' => array_reduce($rows, static fn(int $carry, array $row): int => $carry + max(0, (int) ($row['shipping_discount_minor'] ?? 0)), 0),
        ];
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function usageAnalytics(int $limit = 1000): array
    {
        $rows = $this->usageSummaries($limit);

        return [
            'by_code' => $this->aggregateUsage($rows, static fn(array $row): string => (string) ($row['promotion_code'] ?? 'unknown')),
            'by_source' => $this->aggregateUsage($rows, static fn(array $row): string => (string) ($row['source'] ?? 'unknown')),
            'by_currency' => $this->aggregateUsage($rows, static fn(array $row): string => (string) ($row['currency'] ?? 'SEK')),
            'by_customer_segment' => $this->aggregateUsageBySegments($rows),
            'by_customer' => $this->aggregateUsage($rows, static function (array $row): string {
                $context = is_array($row['context'] ?? null) ? $row['context'] : [];
                $email = strtolower(trim((string) ($context['customer_email'] ?? '')));

                if ($email !== '') {
                    return $email;
                }

                $userId = (int) ($row['user_id'] ?? 0);

                return $userId > 0 ? 'user:' . $userId : 'anonymous';
            }),
            'by_day' => $this->aggregateUsage($rows, static function (array $row): string {
                $createdAt = (string) ($row['created_at'] ?? '');
                $timestamp = strtotime($createdAt);

                return $timestamp !== false ? gmdate('Y-m-d', $timestamp) : 'unknown';
            }),
        ];
    }

    public function usageCountForCustomer(string $code, ?int $userId = null, string $email = ''): int
    {
        $code = strtoupper(trim($code));
        $userId = $userId !== null && $userId > 0 ? $userId : null;
        $email = strtolower(trim($email));

        if ($code === '' || ($userId === null && $email === '')) {
            return 0;
        }

        return count(array_filter($this->usageRowsForCode($code), function (array $row) use ($userId, $email): bool {
            $context = $this->decodeCriteria((string) ($row['context'] ?? '[]'));
            $rowEmail = strtolower(trim((string) ($context['customer_email'] ?? '')));

            return ($userId !== null && (int) ($row['user_id'] ?? 0) === $userId)
                || ($email !== '' && $rowEmail === $email);
        }));
    }

    /**
     * @param list<string> $segments
     */
    public function usageCountForSegments(string $code, array $segments): int
    {
        $code = strtoupper(trim($code));
        $segments = array_values(array_unique(array_filter(array_map(
            static fn(mixed $segment): string => strtolower(trim((string) $segment)),
            $segments
        ), static fn(string $segment): bool => $segment !== '')));

        if ($code === '' || $segments === []) {
            return 0;
        }

        return count(array_filter($this->usageRowsForCode($code), function (array $row) use ($segments): bool {
            $context = $this->decodeCriteria((string) ($row['context'] ?? '[]'));
            $rowSegments = array_values(array_unique(array_filter(array_map(
                static fn(mixed $segment): string => strtolower(trim((string) $segment)),
                (array) ($context['customer_segments'] ?? [])
            ), static fn(string $segment): bool => $segment !== '')));

            return array_intersect($segments, $rowSegments) !== [];
        }));
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
            'ID' => (int) ($summary['id'] ?? 0),
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
            'ALLOWED_USER_IDS' => (array) ($criteria['allowed_user_ids'] ?? []),
            'ALLOWED_CUSTOMER_EMAILS' => (array) ($criteria['allowed_customer_emails'] ?? []),
            'ALLOWED_CUSTOMER_SEGMENTS' => (array) ($criteria['allowed_customer_segments'] ?? []),
            'EXCLUDED_PRODUCT_IDS' => (array) ($criteria['excluded_product_ids'] ?? []),
            'EXCLUDED_PRODUCT_SLUGS' => (array) ($criteria['excluded_product_slugs'] ?? []),
            'EXCLUDED_FULFILLMENT_TYPES' => (array) ($criteria['excluded_fulfillment_types'] ?? []),
            'EXCLUDED_USER_IDS' => (array) ($criteria['excluded_user_ids'] ?? []),
            'EXCLUDED_CUSTOMER_EMAILS' => (array) ($criteria['excluded_customer_emails'] ?? []),
            'EXCLUDED_CUSTOMER_SEGMENTS' => (array) ($criteria['excluded_customer_segments'] ?? []),
            'REQUIRED_FULFILLMENT_TYPES' => (array) ($criteria['required_fulfillment_types'] ?? []),
            'REQUIRED_CUSTOMER_SEGMENTS' => (array) ($criteria['required_customer_segments'] ?? []),
            'PER_CUSTOMER_LIMIT' => (int) ($criteria['per_customer_limit'] ?? 0),
            'PER_SEGMENT_LIMIT' => (int) ($criteria['per_segment_limit'] ?? 0),
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

        foreach ([
            'per_customer_limit' => ['per_customer_limit', 'per_user_limit'],
            'per_segment_limit' => ['per_segment_limit', 'customer_segment_limit'],
        ] as $target => $aliases) {
            foreach ($aliases as $alias) {
                $value = max(0, (int) ($criteria[$alias] ?? 0));

                if ($value > 0) {
                    $normalized[$target] = $value;
                    break;
                }
            }
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

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapUsageSummary(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'promotion_id' => (int) ($row['promotion_id'] ?? 0),
            'promotion_code' => (string) ($row['promotion_code'] ?? ''),
            'order_id' => (int) ($row['order_id'] ?? 0),
            'cart_id' => (int) ($row['cart_id'] ?? 0),
            'user_id' => (int) ($row['user_id'] ?? 0),
            'currency' => (string) ($row['currency'] ?? 'SEK'),
            'discount_minor' => max(0, (int) ($row['discount_minor'] ?? 0)),
            'item_discount_minor' => max(0, (int) ($row['item_discount_minor'] ?? 0)),
            'shipping_discount_minor' => max(0, (int) ($row['shipping_discount_minor'] ?? 0)),
            'source' => (string) ($row['source'] ?? 'database'),
            'context' => $this->decodeCriteria((string) ($row['context'] ?? '[]')),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'order_path' => ((int) ($row['order_id'] ?? 0)) > 0 ? '/admin/orders/' . (int) ($row['order_id'] ?? 0) : '',
        ];
    }

    private function incrementUsageCount(int $promotionId, string $timestamp): void
    {
        $this->db->execute(
            'UPDATE promotions SET usage_count = usage_count + 1, updated_at = ? WHERE id = ?',
            [$timestamp, $promotionId]
        );
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param callable(array<string, mixed>): string $keyResolver
     * @return list<array<string, mixed>>
     */
    private function aggregateUsage(array $rows, callable $keyResolver): array
    {
        $buckets = [];

        foreach ($rows as $row) {
            $key = trim($keyResolver($row)) ?: 'unknown';
            $buckets[$key] ??= [
                'key' => $key,
                'uses' => 0,
                'orders' => [],
                'users' => [],
                'discount_minor' => 0,
                'item_discount_minor' => 0,
                'shipping_discount_minor' => 0,
            ];
            $buckets[$key]['uses']++;

            $orderId = (int) ($row['order_id'] ?? 0);
            if ($orderId > 0) {
                $buckets[$key]['orders'][$orderId] = true;
            }

            $userId = (int) ($row['user_id'] ?? 0);
            if ($userId > 0) {
                $buckets[$key]['users'][$userId] = true;
            }

            $buckets[$key]['discount_minor'] += max(0, (int) ($row['discount_minor'] ?? 0));
            $buckets[$key]['item_discount_minor'] += max(0, (int) ($row['item_discount_minor'] ?? 0));
            $buckets[$key]['shipping_discount_minor'] += max(0, (int) ($row['shipping_discount_minor'] ?? 0));
        }

        $summary = array_map(static function (array $bucket): array {
            return [
                'key' => (string) ($bucket['key'] ?? ''),
                'uses' => (int) ($bucket['uses'] ?? 0),
                'orders' => count((array) ($bucket['orders'] ?? [])),
                'users' => count((array) ($bucket['users'] ?? [])),
                'discount_minor' => (int) ($bucket['discount_minor'] ?? 0),
                'item_discount_minor' => (int) ($bucket['item_discount_minor'] ?? 0),
                'shipping_discount_minor' => (int) ($bucket['shipping_discount_minor'] ?? 0),
            ];
        }, array_values($buckets));

        usort($summary, static fn(array $left, array $right): int => ((int) ($right['discount_minor'] ?? 0) <=> (int) ($left['discount_minor'] ?? 0))
            ?: ((int) ($right['uses'] ?? 0) <=> (int) ($left['uses'] ?? 0))
            ?: strcmp((string) ($left['key'] ?? ''), (string) ($right['key'] ?? '')));

        return $summary;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function aggregateUsageBySegments(array $rows): array
    {
        $expanded = [];

        foreach ($rows as $row) {
            $context = is_array($row['context'] ?? null) ? $row['context'] : [];
            $segments = array_values(array_unique(array_filter(array_map(
                static fn(mixed $segment): string => strtolower(trim((string) $segment)),
                (array) ($context['customer_segments'] ?? [])
            ), static fn(string $segment): bool => $segment !== '')));

            if ($segments === []) {
                $segments = ['unsegmented'];
            }

            foreach ($segments as $segment) {
                $expanded[] = [
                    ...$row,
                    '_segment' => $segment,
                ];
            }
        }

        return $this->aggregateUsage($expanded, static fn(array $row): string => (string) ($row['_segment'] ?? 'unsegmented'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function usageRowsForCode(string $code): array
    {
        try {
            return $this->db->fetchAll(
                'SELECT * FROM promotion_usages WHERE promotion_code = ? ORDER BY id DESC',
                [$code]
            );
        } catch (\Throwable) {
            return [];
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
