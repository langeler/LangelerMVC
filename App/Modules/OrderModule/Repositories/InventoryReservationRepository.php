<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\OrderModule\Models\InventoryReservation;

class InventoryReservationRepository extends Repository
{
    protected string $modelClass = InventoryReservation::class;

    /**
     * @param array<string, mixed> $attributes
     */
    public function createReservation(array $attributes): InventoryReservation
    {
        /** @var InventoryReservation $reservation */
        $reservation = $this->create([
            'order_id' => $this->nullableInt($attributes['order_id'] ?? null),
            'cart_id' => $this->nullableInt($attributes['cart_id'] ?? null),
            'product_id' => max(0, (int) ($attributes['product_id'] ?? 0)),
            'reservation_key' => trim((string) ($attributes['reservation_key'] ?? $this->nextReservationKey())),
            'quantity' => max(1, (int) ($attributes['quantity'] ?? 1)),
            'status' => $this->normalizeStatus((string) ($attributes['status'] ?? 'reserved')),
            'source' => trim((string) ($attributes['source'] ?? 'checkout')) ?: 'checkout',
            'expires_at' => $this->nullableString($attributes['expires_at'] ?? null),
            'committed_at' => $this->nullableString($attributes['committed_at'] ?? null),
            'released_at' => $this->nullableString($attributes['released_at'] ?? null),
            'metadata' => $this->toJson(
                is_array($attributes['metadata'] ?? null) ? $attributes['metadata'] : [],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ),
        ]);

        return $reservation;
    }

    public function nextReservationKey(): string
    {
        return 'invres-' . gmdate('YmdHis') . '-' . strtolower(substr(bin2hex(random_bytes(6)), 0, 12));
    }

    public function attachKeyToOrder(string $reservationKey, int $orderId, string $status = 'reserved'): int
    {
        $reservationKey = trim($reservationKey);

        if ($reservationKey === '' || $orderId <= 0) {
            return 0;
        }

        $status = $this->normalizeStatus($status);
        $attributes = [
            'order_id' => $orderId,
            'status' => $status,
            'updated_at' => $this->freshTimestamp(),
        ];

        if ($status === 'committed') {
            $attributes['committed_at'] = $this->freshTimestamp();
        }

        $query = $this->db
            ->dataQuery($this->getTable())
            ->update($this->getTable(), $attributes)
            ->where('reservation_key', '=', $reservationKey)
            ->toExecutable();

        return $this->db->execute($query['sql'], $query['bindings']);
    }

    public function commitForOrder(int $orderId): int
    {
        return $this->transitionForOrder($orderId, 'committed', ['reserved']);
    }

