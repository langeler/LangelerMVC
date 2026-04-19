<?php

declare(strict_types=1);

namespace App\Contracts\Support;

use App\Support\Payments\PaymentFlow;
use App\Support\Payments\PaymentIntent;
use App\Support\Payments\PaymentMethod;
use App\Support\Payments\PaymentResult;

interface PaymentManagerInterface
{
    public function driverName(): string;

    /**
     * @return array<string, mixed>
     */
    public function capabilities(): array;

    public function supports(string $feature): bool;

    /**
     * @return list<string>
     */
    public function supportedMethods(): array;

    /**
     * @return list<string>
     */
    public function supportedFlows(): array;

    public function supportsMethod(PaymentMethod|string $method): bool;

    public function supportsFlow(PaymentFlow|string $flow): bool;

    /**
     * @param array<string, mixed> $metadata
     */
    public function createIntent(
        int $amount,
        ?string $currency = null,
        string $description = '',
        array $metadata = [],
        PaymentMethod|string|null $method = null,
        PaymentFlow|string|null $flow = null,
        ?string $idempotencyKey = null
    ): PaymentIntent;

    public function authorize(PaymentIntent $intent): PaymentResult;

    public function capture(PaymentIntent $intent, ?int $amount = null): PaymentResult;

    public function cancel(PaymentIntent $intent, ?string $reason = null): PaymentResult;

    public function refund(PaymentIntent $intent, ?int $amount = null, ?string $reason = null): PaymentResult;

    /**
     * @param array<string, mixed> $payload
     */
    public function reconcile(PaymentIntent $intent, array $payload = []): PaymentResult;
}
