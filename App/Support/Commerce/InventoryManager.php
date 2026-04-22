<?php

declare(strict_types=1);

namespace App\Support\Commerce;

use App\Modules\ShopModule\Models\Product;
use App\Modules\ShopModule\Repositories\ProductRepository;

class InventoryManager
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return array{available:bool,issues:list<string>}
     */
    public function ensureAvailable(array $items): array
    {
        $issues = [];

        foreach ($this->normalizeItems($items) as $item) {
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
     * @return array{reserved:bool,issues:list<string>,items:list<array<string,mixed>>}
     */
    public function reserve(array $items): array
    {
        $availability = $this->ensureAvailable($items);

        if (!$availability['available']) {
            return [
                'reserved' => false,
                'issues' => $availability['issues'],
                'items' => [],
            ];
        }

        $reserved = [];

        foreach ($this->normalizeItems($items) as $item) {
            $product = $this->products->adjustStock((int) $item['product_id'], -((int) $item['quantity']));

            if ($product instanceof Product) {
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
        ];
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return array{released:bool,items:list<array<string,mixed>>}
     */
    public function release(array $items): array
    {
        $released = [];

        foreach ($this->normalizeItems($items) as $item) {
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
     * @param list<array<string, mixed>> $items
     * @return list<array{product_id:int,quantity:int}>
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
            ];
        }

        return $normalized;
    }
}
