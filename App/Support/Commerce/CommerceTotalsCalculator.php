<?php

declare(strict_types=1);

namespace App\Support\Commerce;

use App\Core\Config;
use App\Utilities\Traits\MoneyFormattingTrait;

class CommerceTotalsCalculator
{
    use MoneyFormattingTrait;

    public function __construct(private readonly Config $config)
    {
    }

    /**
     * @param list<array<string, mixed>> $items
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function calculate(array $items, string $currency = 'SEK', array $context = []): array
    {
        $currency = strtoupper(trim($currency)) !== ''
            ? strtoupper(trim($currency))
            : strtoupper((string) $this->config->get('commerce', 'CURRENCY', 'SEK'));

        $subtotalMinor = array_reduce(
            $items,
            static fn(int $carry, array $item): int => $carry + max(0, (int) ($item['line_total_minor'] ?? 0)),
            0
        );

        $discountRateBps = max(0, (int) ($context['discount_rate_bps'] ?? $this->config->get('commerce', 'DISCOUNT.RATE_BPS', 0)));
        $configuredDiscountMinor = max(0, (int) ($context['discount_minor'] ?? 0));
        $discountCapMinor = max(0, (int) $this->config->get('commerce', 'DISCOUNT.MAX_MINOR', 0));
        $discountMinor = $configuredDiscountMinor > 0
            ? min($subtotalMinor, $configuredDiscountMinor)
            : (int) round($subtotalMinor * ($discountRateBps / 10000));

        if ($discountCapMinor > 0) {
            $discountMinor = min($discountMinor, $discountCapMinor);
        }

        $discountMinor = min($subtotalMinor, max(0, $discountMinor));
        $discountedSubtotalMinor = max(0, $subtotalMinor - $discountMinor);

        $flatShippingMinor = max(0, (int) ($context['shipping_flat_rate_minor'] ?? $this->config->get('commerce', 'SHIPPING.FLAT_RATE_MINOR', 0)));
        $freeShippingOverMinor = max(0, (int) ($context['free_shipping_over_minor'] ?? $this->config->get('commerce', 'SHIPPING.FREE_OVER_MINOR', 0)));
        $configuredShippingMinor = $context['shipping_minor'] ?? null;
        $shippingMinor = is_numeric($configuredShippingMinor)
            ? max(0, (int) $configuredShippingMinor)
            : (($freeShippingOverMinor > 0 && $discountedSubtotalMinor >= $freeShippingOverMinor) ? 0 : $flatShippingMinor);

        $taxRateBps = max(0, (int) ($context['tax_rate_bps'] ?? $this->config->get('commerce', 'TAX.RATE_BPS', 0)));
        $taxableMinor = max(0, $discountedSubtotalMinor + $shippingMinor);
        $configuredTaxMinor = $context['tax_minor'] ?? null;
        $taxMinor = is_numeric($configuredTaxMinor)
            ? max(0, (int) $configuredTaxMinor)
            : (int) round($taxableMinor * ($taxRateBps / 10000));

        $totalMinor = max(0, $discountedSubtotalMinor + $shippingMinor + $taxMinor);

        return [
            'currency' => $currency,
            'subtotal_minor' => $subtotalMinor,
            'discount_minor' => $discountMinor,
            'shipping_minor' => $shippingMinor,
            'tax_minor' => $taxMinor,
            'total_minor' => $totalMinor,
            'subtotal' => $this->formatMoneyMinor($subtotalMinor, $currency),
            'discount' => $this->formatMoneyMinor($discountMinor, $currency),
            'shipping' => $this->formatMoneyMinor($shippingMinor, $currency),
            'tax' => $this->formatMoneyMinor($taxMinor, $currency),
            'total' => $this->formatMoneyMinor($totalMinor, $currency),
            'breakdown' => [
                'subtotal' => $this->formatMoneyMinor($subtotalMinor, $currency),
                'discount' => $this->formatMoneyMinor($discountMinor, $currency),
                'shipping' => $this->formatMoneyMinor($shippingMinor, $currency),
                'tax' => $this->formatMoneyMinor($taxMinor, $currency),
                'total' => $this->formatMoneyMinor($totalMinor, $currency),
            ],
            'rates' => [
                'discount_rate_bps' => $discountRateBps,
                'tax_rate_bps' => $taxRateBps,
                'shipping_flat_rate_minor' => $flatShippingMinor,
                'free_shipping_over_minor' => $freeShippingOverMinor,
            ],
        ];
    }
}
