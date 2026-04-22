<?php

declare(strict_types=1);

namespace App\Modules\ShopModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\ShopModule\Models\Product;

class ProductRepository extends Repository
{
    protected string $modelClass = Product::class;

    public function adjustStock(int $productId, int $delta): ?Product
    {
        $product = $this->find($productId);

        if (!$product instanceof Product) {
            return null;
        }

        $stock = max(0, (int) ($product->getAttribute('stock') ?? 0) + $delta);
        $this->update($productId, ['stock' => $stock]);

        $fresh = $this->find($productId);

        return $fresh instanceof Product ? $fresh : null;
    }

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
        return $this->paginatePublishedCatalog([], $perPage, $page);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{data:list<array<string,mixed>>,total:int,per_page:int,current_page:int,last_page:int}
     */
    public function paginatePublishedCatalog(array $filters = [], int $perPage = 12, int $page = 1): array
    {
        $perPage = max(1, $perPage);
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $countQueryBuilder = $this->db
            ->dataQuery($this->getTable())
            ->select(['COUNT(*) AS aggregate']);

        $dataQueryBuilder = $this->db
            ->dataQuery($this->getTable())
            ->select(['*']);

        $this->applyCatalogFilters($countQueryBuilder, $filters);
        $this->applyCatalogFilters($dataQueryBuilder, $filters);
        $this->applyCatalogSorting($dataQueryBuilder, (string) ($filters['sort'] ?? 'newest'));

        $countQuery = $countQueryBuilder->toExecutable();
        $dataQuery = $dataQueryBuilder
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
            ->select([
                'products.id',
                'products.category_id',
                'products.name',
                'products.slug',
                'products.description',
                'products.visibility',
                'products.price_minor',
                'products.currency',
                'products.stock',
                'products.media',
                'categories.name AS category_name',
            ])
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
                'description' => (string) ($row['description'] ?? ''),
                'category_id' => (int) ($row['category_id'] ?? 0),
                'visibility' => (string) ($row['visibility'] ?? ''),
                'price_minor' => (int) ($row['price_minor'] ?? 0),
                'currency' => (string) ($row['currency'] ?? 'SEK'),
                'price' => $this->formatMoneyMinor((int) ($row['price_minor'] ?? 0), (string) ($row['currency'] ?? 'SEK')),
                'stock' => (int) ($row['stock'] ?? 0),
                'status' => match ((string) ($row['visibility'] ?? 'draft')) {
                    'published' => 'Published',
                    'archived' => 'Archived',
                    default => 'Draft',
                },
                'category' => (string) ($row['category_name'] ?? ''),
                'media' => $this->decodeMedia((string) ($row['media'] ?? '[]')),
                'media_input' => implode(', ', $this->decodeMedia((string) ($row['media'] ?? '[]'))),
                'storefront_path' => '/shop/products/' . (string) ($row['slug'] ?? ''),
                'update_path' => '/admin/catalog/products/' . (int) ($row['id'] ?? 0) . '/update',
                'publish_path' => '/admin/catalog/products/' . (int) ($row['id'] ?? 0) . '/publish',
                'draft_path' => '/admin/catalog/products/' . (int) ($row['id'] ?? 0) . '/draft',
                'archive_path' => '/admin/catalog/products/' . (int) ($row['id'] ?? 0) . '/archive',
                'delete_path' => '/admin/catalog/products/' . (int) ($row['id'] ?? 0) . '/delete',
            ];
        }, $this->db->fetchAll($query['sql'], $query['bindings']));
    }

    /**
     * @return array<string, mixed>
     */
    public function mapProductData(Product $product): array
    {
        $stock = (int) ($product->getAttribute('stock') ?? 0);

        return [
            'id' => (int) $product->getKey(),
            'category_id' => (int) ($product->getAttribute('category_id') ?? 0),
            'name' => (string) $product->getAttribute('name'),
            'slug' => (string) $product->getAttribute('slug'),
            'description' => (string) ($product->getAttribute('description') ?? ''),
            'price_minor' => (int) ($product->getAttribute('price_minor') ?? 0),
            'currency' => (string) ($product->getAttribute('currency') ?? 'SEK'),
            'price' => $this->formatMoneyMinor((int) ($product->getAttribute('price_minor') ?? 0), (string) ($product->getAttribute('currency') ?? 'SEK')),
            'visibility' => (string) ($product->getAttribute('visibility') ?? 'draft'),
            'stock' => $stock,
            'is_in_stock' => $stock > 0,
            'availability' => $stock > 0 ? 'In stock' : 'Out of stock',
            'media' => $this->decodeMedia((string) ($product->getAttribute('media') ?? '[]')),
        ];
    }

    /**
     * @param object $query
     * @param array<string, mixed> $filters
     */
    private function applyCatalogFilters(object $query, array $filters): void
    {
        $query->where('visibility', '=', 'published');

        if ((int) ($filters['category_id'] ?? 0) > 0) {
            $query->where('category_id', '=', (int) $filters['category_id']);
        }

        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {
            $query->where('name', 'LIKE', '%' . $search . '%');
        }

        match ((string) ($filters['availability'] ?? 'all')) {
            'in_stock' => $query->where('stock', '>', 0),
            'out_of_stock' => $query->where('stock', '<=', 0),
            default => null,
        };
    }

    private function applyCatalogSorting(object $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->orderBy('id', 'ASC'),
            'name' => $query->orderBy('name', 'ASC'),
            'price_low' => $query->orderBy('price_minor', 'ASC'),
            'price_high' => $query->orderBy('price_minor', 'DESC'),
            default => $query->orderBy('id', 'DESC'),
        };
    }

    /**
     * @return list<string>
     */
    private function decodeMedia(string $payload): array
    {
        try {
            $decoded = $this->fromJson($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return $this->isArray($decoded) ? array_values(array_map('strval', $decoded)) : [];
    }
}
