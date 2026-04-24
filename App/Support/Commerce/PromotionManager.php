<?php

declare(strict_types=1);

namespace App\Support\Commerce;

use App\Core\Config;
use App\Modules\CartModule\Repositories\PromotionRepository;
use App\Utilities\Traits\MoneyFormattingTrait;

class PromotionManager
{
    use MoneyFormattingTrait;

    public function __construct(
        private readonly Config $config,
        private readonly ?PromotionRepository $repository = null
    ) {
    }

    /**
     * @param list<array<string, mixed>> $items
     * @param array<string, mixed> $quote
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function evaluate(string $code, array $items, string $currency = 'SEK', array $quote = [], array $context = []): array
    {
        $currency = $this->normalizeCurrency($currency);
        $normalizedCode = strtoupper(trim($code));

        if ($normalizedCode === '') {
            return $this->emptyEvaluation($currency);
        }

        $promotion = $this->promotionByCode($normalizedCode, $currency);

        if ($promotion === null) {
            return [
                ...$this->emptyEvaluation($currency),
                'requested_code' => $normalizedCode,
                'message' => 'The provided promotion code could not be found.',
            ];
        }

        return $this->evaluatePromotion($promotion, $items, $currency, $quote, $context);
    }

    /**
     * @param list<array<string, mixed>> $items
     * @param array<string, mixed> $quote
     * @param array<string, mixed> $context
     * @return list<array<string, mixed>>
     */
    public function catalog(array $items = [], string $currency = 'SEK', array $quote = [], array $context = []): array
    {
        $currency = $this->normalizeCurrency($currency);
        $catalog = [];

        foreach ($this->configuredPromotions($currency) as $promotion) {
            $evaluation = $this->evaluatePromotion($promotion, $items, $currency, $quote, $context);
            $catalog[] = [
                'code' => (string) ($promotion['code'] ?? ''),
                'promotion_id' => (int) ($promotion['id'] ?? 0),
                'label' => (string) ($promotion['label'] ?? ''),
                'description' => (string) ($promotion['description'] ?? ''),
                'type' => (string) ($promotion['type'] ?? 'fixed_amount'),
                'source' => (string) ($promotion['source'] ?? 'config'),
                'active' => (bool) ($promotion['active'] ?? true),
                'eligible' => (bool) ($evaluation['valid'] ?? false),
                'message' => (string) ($evaluation['message'] ?? ''),
                'currency' => $currency,
                'min_subtotal_minor' => (int) ($promotion['min_subtotal_minor'] ?? 0),
                'min_subtotal' => $this->formatMoneyMinor((int) ($promotion['min_subtotal_minor'] ?? 0), $currency),
                'max_subtotal_minor' => (int) ($promotion['max_subtotal_minor'] ?? 0),
                'max_subtotal' => $this->formatMoneyMinor((int) ($promotion['max_subtotal_minor'] ?? 0), $currency),
                'min_items' => (int) ($promotion['min_items'] ?? 0),
                'max_items' => (int) ($promotion['max_items'] ?? 0),
                'applies_to' => (string) ($promotion['applies_to'] ?? 'cart_subtotal'),
                'estimated_discount_minor' => (int) ($evaluation['discount_minor'] ?? 0),
                'estimated_discount' => (string) ($evaluation['discount'] ?? $this->formatMoneyMinor(0, $currency)),
                'estimated_shipping_discount_minor' => (int) ($evaluation['shipping_discount_minor'] ?? 0),
                'estimated_shipping_discount' => (string) ($evaluation['shipping_discount'] ?? $this->formatMoneyMinor(0, $currency)),
                'effect' => $this->effectLabel($promotion, $currency),
                'allowed_currencies' => (array) ($promotion['allowed_currencies'] ?? []),
                'allowed_countries' => (array) ($promotion['allowed_countries'] ?? []),
                'allowed_zones' => (array) ($promotion['allowed_zones'] ?? []),
                'allowed_carriers' => (array) ($promotion['allowed_carriers'] ?? []),
                'allowed_shipping_options' => (array) ($promotion['allowed_shipping_options'] ?? []),
                'allowed_fulfillment_types' => (array) ($promotion['allowed_fulfillment_types'] ?? []),
                'required_fulfillment_types' => (array) ($promotion['required_fulfillment_types'] ?? []),
                'usage_limit' => (int) ($promotion['usage_limit'] ?? 0),
                'usage_count' => (int) ($promotion['usage_count'] ?? 0),
            ];
        }

        usort($catalog, static function (array $left, array $right): int {
            return strcmp((string) ($left['code'] ?? ''), (string) ($right['code'] ?? ''));
        });

        return $catalog;
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluatePromotion(array $promotion, array $items, string $currency, array $quote, array $context): array
    {
        $subtotalMinor = array_reduce(
            $items,
            static fn(int $carry, array $item): int => $carry + max(0, (int) ($item['line_total_minor'] ?? 0)),
            0
        );
        $eligibleItems = $this->eligibleItems($items, $promotion);
        $eligibleSubtotalMinor = array_reduce(
            $eligibleItems,
            static fn(int $carry, array $item): int => $carry + max(0, (int) ($item['line_total_minor'] ?? 0)),
            0
        );
        $appliesTo = (string) ($promotion['applies_to'] ?? 'cart_subtotal');
        $discountBaseMinor = $appliesTo === 'qualified_items' ? $eligibleSubtotalMinor : $subtotalMinor;
        $itemCount = array_reduce(
            $items,
            static fn(int $carry, array $item): int => $carry + max(0, (int) ($item['quantity'] ?? 0)),
            0
        );
        $fulfillmentTypes = $this->cartFulfillmentTypes($items);
        $selected = is_array($quote['selected'] ?? null) ? $quote['selected'] : [];
        $shippingBaseMinor = max(0, (int) ($selected['effective_rate_minor'] ?? $selected['rate_minor'] ?? 0));
        $country = strtoupper((string) ($quote['country'] ?? $context['country'] ?? 'SE'));
        $zone = strtoupper((string) ($quote['zone'] ?? $context['zone'] ?? 'SE'));
        $carrier = strtolower((string) ($selected['carrier_code'] ?? $context['carrier_code'] ?? ''));
        $shippingOption = strtolower((string) ($selected['code'] ?? $context['shipping_option'] ?? ''));
        $freeShippingEligible = (bool) ($selected['free_shipping_eligible'] ?? false);

        $base = [
            'requested_code' => (string) ($promotion['code'] ?? ''),
            'code' => (string) ($promotion['code'] ?? ''),
            'promotion_id' => (int) ($promotion['id'] ?? 0),
            'label' => (string) ($promotion['label'] ?? ''),
            'description' => (string) ($promotion['description'] ?? ''),
            'type' => (string) ($promotion['type'] ?? 'fixed_amount'),
            'source' => (string) ($promotion['source'] ?? 'config'),
            'currency' => $currency,
            'valid' => false,
            'applied' => false,
            'message' => '',
            'subtotal_minor' => $subtotalMinor,
            'subtotal' => $this->formatMoneyMinor($subtotalMinor, $currency),
            'eligible_subtotal_minor' => $eligibleSubtotalMinor,
            'eligible_subtotal' => $this->formatMoneyMinor($eligibleSubtotalMinor, $currency),
            'discount_base_minor' => $discountBaseMinor,
            'item_count' => $itemCount,
            'applies_to' => $appliesTo,
            'item_discount_minor' => 0,
            'shipping_discount_minor' => 0,
            'discount_minor' => 0,
            'discount' => $this->formatMoneyMinor(0, $currency),
            'shipping_discount' => $this->formatMoneyMinor(0, $currency),
            'shipping_base_minor' => $shippingBaseMinor,
            'shipping_base' => $this->formatMoneyMinor($shippingBaseMinor, $currency),
            'snapshot' => [],
        ];

        if (!(bool) ($promotion['active'] ?? true)) {
            return [
                ...$base,
                'message' => 'This promotion code is not currently active.',
            ];
        }

        $usageLimit = max(0, (int) ($promotion['usage_limit'] ?? 0));
        $usageCount = max(0, (int) ($promotion['usage_count'] ?? 0));

        if ($usageLimit > 0 && $usageCount >= $usageLimit) {
            return [
                ...$base,
                'message' => 'This promotion has reached its usage limit.',
            ];
        }

        if (!$this->currencyAllowed($promotion, $currency)) {
            return [
                ...$base,
                'message' => 'This promotion is not available for the selected currency.',
            ];
        }

        $activeWindow = $this->activeWindow($promotion);
        if (!$activeWindow['active']) {
            return [
                ...$base,
                'message' => (string) ($activeWindow['message'] ?? 'This promotion is not available right now.'),
            ];
        }

        $minSubtotalMinor = max(0, (int) ($promotion['min_subtotal_minor'] ?? 0));

        if ($minSubtotalMinor > 0 && $subtotalMinor < $minSubtotalMinor) {
            return [
                ...$base,
                'message' => sprintf('This promotion requires a cart subtotal of at least %s.', $this->formatMoneyMinor($minSubtotalMinor, $currency)),
            ];
        }

        $maxSubtotalMinor = max(0, (int) ($promotion['max_subtotal_minor'] ?? 0));

        if ($maxSubtotalMinor > 0 && $subtotalMinor > $maxSubtotalMinor) {
            return [
                ...$base,
                'message' => sprintf('This promotion is only available for cart subtotals up to %s.', $this->formatMoneyMinor($maxSubtotalMinor, $currency)),
            ];
        }

        $minItems = max(0, (int) ($promotion['min_items'] ?? 0));
        if ($minItems > 0 && $itemCount < $minItems) {
            return [
                ...$base,
                'message' => sprintf('This promotion requires at least %d cart items.', $minItems),
            ];
        }

        $maxItems = max(0, (int) ($promotion['max_items'] ?? 0));
        if ($maxItems > 0 && $itemCount > $maxItems) {
            return [
                ...$base,
                'message' => sprintf('This promotion is only available for carts with %d items or fewer.', $maxItems),
            ];
        }

        $requiredFulfillmentTypes = array_map('strtolower', array_map('strval', (array) ($promotion['required_fulfillment_types'] ?? [])));
        foreach ($requiredFulfillmentTypes as $requiredType) {
            if (!in_array($requiredType, $fulfillmentTypes, true)) {
                return [
                    ...$base,
                    'message' => 'This promotion requires a different product fulfillment mix.',
                ];
            }
        }

        if (!$this->hasEligibleItems($promotion, $items, $eligibleItems)) {
            return [
                ...$base,
                'message' => 'This promotion does not match the products currently in the cart.',
            ];
        }

        $allowedCountries = array_map('strtoupper', array_map('strval', (array) ($promotion['allowed_countries'] ?? [])));
        if ($allowedCountries !== [] && !in_array($country, $allowedCountries, true)) {
            return [
                ...$base,
                'message' => 'This promotion is not available for the selected shipping country.',
            ];
        }

        $allowedZones = array_map('strtoupper', array_map('strval', (array) ($promotion['allowed_zones'] ?? [])));
        if ($allowedZones !== [] && !in_array($zone, $allowedZones, true)) {
            return [
                ...$base,
                'message' => 'This promotion is not available for the selected shipping zone.',
            ];
        }

        $allowedCarriers = array_map('strtolower', array_map('strval', (array) ($promotion['allowed_carriers'] ?? [])));
        if ($allowedCarriers !== [] && !in_array($carrier, $allowedCarriers, true)) {
            return [
                ...$base,
                'message' => 'This promotion is not valid for the selected carrier.',
            ];
        }

        $allowedOptions = array_map('strtolower', array_map('strval', (array) ($promotion['allowed_shipping_options'] ?? [])));
        if ($allowedOptions !== [] && !in_array($shippingOption, $allowedOptions, true)) {
            return [
                ...$base,
                'message' => 'This promotion is not valid for the selected shipping option.',
            ];
        }

        $type = (string) ($promotion['type'] ?? 'fixed_amount');
        $itemDiscountMinor = 0;
        $shippingDiscountMinor = 0;

        switch ($type) {
            case 'percentage':
                $itemDiscountMinor = (int) round($discountBaseMinor * (max(0, (int) ($promotion['rate_bps'] ?? 0)) / 10000));
                break;

            case 'fixed_amount':
                $itemDiscountMinor = max(0, (int) ($promotion['amount_minor'] ?? 0));
                break;

            case 'free_shipping':
                if ((bool) ($promotion['free_shipping_eligible_only'] ?? false) && !$freeShippingEligible) {
                    return [
                        ...$base,
                        'message' => 'This promotion only applies to free-shipping eligible delivery options.',
                    ];
                }
                $shippingDiscountMinor = $shippingBaseMinor;
                break;

            case 'shipping_fixed':
                $targetRateMinor = max(0, (int) ($promotion['shipping_rate_minor'] ?? 0));
                $shippingDiscountMinor = max(0, $shippingBaseMinor - $targetRateMinor);
                break;

            case 'shipping_percentage':
                $shippingDiscountMinor = (int) round($shippingBaseMinor * (max(0, (int) ($promotion['rate_bps'] ?? 0)) / 10000));
                break;

            default:
                return [
                    ...$base,
                    'message' => 'This promotion uses an unsupported discount type.',
                ];
        }

        $itemDiscountMinor = min($discountBaseMinor, max(0, $itemDiscountMinor));
        $shippingDiscountMinor = min($shippingBaseMinor, max(0, $shippingDiscountMinor));

        $maxDiscountMinor = max(0, (int) ($promotion['max_discount_minor'] ?? 0));
        if ($maxDiscountMinor > 0 && $itemDiscountMinor > $maxDiscountMinor) {
            $itemDiscountMinor = $maxDiscountMinor;
        }

        $discountMinor = $itemDiscountMinor + $shippingDiscountMinor;

        if ($discountMinor <= 0) {
            return [
                ...$base,
                'valid' => true,
                'message' => 'The promotion is valid but does not currently reduce the order total.',
            ];
        }

        $snapshot = [
            'code' => (string) ($promotion['code'] ?? ''),
            'label' => (string) ($promotion['label'] ?? ''),
            'description' => (string) ($promotion['description'] ?? ''),
            'type' => $type,
            'currency' => $currency,
            'subtotal_minor' => $subtotalMinor,
            'eligible_subtotal_minor' => $eligibleSubtotalMinor,
            'discount_base_minor' => $discountBaseMinor,
            'applies_to' => $appliesTo,
            'item_count' => $itemCount,
            'item_discount_minor' => $itemDiscountMinor,
            'shipping_discount_minor' => $shippingDiscountMinor,
            'discount_minor' => $discountMinor,
            'shipping_base_minor' => $shippingBaseMinor,
            'country' => $country,
            'zone' => $zone,
            'carrier' => $carrier,
            'shipping_option' => $shippingOption,
            'fulfillment_types' => $fulfillmentTypes,
            'promotion_id' => (int) ($promotion['id'] ?? 0),
            'source' => (string) ($promotion['source'] ?? 'config'),
        ];

        return [
            ...$base,
            'valid' => true,
            'applied' => true,
            'message' => sprintf('%s was applied to the cart.', (string) ($promotion['label'] ?? $promotion['code'] ?? 'Promotion')),
            'item_discount_minor' => $itemDiscountMinor,
            'shipping_discount_minor' => $shippingDiscountMinor,
            'discount_minor' => $discountMinor,
            'discount' => $this->formatMoneyMinor($discountMinor, $currency),
            'shipping_discount' => $this->formatMoneyMinor($shippingDiscountMinor, $currency),
            'snapshot' => $snapshot,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function configuredPromotions(string $currency): array
    {
        $configured = $this->config->get('commerce', 'PROMOTIONS', []);
        $promotions = [];

        if (is_array($configured)) {
            foreach ($configured as $code => $definition) {
                if (!is_array($definition)) {
                    continue;
                }

                $promotions[] = $this->normalizePromotion((string) $code, [
                    ...$definition,
                    'SOURCE' => 'config',
                ], $currency);
            }
        }

        foreach ($this->databasePromotions($currency) as $definition) {
            $promotions[] = $this->normalizePromotion((string) ($definition['CODE'] ?? ''), [
                ...$definition,
                'SOURCE' => 'database',
            ], $currency);
        }

        $byCode = [];

        foreach ($promotions as $promotion) {
            $code = (string) ($promotion['code'] ?? '');

            if ($code !== '') {
                $byCode[$code] = $promotion;
            }
        }

        return array_values($byCode);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function promotionByCode(string $code, string $currency): ?array
    {
        foreach ($this->configuredPromotions($currency) as $promotion) {
            if (($promotion['code'] ?? '') === $code) {
                return $promotion;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    private function normalizePromotion(string $code, array $definition, string $currency): array
    {
        $amountMinor = $this->currencyAmount($definition, 'AMOUNT_MINOR_BY_CURRENCY', 'amount_minor_by_currency', $currency);
        $minSubtotalMinor = $this->currencyAmount($definition, 'MIN_SUBTOTAL_MINOR_BY_CURRENCY', 'min_subtotal_minor_by_currency', $currency);
        $maxSubtotalMinor = $this->currencyAmount($definition, 'MAX_SUBTOTAL_MINOR_BY_CURRENCY', 'max_subtotal_minor_by_currency', $currency);
        $maxDiscountMinor = $this->currencyAmount($definition, 'MAX_DISCOUNT_MINOR_BY_CURRENCY', 'max_discount_minor_by_currency', $currency);

        return [
            'id' => max(0, (int) ($definition['ID'] ?? $definition['id'] ?? 0)),
            'code' => strtoupper(trim((string) ($definition['CODE'] ?? $code))),
            'label' => trim((string) ($definition['LABEL'] ?? $definition['label'] ?? strtoupper($code))),
            'description' => trim((string) ($definition['DESCRIPTION'] ?? $definition['description'] ?? '')),
            'type' => strtolower(trim((string) ($definition['TYPE'] ?? $definition['type'] ?? 'fixed_amount'))),
            'applies_to' => strtolower(trim((string) ($definition['APPLIES_TO'] ?? $definition['applies_to'] ?? 'cart_subtotal'))),
            'active' => (bool) ($definition['ACTIVE'] ?? $definition['active'] ?? true),
            'rate_bps' => max(0, (int) ($definition['RATE_BPS'] ?? $definition['rate_bps'] ?? 0)),
            'amount_minor' => max(0, $amountMinor ?? (int) ($definition['AMOUNT_MINOR'] ?? $definition['amount_minor'] ?? 0)),
            'shipping_rate_minor' => max(0, (int) ($definition['SHIPPING_RATE_MINOR'] ?? $definition['shipping_rate_minor'] ?? 0)),
            'min_subtotal_minor' => max(0, $minSubtotalMinor ?? (int) ($definition['MIN_SUBTOTAL_MINOR'] ?? $definition['min_subtotal_minor'] ?? 0)),
            'max_subtotal_minor' => max(0, $maxSubtotalMinor ?? (int) ($definition['MAX_SUBTOTAL_MINOR'] ?? $definition['max_subtotal_minor'] ?? 0)),
            'max_discount_minor' => max(0, $maxDiscountMinor ?? (int) ($definition['MAX_DISCOUNT_MINOR'] ?? $definition['max_discount_minor'] ?? 0)),
            'min_items' => max(0, (int) ($definition['MIN_ITEMS'] ?? $definition['min_items'] ?? 0)),
            'max_items' => max(0, (int) ($definition['MAX_ITEMS'] ?? $definition['max_items'] ?? 0)),
            'free_shipping_eligible_only' => (bool) ($definition['FREE_SHIPPING_ELIGIBLE_ONLY'] ?? $definition['free_shipping_eligible_only'] ?? false),
            'usage_limit' => max(0, (int) ($definition['USAGE_LIMIT'] ?? $definition['usage_limit'] ?? 0)),
            'usage_count' => max(0, (int) ($definition['USAGE_COUNT'] ?? $definition['usage_count'] ?? 0)),
            'starts_at' => trim((string) ($definition['STARTS_AT'] ?? $definition['starts_at'] ?? '')),
            'ends_at' => trim((string) ($definition['ENDS_AT'] ?? $definition['ends_at'] ?? '')),
            'allowed_currencies' => array_values(array_map('strtoupper', array_map('strval', (array) ($definition['ALLOWED_CURRENCIES'] ?? $definition['allowed_currencies'] ?? [])))),
            'allowed_countries' => array_values(array_map('strtoupper', array_map('strval', (array) ($definition['ALLOWED_COUNTRIES'] ?? $definition['allowed_countries'] ?? [])))),
            'allowed_zones' => array_values(array_map('strtoupper', array_map('strval', (array) ($definition['ALLOWED_ZONES'] ?? $definition['allowed_zones'] ?? [])))),
            'allowed_carriers' => array_values(array_map('strtolower', array_map('strval', (array) ($definition['ALLOWED_CARRIERS'] ?? $definition['allowed_carriers'] ?? [])))),
            'allowed_shipping_options' => array_values(array_map('strtolower', array_map('strval', (array) ($definition['ALLOWED_SHIPPING_OPTIONS'] ?? $definition['allowed_shipping_options'] ?? [])))),
            'allowed_product_ids' => array_values(array_map('intval', (array) ($definition['ALLOWED_PRODUCT_IDS'] ?? $definition['allowed_product_ids'] ?? []))),
            'allowed_product_slugs' => array_values(array_map('strtolower', array_map('strval', (array) ($definition['ALLOWED_PRODUCT_SLUGS'] ?? $definition['allowed_product_slugs'] ?? [])))),
            'allowed_category_ids' => array_values(array_map('intval', (array) ($definition['ALLOWED_CATEGORY_IDS'] ?? $definition['allowed_category_ids'] ?? []))),
            'allowed_fulfillment_types' => array_values(array_map('strtolower', array_map('strval', (array) ($definition['ALLOWED_FULFILLMENT_TYPES'] ?? $definition['allowed_fulfillment_types'] ?? [])))),
            'excluded_product_ids' => array_values(array_map('intval', (array) ($definition['EXCLUDED_PRODUCT_IDS'] ?? $definition['excluded_product_ids'] ?? []))),
            'excluded_product_slugs' => array_values(array_map('strtolower', array_map('strval', (array) ($definition['EXCLUDED_PRODUCT_SLUGS'] ?? $definition['excluded_product_slugs'] ?? [])))),
            'excluded_fulfillment_types' => array_values(array_map('strtolower', array_map('strval', (array) ($definition['EXCLUDED_FULFILLMENT_TYPES'] ?? $definition['excluded_fulfillment_types'] ?? [])))),
            'required_fulfillment_types' => array_values(array_map('strtolower', array_map('strval', (array) ($definition['REQUIRED_FULFILLMENT_TYPES'] ?? $definition['required_fulfillment_types'] ?? [])))),
            'source' => strtolower(trim((string) ($definition['SOURCE'] ?? $definition['source'] ?? 'config'))),
            'currency' => $currency,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function databasePromotions(string $currency): array
    {
        if ($this->repository === null) {
            return [];
        }

        try {
            return $this->repository->definitionCatalog($currency);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyEvaluation(string $currency): array
    {
        return [
            'requested_code' => '',
            'code' => '',
            'label' => '',
            'description' => '',
            'type' => '',
            'source' => '',
            'currency' => $currency,
            'valid' => false,
            'applied' => false,
            'message' => '',
            'subtotal_minor' => 0,
            'subtotal' => $this->formatMoneyMinor(0, $currency),
            'eligible_subtotal_minor' => 0,
            'eligible_subtotal' => $this->formatMoneyMinor(0, $currency),
            'discount_base_minor' => 0,
            'item_count' => 0,
            'applies_to' => '',
            'item_discount_minor' => 0,
            'shipping_discount_minor' => 0,
            'discount_minor' => 0,
            'discount' => $this->formatMoneyMinor(0, $currency),
            'shipping_discount' => $this->formatMoneyMinor(0, $currency),
            'shipping_base_minor' => 0,
            'shipping_base' => $this->formatMoneyMinor(0, $currency),
            'snapshot' => [],
        ];
    }

    private function effectLabel(array $promotion, string $currency): string
    {
        return match ((string) ($promotion['type'] ?? 'fixed_amount')) {
            'percentage' => sprintf('%s%% off the cart subtotal', number_format(max(0, (int) ($promotion['rate_bps'] ?? 0)) / 100, 0)),
            'fixed_amount' => sprintf('%s off the cart subtotal', $this->formatMoneyMinor((int) ($promotion['amount_minor'] ?? 0), $currency)),
            'free_shipping' => 'Free shipping on eligible delivery methods',
            'shipping_fixed' => sprintf('Shipping reduced to %s', $this->formatMoneyMinor((int) ($promotion['shipping_rate_minor'] ?? 0), $currency)),
            'shipping_percentage' => sprintf('%s%% off shipping', number_format(max(0, (int) ($promotion['rate_bps'] ?? 0)) / 100, 0)),
            default => 'Promotion available',
        };
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    private function eligibleItems(array $items, array $promotion): array
    {
        return array_values(array_filter($items, fn(array $item): bool => $this->itemMatchesCriteria($item, $promotion)));
    }

    /**
     * @param list<array<string, mixed>> $items
     * @param list<array<string, mixed>> $eligibleItems
     */
    private function hasEligibleItems(array $promotion, array $items, array $eligibleItems): bool
    {
        if (!$this->hasItemCriteria($promotion)) {
            return true;
        }

        return $items !== [] && $eligibleItems !== [];
    }

    private function hasItemCriteria(array $promotion): bool
    {
        foreach ([
            'allowed_product_ids',
            'allowed_product_slugs',
            'allowed_category_ids',
            'allowed_fulfillment_types',
            'excluded_product_ids',
            'excluded_product_slugs',
            'excluded_fulfillment_types',
        ] as $key) {
            if ((array) ($promotion[$key] ?? []) !== []) {
                return true;
            }
        }

        return false;
    }

    private function itemMatchesCriteria(array $item, array $promotion): bool
    {
        $productId = (int) ($item['product_id'] ?? $item['id'] ?? 0);
        $slug = strtolower((string) ($item['slug'] ?? ''));
        $categoryId = (int) ($item['category_id'] ?? 0);
        $fulfillmentType = strtolower((string) ($item['fulfillment_type'] ?? 'physical_shipping'));

        $allowedProductIds = (array) ($promotion['allowed_product_ids'] ?? []);
        if ($allowedProductIds !== [] && !in_array($productId, $allowedProductIds, true)) {
            return false;
        }

        $allowedProductSlugs = (array) ($promotion['allowed_product_slugs'] ?? []);
        if ($allowedProductSlugs !== [] && !in_array($slug, $allowedProductSlugs, true)) {
            return false;
        }

        $allowedCategoryIds = (array) ($promotion['allowed_category_ids'] ?? []);
        if ($allowedCategoryIds !== [] && !in_array($categoryId, $allowedCategoryIds, true)) {
            return false;
        }

        $allowedFulfillmentTypes = (array) ($promotion['allowed_fulfillment_types'] ?? []);
        if ($allowedFulfillmentTypes !== [] && !in_array($fulfillmentType, $allowedFulfillmentTypes, true)) {
            return false;
        }

        if (in_array($productId, (array) ($promotion['excluded_product_ids'] ?? []), true)) {
            return false;
        }

        if ($slug !== '' && in_array($slug, (array) ($promotion['excluded_product_slugs'] ?? []), true)) {
            return false;
        }

        return !in_array($fulfillmentType, (array) ($promotion['excluded_fulfillment_types'] ?? []), true);
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return list<string>
     */
    private function cartFulfillmentTypes(array $items): array
    {
        $types = [];

        foreach ($items as $item) {
            $type = strtolower(trim((string) ($item['fulfillment_type'] ?? 'physical_shipping')));

            if ($type !== '') {
                $types[] = $type;
            }
        }

        return array_values(array_unique($types));
    }

    private function currencyAllowed(array $promotion, string $currency): bool
    {
        $allowed = array_map('strtoupper', array_map('strval', (array) ($promotion['allowed_currencies'] ?? [])));

        return $allowed === [] || in_array(strtoupper($currency), $allowed, true);
    }

    /**
     * @return array{active:bool,message?:string}
     */
    private function activeWindow(array $promotion): array
    {
        $now = time();
        $startsAt = trim((string) ($promotion['starts_at'] ?? ''));
        $endsAt = trim((string) ($promotion['ends_at'] ?? ''));

        if ($startsAt !== '' && ($start = strtotime($startsAt)) !== false && $now < $start) {
            return ['active' => false, 'message' => 'This promotion has not started yet.'];
        }

        if ($endsAt !== '' && ($end = strtotime($endsAt)) !== false && $now > $end) {
            return ['active' => false, 'message' => 'This promotion has expired.'];
        }

        return ['active' => true];
    }

    private function currencyAmount(array $definition, string $upperKey, string $lowerKey, string $currency): ?int
    {
        $map = $definition[$upperKey] ?? $definition[$lowerKey] ?? null;

        if (!is_array($map)) {
            return null;
        }

        $currency = strtoupper($currency);

        foreach ($map as $key => $value) {
            if (strtoupper((string) $key) === $currency) {
                return max(0, (int) $value);
            }
        }

        return null;
    }

    private function normalizeCurrency(string $currency): string
    {
        $currency = strtoupper(trim($currency));

        return $currency !== '' ? $currency : strtoupper((string) $this->config->get('commerce', 'CURRENCY', 'SEK'));
    }
}
