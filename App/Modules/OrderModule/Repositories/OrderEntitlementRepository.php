<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\OrderModule\Models\OrderEntitlement;

class OrderEntitlementRepository extends Repository
{
    protected string $modelClass = OrderEntitlement::class;

    public function findByAccessKey(string $accessKey): ?OrderEntitlement
    {
        $accessKey = trim($accessKey);

        if ($accessKey === '') {
            return null;
        }

        $entitlement = $this->findOneBy(['access_key' => $accessKey]);

        return $entitlement instanceof OrderEntitlement ? $entitlement : null;
    }

    public function findForOrderItem(int $orderItemId): ?OrderEntitlement
    {
        $entitlement = $this->findOneBy(['order_item_id' => $orderItemId]);

        return $entitlement instanceof OrderEntitlement ? $entitlement : null;
    }

    /**
     * @return list<OrderEntitlement>
     */
    public function forOrder(int $orderId): array
    {
        return array_values(array_filter(
            $this->findBy(['order_id' => $orderId]),
            static fn(mixed $entitlement): bool => $entitlement instanceof OrderEntitlement
        ));
    }

    /**
     * @return list<OrderEntitlement>
     */
    public function forUser(int $userId): array
    {
        return array_values(array_filter(
            $this->findBy(['user_id' => $userId]),
            static fn(mixed $entitlement): bool => $entitlement instanceof OrderEntitlement
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function summaryForOrder(int $orderId): array
    {
        return array_map(fn(OrderEntitlement $entitlement): array => $this->mapSummary($entitlement), $this->forOrder($orderId));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function summaryForUser(int $userId): array
    {
        return array_map(fn(OrderEntitlement $entitlement): array => $this->mapSummary($entitlement), $this->forUser($userId));
    }

    public function updateStatus(int $entitlementId, string $status): ?OrderEntitlement
    {
        $this->update($entitlementId, ['status' => $status]);
        $fresh = $this->find($entitlementId);

        return $fresh instanceof OrderEntitlement ? $fresh : null;
    }

    public function recordAccess(int $entitlementId): ?OrderEntitlement
    {
        $entitlement = $this->find($entitlementId);

        if (!$entitlement instanceof OrderEntitlement) {
            return null;
        }

        $this->update($entitlementId, [
            'downloads_used' => max(0, (int) ($entitlement->getAttribute('downloads_used') ?? 0)) + 1,
            'last_accessed_at' => $this->freshTimestamp(),
        ]);

        $fresh = $this->find($entitlementId);

        return $fresh instanceof OrderEntitlement ? $fresh : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function mapSummary(OrderEntitlement $entitlement): array
    {
        $metadata = $this->decodeMetadata((string) ($entitlement->getAttribute('metadata') ?? '[]'));
        $downloadLimit = max(0, (int) ($entitlement->getAttribute('download_limit') ?? 0));
        $downloadsUsed = max(0, (int) ($entitlement->getAttribute('downloads_used') ?? 0));

        return [
            'id' => (int) $entitlement->getKey(),
            'order_id' => (int) ($entitlement->getAttribute('order_id') ?? 0),
            'order_item_id' => (int) ($entitlement->getAttribute('order_item_id') ?? 0),
            'user_id' => (int) ($entitlement->getAttribute('user_id') ?? 0),
            'product_id' => (int) ($entitlement->getAttribute('product_id') ?? 0),
            'type' => (string) ($entitlement->getAttribute('type') ?? ''),
            'status' => (string) ($entitlement->getAttribute('status') ?? 'pending'),
            'label' => (string) ($entitlement->getAttribute('label') ?? ''),
            'access_key' => (string) ($entitlement->getAttribute('access_key') ?? ''),
            'access_path' => '/orders/entitlements/' . rawurlencode((string) ($entitlement->getAttribute('access_key') ?? '')),
            'access_url' => (string) ($entitlement->getAttribute('access_url') ?? ''),
            'download_limit' => $downloadLimit,
            'downloads_used' => $downloadsUsed,
            'downloads_remaining' => $downloadLimit > 0 ? max(0, $downloadLimit - $downloadsUsed) : null,
            'starts_at' => (string) ($entitlement->getAttribute('starts_at') ?? ''),
            'expires_at' => (string) ($entitlement->getAttribute('expires_at') ?? ''),
            'last_accessed_at' => (string) ($entitlement->getAttribute('last_accessed_at') ?? ''),
            'metadata' => $metadata,
            'created_at' => (string) ($entitlement->getAttribute('created_at') ?? ''),
            'updated_at' => (string) ($entitlement->getAttribute('updated_at') ?? ''),
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
