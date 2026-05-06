<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Commerce;

use App\Core\Config;
use App\Modules\OrderModule\Repositories\InventoryReservationRepository;
use App\Modules\ShopModule\Models\Product;
use App\Modules\ShopModule\Repositories\ProductRepository;

class InventoryManager
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ?InventoryReservationRepository $reservations = null,
        private readonly ?Config $config = null
    ) {
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return array{available:bool,issues:list<string>}
     */
    public function ensureAvailable(array $items): array
    {
        $this->releaseExpired();

        $issues = [];

        foreach ($this->normalizeItems($items) as $item) {
            if (!$this->requiresStock((string) ($item['fulfillment_type'] ?? 'physical_shipping'))) {
                continue;
            }

            $product = $this->products->find((int) $item['product_id']);

            if (!$product instanceof Product) {
                $issues[] = sprintf('Product #%d could not be resolved for inventory validation.', (int) $item['product_id']);
                continue;
            }

            $available = (int) ($product->getAttribute('stock') ?? 0);
            $required = (int) $item['quantity'];

            if ($available < $required) {
                $issues[] = sprintf(
                    '%s only has %d item(s) available, but %d were requested.',
                    (string) ($product->getAttribute('name') ?? ('Product #' . $item['product_id'])),
                    $available,
                    $required
                );
            }
        }

        return [
            'available' => $issues === [],
            'issues' => $issues,
        ];
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return array{reserved:bool,issues:list<string>,items:list<array<string,mixed>>,reservation_key:string,expires_at:string}
     */
    public function reserve(array $items, array $context = []): array
    {
        $availability = $this->ensureAvailable($items);

        if (!$availability['available']) {
            return [
                'reserved' => false,
                'issues' => $availability['issues'],
                'items' => [],
                'reservation_key' => '',
                'expires_at' => '',
            ];
        }

        $reserved = [];
        $reservationKey = trim((string) ($context['reservation_key'] ?? ''));
        $reservationKey = $reservationKey !== ''
            ? $reservationKey
            : ($this->reservations?->nextReservationKey() ?? $this->fallbackReservationKey());
        $expiresAt = $this->reservationExpiresAt($context);

        foreach ($this->normalizeItems($items) as $item) {
            if (!$this->requiresStock((string) ($item['fulfillment_type'] ?? 'physical_shipping'))) {
                continue;
            }

            $product = $this->products->adjustStock((int) $item['product_id'], -((int) $item['quantity']));

            if ($product instanceof Product) {
                $this->reservations?->createReservation([
                    'order_id' => $context['order_id'] ?? null,
                    'cart_id' => $context['cart_id'] ?? null,
                    'product_id' => (int) $item['product_id'],
                    'reservation_key' => $reservationKey,
                    'quantity' => (int) $item['quantity'],
                    'status' => 'reserved',
                    'source' => $context['source'] ?? 'checkout',
                    'expires_at' => $expiresAt,
                    'metadata' => [
                        'fulfillment_type' => (string) ($item['fulfillment_type'] ?? 'physical_shipping'),
                        'name' => (string) ($item['name'] ?? ''),
                    ],
                ]);

                $reserved[] = [
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (int) $item['quantity'],
                    'stock' => (int) ($product->getAttribute('stock') ?? 0),
                ];
            }
        }

        return [
            'reserved' => true,
            'issues' => [],
            'items' => $reserved,
            'reservation_key' => $reserved !== [] ? $reservationKey : '',
            'expires_at' => $reserved !== [] ? (string) ($expiresAt ?? '') : '',
        ];
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return array{released:bool,items:list<array<string,mixed>>}
     */
    public function release(array $items, array $context = []): array
    {
        $released = [];

        foreach ($this->normalizeItems($items) as $item) {
            if (!$this->requiresStock((string) ($item['fulfillment_type'] ?? 'physical_shipping'))) {
                continue;
            }

            $product = $this->products->adjustStock((int) $item['product_id'], (int) $item['quantity']);

            if ($product instanceof Product) {
                $released[] = [
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (int) $item['quantity'],
                    'stock' => (int) ($product->getAttribute('stock') ?? 0),
                ];
            }
        }

        return [
            'released' => true,
            'items' => $released,
        ];
    }

    /**
     * @param list<array<string, mixed>> $fallbackItems
     * @return array{released:bool,items:list<array<string,mixed>>}
     */
    public function releaseForOrder(int $orderId, array $fallbackItems = [], string $reason = 'release'): array
    {
        if ($this->reservations === null || $orderId <= 0) {
            return $this->release($fallbackItems, ['reason' => $reason]);
        }

        $rows = $this->reservations->activeForOrder($orderId);

        if ($rows === []) {
            return $this->release($fallbackItems, ['reason' => $reason]);
        }

        $released = [];

        foreach ($rows as $row) {
            $product = $this->products->adjustStock((int) ($row['product_id'] ?? 0), (int) ($row['quantity'] ?? 0));

            if ($product instanceof Product) {
                $released[] = [
                    'product_id' => (int) ($row['product_id'] ?? 0),
                    'quantity' => (int) ($row['quantity'] ?? 0),
                    'stock' => (int) ($product->getAttribute('stock') ?? 0),
                ];
            }
        }

        $this->reservations->markReleasedByIds(
            array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $rows),
            $reason === 'expired' ? 'expired' : 'released'
        );

        return [
            'released' => true,
            'items' => $released,
        ];
    }

    public function attachReservations(string $reservationKey, int $orderId, string $status = 'reserved'): int
    {
        return $this->reservations?->attachKeyToOrder($reservationKey, $orderId, $status) ?? 0;
    }

    public function commitForOrder(int $orderId): int
    {
        return $this->reservations?->commitForOrder($orderId) ?? 0;
    }

    /**
     * @return array{released:bool,items:list<array<string,mixed>>}
     */
    public function releaseExpired(): array
    {
        if ($this->reservations === null) {
            return [
                'released' => true,
                'items' => [],
            ];
        }

        $rows = $this->reservations->expiredOpen(gmdate('Y-m-d H:i:s'));
        $released = [];

        foreach ($rows as $row) {
            $product = $this->products->adjustStock((int) ($row['product_id'] ?? 0), (int) ($row['quantity'] ?? 0));

            if ($product instanceof Product) {
                $released[] = [
                    'product_id' => (int) ($row['product_id'] ?? 0),
                    'quantity' => (int) ($row['quantity'] ?? 0),
                    'stock' => (int) ($product->getAttribute('stock') ?? 0),
                ];
            }
        }

        $this->reservations->markReleasedByIds(
            array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $rows),
            'expired'
        );

        return [
            'released' => true,
            'items' => $released,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function summariesForOrder(int $orderId): array
    {
        return $this->reservations?->summariesForOrder($orderId) ?? [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentReservations(int $limit = 25): array
    {
        return $this->reservations?->recent($limit) ?? [];
    }

    /**
     * @return array<string, int>
     */
    public function metrics(): array
    {
        return $this->reservations?->metrics() ?? [
            'inventory_reservations' => 0,
            'reserved_inventory' => 0,
            'committed_inventory' => 0,
            'released_inventory' => 0,
        ];
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return list<array{product_id:int,quantity:int,fulfillment_type:string}>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = max(0, (int) ($item['quantity'] ?? 0));

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $normalized[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'fulfillment_type' => strtolower(trim((string) ($item['fulfillment_type'] ?? 'physical_shipping'))) ?: 'physical_shipping',
                'name' => (string) ($item['name'] ?? ''),
            ];
        }

        return $normalized;
    }

    private function requiresStock(string $fulfillmentType): bool
    {
        return in_array(strtolower(trim($fulfillmentType)), [
            'physical_shipping',
            'store_pickup',
            'scheduled_pickup',
        ], true);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function reservationExpiresAt(array $context): ?string
    {
        $explicit = trim((string) ($context['expires_at'] ?? ''));

        if ($explicit !== '') {
            return $explicit;
        }

        $minutes = max(0, (int) ($context['ttl_minutes'] ?? $this->config?->get('commerce', 'INVENTORY.RESERVATION_TTL_MINUTES', 60) ?? 60));

        return $minutes > 0 ? gmdate('Y-m-d H:i:s', time() + ($minutes * 60)) : null;
    }

    private function fallbackReservationKey(): string
    {
        return 'invres-' . gmdate('YmdHis') . '-' . strtolower(substr(bin2hex(random_bytes(6)), 0, 12));
    }
}
