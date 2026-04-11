<?php

declare(strict_types=1);

namespace App\Modules\ShopModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\ShopModule\Models\Product;

class ProductRepository extends Repository
{
    protected string $modelClass = Product::class;

    public function findBySlug(string $slug): ?Product
    {
        $product = $this->findOneBy(['slug' => $slug]);

        return $product instanceof Product ? $product : null;
    }

    public function findPublishedBySlug(string $slug): ?Product
    {
        $product = $this->findOneBy([
            'slug' => $slug,
            'visibility' => 'published',
        ]);

        return $product instanceof Product ? $product : null;
    }

    /**
     * @return array{data:list<array<string,mixed>>,total:int,per_page:int,current_page:int,last_page:int}
     */
    public function paginatePublished(int $perPage = 12, int $page = 1): array
    {
        $perPage = max(1, $perPage);
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $countQuery = $this->db
            ->dataQuery($this->getTable())
            ->select(['COUNT(*) AS aggregate'])
            ->where('visibility', '=', 'published')
            ->toExecutable();

        $dataQuery = $this->db
            ->dataQuery($this->getTable())
            ->select(['*'])
            ->where('visibility', '=', 'published')
            ->orderBy('id')
            ->limit($perPage)
            ->offset($offset)
            ->toExecutable();

        $total = (int) $this->db->fetchColumn($countQuery['sql'], $countQuery['bindings']);
        $rows = $this->db->fetchAll($dataQuery['sql'], $dataQuery['bindings']);

        return [
            'data' => array_map(fn(array $row): array => $this->mapProductData($this->mapRowToModel($row)), $rows),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function relatedPublished(int $categoryId, int $excludeProductId, int $limit = 3): array
    {
        $query = $this->db
            ->dataQuery($this->getTable())
            ->select(['*'])
            ->where('category_id', '=', $categoryId)
            ->where('visibility', '=', 'published')
            ->where('id', '!=', $excludeProductId)
            ->orderBy('id')
            ->limit($limit)
            ->toExecutable();

        return array_map(
            fn(array $row): array => $this->mapProductData($this->mapRowToModel($row)),
            $this->db->fetchAll($query['sql'], $query['bindings'])
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function adminCatalog(): array
    {
        $query = $this->db
            ->dataQuery($this->getTable())
            ->select(['products.id', 'products.name', 'products.slug', 'products.visibility', 'products.price_minor', 'products.currency', 'products.stock', 'categories.name AS category_name'])
            ->joinTable(
                'categories',
                [['products.category_id', '=', ['column' => 'categories.id']]],
                ['categories.name AS category_name']
            )
            ->orderBy('products.id')
            ->toExecutable();

        return array_map(function (array $row): array {
            return [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'slug' => (string) ($row['slug'] ?? ''),
                'visibility' => (string) ($row['visibility'] ?? ''),
                'price' => $this->formatMoney((int) ($row['price_minor'] ?? 0), (string) ($row['currency'] ?? 'SEK')),
                'stock' => (int) ($row['stock'] ?? 0),
                'category' => (string) ($row['category_name'] ?? ''),
            ];
        }, $this->db->fetchAll($query['sql'], $query['bindings']));
    }

    /**
     * @return array<string, mixed>
     */
    public function mapProductData(Product $product): array
    {
        return [
            'id' => (int) $product->getKey(),
            'category_id' => (int) ($product->getAttribute('category_id') ?? 0),
            'name' => (string) $product->getAttribute('name'),
            'slug' => (string) $product->getAttribute('slug'),
            'description' => (string) ($product->getAttribute('description') ?? ''),
            'price_minor' => (int) ($product->getAttribute('price_minor') ?? 0),
            'currency' => (string) ($product->getAttribute('currency') ?? 'SEK'),
            'price' => $this->formatMoney((int) ($product->getAttribute('price_minor') ?? 0), (string) ($product->getAttribute('currency') ?? 'SEK')),
            'visibility' => (string) ($product->getAttribute('visibility') ?? 'draft'),
            'stock' => (int) ($product->getAttribute('stock') ?? 0),
            'media' => $this->decodeMedia((string) ($product->getAttribute('media') ?? '[]')),
        ];
    }

    private function formatMoney(int $amount, string $currency): string
    {
        return strtoupper($currency) . ' ' . number_format($amount / 100, 2, '.', ' ');
    }

    /**
     * @return list<string>
     */
    private function decodeMedia(string $payload): array
    {
        $decoded = json_decode($payload, true);

        return is_array($decoded) ? array_values(array_map('strval', $decoded)) : [];
    }
}
