<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\OrderModule\Models\OrderDocument;

class OrderDocumentRepository extends Repository
{
    protected string $modelClass = OrderDocument::class;

    public function nextDocumentNumber(string $type): string
    {
        $prefix = match (strtolower(trim($type))) {
            'credit_note' => 'CN',
            'packing_slip' => 'PS',
            'return_authorization' => 'RA',
            default => 'INV',
        };

        return $prefix . '-' . gmdate('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function issue(array $attributes): OrderDocument
    {
        $type = strtolower(trim((string) ($attributes['type'] ?? 'invoice'))) ?: 'invoice';
        $attributes['document_number'] = trim((string) ($attributes['document_number'] ?? '')) !== ''
            ? (string) $attributes['document_number']
            : $this->nextDocumentNumber($type);
        $attributes['type'] = $type;
        $attributes['status'] = trim((string) ($attributes['status'] ?? 'issued')) ?: 'issued';
        $attributes['issued_at'] = trim((string) ($attributes['issued_at'] ?? '')) ?: $this->freshTimestamp();
        $attributes['content'] = $this->encodePayload($attributes['content'] ?? []);

        /** @var OrderDocument $document */
        $document = $this->create($attributes);

        return $document;
    }

    /**
     * @return list<OrderDocument>
     */
    public function forOrder(int $orderId): array
    {
        return array_values(array_filter(
            $this->findBy(['order_id' => $orderId]),
            static fn(mixed $document): bool => $document instanceof OrderDocument
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function summaryForOrder(int $orderId): array
    {
        return array_map(fn(OrderDocument $document): array => $this->mapSummary($document), $this->forOrder($orderId));
    }

    public function voidDocument(int $documentId, string $notes = ''): ?OrderDocument
    {
        $this->update($documentId, [
            'status' => 'voided',
            'voided_at' => $this->freshTimestamp(),
            'notes' => $notes,
        ]);
        $fresh = $this->find($documentId);

        return $fresh instanceof OrderDocument ? $fresh : null;
    }

    /**
     * @return array<string, int>
     */
    public function metrics(): array
    {
        $rows = $this->db->fetchAll(sprintf(
            'SELECT type, status, COUNT(*) AS records, COALESCE(SUM(total_minor), 0) AS total_minor FROM %s GROUP BY type, status',
            $this->quoteTable()
        ));
        $metrics = [
            'order_documents' => 0,
            'issued_documents' => 0,
            'voided_documents' => 0,
            'invoices' => 0,
            'credit_notes' => 0,
            'packing_slips' => 0,
            'document_total_minor' => 0,
        ];

        foreach ($rows as $row) {
            $type = strtolower((string) ($row['type'] ?? ''));
            $status = strtolower((string) ($row['status'] ?? ''));
            $records = (int) ($row['records'] ?? 0);

            $metrics['order_documents'] += $records;
            $metrics['document_total_minor'] += (int) ($row['total_minor'] ?? 0);

            if (isset($metrics[$status . '_documents'])) {
                $metrics[$status . '_documents'] += $records;
            }

            if ($type === 'invoice') {
                $metrics['invoices'] += $records;
            }

            if ($type === 'credit_note') {
                $metrics['credit_notes'] += $records;
            }

            if ($type === 'packing_slip') {
                $metrics['packing_slips'] += $records;
            }
        }

        return $metrics;
    }

    /**
     * @return array<string, mixed>
     */
    public function mapSummary(OrderDocument $document): array
    {
        $currency = (string) ($document->getAttribute('currency') ?? 'SEK');

        return [
            'id' => (int) $document->getKey(),
            'order_id' => (int) ($document->getAttribute('order_id') ?? 0),
            'return_id' => (int) ($document->getAttribute('return_id') ?? 0),
            'document_number' => (string) ($document->getAttribute('document_number') ?? ''),
            'type' => (string) ($document->getAttribute('type') ?? 'invoice'),
            'status' => (string) ($document->getAttribute('status') ?? 'issued'),
            'currency' => $currency,
            'subtotal_minor' => (int) ($document->getAttribute('subtotal_minor') ?? 0),
            'discount_minor' => (int) ($document->getAttribute('discount_minor') ?? 0),
            'shipping_minor' => (int) ($document->getAttribute('shipping_minor') ?? 0),
            'tax_minor' => (int) ($document->getAttribute('tax_minor') ?? 0),
            'total_minor' => (int) ($document->getAttribute('total_minor') ?? 0),
            'subtotal' => $this->formatMoneyMinor((int) ($document->getAttribute('subtotal_minor') ?? 0), $currency),
            'discount' => $this->formatMoneyMinor((int) ($document->getAttribute('discount_minor') ?? 0), $currency),
            'shipping' => $this->formatMoneyMinor((int) ($document->getAttribute('shipping_minor') ?? 0), $currency),
            'tax' => $this->formatMoneyMinor((int) ($document->getAttribute('tax_minor') ?? 0), $currency),
            'total' => $this->formatMoneyMinor((int) ($document->getAttribute('total_minor') ?? 0), $currency),
            'vat_rate_bps' => (int) ($document->getAttribute('vat_rate_bps') ?? 0),
            'seller_name' => (string) ($document->getAttribute('seller_name') ?? ''),
            'seller_vat_id' => (string) ($document->getAttribute('seller_vat_id') ?? ''),
            'billing_country' => (string) ($document->getAttribute('billing_country') ?? ''),
            'notes' => (string) ($document->getAttribute('notes') ?? ''),
            'content' => $this->decodePayload((string) ($document->getAttribute('content') ?? '[]')),
            'issued_at' => (string) ($document->getAttribute('issued_at') ?? ''),
            'voided_at' => (string) ($document->getAttribute('voided_at') ?? ''),
            'created_at' => (string) ($document->getAttribute('created_at') ?? ''),
            'updated_at' => (string) ($document->getAttribute('updated_at') ?? ''),
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
