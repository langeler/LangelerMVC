<?php

declare(strict_types=1);

namespace App\Support\Payments;

enum PaymentFlow: string
{
    case AuthorizeCapture = 'authorize_capture';
    case Purchase = 'purchase';
    case Redirect = 'redirect';
    case Async = 'async';
    case ManualReview = 'manual_review';

    public static function default(): self
    {
        return self::AuthorizeCapture;
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
