<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\OrderModule\Models\OrderSubscription;

class OrderSubscriptionRepository extends Repository
{
    protected string $modelClass = OrderSubscription::class;

    public function findForOrderItem(int $orderItemId): ?OrderSubscription
    {
        $subscription = $this->findOneBy(['order_item_id' => $orderItemId]);

        return $subscription instanceof OrderSubscription ? $subscription : null;
    }

    public function findBySubscriptionKey(string $subscriptionKey): ?OrderSubscription
    {
        $subscriptionKey = trim($subscriptionKey);

        if ($subscriptionKey === '') {
            return null;
        }

        $subscription = $this->findOneBy(['subscription_key' => $subscriptionKey]);

        return $subscription instanceof OrderSubscription ? $subscription : null;
    }

    public function findByProviderReference(string $driver, string $reference): ?OrderSubscription
    {
        $reference = trim($reference);

        if ($reference === '') {
            return null;
        }

        $subscription = $this->findOneBy([
            'payment_driver' => strtolower(trim($driver)),
            'provider_subscription_reference' => $reference,
        ]);

        return $subscription instanceof OrderSubscription ? $subscription : null;
    }

    /**
     * @return list<OrderSubscription>
     */
    public function forOrder(int $orderId): array
    {
        return array_values(array_filter(
            $this->findBy(['order_id' => $orderId]),
            static fn(mixed $subscription): bool => $subscription instanceof OrderSubscription
        ));
    }

    /**
     * @return list<OrderSubscription>
     */
    public function forUser(int $userId): array
    {
        return array_values(array_filter(
            $this->findBy(['user_id' => $userId]),
            static fn(mixed $subscription): bool => $subscription instanceof OrderSubscription
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function summaryForOrder(int $orderId): array
    {
        return array_map(fn(OrderSubscription $subscription): array => $this->mapSummary($subscription), $this->forOrder($orderId));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function summaryForUser(int $userId): array
    {
        return array_map(fn(OrderSubscription $subscription): array => $this->mapSummary($subscription), $this->forUser($userId));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function allSummary(): array
    {
        return array_map(
            fn(OrderSubscription $subscription): array => $this->mapSummary($subscription),
            array_values(array_filter($this->all(), static fn(mixed $subscription): bool => $subscription instanceof OrderSubscription))
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function updateState(int $subscriptionId, array $attributes): ?OrderSubscription
    {
        $this->update($subscriptionId, $attributes);
        $fresh = $this->find($subscriptionId);

        return $fresh instanceof OrderSubscription ? $fresh : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function mapSummary(OrderSubscription $subscription): array
    {
        $metadata = $this->decodeMetadata((string) ($subscription->getAttribute('metadata') ?? '[]'));
        $currency = (string) ($subscription->getAttribute('currency') ?? 'SEK');
        $amountMinor = (int) ($subscription->getAttribute('amount_minor') ?? 0);

        return [
            'id' => (int) $subscription->getKey(),
            'order_id' => (int) ($subscription->getAttribute('order_id') ?? 0),
            'order_item_id' => (int) ($subscription->getAttribute('order_item_id') ?? 0),
            'user_id' => (int) ($subscription->getAttribute('user_id') ?? 0),
            'product_id' => (int) ($subscription->getAttribute('product_id') ?? 0),
            'entitlement_id' => (int) ($subscription->getAttribute('entitlement_id') ?? 0),
            'latest_order_id' => (int) ($subscription->getAttribute('latest_order_id') ?? 0),
            'subscription_key' => (string) ($subscription->getAttribute('subscription_key') ?? ''),
            'plan_code' => (string) ($subscription->getAttribute('plan_code') ?? ''),
            'plan_label' => (string) ($subscription->getAttribute('plan_label') ?? ''),
            'status' => (string) ($subscription->getAttribute('status') ?? 'active'),
            'interval' => (string) ($subscription->getAttribute('interval') ?? 'monthly'),
            'interval_count' => (int) ($subscription->getAttribute('interval_count') ?? 1),
            'quantity' => (int) ($subscription->getAttribute('quantity') ?? 1),
            'amount_minor' => $amountMinor,
            'amount' => $this->formatMoneyMinor($amountMinor, $currency),
            'currency' => $currency,
            'trial_ends_at' => (string) ($subscription->getAttribute('trial_ends_at') ?? ''),
            'current_period_start' => (string) ($subscription->getAttribute('current_period_start') ?? ''),
            'current_period_end' => (string) ($subscription->getAttribute('current_period_end') ?? ''),
            'next_billing_at' => (string) ($subscription->getAttribute('next_billing_at') ?? ''),
            'next_retry_at' => (string) ($subscription->getAttribute('next_retry_at') ?? ''),
            'retry_count' => (int) ($subscription->getAttribute('retry_count') ?? 0),
            'max_retries' => (int) ($subscription->getAttribute('max_retries') ?? 3),
            'renewal_count' => (int) ($subscription->getAttribute('renewal_count') ?? 0),
            'payment_driver' => (string) ($subscription->getAttribute('payment_driver') ?? ''),
            'provider_subscription_reference' => (string) ($subscription->getAttribute('provider_subscription_reference') ?? ''),
            'provider_customer_reference' => (string) ($subscription->getAttribute('provider_customer_reference') ?? ''),
            'cancellation_reason' => (string) ($subscription->getAttribute('cancellation_reason') ?? ''),
            'paused_at' => (string) ($subscription->getAttribute('paused_at') ?? ''),
            'resumed_at' => (string) ($subscription->getAttribute('resumed_at') ?? ''),
            'cancelled_at' => (string) ($subscription->getAttribute('cancelled_at') ?? ''),
            'metadata' => $metadata,
            'created_at' => (string) ($subscription->getAttribute('created_at') ?? ''),
            'updated_at' => (string) ($subscription->getAttribute('updated_at') ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeMetadata(string $payload): array
    {
        if (trim($payload) === '') {
            return [];
        }

        try {
            $decoded = $this->fromJson($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return $this->isArray($decoded) ? $decoded : [];
    }
}
