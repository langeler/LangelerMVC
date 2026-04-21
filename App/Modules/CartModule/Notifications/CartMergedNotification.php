<?php

declare(strict_types=1);

namespace App\Modules\CartModule\Notifications;

use App\Abstracts\Support\Notification;

class CartMergedNotification extends Notification
{
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'guest_cart_id' => $this->payload['guest_cart_id'] ?? null,
            'user_cart_id' => $this->payload['user_cart_id'] ?? null,
            'merged_items' => $this->payload['merged_items'] ?? 0,
            'message' => $this->payload['message'] ?? 'A guest cart was merged into your active cart.',
        ];
    }
}
