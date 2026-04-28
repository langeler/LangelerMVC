<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\OrderModule\Models\PaymentWebhookEvent;

class PaymentWebhookEventRepository extends Repository
{
    protected string $modelClass = PaymentWebhookEvent::class;

    public function findByEventId(string $driver, string $eventId): ?PaymentWebhookEvent
    {
        $driver = strtolower(trim($driver));
        $eventId = trim($eventId);

        if ($driver === '' || $eventId === '') {
            return null;
        }

        $event = $this->findOneBy([
            'driver' => $driver,
            'event_id' => $eventId,
        ]);

        return $event instanceof PaymentWebhookEvent ? $event : null;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function recordReceived(array $attributes): PaymentWebhookEvent
    {
        $driver = strtolower(trim((string) ($attributes['driver'] ?? '')));
        $eventId = trim((string) ($attributes['event_id'] ?? ''));

        $existing = $this->findByEventId($driver, $eventId);

        if ($existing instanceof PaymentWebhookEvent) {
            return $existing;
        }

        /** @var PaymentWebhookEvent $event */
        $event = $this->create($this->normalizeAttributes($attributes, 'received'));

        return $event;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function markProcessed(int $eventId, array $attributes): PaymentWebhookEvent
    {
        $attributes = $this->normalizeUpdateAttributes($attributes, (string) ($attributes['processing_status'] ?? 'processed'));
        $attributes['processed_at'] ??= $this->freshTimestamp();

        $this->update($eventId, $attributes);

        /** @var PaymentWebhookEvent $fresh */
        $fresh = $this->find($eventId);

        return $fresh;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentSummaries(int $limit = 25): array
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
     * @return array<string, mixed>
     */
    public function mapSummary(PaymentWebhookEvent $event): array
    {
        try {
            $payload = $this->fromJson((string) ($event->getAttribute('payload') ?? '[]'), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $payload = [];
        }

        return [
            'id' => (int) $event->getKey(),
            'driver' => (string) ($event->getAttribute('driver') ?? ''),
            'event_id' => (string) ($event->getAttribute('event_id') ?? ''),
            'order_id' => (int) ($event->getAttribute('order_id') ?? 0),
            'order_reference' => (string) ($event->getAttribute('order_reference') ?? ''),
            'event_type' => (string) ($event->getAttribute('event_type') ?? ''),
            'payment_status' => (string) ($event->getAttribute('payment_status') ?? ''),
            'processing_status' => (string) ($event->getAttribute('processing_status') ?? ''),
            'signature_verified' => (bool) ($event->getAttribute('signature_verified') ?? false),
            'message' => (string) ($event->getAttribute('message') ?? ''),
            'payload' => is_array($payload) ? $payload : [],
            'received_at' => (string) ($event->getAttribute('received_at') ?? ''),
            'processed_at' => (string) ($event->getAttribute('processed_at') ?? ''),
            'created_at' => (string) ($event->getAttribute('created_at') ?? ''),
            'updated_at' => (string) ($event->getAttribute('updated_at') ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function normalizeAttributes(array $attributes, string $defaultStatus): array
    {
        $payload = $attributes['payload'] ?? [];

        return [
            'driver' => strtolower(trim((string) ($attributes['driver'] ?? ''))),
            'event_id' => trim((string) ($attributes['event_id'] ?? '')),
            'order_id' => max(0, (int) ($attributes['order_id'] ?? 0)) ?: null,
            'order_reference' => trim((string) ($attributes['order_reference'] ?? '')) ?: null,
            'event_type' => trim((string) ($attributes['event_type'] ?? '')) ?: null,
            'payment_status' => trim((string) ($attributes['payment_status'] ?? '')) ?: null,
            'processing_status' => trim((string) ($attributes['processing_status'] ?? $defaultStatus)) ?: $defaultStatus,
            'signature_verified' => (bool) ($attributes['signature_verified'] ?? false),
            'payload' => is_array($payload)
                ? $this->toJson($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
                : (string) $payload,
            'message' => trim((string) ($attributes['message'] ?? '')) ?: null,
            'received_at' => trim((string) ($attributes['received_at'] ?? '')) ?: $this->freshTimestamp(),
            'processed_at' => trim((string) ($attributes['processed_at'] ?? '')) ?: null,
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function normalizeUpdateAttributes(array $attributes, string $defaultStatus): array
    {
        $normalized = [];

        foreach ([
            'driver',
            'event_id',
            'order_reference',
            'event_type',
            'payment_status',
            'processing_status',
            'message',
            'received_at',
            'processed_at',
        ] as $key) {
            if (!array_key_exists($key, $attributes)) {
                continue;
            }

            $normalized[$key] = match ($key) {
                'driver' => strtolower(trim((string) $attributes[$key])),
                'processing_status' => trim((string) $attributes[$key]) ?: $defaultStatus,
                default => trim((string) $attributes[$key]) ?: null,
            };
        }

        if (array_key_exists('order_id', $attributes)) {
            $normalized['order_id'] = max(0, (int) $attributes['order_id']) ?: null;
        }

        if (array_key_exists('signature_verified', $attributes)) {
            $normalized['signature_verified'] = (bool) $attributes['signature_verified'];
        }

        if (array_key_exists('payload', $attributes)) {
            $payload = $attributes['payload'];
            $normalized['payload'] = is_array($payload)
                ? $this->toJson($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
                : (string) $payload;
        }

        if (!array_key_exists('processing_status', $normalized)) {
            $normalized['processing_status'] = $defaultStatus;
        }

        return $normalized;
    }
}
