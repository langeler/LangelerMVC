<?php

declare(strict_types=1);

namespace App\Modules\CartModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Exceptions\Database\RepositoryException;
use App\Modules\CartModule\Models\CartItem;

class CartItemRepository extends Repository
{
    protected string $modelClass = CartItem::class;

    /**
     * @return list<CartItem>
     */
    public function forCart(int $cartId): array
    {
        return array_values(array_filter(
            $this->findBy(['cart_id' => $cartId]),
            static fn(mixed $item): bool => $item instanceof CartItem
        ));
    }

    public function findLine(int $cartId, int $productId): ?CartItem
    {
        $item = $this->findOneBy([
            'cart_id' => $cartId,
            'product_id' => $productId,
        ]);

        return $item instanceof CartItem ? $item : null;
    }

    public function addOrIncrement(int $cartId, array $product, int $quantity): CartItem
    {
        $existing = $this->findLine($cartId, (int) ($product['id'] ?? 0));

        if ($existing instanceof CartItem) {
            $newQuantity = ((int) $existing->getAttribute('quantity')) + $quantity;

            return $this->updateQuantity((int) $existing->getKey(), $newQuantity);
        }

        /** @var CartItem $item */
        $item = $this->create([
            'cart_id' => $cartId,
            'product_id' => (int) ($product['id'] ?? 0),
            'product_name' => (string) ($product['name'] ?? 'Product'),
            'unit_price_minor' => (int) ($product['price_minor'] ?? 0),
            'quantity' => $quantity,
            'line_total_minor' => ((int) ($product['price_minor'] ?? 0)) * $quantity,
            'metadata' => $this->toJson([
                'slug' => $product['slug'] ?? null,
                'currency' => $product['currency'] ?? 'SEK',
            ], JSON_THROW_ON_ERROR),
        ]);

        return $item;
    }

    public function updateQuantity(int $itemId, int $quantity): CartItem
    {
        $item = $this->find($itemId);

        if (!$item instanceof CartItem) {
            throw new RepositoryException(sprintf('Cart item [%d] could not be found.', $itemId));
        }

        $quantity = max(1, $quantity);
        $unitPrice = (int) ($item->getAttribute('unit_price_minor') ?? 0);
        $query = $this->db
            ->dataQuery($this->getTable())
            ->update($this->getTable(), [
                'quantity' => $quantity,
                'line_total_minor' => $unitPrice * $quantity,
                'updated_at' => $this->freshTimestamp(),
            ])
            ->where('id', '=', $itemId)
            ->toExecutable();

        $this->db->execute($query['sql'], $query['bindings']);

        /** @var CartItem $fresh */
        $fresh = $this->find($itemId);

        return $fresh;
    }

    public function removeByCart(int $cartId): void
    {
        $query = $this->db
            ->dataQuery($this->getTable())
            ->delete($this->getTable())
            ->where('cart_id', '=', $cartId)
            ->toExecutable();

        $this->db->execute($query['sql'], $query['bindings']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function summaryForCart(int $cartId): array
    {
        return array_map(function (CartItem $item): array {
            try {
                $metadata = $this->fromJson((string) ($item->getAttribute('metadata') ?? '[]'), true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $metadata = [];
            }

            return [
                'id' => (int) $item->getKey(),
                'product_id' => (int) ($item->getAttribute('product_id') ?? 0),
                'name' => (string) ($item->getAttribute('product_name') ?? ''),
                'quantity' => (int) ($item->getAttribute('quantity') ?? 0),
                'unit_price_minor' => (int) ($item->getAttribute('unit_price_minor') ?? 0),
                'unit_price' => $this->formatMoneyMinor((int) ($item->getAttribute('unit_price_minor') ?? 0), (string) (($metadata['currency'] ?? 'SEK'))),
                'line_total_minor' => (int) ($item->getAttribute('line_total_minor') ?? 0),
                'line_total' => $this->formatMoneyMinor((int) ($item->getAttribute('line_total_minor') ?? 0), (string) (($metadata['currency'] ?? 'SEK'))),
                'slug' => (string) ($metadata['slug'] ?? ''),
            ];
        }, $this->forCart($cartId));
    }
}
