<?php

declare(strict_types=1);

namespace App\Support\Commerce;

use App\Contracts\Async\EventDispatcherInterface;
use App\Contracts\Support\AuditLoggerInterface;
use App\Core\Database;
use App\Modules\OrderModule\Models\Order;
use App\Modules\OrderModule\Repositories\OrderItemRepository;
use App\Modules\OrderModule\Repositories\OrderRepository;
use App\Support\Payments\PaymentIntent;
use App\Utilities\Managers\Security\AuthManager;
use App\Utilities\Managers\Support\PaymentManager;

class OrderLifecycleManager
{
    public function __construct(
        private readonly Database $database,
        private readonly OrderRepository $orders,
        private readonly OrderItemRepository $orderItems,
        private readonly PaymentManager $payments,
        private readonly EventDispatcherInterface $events,
        private readonly AuthManager $auth,
        private readonly AuditLoggerInterface $audit,
        private readonly InventoryManager $inventory,
        private readonly ShippingManager $shipping,
        private readonly EntitlementManager $entitlements
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function transition(int $orderId, string $action, array $payload = []): array
    {
        $order = $this->orders->find($orderId);

        if (!$order instanceof Order) {
            return [
                'successful' => false,
                'status' => 404,
                'title' => 'Order not found',
                'message' => 'The requested order could not be found.',
            ];
        }

        $intent = PaymentIntent::fromArray($this->decodeIntent((string) ($order->getAttribute('payment_intent') ?? '{}')));
        $result = match ($action) {
            'capture' => $this->payments->capture($intent),
            'refund' => $this->payments->refund($intent),
            'reconcile' => $this->payments->reconcile($intent, $payload),
            default => $this->payments->cancel($intent),
        };

        if (!$result->successful) {
            return [
                'successful' => false,
                'status' => 422,
                'title' => 'Payment transition failed',
                'message' => $result->message,
                'order' => $this->orders->mapSummary($order),
            ];
        }

        $currentInventoryStatus = (string) ($order->getAttribute('inventory_status') ?? 'unreserved');
        $currentFulfillmentStatus = (string) ($order->getAttribute('fulfillment_status') ?? 'unfulfilled');
        $inventoryStatus = $this->inventoryStatusForTransition($action, $result->intent, $currentInventoryStatus);
        $fulfillmentStatus = $this->fulfillmentStatusForIntent($result->intent, $currentFulfillmentStatus);

        $this->database->beginTransaction();

        try {
            if ($action === 'cancel' && $inventoryStatus === 'released' && $currentInventoryStatus !== 'released') {
                $this->inventory->release($this->orderItems->summaryForOrder($orderId));
            }

            $status = $this->orderStatusForIntent($result->intent);
            $updated = $this->orders->updateLifecycle($orderId, [
                'status' => $status,
                'payment_status' => $result->intent->status,
                'payment_driver' => $result->driver,
                'payment_method' => $result->intent->method,
                'payment_flow' => $result->intent->flow,
                'payment_reference' => $result->intent->reference,
                'payment_provider_reference' => $result->intent->providerReference,
                'payment_external_reference' => $result->intent->externalReference,
                'payment_webhook_reference' => $result->intent->webhookReference,
                'payment_idempotency_key' => $result->intent->idempotencyKey,
                'payment_customer_action_required' => $result->intent->customerActionRequired,
                'payment_next_action' => json_encode($result->intent->nextAction, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'payment_intent' => json_encode($result->intent->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'fulfillment_status' => $fulfillmentStatus,
                'inventory_status' => $inventoryStatus,
            ]);

            if (in_array($result->intent->status, ['captured', 'partially_captured'], true)) {
                $entitlementSync = $this->entitlements->syncForOrder($orderId, 'payment.' . $action);

                if (
                    (int) ($entitlementSync['eligible'] ?? 0) > 0
                    && !((bool) ($entitlementSync['physical_fulfillment_required'] ?? true))
                ) {
                    $updated = $this->orders->updateLifecycle($orderId, [
                        'status' => 'completed',
                        'fulfillment_status' => 'access_granted',
                    ]);
                }
            }

            if (in_array($result->intent->status, ['cancelled', 'refunded'], true)) {
                $this->entitlements->revokeForOrder($orderId, 'payment.' . $action);
            }

            $this->database->commit();
        } catch (\Throwable $exception) {
            $this->database->rollBack();
            throw $exception;
        }

        $event = $this->eventForPaymentTransition($action, $result->intent);

        if ($event !== null) {
            $this->events->dispatch($event, ['order_id' => $orderId]);
        }

        $this->audit->record('order.' . $action, [
            'actor_id' => $this->auth->check() ? (string) $this->auth->id() : null,
            'order_id' => (string) $orderId,
            'payment_driver' => $result->driver,
            'payment_status' => $result->intent->status,
            'payment_method' => $result->intent->method,
            'payment_flow' => $result->intent->flow,
            'status' => (string) ($updated->getAttribute('status') ?? ''),
            'fulfillment_status' => (string) ($updated->getAttribute('fulfillment_status') ?? ''),
            'inventory_status' => (string) ($updated->getAttribute('inventory_status') ?? ''),
        ], 'order');

        return [
            'successful' => true,
            'status' => 200,
            'title' => 'Order updated',
            'message' => ucfirst($action) . ' completed successfully.',
            'order' => $this->orders->mapSummary($updated),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function transitionFulfillment(int $orderId, string $action, array $payload = []): array
    {
        $order = $this->orders->find($orderId);

        if (!$order instanceof Order) {
            return [
                'successful' => false,
                'status' => 404,
                'title' => 'Order not found',
                'message' => 'The requested order could not be found.',
            ];
        }

        $summary = $this->orders->mapSummary($order);
        $allowed = $this->availableFulfillmentTransitions($summary);

        if (!in_array($action, $allowed, true)) {
            return [
                'successful' => false,
                'status' => 409,
                'title' => 'Fulfillment transition blocked',
                'message' => 'The requested fulfillment step is not valid for the current order state.',
                'order' => $summary,
            ];
        }

        $currentFulfillmentStatus = (string) ($summary['fulfillment_status'] ?? 'unfulfilled');
        $nextFulfillmentStatus = match ($action) {
            'pack' => 'packed',
            'ship' => 'shipped',
            'deliver' => 'delivered',
            default => $currentFulfillmentStatus,
        };
        $nextStatus = match ($action) {
            'pack' => 'processing',
            'ship' => 'fulfilled',
            'deliver' => 'completed',
            default => (string) ($summary['status'] ?? 'processing'),
        };
        $transitionAttributes = $this->fulfillmentAttributesForAction($action, $summary, $payload);

        if (($transitionAttributes['successful'] ?? true) === false) {
            return [
                'successful' => false,
                'status' => (int) ($transitionAttributes['status'] ?? 422),
                'title' => (string) ($transitionAttributes['title'] ?? 'Fulfillment update failed'),
                'message' => (string) ($transitionAttributes['message'] ?? 'The requested fulfillment step could not be completed.'),
                'order' => $summary,
            ];
        }

        $updated = $this->orders->updateLifecycle($orderId, [
            'status' => $nextStatus,
            'fulfillment_status' => $nextFulfillmentStatus,
            ...(is_array($transitionAttributes['attributes'] ?? null) ? $transitionAttributes['attributes'] : []),
        ]);

        $this->events->dispatch('order.fulfillment.updated', [
            'order_id' => $orderId,
            'action' => $action,
            'fulfillment_status' => $nextFulfillmentStatus,
            'tracking_number' => (string) ($updated->getAttribute('tracking_number') ?? ''),
            'shipping_carrier' => (string) ($updated->getAttribute('shipping_carrier') ?? ''),
        ]);
        $this->audit->record('order.fulfillment.' . $action, [
            'actor_id' => $this->auth->check() ? (string) $this->auth->id() : null,
            'order_id' => (string) $orderId,
            'status' => $nextStatus,
            'fulfillment_status' => $nextFulfillmentStatus,
            'shipping_carrier' => (string) ($updated->getAttribute('shipping_carrier') ?? ''),
            'tracking_number' => (string) ($updated->getAttribute('tracking_number') ?? ''),
        ], 'order');

        return [
            'successful' => true,
            'status' => 200,
            'title' => 'Fulfillment updated',
            'message' => ucfirst($action) . ' completed successfully.',
            'order' => $this->orders->mapSummary($updated),
        ];
    }

    /**
     * @param array<string, mixed> $order
     * @return list<string>
     */
    public function availableTransitions(array $order): array
    {
        $paymentStatus = (string) ($order['payment_status'] ?? '');
        $actions = [];

        if (in_array($paymentStatus, ['authorized', 'partially_captured'], true)) {
            $actions[] = 'capture';
        }

        if (in_array($paymentStatus, ['authorized', 'requires_action', 'processing', 'pending_review'], true)) {
            $actions[] = 'reconcile';
        }

        if (in_array($paymentStatus, ['captured', 'partially_captured', 'partially_refunded'], true)) {
            $actions[] = 'refund';
        }

        if (!in_array($paymentStatus, ['cancelled', 'refunded'], true)) {
            $actions[] = 'cancel';
        }

        return $actions;
    }

    /**
     * @param array<string, mixed> $order
     * @return list<string>
     */
    public function availableFulfillmentTransitions(array $order): array
    {
        $paymentStatus = (string) ($order['payment_status'] ?? '');
        $fulfillmentStatus = (string) ($order['fulfillment_status'] ?? 'unfulfilled');
        $status = (string) ($order['status'] ?? '');

        if (!in_array($paymentStatus, ['captured', 'partially_captured', 'partially_refunded'], true)) {
            return [];
        }

        if (in_array($status, ['cancelled', 'refunded', 'completed'], true)) {
            return [];
        }

        if (in_array($fulfillmentStatus, ['access_granted', 'not_required'], true)) {
            return [];
        }

        return match ($fulfillmentStatus) {
            'ready_to_fulfill' => ['pack'],
            'packed' => ['ship'],
            'shipped' => ['deliver'],
            default => [],
        };
    }

    public function orderStatusForIntent(PaymentIntent $intent): string
    {
        return match ($intent->status) {
            'captured' => 'processing',
            'partially_refunded', 'refunded' => 'refunded',
            'cancelled' => 'cancelled',
            'requires_action' => 'awaiting_payment_action',
            'processing', 'pending_review' => 'pending_payment',
            default => 'placed',
        };
    }

    public function fulfillmentStatusForIntent(PaymentIntent $intent, string $current = 'unfulfilled'): string
    {
        return match ($intent->status) {
            'captured' => 'ready_to_fulfill',
            'partially_refunded' => 'partially_refunded',
            'refunded' => 'refunded',
            'cancelled' => 'cancelled',
            'requires_action' => 'awaiting_payment',
            'processing', 'pending_review' => 'on_hold',
            default => $current !== '' ? $current : 'unfulfilled',
        };
    }

    public function inventoryStatusForIntent(PaymentIntent $intent, string $current = 'reserved'): string
    {
        return match ($intent->status) {
            'captured' => 'committed',
            'cancelled' => 'released',
            default => $current !== '' ? $current : 'reserved',
        };
    }

    public function eventForPaymentTransition(string $action, PaymentIntent $intent): ?string
    {
        return match ($action) {
            'capture' => 'order.paid',
            'refund' => 'order.refunded',
            'cancel' => 'order.cancelled',
            'reconcile' => $intent->status === 'captured' ? 'order.paid' : null,
            default => null,
        };
    }

    private function inventoryStatusForTransition(string $action, PaymentIntent $intent, string $current): string
    {
        if ($current === 'not_required') {
            return 'not_required';
        }

        if ($action === 'cancel') {
            return 'released';
        }

        return $this->inventoryStatusForIntent($intent, $current);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeIntent(string $payload): array
    {
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function fulfillmentAttributesForAction(string $action, array $order, array $payload): array
    {
        if ($action === 'ship') {
            return $this->shipping->prepareShipmentUpdate($order, $payload);
        }

        if ($action === 'deliver') {
            return $this->shipping->markDelivered($order);
        }

        if ($action === 'pack' && trim((string) ($order['shipment_reference'] ?? '')) === '') {
            return [
                'successful' => true,
                'status' => 200,
                'attributes' => [
                    'shipment_reference' => sprintf(
                        'SHP-%s-%s',
                        preg_replace('/[^A-Z0-9]+/', '', strtoupper((string) ($order['order_number'] ?? 'ORD'))) ?: 'ORDER',
                        strtoupper(substr(bin2hex(random_bytes(4)), 0, 6))
                    ),
                ],
            ];
        }

        return [
            'successful' => true,
            'status' => 200,
            'attributes' => [],
        ];
    }
}
