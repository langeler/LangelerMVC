<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Commerce;

use App\Contracts\Async\EventDispatcherInterface;
use App\Contracts\Support\AuditLoggerInterface;
use App\Core\Config;
use App\Modules\OrderModule\Models\Order;
use App\Modules\OrderModule\Models\OrderEntitlement;
use App\Modules\OrderModule\Models\OrderSubscription;
use App\Modules\OrderModule\Repositories\OrderEntitlementRepository;
use App\Modules\OrderModule\Repositories\OrderItemRepository;
use App\Modules\OrderModule\Repositories\OrderRepository;
use App\Modules\OrderModule\Repositories\OrderSubscriptionRepository;
use App\Support\Payments\PaymentIntent;
use App\Utilities\Managers\Security\AuthManager;

class SubscriptionManager
{
    public function __construct(
        private readonly OrderSubscriptionRepository $subscriptions,
        private readonly OrderRepository $orders,
        private readonly OrderItemRepository $orderItems,
        private readonly OrderEntitlementRepository $entitlements,
        private readonly EventDispatcherInterface $events,
        private readonly AuthManager $auth,
        private readonly AuditLoggerInterface $audit,
        private readonly Config $config
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function syncForOrder(int $orderId, string $source = 'order.paid'): array
    {
        $order = $this->orders->find($orderId);

        if (!$order instanceof Order) {
            return [
                'successful' => false,
                'status' => 404,
                'message' => 'The order could not be found.',
                'created' => 0,
                'activated' => 0,
                'eligible' => 0,
                'subscriptions' => [],
            ];
        }

        $summary = $this->orders->mapSummary($order);
        $created = 0;
        $activated = 0;
        $eligible = 0;

        foreach ($this->orderItems->summaryForOrder($orderId) as $item) {
            if (strtolower(trim((string) ($item['fulfillment_type'] ?? ''))) !== 'subscription') {
                continue;
            }

            $eligible++;
            $existing = $this->subscriptions->findForOrderItem((int) ($item['id'] ?? 0));

            if ($existing instanceof OrderSubscription) {
                if ((string) ($existing->getAttribute('status') ?? '') === 'pending') {
                    $this->subscriptions->updateState((int) $existing->getKey(), [
                        'status' => 'active',
                        'resumed_at' => $this->freshTimestamp(),
                    ]);
                    $activated++;
                }

                continue;
            }

            $entitlement = $this->entitlements->findForOrderItem((int) ($item['id'] ?? 0));
            $this->subscriptions->create($this->attributesForItem($summary, $item, $entitlement, $source));
            $created++;
        }

        if ($created > 0 || $activated > 0) {
            $this->events->dispatch('order.subscriptions.synced', [
                'order_id' => $orderId,
                'created' => $created,
                'activated' => $activated,
                'source' => $source,
            ]);
            $this->audit->record('order.subscriptions.synced', [
                'actor_id' => $this->auth->check() ? (string) $this->auth->id() : null,
                'order_id' => (string) $orderId,
                'created' => $created,
                'activated' => $activated,
                'source' => $source,
            ], 'order');
        }

        return [
            'successful' => true,
            'status' => 200,
            'message' => 'Subscriptions synchronized.',
            'created' => $created,
            'activated' => $activated,
            'eligible' => $eligible,
            'subscriptions' => $this->summariesForOrder($orderId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cancelForOrder(int $orderId, string $reason = 'payment_lifecycle'): array
    {
        $cancelled = 0;

        foreach ($this->subscriptions->forOrder($orderId) as $subscription) {
            $status = (string) ($subscription->getAttribute('status') ?? '');

            if ($status === 'cancelled') {
                continue;
            }

            $this->transition((int) $subscription->getKey(), 'cancel', $reason);
            $cancelled++;
        }

        return [
            'successful' => true,
            'status' => 200,
            'message' => 'Subscriptions cancelled.',
            'cancelled' => $cancelled,
            'subscriptions' => $this->summariesForOrder($orderId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function transition(int $subscriptionId, string $action, string $reason = 'admin'): array
    {
        $subscription = $this->subscriptions->find($subscriptionId);

        if (!$subscription instanceof OrderSubscription) {
            return [
                'successful' => false,
                'status' => 404,
                'message' => 'The subscription could not be found.',
            ];
        }

        $summary = $this->subscriptions->mapSummary($subscription);
        $currentStatus = (string) ($summary['status'] ?? 'active');
        $now = $this->freshTimestamp();
        $attributes = match ($action) {
            'pause' => in_array($currentStatus, ['active', 'trialing', 'past_due'], true)
                ? ['status' => 'paused', 'paused_at' => $now, 'next_billing_at' => null]
                : [],
            'resume' => in_array($currentStatus, ['paused', 'past_due', 'unpaid'], true)
                ? [
                    'status' => 'active',
                    'paused_at' => null,
                    'resumed_at' => $now,
                    'retry_count' => 0,
                    'next_retry_at' => null,
                    'next_billing_at' => (string) ($summary['current_period_end'] ?? '') ?: $this->addInterval($now, (string) ($summary['interval'] ?? 'monthly'), (int) ($summary['interval_count'] ?? 1)),
                ]
                : [],
            'cancel' => $currentStatus !== 'cancelled'
                ? [
                    'status' => 'cancelled',
                    'cancelled_at' => $now,
                    'next_billing_at' => null,
                    'next_retry_at' => null,
                    'cancellation_reason' => $reason,
                ]
                : [],
            default => [],
        };

        if ($attributes === []) {
            return [
                'successful' => false,
                'status' => 409,
                'message' => 'The requested subscription transition is not valid for the current state.',
                'subscription' => $summary,
            ];
        }

        $attributes['metadata'] = $this->encodeMetadata($this->metadataWithLifecycleEvent(
            $summary,
            'subscription.' . $action,
            ['reason' => $reason]
        ));
        $updated = $this->subscriptions->updateState($subscriptionId, $attributes);
        $resolved = $updated instanceof OrderSubscription ? $this->subscriptions->mapSummary($updated) : $summary;
        $this->syncLinkedEntitlement($resolved);
        $this->events->dispatch('order.subscription.' . $action, [
            'order_id' => (int) ($resolved['order_id'] ?? 0),
            'subscription_id' => $subscriptionId,
            'status' => (string) ($resolved['status'] ?? ''),
            'reason' => $reason,
        ]);
        $this->audit->record('order.subscription.' . $action, [
            'actor_id' => $this->auth->check() ? (string) $this->auth->id() : null,
            'order_id' => (string) ($resolved['order_id'] ?? 0),
            'subscription_id' => (string) $subscriptionId,
            'status' => (string) ($resolved['status'] ?? ''),
            'reason' => $reason,
        ], 'order');

        return [
            'successful' => true,
            'status' => 200,
            'message' => 'Subscription ' . $action . ' completed successfully.',
            'subscription' => $resolved,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function processProviderEvent(string $driver, array $payload): array
    {
        $eventType = $this->normalizeEventType((string) ($payload['event_type'] ?? $payload['event'] ?? $payload['type'] ?? 'subscription.event'));
        $eventId = $this->eventId($driver, $payload);
        $subscription = $this->locateSubscription($driver, $payload);

        if (!$subscription instanceof OrderSubscription) {
            return [
                'successful' => false,
                'status' => 202,
                'processing_status' => 'unmatched',
                'message' => 'No matching subscription could be found for this provider event.',
                'event_id' => $eventId,
                'event_type' => $eventType,
            ];
        }

        $summary = $this->subscriptions->mapSummary($subscription);

        if ($this->hasProcessedEvent($summary, $eventId)) {
            return [
                'successful' => true,
                'status' => 200,
                'processing_status' => 'processed',
                'idempotent' => true,
                'message' => 'Subscription provider event was already processed.',
                'subscription' => $summary,
                'order_id' => (int) ($summary['order_id'] ?? 0),
                'event_id' => $eventId,
                'event_type' => $eventType,
            ];
        }

        $result = match ($eventType) {
            'renewed' => $this->applyRenewal($summary, $payload, $eventId),
            'payment_failed' => $this->applyPaymentFailure($summary, $payload, $eventId),
            'cancelled' => $this->applyProviderState($summary, $payload, $eventId, 'cancelled', 'cancel'),
            'paused' => $this->applyProviderState($summary, $payload, $eventId, 'paused', 'pause'),
            'resumed' => $this->applyProviderState($summary, $payload, $eventId, 'resumed', 'resume'),
            default => $this->recordProviderEventOnly($summary, $payload, $eventId, $eventType),
        };

        $resolved = is_array($result['subscription'] ?? null) ? $result['subscription'] : $this->subscriptionSummary((int) ($summary['id'] ?? 0));

        return [
            ...$result,
            'processing_status' => ($result['successful'] ?? false) ? 'processed' : 'failed',
            'subscription' => $resolved,
            'order_id' => (int) ($resolved['order_id'] ?? $summary['order_id'] ?? 0),
            'event_id' => $eventId,
            'event_type' => $eventType,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function summariesForOrder(int $orderId): array
    {
        return $this->subscriptions->summaryForOrder($orderId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function summariesForUser(int $userId): array
    {
        return $this->subscriptions->summaryForUser($userId);
    }

    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function attributesForItem(array $order, array $item, ?OrderEntitlement $entitlement, string $source): array
    {
        $policy = is_array($item['fulfillment_policy'] ?? null) ? $item['fulfillment_policy'] : [];
        $interval = $this->normalizeInterval((string) ($policy['interval'] ?? $policy['billing_interval'] ?? $this->config->get('commerce', 'FULFILLMENT.SUBSCRIPTIONS.DEFAULT_INTERVAL', 'monthly')));
        $intervalCount = max(1, (int) ($policy['interval_count'] ?? $policy['billing_interval_count'] ?? 1));
        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        $amountMinor = max(0, (int) ($policy['amount_minor'] ?? $item['unit_price_minor'] ?? 0)) * $quantity;
        $startsAt = $this->normalizeTimestamp((string) ($policy['starts_at'] ?? $item['available_at'] ?? '')) ?? $this->freshTimestamp();
        $trialDays = max(0, (int) ($policy['trial_days'] ?? $this->config->get('commerce', 'FULFILLMENT.SUBSCRIPTIONS.TRIAL_DAYS', 0)));
        $trialEndsAt = $trialDays > 0 ? gmdate('Y-m-d H:i:s', ($this->timestamp($startsAt) ?? time()) + ($trialDays * 86400)) : null;
        $periodStart = $startsAt;
        $periodEnd = $this->addInterval($periodStart, $interval, $intervalCount);
        $providerReference = trim((string) ($policy['provider_subscription_reference'] ?? ''));
        $providerReference = $providerReference !== '' ? $providerReference : $this->newSubscriptionKey('provider');
        $subscriptionKey = $this->newSubscriptionKey('sub');

        return [
            'order_id' => (int) ($order['id'] ?? 0),
            'order_item_id' => (int) ($item['id'] ?? 0),
            'user_id' => (int) ($order['user_id'] ?? 0) > 0 ? (int) ($order['user_id'] ?? 0) : null,
            'product_id' => (int) ($item['product_id'] ?? 0) > 0 ? (int) ($item['product_id'] ?? 0) : null,
            'entitlement_id' => $entitlement instanceof OrderEntitlement ? (int) $entitlement->getKey() : null,
            'latest_order_id' => null,
            'subscription_key' => $subscriptionKey,
            'plan_code' => $this->planCode($policy, $item),
            'plan_label' => (string) ($policy['plan_label'] ?? $policy['label'] ?? $item['name'] ?? 'Subscription'),
            'status' => $trialEndsAt !== null ? 'trialing' : 'active',
            'interval' => $interval,
            'interval_count' => $intervalCount,
            'quantity' => $quantity,
            'amount_minor' => $amountMinor,
            'currency' => (string) ($order['currency'] ?? 'SEK'),
            'trial_ends_at' => $trialEndsAt,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'next_billing_at' => $trialEndsAt ?? $periodEnd,
            'next_retry_at' => null,
            'retry_count' => 0,
            'max_retries' => max(0, (int) ($policy['max_retries'] ?? $this->config->get('commerce', 'FULFILLMENT.SUBSCRIPTIONS.MAX_RETRIES', 3))),
            'renewal_count' => 0,
            'payment_driver' => (string) ($order['payment_driver'] ?? 'testing'),
            'provider_subscription_reference' => $providerReference,
            'provider_customer_reference' => trim((string) ($policy['provider_customer_reference'] ?? '')) ?: null,
            'metadata' => $this->encodeMetadata([
                'source' => $source,
                'order_number' => (string) ($order['order_number'] ?? ''),
                'subscription_key' => $subscriptionKey,
                'policy' => $policy,
                'created_at' => $this->freshTimestamp(),
                'provider_events' => [],
                'renewal_orders' => [],
            ]),
        ];
    }

    /**
     * @param array<string, mixed> $subscription
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function applyRenewal(array $subscription, array $payload, string $eventId): array
    {
        $periodStart = $this->normalizeTimestamp((string) ($payload['current_period_start'] ?? $payload['period_start'] ?? ''))
            ?? ((string) ($subscription['current_period_end'] ?? '') ?: $this->freshTimestamp());
        $periodEnd = $this->normalizeTimestamp((string) ($payload['current_period_end'] ?? $payload['period_end'] ?? ''))
            ?? $this->addInterval($periodStart, (string) ($subscription['interval'] ?? 'monthly'), (int) ($subscription['interval_count'] ?? 1));
        $renewalOrder = $this->createRenewalOrder($subscription, $payload, $eventId, $periodStart, $periodEnd);
        $metadata = $this->metadataWithProviderEvent($subscription, $payload, $eventId, 'renewed');
        $metadata['renewal_orders'][] = [
            'order_id' => (int) ($renewalOrder['id'] ?? 0),
            'event_id' => $eventId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ];

        $updated = $this->subscriptions->updateState((int) ($subscription['id'] ?? 0), [
            'status' => 'active',
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'next_billing_at' => $periodEnd,
            'next_retry_at' => null,
            'retry_count' => 0,
            'renewal_count' => max(0, (int) ($subscription['renewal_count'] ?? 0)) + 1,
            'latest_order_id' => (int) ($renewalOrder['id'] ?? 0) ?: null,
            'metadata' => $this->encodeMetadata($metadata),
        ]);
        $resolved = $updated instanceof OrderSubscription ? $this->subscriptions->mapSummary($updated) : $subscription;
        $this->syncLinkedEntitlement($resolved, $periodEnd);
        $this->events->dispatch('order.subscription.renewed', [
            'order_id' => (int) ($resolved['order_id'] ?? 0),
            'subscription_id' => (int) ($resolved['id'] ?? 0),
            'renewal_order_id' => (int) ($renewalOrder['id'] ?? 0),
        ]);
        $this->audit->record('order.subscription.renewed', [
            'actor_id' => null,
            'order_id' => (string) ($resolved['order_id'] ?? 0),
            'subscription_id' => (string) ($resolved['id'] ?? 0),
            'renewal_order_id' => (string) ($renewalOrder['id'] ?? 0),
            'event_id' => $eventId,
        ], 'order');

        return [
            'successful' => true,
            'status' => 200,
            'message' => 'Subscription renewal processed.',
            'subscription' => $resolved,
            'renewal_order' => $renewalOrder,
        ];
    }

    /**
     * @param array<string, mixed> $subscription
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function applyPaymentFailure(array $subscription, array $payload, string $eventId): array
    {
        $retryCount = max(0, (int) ($subscription['retry_count'] ?? 0)) + 1;
        $maxRetries = max(0, (int) ($subscription['max_retries'] ?? 0));
        $exhausted = $maxRetries > 0 && $retryCount >= $maxRetries;
        $nextRetryAt = $exhausted ? null : $this->nextRetryAt($subscription, $retryCount);
        $updated = $this->subscriptions->updateState((int) ($subscription['id'] ?? 0), [
            'status' => $exhausted ? 'unpaid' : 'past_due',
            'retry_count' => $retryCount,
            'next_retry_at' => $nextRetryAt,
            'metadata' => $this->encodeMetadata($this->metadataWithProviderEvent($subscription, $payload, $eventId, 'payment_failed')),
        ]);
        $resolved = $updated instanceof OrderSubscription ? $this->subscriptions->mapSummary($updated) : $subscription;
        $this->syncLinkedEntitlement($resolved);
        $this->events->dispatch('order.subscription.payment_failed', [
            'order_id' => (int) ($resolved['order_id'] ?? 0),
            'subscription_id' => (int) ($resolved['id'] ?? 0),
            'retry_count' => $retryCount,
            'exhausted' => $exhausted,
        ]);
        $this->audit->record('order.subscription.payment_failed', [
            'actor_id' => null,
            'order_id' => (string) ($resolved['order_id'] ?? 0),
            'subscription_id' => (string) ($resolved['id'] ?? 0),
            'retry_count' => (string) $retryCount,
            'status' => (string) ($resolved['status'] ?? ''),
            'event_id' => $eventId,
        ], 'order');

        return [
            'successful' => true,
            'status' => 200,
            'message' => 'Subscription payment failure processed.',
            'subscription' => $resolved,
        ];
    }

    /**
     * @param array<string, mixed> $subscription
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function recordProviderEventOnly(array $subscription, array $payload, string $eventId, string $eventType): array
    {
        $updated = $this->subscriptions->updateState((int) ($subscription['id'] ?? 0), [
            'metadata' => $this->encodeMetadata($this->metadataWithProviderEvent($subscription, $payload, $eventId, $eventType)),
        ]);

        return [
            'successful' => true,
            'status' => 200,
            'message' => 'Subscription provider event recorded.',
            'subscription' => $updated instanceof OrderSubscription ? $this->subscriptions->mapSummary($updated) : $subscription,
        ];
    }

    /**
     * @param array<string, mixed> $subscription
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function applyProviderState(array $subscription, array $payload, string $eventId, string $eventType, string $action): array
    {
        $currentStatus = (string) ($subscription['status'] ?? '');
        $targetStatus = match ($action) {
            'pause' => 'paused',
            'resume' => 'active',
            'cancel' => 'cancelled',
            default => $currentStatus,
        };

        if ($currentStatus === $targetStatus) {
            return $this->recordProviderEventOnly($subscription, $payload, $eventId, $eventType);
        }

        $transition = $this->transition((int) ($subscription['id'] ?? 0), $action, 'provider_event');

        if (!($transition['successful'] ?? false)) {
            return $transition;
        }

        $resolved = is_array($transition['subscription'] ?? null) ? $transition['subscription'] : $subscription;
        $updated = $this->subscriptions->updateState((int) ($resolved['id'] ?? $subscription['id'] ?? 0), [
            'metadata' => $this->encodeMetadata($this->metadataWithProviderEvent($resolved, $payload, $eventId, $eventType)),
        ]);

        return [
            ...$transition,
            'subscription' => $updated instanceof OrderSubscription ? $this->subscriptions->mapSummary($updated) : $resolved,
        ];
    }

    /**
     * @param array<string, mixed> $subscription
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function createRenewalOrder(array $subscription, array $payload, string $eventId, string $periodStart, string $periodEnd): array
    {
        $original = $this->orders->find((int) ($subscription['order_id'] ?? 0));
        $items = $this->orderItems->summaryForOrder((int) ($subscription['order_id'] ?? 0));
        $sourceItem = [];

        foreach ($items as $item) {
            if ((int) ($item['id'] ?? 0) === (int) ($subscription['order_item_id'] ?? 0)) {
                $sourceItem = $item;
                break;
            }
        }

        $amountMinor = max(0, (int) ($payload['amount_minor'] ?? $subscription['amount_minor'] ?? 0));
        $currency = (string) ($payload['currency'] ?? $subscription['currency'] ?? 'SEK');
        $reference = trim((string) ($payload['payment_reference'] ?? $payload['invoice_reference'] ?? ''));
        $reference = $reference !== '' ? $reference : 'ren_' . substr(hash('sha256', $eventId . (string) ($subscription['subscription_key'] ?? '')), 0, 24);
        $intent = new PaymentIntent(
            $amountMinor,
            $currency,
            'Subscription renewal',
            [
                'subscription_id' => (int) ($subscription['id'] ?? 0),
                'subscription_key' => (string) ($subscription['subscription_key'] ?? ''),
                'event_id' => $eventId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ],
            (string) ($subscription['payment_driver'] ?? 'testing'),
            'card',
            'authorize_capture',
            $reference,
            trim((string) ($payload['provider_reference'] ?? $payload['invoice_id'] ?? '')) ?: null,
            trim((string) ($payload['external_reference'] ?? '')) ?: null,
            'subscription-' . $eventId,
            $eventId,
            [],
            false,
            'captured',
            $amountMinor,
            $amountMinor,
            0
        );

        $order = $this->orders->create([
            'user_id' => (int) ($subscription['user_id'] ?? 0) > 0 ? (int) ($subscription['user_id'] ?? 0) : null,
            'cart_id' => null,
            'order_number' => $this->orders->nextOrderNumber(),
            'contact_name' => $original instanceof Order ? (string) ($original->getAttribute('contact_name') ?? 'Subscription customer') : 'Subscription customer',
            'contact_email' => $original instanceof Order ? (string) ($original->getAttribute('contact_email') ?? '') : '',
            'status' => 'completed',
            'payment_status' => 'captured',
            'payment_driver' => (string) ($subscription['payment_driver'] ?? 'testing'),
            'payment_method' => 'card',
            'payment_flow' => 'authorize_capture',
            'payment_reference' => $intent->reference,
            'payment_provider_reference' => $intent->providerReference,
            'payment_external_reference' => $intent->externalReference,
            'payment_webhook_reference' => $intent->webhookReference,
            'payment_idempotency_key' => $intent->idempotencyKey,
            'payment_customer_action_required' => false,
            'currency' => $currency,
            'subtotal_minor' => $amountMinor,
            'discount_code' => '',
            'discount_label' => '',
            'discount_snapshot' => $this->encodeMetadata([]),
            'discount_minor' => 0,
            'shipping_minor' => 0,
            'tax_minor' => 0,
            'total_minor' => $amountMinor,
            'shipping_country' => 'SE',
            'shipping_zone' => 'SE',
            'shipping_option' => 'digital-delivery',
            'shipping_option_label' => 'Digital / online delivery',
            'shipping_carrier' => '',
            'shipping_carrier_label' => '',
            'shipping_service' => 'subscription_renewal',
            'shipping_service_label' => 'Subscription renewal',
            'shipping_service_point_id' => '',
            'shipping_service_point_name' => '',
            'tracking_number' => '',
            'tracking_url' => '',
            'shipment_reference' => '',
            'tracking_events' => $this->encodeMetadata([]),
            'shipped_at' => null,
            'delivered_at' => null,
            'fulfillment_status' => 'access_granted',
            'inventory_status' => 'not_required',
            'payment_next_action' => $this->encodeMetadata([]),
            'payment_intent' => $this->encodeMetadata($intent->toArray()),
        ]);

        $this->orderItems->create([
            'order_id' => (int) $order->getKey(),
            'product_id' => (int) ($subscription['product_id'] ?? 0) ?: null,
            'product_name' => (string) ($sourceItem['name'] ?? $subscription['plan_label'] ?? 'Subscription renewal'),
            'quantity' => max(1, (int) ($subscription['quantity'] ?? 1)),
            'unit_price_minor' => max(0, (int) ($subscription['amount_minor'] ?? 0)),
            'line_total_minor' => $amountMinor,
            'metadata' => $this->encodeMetadata([
                'slug' => (string) ($sourceItem['slug'] ?? ''),
                'category_id' => (int) ($sourceItem['category_id'] ?? 0),
                'fulfillment_type' => 'subscription',
                'fulfillment_label' => 'Subscription / recurring',
                'fulfillment_policy' => is_array($sourceItem['fulfillment_policy'] ?? null) ? $sourceItem['fulfillment_policy'] : [],
                'available_at' => $periodStart,
                'subscription_id' => (int) ($subscription['id'] ?? 0),
                'subscription_key' => (string) ($subscription['subscription_key'] ?? ''),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ]),
        ]);

        return $this->orders->mapSummary($order);
    }

    /**
     * @param array<string, mixed> $subscription
     */
    private function syncLinkedEntitlement(array $subscription, ?string $expiresAt = null): void
    {
        $entitlementId = (int) ($subscription['entitlement_id'] ?? 0);

        if ($entitlementId <= 0) {
            return;
        }

        $status = match ((string) ($subscription['status'] ?? 'active')) {
            'active', 'trialing' => 'active',
            'cancelled' => 'revoked',
            'paused', 'unpaid' => 'pending',
            default => 'active',
        };
        $attributes = ['status' => $status];

        if ($expiresAt !== null) {
            $attributes['expires_at'] = $expiresAt;
        }

        $this->entitlements->update($entitlementId, $attributes);
    }

    /**
     * @param array<string, mixed> $subscription
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function metadataWithProviderEvent(array $subscription, array $payload, string $eventId, string $eventType): array
    {
        $metadata = is_array($subscription['metadata'] ?? null) ? $subscription['metadata'] : [];
        $metadata['provider_events'] = is_array($metadata['provider_events'] ?? null) ? $metadata['provider_events'] : [];
        $metadata['renewal_orders'] = is_array($metadata['renewal_orders'] ?? null) ? $metadata['renewal_orders'] : [];
        $metadata['provider_events'][] = [
            'event_id' => $eventId,
            'event_type' => $eventType,
            'received_at' => $this->freshTimestamp(),
            'payload' => $payload,
        ];

        return $metadata;
    }

    /**
     * @param array<string, mixed> $subscription
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function metadataWithLifecycleEvent(array $subscription, string $eventType, array $context = []): array
    {
        $metadata = is_array($subscription['metadata'] ?? null) ? $subscription['metadata'] : [];
        $metadata['lifecycle_events'] = is_array($metadata['lifecycle_events'] ?? null) ? $metadata['lifecycle_events'] : [];
        $metadata['lifecycle_events'][] = [
            'event_type' => $eventType,
            'occurred_at' => $this->freshTimestamp(),
            'context' => $context,
        ];

        return $metadata;
    }

    /**
     * @param array<string, mixed> $subscription
     */
    private function hasProcessedEvent(array $subscription, string $eventId): bool
    {
        $metadata = is_array($subscription['metadata'] ?? null) ? $subscription['metadata'] : [];

        foreach ((array) ($metadata['provider_events'] ?? []) as $event) {
            if (is_array($event) && (string) ($event['event_id'] ?? '') === $eventId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function locateSubscription(string $driver, array $payload): ?OrderSubscription
    {
        foreach ([
            $payload['subscription_key'] ?? null,
            $payload['subscription_id'] ?? null,
            $this->nestedValue($payload, 'metadata.subscription_key'),
            $this->nestedValue($payload, 'subscription.key'),
            $this->nestedValue($payload, 'subscription.id'),
            $this->nestedValue($payload, 'data.object.subscription_key'),
            $this->nestedValue($payload, 'data.object.subscription.id'),
        ] as $candidate) {
            $candidate = trim((string) $candidate);

            if ($candidate === '') {
                continue;
            }

            if (ctype_digit($candidate)) {
                $subscription = $this->subscriptions->find((int) $candidate);

                if ($subscription instanceof OrderSubscription) {
                    return $subscription;
                }
            }

            $subscription = $this->subscriptions->findBySubscriptionKey($candidate);

            if ($subscription instanceof OrderSubscription) {
                return $subscription;
            }
        }

        foreach ([
            $payload['provider_subscription_reference'] ?? null,
            $payload['subscription_reference'] ?? null,
            $payload['provider_reference'] ?? null,
            $payload['reference'] ?? null,
            $this->nestedValue($payload, 'metadata.provider_subscription_reference'),
            $this->nestedValue($payload, 'subscription.provider_reference'),
            $this->nestedValue($payload, 'data.object.subscription'),
            $this->nestedValue($payload, 'data.object.id'),
            $this->nestedValue($payload, 'resource.subscription'),
        ] as $candidate) {
            $candidate = trim((string) $candidate);

            if ($candidate === '') {
                continue;
            }

            $subscription = $this->subscriptions->findByProviderReference($driver, $candidate);

            if ($subscription instanceof OrderSubscription) {
                return $subscription;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function nestedValue(array $payload, string $path): mixed
    {
        $current = $payload;

        foreach (explode('.', $path) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    private function normalizeEventType(string $eventType): string
    {
        $eventType = strtolower(trim($eventType));

        return match ($eventType) {
            'subscription.renewed', 'subscription.renewal_succeeded', 'subscription.payment_succeeded', 'invoice.payment_succeeded', 'payment.succeeded' => 'renewed',
            'subscription.payment_failed', 'subscription.renewal_failed', 'invoice.payment_failed', 'payment.failed' => 'payment_failed',
            'subscription.cancelled', 'subscription.canceled', 'subscription.deleted', 'subscription.ended' => 'cancelled',
            'subscription.paused' => 'paused',
            'subscription.resumed' => 'resumed',
            default => $eventType !== '' ? $eventType : 'subscription.event',
        };
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function eventId(string $driver, array $payload): string
    {
        foreach ([
            $payload['event_id'] ?? null,
            $payload['eventId'] ?? null,
            $payload['webhook_id'] ?? null,
            $payload['id'] ?? null,
        ] as $candidate) {
            $candidate = trim((string) $candidate);

            if ($candidate !== '') {
                return $candidate;
            }
        }

        return 'subevt_' . substr(hash('sha256', $driver . '|' . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)), 0, 32);
    }

    /**
     * @param array<string, mixed> $policy
     * @param array<string, mixed> $item
     */
    private function planCode(array $policy, array $item): string
    {
        $code = strtolower(trim((string) ($policy['plan_code'] ?? $policy['code'] ?? $item['slug'] ?? 'subscription')));
        $code = preg_replace('/[^a-z0-9]+/', '-', $code) ?? 'subscription';

        return trim($code, '-') ?: 'subscription';
    }

    private function normalizeInterval(string $interval): string
    {
        $interval = strtolower(trim($interval));

        return in_array($interval, ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'], true)
            ? $interval
            : 'monthly';
    }

    private function addInterval(string $date, string $interval, int $count): string
    {
        $base = $this->timestamp($date) ?? time();
        $count = max(1, $count);
        $modifier = match ($this->normalizeInterval($interval)) {
            'daily' => '+' . $count . ' day',
            'weekly' => '+' . $count . ' week',
            'quarterly' => '+' . ($count * 3) . ' month',
            'yearly' => '+' . $count . ' year',
            default => '+' . $count . ' month',
        };

        return gmdate('Y-m-d H:i:s', strtotime($modifier, $base) ?: $base);
    }

    /**
     * @param array<string, mixed> $subscription
     */
    private function nextRetryAt(array $subscription, int $retryCount): string
    {
        $metadata = is_array($subscription['metadata'] ?? null) ? $subscription['metadata'] : [];
        $policy = is_array($metadata['policy'] ?? null) ? $metadata['policy'] : [];
        $retryDays = is_array($policy['dunning_retry_days'] ?? null)
            ? $policy['dunning_retry_days']
            : (array) $this->config->get('commerce', 'FULFILLMENT.SUBSCRIPTIONS.DUNNING_RETRY_DAYS', [1, 3, 7]);
        $days = max(1, (int) ($retryDays[max(0, $retryCount - 1)] ?? end($retryDays) ?: 1));

        return gmdate('Y-m-d H:i:s', time() + ($days * 86400));
    }

    private function subscriptionSummary(int $subscriptionId): array
    {
        $subscription = $this->subscriptions->find($subscriptionId);

        return $subscription instanceof OrderSubscription ? $this->subscriptions->mapSummary($subscription) : [];
    }

    private function newSubscriptionKey(string $prefix): string
    {
        return strtolower($prefix) . '_' . bin2hex(random_bytes(16));
    }

    private function encodeMetadata(array $metadata): string
    {
        return json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function normalizeTimestamp(string $value): ?string
    {
        $timestamp = $this->timestamp($value);

        return $timestamp !== null ? gmdate('Y-m-d H:i:s', $timestamp) : null;
    }

    private function timestamp(string $value): ?int
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : $timestamp;
    }

    private function freshTimestamp(): string
    {
        return gmdate('Y-m-d H:i:s');
    }
}
