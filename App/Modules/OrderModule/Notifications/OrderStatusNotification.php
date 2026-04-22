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
        $fulfillmentStatus = (string) ($this->payload['fulfillment_status'] ?? '');
        $paymentStatus = (string) ($this->payload['payment_status'] ?? '');
        $paymentMethod = (string) ($this->payload['payment_method'] ?? '');
        $shippingCarrier = (string) ($this->payload['shipping_carrier_label'] ?? '');
        $trackingNumber = (string) ($this->payload['tracking_number'] ?? '');
        $total = (string) ($this->payload['total'] ?? '');
        $fulfillmentSegment = $fulfillmentStatus !== '' ? ' Fulfillment: ' . $fulfillmentStatus . '.' : '';
        $shipmentSegment = ($shippingCarrier !== '' || $trackingNumber !== '')
            ? sprintf(' Shipment: %s %s.', $shippingCarrier !== '' ? $shippingCarrier : 'carrier', trim($trackingNumber))
            : '';

        return [
            'subject' => sprintf('Order %s %s', $orderNumber, $status),
            'text' => sprintf(
                'Order %s is now %s.%s%s Payment status: %s. Payment method: %s. Total: %s.',
                $orderNumber,
                $status,
                $fulfillmentSegment,
                $shipmentSegment,
                $paymentStatus,
                $paymentMethod,
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
            'fulfillment_status' => $this->payload['fulfillment_status'] ?? '',
            'payment_status' => $this->payload['payment_status'] ?? '',
            'payment_method' => $this->payload['payment_method'] ?? '',
            'shipping_carrier_label' => $this->payload['shipping_carrier_label'] ?? '',
            'tracking_number' => $this->payload['tracking_number'] ?? '',
            'total' => $this->payload['total'] ?? '',
        ];
    }
}
