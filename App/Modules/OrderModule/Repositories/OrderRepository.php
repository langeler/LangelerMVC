<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\OrderModule\Models\Order;

class OrderRepository extends Repository
{
    protected string $modelClass = Order::class;

    public function findByReference(string $reference): ?Order
    {
        $reference = trim($reference);

        if ($reference === '') {
            return null;
        }

        foreach ([
            'payment_reference',
            'payment_provider_reference',
            'payment_external_reference',
            'payment_webhook_reference',
            'order_number',
        ] as $column) {
            $order = $this->findOneBy([$column => $reference]);

            if ($order instanceof Order) {
                return $order;
            }
        }

        return null;
    }

    public function findByPaymentIdempotencyKey(string $idempotencyKey): ?Order
    {
        if ($idempotencyKey === '') {
            return null;
        }

        $order = $this->findOneBy([
            'payment_idempotency_key' => $idempotencyKey,
        ]);

        return $order instanceof Order ? $order : null;
    }

    public function nextOrderNumber(): string
    {
        return 'ORD-' . gmdate('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forUserSummary(int $userId): array
    {
        $query = $this->db
            ->dataQuery($this->getTable())
            ->select(['*'])
            ->where('user_id', '=', $userId)
            ->orderBy('id')
            ->toExecutable();

        return array_map(fn(array $row): array => $this->mapSummary($this->mapRowToModel($row)), $this->db->fetchAll($query['sql'], $query['bindings']));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function allSummary(): array
    {
        return array_map(
            fn(Order $order): array => $this->mapSummary($order),
            array_values(array_filter($this->all(), static fn(mixed $order): bool => $order instanceof Order))
        );
    }

    public function updateLifecycle(int $orderId, array $attributes): Order
    {
        $query = $this->db
            ->dataQuery($this->getTable())
            ->update($this->getTable(), array_merge(
                $attributes,
                ['updated_at' => $this->freshTimestamp()]
            ))
            ->where('id', '=', $orderId)
            ->toExecutable();

        $this->db->execute($query['sql'], $query['bindings']);

        /** @var Order $order */
        $order = $this->find($orderId);

        return $order;
    }

    /**
     * @return array<string, mixed>
     */
    public function mapSummary(Order $order): array
    {
        try {
            $intent = $this->fromJson((string) ($order->getAttribute('payment_intent') ?? '{}'), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $intent = [];
        }

        $intent = $this->isArray($intent) ? $intent : [];

        try {
            $nextAction = $this->fromJson((string) ($order->getAttribute('payment_next_action') ?? '[]'), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $nextAction = [];
        }

        $nextAction = $this->isArray($nextAction) ? $nextAction : [];

        try {
            $trackingEvents = $this->fromJson((string) ($order->getAttribute('tracking_events') ?? '[]'), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $trackingEvents = [];
        }

        $trackingEvents = $this->isArray($trackingEvents) ? array_values($trackingEvents) : [];

        return [
            'id' => (int) $order->getKey(),
            'user_id' => (int) ($order->getAttribute('user_id') ?? 0),
            'cart_id' => (int) ($order->getAttribute('cart_id') ?? 0),
            'order_number' => (string) ($order->getAttribute('order_number') ?? ''),
            'contact_name' => (string) ($order->getAttribute('contact_name') ?? ''),
            'contact_email' => (string) ($order->getAttribute('contact_email') ?? ''),
            'status' => (string) ($order->getAttribute('status') ?? ''),
            'payment_status' => (string) ($order->getAttribute('payment_status') ?? ''),
            'payment_driver' => (string) ($order->getAttribute('payment_driver') ?? ''),
            'payment_method' => (string) ($order->getAttribute('payment_method') ?? ''),
            'payment_flow' => (string) ($order->getAttribute('payment_flow') ?? ''),
            'payment_reference' => (string) ($order->getAttribute('payment_reference') ?? ''),
            'payment_provider_reference' => (string) ($order->getAttribute('payment_provider_reference') ?? ''),
            'payment_external_reference' => (string) ($order->getAttribute('payment_external_reference') ?? ''),
            'payment_webhook_reference' => (string) ($order->getAttribute('payment_webhook_reference') ?? ''),
            'payment_idempotency_key' => (string) ($order->getAttribute('payment_idempotency_key') ?? ''),
            'payment_customer_action_required' => (bool) ($order->getAttribute('payment_customer_action_required') ?? false),
            'currency' => (string) ($order->getAttribute('currency') ?? 'SEK'),
            'subtotal_minor' => (int) ($order->getAttribute('subtotal_minor') ?? 0),
            'discount_minor' => (int) ($order->getAttribute('discount_minor') ?? 0),
            'shipping_minor' => (int) ($order->getAttribute('shipping_minor') ?? 0),
            'tax_minor' => (int) ($order->getAttribute('tax_minor') ?? 0),
            'total_minor' => (int) ($order->getAttribute('total_minor') ?? 0),
            'shipping_country' => (string) ($order->getAttribute('shipping_country') ?? 'SE'),
            'shipping_zone' => (string) ($order->getAttribute('shipping_zone') ?? 'SE'),
            'shipping_option' => (string) ($order->getAttribute('shipping_option') ?? ''),
            'shipping_option_label' => (string) ($order->getAttribute('shipping_option_label') ?? ''),
            'shipping_carrier' => (string) ($order->getAttribute('shipping_carrier') ?? ''),
            'shipping_carrier_label' => (string) ($order->getAttribute('shipping_carrier_label') ?? ''),
            'shipping_service' => (string) ($order->getAttribute('shipping_service') ?? ''),
            'shipping_service_label' => (string) ($order->getAttribute('shipping_service_label') ?? ''),
            'shipping_service_point_id' => (string) ($order->getAttribute('shipping_service_point_id') ?? ''),
            'shipping_service_point_name' => (string) ($order->getAttribute('shipping_service_point_name') ?? ''),
            'tracking_number' => (string) ($order->getAttribute('tracking_number') ?? ''),
            'tracking_url' => (string) ($order->getAttribute('tracking_url') ?? ''),
            'shipment_reference' => (string) ($order->getAttribute('shipment_reference') ?? ''),
            'tracking_events' => $trackingEvents,
            'shipped_at' => (string) ($order->getAttribute('shipped_at') ?? ''),
            'delivered_at' => (string) ($order->getAttribute('delivered_at') ?? ''),
            'subtotal' => $this->formatMoneyMinor((int) ($order->getAttribute('subtotal_minor') ?? 0), (string) ($order->getAttribute('currency') ?? 'SEK')),
            'discount' => $this->formatMoneyMinor((int) ($order->getAttribute('discount_minor') ?? 0), (string) ($order->getAttribute('currency') ?? 'SEK')),
            'shipping' => $this->formatMoneyMinor((int) ($order->getAttribute('shipping_minor') ?? 0), (string) ($order->getAttribute('currency') ?? 'SEK')),
            'tax' => $this->formatMoneyMinor((int) ($order->getAttribute('tax_minor') ?? 0), (string) ($order->getAttribute('currency') ?? 'SEK')),
            'total' => $this->formatMoneyMinor((int) ($order->getAttribute('total_minor') ?? 0), (string) ($order->getAttribute('currency') ?? 'SEK')),
            'fulfillment_status' => (string) ($order->getAttribute('fulfillment_status') ?? 'unfulfilled'),
            'inventory_status' => (string) ($order->getAttribute('inventory_status') ?? 'unreserved'),
            'payment_next_action' => $nextAction,
            'payment_intent' => $intent,
            'created_at' => (string) ($order->getAttribute('created_at') ?? ''),
            'updated_at' => (string) ($order->getAttribute('updated_at') ?? ''),
        ];
    }
}