    public function markReleasedByIds(array $ids, string $status = 'released'): int
    {
        $ids = array_values(array_filter(array_map(
            static fn(mixed $id): int => max(0, (int) $id),
            $ids
        ), static fn(int $id): bool => $id > 0));

        if ($ids === []) {
            return 0;
        }

        $query = $this->db
            ->dataQuery($this->getTable())
            ->update($this->getTable(), [
                'status' => $this->normalizeStatus($status),
                'released_at' => $this->freshTimestamp(),
                'updated_at' => $this->freshTimestamp(),
            ])
            ->in('id', $ids)
            ->toExecutable();

        return $this->db->execute($query['sql'], $query['bindings']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activeForOrder(int $orderId): array
    {
        if ($orderId <= 0) {
            return [];
        }

        return $this->rowsForCriteria([
            'order_id' => $orderId,
            'status' => ['in' => ['reserved', 'committed']],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function expiredOpen(string $now): array
    {
        $rows = $this->db->fetchAll(
            'SELECT * FROM inventory_reservations WHERE status = ? AND expires_at IS NOT NULL AND expires_at <= ? ORDER BY id ASC',
            ['reserved', $now]
        );

        return array_map(fn(array $row): array => $this->mapSummary($this->mapRowToModel($row)), $rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function summariesForOrder(int $orderId): array
    {
        if ($orderId <= 0) {
            return [];
        }

        return $this->rowsForCriteria(['order_id' => $orderId]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recent(int $limit = 25): array
    {
        $query = $this->db
            ->dataQuery($this->getTable())
            ->select(['*'])
            ->orderBy('id', 'DESC')
            ->limit(max(1, $limit))
            ->toExecutable();

        return array_map(
            fn(array $row): array => $this->mapSummary($this->mapRowToModel($row)),
            $this->db->fetchAll($query['sql'], $query['bindings'])
        );
    }

    /**
     * @return array<string, int>
     */
    public function metrics(): array
    {
        $rows = $this->recent(1000);

        return [
            'inventory_reservations' => count($rows),
            'reserved_inventory' => count(array_filter($rows, static fn(array $row): bool => ($row['status'] ?? '') === 'reserved')),
            'committed_inventory' => count(array_filter($rows, static fn(array $row): bool => ($row['status'] ?? '') === 'committed')),
            'released_inventory' => count(array_filter($rows, static fn(array $row): bool => in_array(($row['status'] ?? ''), ['released', 'expired'], true))),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function mapSummary(InventoryReservation $reservation): array
    {
        try {
            $metadata = $this->fromJson((string) ($reservation->getAttribute('metadata') ?? '[]'), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $metadata = [];
        }

        $metadata = $this->isArray($metadata) ? $metadata : [];

        return [
            'id' => (int) $reservation->getKey(),
            'order_id' => (int) ($reservation->getAttribute('order_id') ?? 0),
            'cart_id' => (int) ($reservation->getAttribute('cart_id') ?? 0),
            'product_id' => (int) ($reservation->getAttribute('product_id') ?? 0),
            'reservation_key' => (string) ($reservation->getAttribute('reservation_key') ?? ''),
            'quantity' => (int) ($reservation->getAttribute('quantity') ?? 0),
            'status' => (string) ($reservation->getAttribute('status') ?? 'reserved'),
            'source' => (string) ($reservation->getAttribute('source') ?? 'checkout'),
            'expires_at' => (string) ($reservation->getAttribute('expires_at') ?? ''),
            'committed_at' => (string) ($reservation->getAttribute('committed_at') ?? ''),
            'released_at' => (string) ($reservation->getAttribute('released_at') ?? ''),
            'metadata' => $metadata,
            'created_at' => (string) ($reservation->getAttribute('created_at') ?? ''),
            'updated_at' => (string) ($reservation->getAttribute('updated_at') ?? ''),
        ];
    }

    private function transitionForOrder(int $orderId, string $status, array $fromStatuses): int
    {
        if ($orderId <= 0 || $fromStatuses === []) {
            return 0;
        }

        $attributes = [
            'status' => $this->normalizeStatus($status),
            'updated_at' => $this->freshTimestamp(),
        ];

        if ($status === 'committed') {
            $attributes['committed_at'] = $this->freshTimestamp();
        }

        if (in_array($status, ['released', 'expired'], true)) {
            $attributes['released_at'] = $this->freshTimestamp();
        }

        $query = $this->db
            ->dataQuery($this->getTable())
            ->update($this->getTable(), $attributes)
            ->where('order_id', '=', $orderId)
            ->in('status', $fromStatuses)
            ->toExecutable();

        return $this->db->execute($query['sql'], $query['bindings']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rowsForCriteria(array $criteria): array
    {
        return array_map(
            fn(InventoryReservation $reservation): array => $this->mapSummary($reservation),
            array_values(array_filter($this->findBy($criteria), static fn(mixed $reservation): bool => $reservation instanceof InventoryReservation))
        );
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return in_array($status, ['reserved', 'committed', 'released', 'expired'], true)
            ? $status
            : 'reserved';
    }

    private function nullableInt(mixed $value): ?int
    {
        $value = max(0, (int) ($value ?? 0));

        return $value > 0 ? $value : null;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
