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

        return [
            'id' => (int) $order->getKey(),
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
            'total_minor' => (int) ($order->getAttribute('total_minor') ?? 0),
            'subtotal' => $this->formatMoneyMinor((int) ($order->getAttribute('subtotal_minor') ?? 0), (string) ($order->getAttribute('currency') ?? 'SEK')),
            'total' => $this->formatMoneyMinor((int) ($order->getAttribute('total_minor') ?? 0), (string) ($order->getAttribute('currency') ?? 'SEK')),
            'payment_next_action' => $nextAction,
            'payment_intent' => $intent,
        ];
    }
}
