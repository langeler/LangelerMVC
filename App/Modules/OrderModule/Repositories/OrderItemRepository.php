<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\OrderModule\Models\OrderItem;

class OrderItemRepository extends Repository
{
    protected string $modelClass = OrderItem::class;

    /**
     * @return list<OrderItem>
     */
    public function forOrder(int $orderId): array
    {
        return array_values(array_filter(
            $this->findBy(['order_id' => $orderId]),
            static fn(mixed $item): bool => $item instanceof OrderItem
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function summaryForOrder(int $orderId): array
    {
        return array_map(function (OrderItem $item): array {
            try {
                $metadata = $this->fromJson((string) ($item->getAttribute('metadata') ?? '[]'), true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $metadata = [];
            }

            $metadata = $this->isArray($metadata) ? $metadata : [];

            return [
                'id' => (int) $item->getKey(),
                'product_id' => (int) ($item->getAttribute('product_id') ?? 0),
                'name' => (string) ($item->getAttribute('product_name') ?? ''),
                'quantity' => (int) ($item->getAttribute('quantity') ?? 0),
                'unit_price_minor' => (int) ($item->getAttribute('unit_price_minor') ?? 0),
                'line_total_minor' => (int) ($item->getAttribute('line_total_minor') ?? 0),
                'slug' => (string) ($metadata['slug'] ?? ''),
                'category_id' => (int) ($metadata['category_id'] ?? 0),
                'fulfillment_type' => (string) ($metadata['fulfillment_type'] ?? 'physical_shipping'),
                'fulfillment_label' => (string) ($metadata['fulfillment_label'] ?? 'Physical shipping'),
                'fulfillment_policy' => is_array($metadata['fulfillment_policy'] ?? null) ? $metadata['fulfillment_policy'] : [],
                'available_at' => (string) ($metadata['available_at'] ?? ''),
            ];
        }, $this->forOrder($orderId));
    }
}
