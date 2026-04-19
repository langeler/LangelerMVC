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
     * @return list<string>
     */
    public function availableDrivers(): array;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function driverCatalog(): array;

    /**
     * @return array<string, mixed>
     */
    public function capabilities(?string $driver = null): array;

    public function supports(string $feature, ?string $driver = null): bool;

    /**
     * @return list<string>
     */
    public function supportedMethods(?string $driver = null): array;

    /**
     * @return list<string>
     */
    public function supportedFlows(?string $driver = null): array;

    public function supportsMethod(PaymentMethod|string $method, ?string $driver = null): bool;

    public function supportsFlow(PaymentFlow|string $flow, ?string $driver = null): bool;

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
        ?string $idempotencyKey = null,
        ?string $driver = null
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
