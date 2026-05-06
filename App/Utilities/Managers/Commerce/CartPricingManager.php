<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Commerce;

use App\Support\Commerce\CommerceTotalsCalculator;

class CartPricingManager
{
    public function __construct(
        private readonly ShippingManager $shipping,
        private readonly PromotionManager $promotions,
        private readonly CommerceTotalsCalculator $totals
    ) {
    }

    /**
     * @param list<array<string, mixed>> $items
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function price(array $items, string $currency = 'SEK', array $context = []): array
    {
        $quote = $this->shipping->quote($items, $currency, $context);
        $promotion = $this->promotions->evaluate(
            (string) ($context['discount_code'] ?? $context['coupon_code'] ?? $context['promotion_code'] ?? ''),
            $items,
            $currency,
            $quote,
            $context
        );
        $totals = $this->totals->calculate($items, $currency, [
            'shipping_minor' => (int) (($quote['selected']['effective_rate_minor'] ?? $quote['selected']['rate_minor'] ?? 0)),
            'item_discount_minor' => (int) ($promotion['item_discount_minor'] ?? 0),
            'shipping_discount_minor' => (int) ($promotion['shipping_discount_minor'] ?? 0),
        ]);

        return [
            'shipping_country' => (string) ($quote['country'] ?? 'SE'),
            'shipping_zone' => (string) ($quote['zone'] ?? 'SE'),
            'shipping_option' => (string) (($quote['selected']['code'] ?? '')),
            'shipping_option_label' => (string) (($quote['selected']['label'] ?? '')),
            'shipping_carrier' => (string) (($quote['selected']['carrier_code'] ?? '')),
            'shipping_carrier_label' => (string) (($quote['selected']['carrier_label'] ?? '')),
            'shipping_quote' => $quote,
            'fulfillment' => is_array($quote['fulfillment'] ?? null) ? $quote['fulfillment'] : [],
            'promotion' => $promotion,
            'promotion_catalog' => $this->promotions->catalog($items, $currency, $quote, $context),
            'discount_code' => (string) ($promotion['code'] ?? ''),
            'discount_label' => (string) ($promotion['label'] ?? ''),
            'discount_snapshot' => is_array($promotion['snapshot'] ?? null) ? $promotion['snapshot'] : [],
            ...$totals,
        ];
    }
}
