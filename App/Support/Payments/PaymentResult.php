<?php

declare(strict_types=1);

namespace App\Support\Payments;

final class PaymentResult implements \JsonSerializable
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly bool $successful,
        public readonly string $action,
        public readonly PaymentIntent $intent,
        public readonly string $driver,
        public readonly string $message = '',
        public readonly array $metadata = []
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'successful' => $this->successful,
            'action' => $this->action,
            'driver' => $this->driver,
            'message' => $this->message,
            'intent' => $this->intent->toArray(),
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
