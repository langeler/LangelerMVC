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
        public readonly string $method = 'card',
        public readonly string $flow = 'authorize_capture',
        public readonly ?string $reference = null,
        public readonly ?string $providerReference = null,
        public readonly ?string $externalReference = null,
        public readonly ?string $idempotencyKey = null,
        public readonly ?string $webhookReference = null,
        public readonly array $nextAction = [],
        public readonly bool $customerActionRequired = false,
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
            PaymentMethod::fromMixed(isset($payload['method']) ? (string) $payload['method'] : null)->value,
            PaymentFlow::fromMixed(isset($payload['flow']) ? (string) $payload['flow'] : null)->value,
            isset($payload['reference']) ? (string) $payload['reference'] : null,
            isset($payload['providerReference']) ? (string) $payload['providerReference'] : (isset($payload['provider_reference']) ? (string) $payload['provider_reference'] : null),
            isset($payload['externalReference']) ? (string) $payload['externalReference'] : (isset($payload['external_reference']) ? (string) $payload['external_reference'] : null),
            isset($payload['idempotencyKey']) ? (string) $payload['idempotencyKey'] : (isset($payload['idempotency_key']) ? (string) $payload['idempotency_key'] : null),
            isset($payload['webhookReference']) ? (string) $payload['webhookReference'] : (isset($payload['webhook_reference']) ? (string) $payload['webhook_reference'] : null),
            is_array($payload['nextAction'] ?? null) ? $payload['nextAction'] : (is_array($payload['next_action'] ?? null) ? $payload['next_action'] : []),
            (bool) ($payload['customerActionRequired'] ?? $payload['customer_action_required'] ?? false),
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
            $this->method,
            $this->flow,
            $this->reference,
            $this->providerReference,
            $this->externalReference,
            $this->idempotencyKey,
            $this->webhookReference,
            $this->nextAction,
            $this->customerActionRequired,
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
            $this->method,
            $this->flow,
            $reference,
            $this->providerReference,
            $this->externalReference,
            $this->idempotencyKey,
            $this->webhookReference,
            $this->nextAction,
            $this->customerActionRequired,
            $this->status,
            $this->authorizedAmount,
            $this->capturedAmount,
            $this->refundedAmount
        );
    }

    public function withMethod(PaymentMethod|string $method): self
    {
        return new self(
            $this->amount,
            $this->currency,
            $this->description,
            $this->metadata,
            PaymentMethod::fromMixed($method)->value,
            $this->flow,
            $this->reference,
            $this->providerReference,
            $this->externalReference,
            $this->idempotencyKey,
            $this->webhookReference,
            $this->nextAction,
            $this->customerActionRequired,
            $this->status,
            $this->authorizedAmount,
            $this->capturedAmount,
            $this->refundedAmount
        );
    }

    public function withFlow(PaymentFlow|string $flow): self
    {
        return new self(
            $this->amount,
            $this->currency,
            $this->description,
            $this->metadata,
            $this->method,
            PaymentFlow::fromMixed($flow)->value,
            $this->reference,
            $this->providerReference,
            $this->externalReference,
            $this->idempotencyKey,
            $this->webhookReference,
            $this->nextAction,
            $this->customerActionRequired,
            $this->status,
            $this->authorizedAmount,
            $this->capturedAmount,
            $this->refundedAmount
        );
    }

    public function withReferences(
        ?string $reference = null,
        ?string $providerReference = null,
        ?string $externalReference = null,
        ?string $webhookReference = null
    ): self {
        return new self(
            $this->amount,
            $this->currency,
            $this->description,
            $this->metadata,
            $this->method,
            $this->flow,
            $reference ?? $this->reference,
            $providerReference ?? $this->providerReference,
            $externalReference ?? $this->externalReference,
            $this->idempotencyKey,
            $webhookReference ?? $this->webhookReference,
            $this->nextAction,
            $this->customerActionRequired,
            $this->status,
            $this->authorizedAmount,
            $this->capturedAmount,
            $this->refundedAmount
        );
    }

    public function withIdempotencyKey(?string $idempotencyKey): self
    {
        return new self(
            $this->amount,
            $this->currency,
            $this->description,
            $this->metadata,
            $this->method,
            $this->flow,
            $this->reference,
            $this->providerReference,
            $this->externalReference,
            $idempotencyKey,
            $this->webhookReference,
            $this->nextAction,
            $this->customerActionRequired,
            $this->status,
            $this->authorizedAmount,
            $this->capturedAmount,
            $this->refundedAmount
        );
    }

    /**
     * @param array<string, mixed> $nextAction
     */
    public function withNextAction(array $nextAction = [], ?bool $customerActionRequired = null): self
    {
        return new self(
            $this->amount,
            $this->currency,
            $this->description,
            $this->metadata,
            $this->method,
            $this->flow,
            $this->reference,
            $this->providerReference,
            $this->externalReference,
            $this->idempotencyKey,
            $this->webhookReference,
            $nextAction,
            $customerActionRequired ?? $this->customerActionRequired,
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
            $this->method,
            $this->flow,
            $this->reference,
            $this->providerReference,
            $this->externalReference,
            $this->idempotencyKey,
            $this->webhookReference,
            $this->nextAction,
            $this->customerActionRequired,
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
            'method' => $this->method,
            'flow' => $this->flow,
            'reference' => $this->reference,
            'providerReference' => $this->providerReference,
            'externalReference' => $this->externalReference,
            'idempotencyKey' => $this->idempotencyKey,
            'webhookReference' => $this->webhookReference,
            'nextAction' => $this->nextAction,
            'customerActionRequired' => $this->customerActionRequired,
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
