<?php

declare(strict_types=1);

namespace App\Support\Payments;

enum PaymentMethod: string
{
    case Card = 'card';
    case Wallet = 'wallet';
    case BankTransfer = 'bank_transfer';
    case Bnpl = 'bnpl';
    case LocalInstant = 'local_instant';
    case Manual = 'manual';
    case Crypto = 'crypto';

    public static function default(): self
    {
        return self::Card;
    }

    public static function fromMixed(string|self|null $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        return self::tryFrom((string) $value) ?? self::default();
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn(self $case): string => $case->value,
            self::cases()
        );
    }
}
