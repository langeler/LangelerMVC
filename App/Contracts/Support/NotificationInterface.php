<?php

declare(strict_types=1);

namespace App\Contracts\Support;

use App\Abstracts\Support\Mailable;

interface NotificationInterface
{
    /**
     * @return list<string>
     */
    public function via(mixed $notifiable): array;

    public function type(): string;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array;

    /**
     * @param array<string, mixed> $payload
     */
    public function withPayload(array $payload): static;

    public function toMail(mixed $notifiable): Mailable|array|null;

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array;
}
