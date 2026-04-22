<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Listeners;

use App\Contracts\Async\ListenerInterface;
use App\Modules\OrderModule\Notifications\OrderStatusNotification;
use App\Modules\OrderModule\Repositories\OrderAddressRepository;
use App\Modules\OrderModule\Repositories\OrderRepository;
use App\Modules\UserModule\Repositories\UserRepository;
use App\Utilities\Managers\Support\NotificationManager;

class OrderLifecycleNotificationListener implements ListenerInterface
{
    public function __construct(
        private readonly NotificationManager $notifications,
        private readonly OrderRepository $orders,
        private readonly OrderAddressRepository $addresses,
        private readonly UserRepository $users
    ) {
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function subscriptions(): array
    {
        return [
            'order.created' => [[
                'method' => 'handle',
                'queued' => true,
                'queue' => 'notifications',
            ]],
            'order.paid' => [[
                'method' => 'handle',
                'queued' => true,
                'queue' => 'notifications',
            ]],
            'order.cancelled' => [[
                'method' => 'handle',
                'queued' => true,
                'queue' => 'notifications',
            ]],
            'order.refunded' => [[
                'method' => 'handle',
                'queued' => true,
                'queue' => 'notifications',
            ]],
            'order.fulfillment.updated' => [[
                'method' => 'handle',
                'queued' => true,
                'queue' => 'notifications',
            ]],
        ];
    }

    public function handle(string $event, array $payload = []): mixed
    {
        $orderId = (int) ($payload['order_id'] ?? 0);

        if ($orderId <= 0) {
            return null;
        }

        $order = $this->orders->find($orderId);

        if ($order === null) {
            return null;
        }

        $summary = $this->orders->mapSummary($order);
        $user = isset($summary['user_id']) ? $this->users->find((int) $summary['user_id']) : null;
        $address = $this->addresses->summaryForOrder($orderId)[0] ?? [];

        $notifiable = $user ?? [
            'type' => 'OrderGuest',
            'id' => null,
            'name' => $summary['contact_name'] ?: ($address['name'] ?? ''),
            'email' => $summary['contact_email'] ?: ($address['email'] ?? ''),
            'routes' => [
                'mail' => $summary['contact_email'] ?: ($address['email'] ?? ''),
            ],
        ];

        $notification = new OrderStatusNotification([
            'order_id' => $orderId,
            'order_number' => $summary['order_number'],
            'status' => $summary['status'],
            'fulfillment_status' => $summary['fulfillment_status'],
            'payment_status' => $summary['payment_status'],
            'payment_method' => $summary['payment_method'],
            'total' => $summary['total'],
        ]);

        $this->notifications->queue($notifiable, $notification, 'notifications');

        return null;
    }
}
