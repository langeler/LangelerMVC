<?php

declare(strict_types=1);

namespace App\Contracts\Support;

interface NotifiableInterface
{
    public function notificationType(): string;

    public function notificationIdentifier(): mixed;

    public function routeNotificationFor(string $channel): mixed;
}
