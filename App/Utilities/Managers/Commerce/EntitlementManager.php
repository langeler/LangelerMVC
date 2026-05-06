<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Commerce;

use App\Contracts\Async\EventDispatcherInterface;
use App\Contracts\Support\AuditLoggerInterface;
use App\Core\Config;
use App\Modules\OrderModule\Models\OrderEntitlement;
use App\Modules\OrderModule\Repositories\OrderEntitlementRepository;
use App\Modules\OrderModule\Repositories\OrderItemRepository;
use App\Modules\OrderModule\Repositories\OrderRepository;
use App\Utilities\Managers\Security\AuthManager;

class EntitlementManager
{
    public function __construct(
        private readonly OrderEntitlementRepository $entitlements,
        private readonly OrderRepository $orders,
        private readonly OrderItemRepository $orderItems,
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

        if ($order === null) {
            return [
                'successful' => false,
                'status' => 404,
                'message' => 'The order could not be found.',
                'created' => 0,
                'activated' => 0,
                'eligible' => 0,
                'physical_fulfillment_required' => false,
                'entitlements' => [],
            ];
        }

        $summary = $this->orders->mapSummary($order);
        $items = $this->orderItems->summaryForOrder($orderId);
        $eligibleTypes = $this->eligibleTypes();
        $created = 0;
        $activated = 0;
        $eligible = 0;
        $physicalFulfillmentRequired = false;

        foreach ($items as $item) {
            $type = strtolower(trim((string) ($item['fulfillment_type'] ?? 'physical_shipping')));

            if (!in_array($type, $eligibleTypes, true)) {
                $physicalFulfillmentRequired = true;
                continue;
            }

            $eligible++;
            $existing = $this->entitlements->findForOrderItem((int) ($item['id'] ?? 0));

            if ($existing instanceof OrderEntitlement) {
                if ((string) ($existing->getAttribute('status') ?? '') === 'pending') {
                    $this->entitlements->updateStatus((int) $existing->getKey(), 'active');
                    $activated++;
                }

                continue;
            }

            $this->entitlements->create($this->attributesForItem($summary, $item, $source));
            $created++;
        }

        if ($created > 0 || $activated > 0) {
            $this->events->dispatch('order.entitlements.issued', [
                'order_id' => $orderId,
                'created' => $created,
                'activated' => $activated,
                'source' => $source,
            ]);
            $this->audit->record('order.entitlements.issued', [
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
            'message' => 'Entitlements synchronized.',
            'created' => $created,
            'activated' => $activated,
            'eligible' => $eligible,
            'physical_fulfillment_required' => $physicalFulfillmentRequired,
            'entitlements' => $this->summariesForOrder($orderId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function revokeForOrder(int $orderId, string $reason = 'payment_lifecycle'): array
    {
        $revoked = 0;

        foreach ($this->entitlements->forOrder($orderId) as $entitlement) {
            $status = (string) ($entitlement->getAttribute('status') ?? '');

            if (in_array($status, ['revoked', 'expired'], true)) {
                continue;
            }

            $this->entitlements->updateStatus((int) $entitlement->getKey(), 'revoked');
            $revoked++;
        }

        if ($revoked > 0) {
            $this->events->dispatch('order.entitlements.revoked', [
                'order_id' => $orderId,
                'revoked' => $revoked,
                'reason' => $reason,
            ]);
            $this->audit->record('order.entitlements.revoked', [
                'actor_id' => $this->auth->check() ? (string) $this->auth->id() : null,
                'order_id' => (string) $orderId,
                'revoked' => $revoked,
                'reason' => $reason,
            ], 'order');
        }

        return [
            'successful' => true,
            'status' => 200,
            'message' => 'Entitlements revoked.',
            'revoked' => $revoked,
            'entitlements' => $this->summariesForOrder($orderId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function transition(int $entitlementId, string $status, string $reason = 'admin'): array
    {
        if (!in_array($status, ['active', 'revoked', 'expired', 'pending'], true)) {
            return [
                'successful' => false,
                'status' => 422,
                'message' => 'Unsupported entitlement status.',
            ];
        }

        $updated = $this->entitlements->updateStatus($entitlementId, $status);

        if (!$updated instanceof OrderEntitlement) {
            return [
                'successful' => false,
                'status' => 404,
                'message' => 'The entitlement could not be found.',
            ];
        }

        $summary = $this->entitlements->mapSummary($updated);
        $this->events->dispatch('order.entitlement.updated', [
            'order_id' => (int) ($summary['order_id'] ?? 0),
            'entitlement_id' => $entitlementId,
            'status' => $status,
            'reason' => $reason,
        ]);
        $this->audit->record('order.entitlement.' . $status, [
            'actor_id' => $this->auth->check() ? (string) $this->auth->id() : null,
            'order_id' => (string) ($summary['order_id'] ?? 0),
            'entitlement_id' => (string) $entitlementId,
            'status' => $status,
            'reason' => $reason,
        ], 'order');

        return [
            'successful' => true,
            'status' => 200,
            'message' => 'Entitlement updated.',
            'entitlement' => $summary,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function access(string $accessKey): array
    {
        $entitlement = $this->entitlements->findByAccessKey($accessKey);

        if (!$entitlement instanceof OrderEntitlement) {
            return $this->accessDenied('Entitlement not found', 'The requested access token does not match an entitlement.', 404);
        }

        $summary = $this->entitlements->mapSummary($entitlement);
        $status = (string) ($summary['status'] ?? 'pending');

        if ($status !== 'active') {
            return $this->accessDenied('Access unavailable', 'This entitlement is not currently active.', 403, $summary);
        }

        $now = time();
        $startsAt = $this->timestamp((string) ($summary['starts_at'] ?? ''));

        if ($startsAt !== null && $startsAt > $now) {
            return $this->accessDenied('Access not started', 'This entitlement is active, but its access window has not started yet.', 403, $summary);
        }

        $expiresAt = $this->timestamp((string) ($summary['expires_at'] ?? ''));

        if ($expiresAt !== null && $expiresAt <= $now) {
            $this->entitlements->updateStatus((int) ($summary['id'] ?? 0), 'expired');

            return $this->accessDenied('Access expired', 'This entitlement access window has expired.', 403, [
                ...$summary,
                'status' => 'expired',
            ]);
        }

        $downloadLimit = max(0, (int) ($summary['download_limit'] ?? 0));
        $downloadsUsed = max(0, (int) ($summary['downloads_used'] ?? 0));

        if ($downloadLimit > 0 && $downloadsUsed >= $downloadLimit) {
            $this->entitlements->updateStatus((int) ($summary['id'] ?? 0), 'expired');

            return $this->accessDenied('Download limit reached', 'This entitlement has used all allowed downloads.', 403, [
                ...$summary,
                'status' => 'expired',
            ]);
        }

        $updated = $this->entitlements->recordAccess((int) ($summary['id'] ?? 0));
        $resolved = $updated instanceof OrderEntitlement ? $this->entitlements->mapSummary($updated) : $summary;
        $this->events->dispatch('order.entitlement.accessed', [
            'order_id' => (int) ($resolved['order_id'] ?? 0),
            'entitlement_id' => (int) ($resolved['id'] ?? 0),
            'type' => (string) ($resolved['type'] ?? ''),
        ]);

        return [
            'successful' => true,
            'status' => 200,
            'title' => 'Access granted',
            'message' => 'Your purchased content access is ready.',
            'entitlement' => $resolved,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function summariesForOrder(int $orderId): array
    {
        return $this->entitlements->summaryForOrder($orderId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function summariesForUser(int $userId): array
    {
        return $this->entitlements->summaryForUser($userId);
    }

    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function attributesForItem(array $order, array $item, string $source): array
    {
        $policy = is_array($item['fulfillment_policy'] ?? null) ? $item['fulfillment_policy'] : [];
        $type = strtolower(trim((string) ($item['fulfillment_type'] ?? 'digital_download')));
        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        $downloadLimit = $this->downloadLimit($type, $policy, $quantity);
        $startsAt = $this->resolveStartsAt($policy, (string) ($item['available_at'] ?? ''));
        $expiresAt = $this->resolveExpiresAt($policy, $startsAt);

        return [
            'order_id' => (int) ($order['id'] ?? 0),
            'order_item_id' => (int) ($item['id'] ?? 0),
            'user_id' => (int) ($order['user_id'] ?? 0) > 0 ? (int) ($order['user_id'] ?? 0) : null,
            'product_id' => (int) ($item['product_id'] ?? 0) > 0 ? (int) ($item['product_id'] ?? 0) : null,
            'type' => $type,
            'status' => 'active',
            'label' => (string) ($policy['label'] ?? $item['name'] ?? 'Purchased access'),
            'access_key' => $this->newAccessKey(),
            'access_url' => $this->accessUrl($type, $policy),
            'download_limit' => $downloadLimit,
            'downloads_used' => 0,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'metadata' => json_encode([
                'order_number' => (string) ($order['order_number'] ?? ''),
                'source' => $source,
                'quantity' => $quantity,
                'fulfillment_label' => (string) ($item['fulfillment_label'] ?? ''),
                'policy' => $policy,
                'issued_at' => $this->freshTimestamp(),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ];
    }

    /**
     * @return list<string>
     */
    private function eligibleTypes(): array
    {
        $types = $this->config->get('commerce', 'FULFILLMENT.ACCESS.ELIGIBLE_TYPES', ['digital_download', 'virtual_access', 'subscription']);

        return array_values(array_unique(array_filter(array_map(
            static fn(mixed $type): string => strtolower(trim((string) $type)),
            is_array($types) ? $types : []
        ))));
    }

    /**
     * @param array<string, mixed> $policy
     */
    private function downloadLimit(string $type, array $policy, int $quantity): int
    {
        if ($type !== 'digital_download') {
            return 0;
        }

        $configured = (int) $this->config->get('commerce', 'FULFILLMENT.ACCESS.DEFAULT_DOWNLOAD_LIMIT', 0);
        $limit = (int) ($policy['download_limit'] ?? $policy['max_downloads'] ?? $configured);

        return $limit > 0 ? $limit * max(1, $quantity) : 0;
    }

    /**
     * @param array<string, mixed> $policy
     */
    private function resolveStartsAt(array $policy, string $availableAt): ?string
    {
        foreach (['starts_at', 'access_starts_at', 'available_at'] as $key) {
            $value = trim((string) ($policy[$key] ?? ''));

            if ($value !== '') {
                return $this->normalizeTimestamp($value);
            }
        }

        return $availableAt !== '' ? $this->normalizeTimestamp($availableAt) : null;
    }

    /**
     * @param array<string, mixed> $policy
     */
    private function resolveExpiresAt(array $policy, ?string $startsAt): ?string
    {
        foreach (['expires_at', 'access_ends_at', 'ends_at'] as $key) {
            $value = trim((string) ($policy[$key] ?? ''));

            if ($value !== '') {
                return $this->normalizeTimestamp($value);
            }
        }

        $days = (int) ($policy['access_days'] ?? $policy['duration_days'] ?? $this->config->get('commerce', 'FULFILLMENT.ACCESS.DEFAULT_ACCESS_DAYS', 0));

        if ($days <= 0) {
            return null;
        }

        $base = $startsAt !== null ? ($this->timestamp($startsAt) ?? time()) : time();

        return gmdate('Y-m-d H:i:s', $base + ($days * 86400));
    }

    /**
     * @param array<string, mixed> $policy
     */
    private function accessUrl(string $type, array $policy): string
    {
        $keys = $type === 'digital_download'
            ? ['download_url', 'access_url', 'content_url']
            : ['access_url', 'content_url', 'download_url'];

        foreach ($keys as $key) {
            $value = trim((string) ($policy[$key] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function newAccessKey(): string
    {
        $prefix = strtolower(trim((string) $this->config->get('commerce', 'FULFILLMENT.ACCESS.ACCESS_KEY_PREFIX', 'ent'))) ?: 'ent';

        return $prefix . '_' . bin2hex(random_bytes(24));
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

    /**
     * @param array<string, mixed> $entitlement
     * @return array<string, mixed>
     */
    private function accessDenied(string $title, string $message, int $status, array $entitlement = []): array
    {
        return [
            'successful' => false,
            'status' => $status,
            'title' => $title,
            'message' => $message,
            'entitlement' => $entitlement,
        ];
    }
}
