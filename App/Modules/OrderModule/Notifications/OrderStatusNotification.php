<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Notifications;

use App\Abstracts\Support\Notification;

class OrderStatusNotification extends Notification
{
    public function via(mixed $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(mixed $notifiable): array|null
    {
        $orderNumber = (string) ($this->payload['order_number'] ?? 'order');
        $status = (string) ($this->payload['status'] ?? 'updated');
        $paymentStatus = (string) ($this->payload['payment_status'] ?? '');
        $total = (string) ($this->payload['total'] ?? '');

        return [
            'subject' => sprintf('Order %s %s', $orderNumber, $status),
            'text' => sprintf(
                'Order %s is now %s. Payment status: %s. Total: %s.',
                $orderNumber,
                $status,
                $paymentStatus,
                $total
            ),
        ];
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'order_id' => $this->payload['order_id'] ?? null,
            'order_number' => $this->payload['order_number'] ?? '',
            'status' => $this->payload['status'] ?? '',
            'payment_status' => $this->payload['payment_status'] ?? '',
            'total' => $this->payload['total'] ?? '',
        ];
    }
}
