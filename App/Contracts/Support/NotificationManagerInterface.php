<?php

declare(strict_types=1);

namespace App\Contracts\Support;

interface NotificationManagerInterface
{
    /**
     * @return array<string, mixed>
     */
    public function send(mixed $notifiable, NotificationInterface $notification): array;

    /**
     * @return array<string, mixed>
     */
    public function sendNow(mixed $notifiable, NotificationInterface $notification): array;

    public function queue(mixed $notifiable, NotificationInterface $notification, ?string $queue = null): string;

    /**
     * @return list<array<string, mixed>>
     */
    public function databaseNotifications(?int $notifiableId = null, ?string $notifiableType = null): array;

    public function markAsRead(int $id): bool;
}
