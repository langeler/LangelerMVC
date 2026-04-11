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
            return [
                'id' => (int) $item->getKey(),
                'product_id' => (int) ($item->getAttribute('product_id') ?? 0),
                'name' => (string) ($item->getAttribute('product_name') ?? ''),
                'quantity' => (int) ($item->getAttribute('quantity') ?? 0),
                'unit_price_minor' => (int) ($item->getAttribute('unit_price_minor') ?? 0),
                'line_total_minor' => (int) ($item->getAttribute('line_total_minor') ?? 0),
            ];
        }, $this->forOrder($orderId));
    }
}
