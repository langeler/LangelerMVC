<?php

declare(strict_types=1);

namespace App\Contracts\Support;

interface NotificationChannelInterface
{
    public function name(): string;

    /**
     * @param array<string, mixed> $notifiable
     * @return array<string, mixed>|bool|null
     */
    public function send(array $notifiable, NotificationInterface $notification): array|bool|null;
}
