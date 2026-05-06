<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Commerce;

use App\Contracts\Async\EventDispatcherInterface;
use App\Contracts\Support\AuditLoggerInterface;
use App\Modules\OrderModule\Models\Order;
use App\Modules\OrderModule\Models\OrderReturn;
use App\Modules\OrderModule\Repositories\OrderItemRepository;
use App\Modules\OrderModule\Repositories\OrderRepository;
use App\Modules\OrderModule\Repositories\OrderReturnRepository;
use App\Utilities\Managers\Security\AuthManager;

class OrderReturnManager
{
    public function __construct(
        private readonly OrderReturnRepository $returns,
        private readonly OrderRepository $orders,
        private readonly OrderItemRepository $orderItems,
        private readonly InventoryManager $inventory,
        private readonly EventDispatcherInterface $events,
        private readonly AuthManager $auth,
        private readonly AuditLoggerInterface $audit
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function request(int $orderId, array $payload): array
    {
        $order = $this->orders->find($orderId);

        if (!$order instanceof Order) {
            return $this->failure('Return unavailable', 'The requested order could not be found.', 404);
        }

        $type = $this->normalizeType((string) ($payload['type'] ?? 'return'));
        $items = $this->orderItems->summaryForOrder($orderId);
        $item = $this->resolveItem($items, (int) ($payload['order_item_id'] ?? 0));

        if ($item === []) {
            return $this->failure('Return unavailable', 'Choose a valid order item before creating a return or exchange.', 422);
        }

        $orderedQuantity = max(1, (int) ($item['quantity'] ?? 1));
        $quantity = max(1, min($orderedQuantity, (int) ($payload['quantity'] ?? 1)));
        $currency = (string) ($order->getAttribute('currency') ?? 'SEK');
        $lineTotalMinor = max(0, (int) ($item['line_total_minor'] ?? 0));
        $defaultRefundMinor = (int) round($lineTotalMinor * ($quantity / $orderedQuantity));
        $refundMinor = max(0, (int) ($payload['refund_minor'] ?? $payload['amount_minor'] ?? $defaultRefundMinor));
        $refundMinor = min($refundMinor, $this->remainingRefundMinor($order));
        $restock = array_key_exists('restock', $payload)
            ? $this->truthy($payload['restock'])
            : $this->stockManaged((string) ($item['fulfillment_type'] ?? 'physical_shipping'));
        $return = $this->returns->createReturn([
            'order_id' => $orderId,
            'order_item_id' => (int) ($item['id'] ?? 0),
            'exchange_product_id' => max(0, (int) ($payload['exchange_product_id'] ?? 0)) ?: null,
            'type' => $type,
            'status' => 'requested',
            'quantity' => $quantity,
            'refund_minor' => $refundMinor,
            'currency' => $currency,
            'reason' => trim((string) ($payload['reason'] ?? '')),
            'resolution' => trim((string) ($payload['resolution'] ?? '')),
            'restock' => $restock,
            'metadata' => [
                'product_id' => (int) ($item['product_id'] ?? 0),
                'product_name' => (string) ($item['name'] ?? ''),
                'fulfillment_type' => (string) ($item['fulfillment_type'] ?? 'physical_shipping'),
                'requested_by' => $this->auth->check() ? (int) $this->auth->id() : null,
            ],
        ]);

        $this->events->dispatch('order.return.requested', [
            'order_id' => $orderId,
            'return_id' => (int) $return->getKey(),
            'type' => $type,
            'refund_minor' => $refundMinor,
        ]);
        $this->audit->record('order.return.requested', [
            'actor_id' => $this->actorId(),
            'order_id' => (string) $orderId,
            'return_id' => (string) $return->getKey(),
            'type' => $type,
            'refund_minor' => $refundMinor,
        ], 'order');

        return [
            'successful' => true,
            'status' => 200,
            'title' => $type === 'exchange' ? 'Exchange requested' : 'Return requested',
            'message' => $type === 'exchange'
                ? 'The exchange request was recorded for operator follow-up.'
                : 'The return request was recorded for operator follow-up.',
            'return' => $this->returns->mapSummary($return),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function transition(int $orderId, int $returnId, string $action, array $payload = []): array
    {
        $return = $this->returns->find($returnId);

        if (!$return instanceof OrderReturn || (int) ($return->getAttribute('order_id') ?? 0) !== $orderId) {
            return $this->failure('Return unavailable', 'The requested return or exchange record could not be found for this order.', 404);
        }

        $status = match ($action) {
            'approve' => 'approved',
            'reject' => 'rejected',
            'complete' => 'completed',
            default => '',
        };

        if ($status === '') {
            return $this->failure('Return transition unavailable', 'The requested return transition is not supported.', 422);
        }

        if ($status === 'completed' && (bool) ($return->getAttribute('restock') ?? false)) {
            $item = $this->resolveItem(
                $this->orderItems->summaryForOrder($orderId),
                (int) ($return->getAttribute('order_item_id') ?? 0)
            );

            if ($item !== []) {
                $this->inventory->release([[
                    'product_id' => (int) ($item['product_id'] ?? 0),
                    'quantity' => max(1, (int) ($return->getAttribute('quantity') ?? 1)),
                    'fulfillment_type' => (string) ($item['fulfillment_type'] ?? 'physical_shipping'),
                    'name' => (string) ($item['name'] ?? ''),
                ]], ['reason' => 'return']);
            }
        }

        $updated = $this->returns->transition(
            $returnId,
            $status,
            trim((string) ($payload['resolution'] ?? $payload['note'] ?? $return->getAttribute('resolution') ?? ''))
        );

        if (!$updated instanceof OrderReturn) {
            return $this->failure('Return transition failed', 'The return transition could not be persisted.', 422);
        }

        $this->events->dispatch('order.return.' . $status, [
            'order_id' => $orderId,
            'return_id' => $returnId,
            'type' => (string) ($updated->getAttribute('type') ?? 'return'),
        ]);
        $this->audit->record('order.return.' . $status, [
            'actor_id' => $this->actorId(),
            'order_id' => (string) $orderId,
            'return_id' => (string) $returnId,
            'type' => (string) ($updated->getAttribute('type') ?? 'return'),
            'refund_minor' => (int) ($updated->getAttribute('refund_minor') ?? 0),
        ], 'order');

        return [
            'successful' => true,
            'status' => 200,
            'title' => 'Return updated',
            'message' => sprintf('Return workflow moved to [%s].', $status),
            'return' => $this->returns->mapSummary($updated),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function summariesForOrder(int $orderId): array
    {
        return $this->returns->summaryForOrder($orderId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recent(int $limit = 25): array
    {
        return $this->returns->recent($limit);
    }

    /**
     * @return array<string, int>
     */
    public function metrics(): array
    {
        return $this->returns->metrics();
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return array<string, mixed>
     */
    private function resolveItem(array $items, int $itemId): array
    {
        foreach ($items as $item) {
            if ((int) ($item['id'] ?? 0) === $itemId) {
                return $item;
            }
        }

        return $itemId <= 0 && isset($items[0]) ? $items[0] : [];
    }

    private function remainingRefundMinor(Order $order): int
    {
        try {
            $intent = json_decode((string) ($order->getAttribute('payment_intent') ?? '{}'), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return max(0, (int) ($order->getAttribute('total_minor') ?? 0));
        }

        $intent = is_array($intent) ? $intent : [];
        $captured = max(0, (int) ($intent['capturedAmount'] ?? $intent['captured_amount'] ?? 0));
        $refunded = max(0, (int) ($intent['refundedAmount'] ?? $intent['refunded_amount'] ?? 0));

        return $captured > 0
            ? max(0, $captured - $refunded)
            : max(0, (int) ($order->getAttribute('total_minor') ?? 0));
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));

        return in_array($type, ['return', 'exchange'], true) ? $type : 'return';
    }

    private function stockManaged(string $fulfillmentType): bool
    {
        return in_array(strtolower(trim($fulfillmentType)), [
            'physical_shipping',
            'store_pickup',
            'scheduled_pickup',
        ], true);
    }

    private function truthy(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    private function actorId(): ?string
    {
        return $this->auth->check() ? (string) $this->auth->id() : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function failure(string $title, string $message, int $status): array
    {
        return [
            'successful' => false,
            'status' => $status,
            'title' => $title,
            'message' => $message,
        ];
    }
}
