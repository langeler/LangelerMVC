<?php

declare(strict_types=1);

namespace App\Support\Payments;

final class PaymentIntent implements \JsonSerializable
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly int $amount,
        public readonly string $currency = 'SEK',
        public readonly string $description = '',
        public readonly array $metadata = [],
        public readonly ?string $reference = null,
        public readonly string $status = 'pending',
        public readonly int $authorizedAmount = 0,
        public readonly int $capturedAmount = 0,
        public readonly int $refundedAmount = 0
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            (int) ($payload['amount'] ?? 0),
            (string) ($payload['currency'] ?? 'SEK'),
            (string) ($payload['description'] ?? ''),
            is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
            isset($payload['reference']) ? (string) $payload['reference'] : null,
            (string) ($payload['status'] ?? 'pending'),
            (int) ($payload['authorizedAmount'] ?? $payload['authorized_amount'] ?? 0),
            (int) ($payload['capturedAmount'] ?? $payload['captured_amount'] ?? 0),
            (int) ($payload['refundedAmount'] ?? $payload['refunded_amount'] ?? 0)
        );
    }

    public function withStatus(string $status): self
    {
        return new self(
            $this->amount,
            $this->currency,
            $this->description,
            $this->metadata,
            $this->reference,
            $status,
            $this->authorizedAmount,
            $this->capturedAmount,
            $this->refundedAmount
        );
    }

    public function withReference(string $reference): self
    {
        return new self(
            $this->amount,
            $this->currency,
            $this->description,
            $this->metadata,
            $reference,
            $this->status,
            $this->authorizedAmount,
            $this->capturedAmount,
            $this->refundedAmount
        );
    }

    public function withTotals(?int $authorizedAmount = null, ?int $capturedAmount = null, ?int $refundedAmount = null, ?string $status = null): self
    {
        return new self(
            $this->amount,
            $this->currency,
            $this->description,
            $this->metadata,
            $this->reference,
            $status ?? $this->status,
            $authorizedAmount ?? $this->authorizedAmount,
            $capturedAmount ?? $this->capturedAmount,
            $refundedAmount ?? $this->refundedAmount
        );
    }

    public function remainingCaptureAmount(): int
    {
        return max(0, $this->authorizedAmount - $this->capturedAmount);
    }

    public function remainingRefundAmount(): int
    {
        return max(0, $this->capturedAmount - $this->refundedAmount);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'reference' => $this->reference,
            'status' => $this->status,
            'authorizedAmount' => $this->authorizedAmount,
            'capturedAmount' => $this->capturedAmount,
            'refundedAmount' => $this->refundedAmount,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
