<?php

declare(strict_types=1);

namespace App\Abstracts\Support;

use App\Contracts\Support\NotificationInterface;
use App\Utilities\Traits\ArrayTrait;

abstract class Notification implements NotificationInterface
{
    use ArrayTrait;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(protected array $payload = [])
    {
    }

    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function type(): string
    {
        return static::class;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function withPayload(array $payload): static
    {
        $this->payload = $payload;

        return $this;
    }

    public function toMail(mixed $notifiable): \App\Abstracts\Support\Mailable|array|null
    {
        return null;
    }

    public function toDatabase(mixed $notifiable): array
    {
        return $this->payload;
    }
}
