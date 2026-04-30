<?php

declare(strict_types=1);

namespace App\Contracts\Support;

use App\Support\Payments\PaymentFlow;
use App\Support\Payments\PaymentIntent;
use App\Support\Payments\PaymentMethod;
use App\Support\Payments\PaymentResult;

interface PaymentDriverInterface
{
    public function driverName(): string;

    /**
     * @param array<string, mixed> $settings
     */
    public function configure(array $settings): static;

    /**
     * @return array<string, mixed>
     */
    public function capabilities(): array;

    public function supports(string $feature): bool;

    /**
     * @return array<string, mixed>
     */
    public function readiness(): array;

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

    public function authorize(PaymentIntent $intent): PaymentResult;

    public function capture(PaymentIntent $intent, ?int $amount = null): PaymentResult;

    public function cancel(PaymentIntent $intent, ?string $reason = null): PaymentResult;

    public function refund(PaymentIntent $intent, ?int $amount = null, ?string $reason = null): PaymentResult;

    /**
     * @param array<string, mixed> $payload
     */
    public function reconcile(PaymentIntent $intent, array $payload = []): PaymentResult;
}
