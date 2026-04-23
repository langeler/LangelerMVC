<?php

declare(strict_types=1);

namespace App\Support\Commerce;

use App\Core\Config;
use App\Utilities\Traits\MoneyFormattingTrait;

class PromotionManager
{
    use MoneyFormattingTrait;

    public function __construct(private readonly Config $config)
    {
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
                'label' => (string) ($promotion['label'] ?? ''),
                'description' => (string) ($promotion['description'] ?? ''),
                'type' => (string) ($promotion['type'] ?? 'fixed_amount'),
                'active' => (bool) ($promotion['active'] ?? true),
                'eligible' => (bool) ($evaluation['valid'] ?? false),
                'message' => (string) ($evaluation['message'] ?? ''),
                'currency' => $currency,
                'min_subtotal_minor' => (int) ($promotion['min_subtotal_minor'] ?? 0),
                'min_subtotal' => $this->formatMoneyMinor((int) ($promotion['min_subtotal_minor'] ?? 0), $currency),
                'estimated_discount_minor' => (int) ($evaluation['discount_minor'] ?? 0),
                'estimated_discount' => (string) ($evaluation['discount'] ?? $this->formatMoneyMinor(0, $currency)),
                'estimated_shipping_discount_minor' => (int) ($evaluation['shipping_discount_minor'] ?? 0),
                'estimated_shipping_discount' => (string) ($evaluation['shipping_discount'] ?? $this->formatMoneyMinor(0, $currency)),
                'effect' => $this->effectLabel($promotion, $currency),
                'allowed_countries' => (array) ($promotion['allowed_countries'] ?? []),
                'allowed_zones' => (array) ($promotion['allowed_zones'] ?? []),
                'allowed_carriers' => (array) ($promotion['allowed_carriers'] ?? []),
                'allowed_shipping_options' => (array) ($promotion['allowed_shipping_options'] ?? []),
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
            'label' => (string) ($promotion['label'] ?? ''),
            'description' => (string) ($promotion['description'] ?? ''),
            'type' => (string) ($promotion['type'] ?? 'fixed_amount'),
            'currency' => $currency,
            'valid' => false,
            'applied' => false,
            'message' => '',
            'subtotal_minor' => $subtotalMinor,
            'subtotal' => $this->formatMoneyMinor($subtotalMinor, $currency),
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

        $minSubtotalMinor = max(0, (int) ($promotion['min_subtotal_minor'] ?? 0));

        if ($minSubtotalMinor > 0 && $subtotalMinor < $minSubtotalMinor) {
            return [
                ...$base,
                'message' => sprintf('This promotion requires a cart subtotal of at least %s.', $this->formatMoneyMinor($minSubtotalMinor, $currency)),
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
                $itemDiscountMinor = (int) round($subtotalMinor * (max(0, (int) ($promotion['rate_bps'] ?? 0)) / 10000));
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

        $itemDiscountMinor = min($subtotalMinor, max(0, $itemDiscountMinor));
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
            'item_discount_minor' => $itemDiscountMinor,
            'shipping_discount_minor' => $shippingDiscountMinor,
            'discount_minor' => $discountMinor,
            'shipping_base_minor' => $shippingBaseMinor,
            'country' => $country,
            'zone' => $zone,
            'carrier' => $carrier,
            'shipping_option' => $shippingOption,
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

        if (!is_array($configured)) {
            return [];
        }

        $promotions = [];

        foreach ($configured as $code => $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $promotions[] = $this->normalizePromotion((string) $code, $definition, $currency);
        }

        return $promotions;
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
        return [
            'code' => strtoupper(trim((string) ($definition['CODE'] ?? $code))),
            'label' => trim((string) ($definition['LABEL'] ?? $definition['label'] ?? strtoupper($code))),
            'description' => trim((string) ($definition['DESCRIPTION'] ?? $definition['description'] ?? '')),
            'type' => strtolower(trim((string) ($definition['TYPE'] ?? $definition['type'] ?? 'fixed_amount'))),
            'active' => (bool) ($definition['ACTIVE'] ?? $definition['active'] ?? true),
            'rate_bps' => max(0, (int) ($definition['RATE_BPS'] ?? $definition['rate_bps'] ?? 0)),
            'amount_minor' => max(0, (int) ($definition['AMOUNT_MINOR'] ?? $definition['amount_minor'] ?? 0)),
            'shipping_rate_minor' => max(0, (int) ($definition['SHIPPING_RATE_MINOR'] ?? $definition['shipping_rate_minor'] ?? 0)),
            'min_subtotal_minor' => max(0, (int) ($definition['MIN_SUBTOTAL_MINOR'] ?? $definition['min_subtotal_minor'] ?? 0)),
            'max_discount_minor' => max(0, (int) ($definition['MAX_DISCOUNT_MINOR'] ?? $definition['max_discount_minor'] ?? 0)),
            'free_shipping_eligible_only' => (bool) ($definition['FREE_SHIPPING_ELIGIBLE_ONLY'] ?? $definition['free_shipping_eligible_only'] ?? false),
            'allowed_countries' => array_values(array_map('strtoupper', array_map('strval', (array) ($definition['ALLOWED_COUNTRIES'] ?? $definition['allowed_countries'] ?? [])))),
            'allowed_zones' => array_values(array_map('strtoupper', array_map('strval', (array) ($definition['ALLOWED_ZONES'] ?? $definition['allowed_zones'] ?? [])))),
            'allowed_carriers' => array_values(array_map('strtolower', array_map('strval', (array) ($definition['ALLOWED_CARRIERS'] ?? $definition['allowed_carriers'] ?? [])))),
            'allowed_shipping_options' => array_values(array_map('strtolower', array_map('strval', (array) ($definition['ALLOWED_SHIPPING_OPTIONS'] ?? $definition['allowed_shipping_options'] ?? [])))),
            'currency' => $currency,
        ];
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
            'currency' => $currency,
            'valid' => false,
            'applied' => false,
            'message' => '',
            'subtotal_minor' => 0,
            'subtotal' => $this->formatMoneyMinor(0, $currency),
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

    private function normalizeCurrency(string $currency): string
    {
        $currency = strtoupper(trim($currency));

        return $currency !== '' ? $currency : strtoupper((string) $this->config->get('commerce', 'CURRENCY', 'SEK'));
    }
}
