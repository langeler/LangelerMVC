<?php

declare(strict_types=1);

namespace App\Contracts\Support;

interface CarrierAdapterInterface
{
    public function carrierCode(): string;

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
     * @param array<string, mixed> $context
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function servicePoints(array $context, array $payload = []): array;

    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function bookShipment(array $order, array $payload = []): array;

    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function syncTracking(array $order, array $payload = []): array;

    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function cancelShipment(array $order, array $payload = []): array;
}
