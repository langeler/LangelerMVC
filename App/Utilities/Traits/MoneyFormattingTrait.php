<?php

declare(strict_types=1);

namespace App\Utilities\Traits;

/**
 * Provides a shared framework representation for money stored in minor units.
 */
trait MoneyFormattingTrait
{
    public function formatMoneyMinor(int $amount, string $currency): string
    {
        return strtoupper(trim($currency)) . ' ' . number_format($amount / 100, 2, '.', ' ');
    }
}
