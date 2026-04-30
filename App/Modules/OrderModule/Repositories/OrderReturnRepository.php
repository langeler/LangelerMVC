<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\OrderModule\Models\OrderReturn;

class OrderReturnRepository extends Repository
{
    protected string $modelClass = OrderReturn::class;

    public function nextReturnNumber(): string
    {
        return 'RET-' . gmdate('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function createReturn(array $attributes): OrderReturn
    {
        $attributes['return_number'] = trim((string) ($attributes['return_number'] ?? '')) !== ''
            ? (string) $attributes['return_number']
            : $this->nextReturnNumber();
        $attributes['metadata'] = $this->encodePayload($attributes['metadata'] ?? []);

        /** @var OrderReturn $return */
        $return = $this->create($attributes);

        return $return;
    }

    /**
     * @return list<OrderReturn>
     */
    public function forOrder(int $orderId): array
    {
        return array_values(array_filter(
            $this->findBy(['order_id' => $orderId]),
            static fn(mixed $return): bool => $return instanceof OrderReturn
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function summaryForOrder(int $orderId): array
    {
        return array_map(fn(OrderReturn $return): array => $this->mapSummary($return), $this->forOrder($orderId));
    }

    public function transition(int $returnId, string $status, string $resolution = ''): ?OrderReturn
    {
        $attributes = [
            'status' => $status,
            'resolution' => $resolution,
        ];

        if ($status === 'approved') {
            $attributes['approved_at'] = $this->freshTimestamp();
        }

        if ($status === 'completed') {
            $attributes['completed_at'] = $this->freshTimestamp();
        }

        if ($status === 'rejected') {
            $attributes['rejected_at'] = $this->freshTimestamp();
        }

        $this->update($returnId, $attributes);
        $fresh = $this->find($returnId);

        return $fresh instanceof OrderReturn ? $fresh : null;
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
        $rows = $this->db->fetchAll(sprintf(
            'SELECT status, type, COUNT(*) AS records, COALESCE(SUM(refund_minor), 0) AS refund_minor FROM %s GROUP BY status, type',
            $this->quoteTable()
        ));
        $metrics = [
            'order_returns' => 0,
            'requested_returns' => 0,
            'approved_returns' => 0,
            'completed_returns' => 0,
            'rejected_returns' => 0,
            'exchange_requests' => 0,
            'return_refund_minor' => 0,
        ];

        foreach ($rows as $row) {
            $status = strtolower((string) ($row['status'] ?? ''));
            $type = strtolower((string) ($row['type'] ?? ''));
            $records = (int) ($row['records'] ?? 0);

            $metrics['order_returns'] += $records;
            $metrics['return_refund_minor'] += (int) ($row['refund_minor'] ?? 0);

            if (isset($metrics[$status . '_returns'])) {
                $metrics[$status . '_returns'] += $records;
            }

            if ($type === 'exchange') {
                $metrics['exchange_requests'] += $records;
            }
        }

        return $metrics;
    }

    /**
     * @return array<string, mixed>
     */
    public function mapSummary(OrderReturn $return): array
    {
        $currency = (string) ($return->getAttribute('currency') ?? 'SEK');
        $refundMinor = (int) ($return->getAttribute('refund_minor') ?? 0);

        return [
            'id' => (int) $return->getKey(),
            'order_id' => (int) ($return->getAttribute('order_id') ?? 0),
            'order_item_id' => (int) ($return->getAttribute('order_item_id') ?? 0),
            'exchange_product_id' => (int) ($return->getAttribute('exchange_product_id') ?? 0),
            'return_number' => (string) ($return->getAttribute('return_number') ?? ''),
            'type' => (string) ($return->getAttribute('type') ?? 'return'),
            'status' => (string) ($return->getAttribute('status') ?? 'requested'),
            'quantity' => (int) ($return->getAttribute('quantity') ?? 0),
            'refund_minor' => $refundMinor,
            'refund' => $this->formatMoneyMinor($refundMinor, $currency),
            'currency' => $currency,
            'reason' => (string) ($return->getAttribute('reason') ?? ''),
            'resolution' => (string) ($return->getAttribute('resolution') ?? ''),
            'restock' => (bool) ($return->getAttribute('restock') ?? false),
            'metadata' => $this->decodePayload((string) ($return->getAttribute('metadata') ?? '[]')),
            'approved_at' => (string) ($return->getAttribute('approved_at') ?? ''),
            'completed_at' => (string) ($return->getAttribute('completed_at') ?? ''),
            'rejected_at' => (string) ($return->getAttribute('rejected_at') ?? ''),
            'created_at' => (string) ($return->getAttribute('created_at') ?? ''),
            'updated_at' => (string) ($return->getAttribute('updated_at') ?? ''),
        ];
    }

    private function encodePayload(mixed $payload): string
    {
        return json_encode(is_array($payload) ? $payload : [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(string $payload): array
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

    private function quoteTable(): string
    {
        return match (strtolower((string) $this->db->getAttribute('driverName'))) {
            'pgsql', 'sqlite' => '"' . $this->getTable() . '"',
            'sqlsrv' => '[' . $this->getTable() . ']',
            default => '`' . $this->getTable() . '`',
        };
    }
}
