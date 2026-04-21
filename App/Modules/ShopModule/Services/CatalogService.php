<?php

declare(strict_types=1);

namespace App\Modules\ShopModule\Services;

use App\Abstracts\Http\Service;
use App\Modules\ShopModule\Repositories\CategoryRepository;
use App\Modules\ShopModule\Repositories\ProductRepository;

class CatalogService extends Service
{
    private string $action = 'catalog';

    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    public function __construct(
        private readonly ProductRepository $products,
        private readonly CategoryRepository $categories
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function forAction(string $action, array $context = []): static
    {
        $this->action = $action;
        $this->context = $context;

        return $this;
    }

    protected function handle(): array
    {
        return match ($this->action) {
            'product' => $this->product((string) ($this->context['slug'] ?? '')),
            'category' => $this->catalog($this->catalogFilters(), (string) ($this->context['category_slug'] ?? '')),
            default => $this->catalog($this->catalogFilters()),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function catalog(array $filters = [], ?string $categorySlug = null): array
    {
        $category = $categorySlug !== null && $categorySlug !== ''
            ? $this->categories->findPublishedBySlug($categorySlug)
            : null;

        if ($categorySlug !== null && $categorySlug !== '' && $category === null) {
            return [
                'template' => 'ShopCatalog',
                'status' => 404,
                'title' => 'Category not found',
                'headline' => 'The requested category is not available.',
                'summary' => 'Browse another published category or clear the storefront filters to continue.',
                'products' => [],
                'categories' => $this->categories->publishedSummaries(),
                'category' => null,
                'filters' => $this->filtersPayload($filters, $categorySlug),
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 12,
                    'total' => 0,
                ],
            ];
        }

        if ($category !== null) {
            $filters['category_id'] = (int) $category->getKey();
        }

        $page = max(1, (int) ($filters['page'] ?? 1));
        $pagination = $this->products->paginatePublishedCatalog($filters, 12, $page);
        $categoryData = $category !== null ? $this->categories->mapCategoryData($category, $categorySlug) : null;
        $title = $categoryData !== null
            ? (string) ($categoryData['name'] ?? 'Shop category')
            : 'Shop catalog';
        $summary = $categoryData !== null
            ? (string) ($categoryData['description'] ?? 'Published products for the selected category.')
            : 'Database-backed categories and products rendered through the framework storefront pipeline.';

        return [
            'template' => 'ShopCatalog',
            'status' => 200,
            'title' => $title,
            'headline' => $categoryData !== null ? 'Browse ' . $title : 'Browse the storefront catalog',
            'summary' => $summary,
            'products' => $pagination['data'],
            'categories' => $this->categories->publishedSummaries($categorySlug),
            'category' => $categoryData,
            'filters' => $this->filtersPayload($filters, $categorySlug),
            'pagination' => [
                'current_page' => $pagination['current_page'],
                'last_page' => $pagination['last_page'],
                'per_page' => $pagination['per_page'],
                'total' => $pagination['total'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function product(string $slug): array
    {
        $product = $this->products->findPublishedBySlug($slug);

        if ($product === null) {
            return [
                'template' => 'ShopProduct',
                'status' => 404,
                'title' => 'Product not found',
                'headline' => 'The requested product is not available.',
                'summary' => 'ShopModule keeps product lookup inside the framework repository layer.',
                'product' => [],
                'category' => null,
                'related' => [],
            ];
        }

        $productData = $this->products->mapProductData($product);
        $category = $this->categories->find((int) $productData['category_id']);
        $categoryData = $category instanceof \App\Modules\ShopModule\Models\Category
            ? $this->categories->mapCategoryData($category)
            : null;

        return [
            'template' => 'ShopProduct',
            'status' => 200,
            'title' => (string) $productData['name'],
            'headline' => (string) $productData['name'],
            'summary' => 'Product details resolved from the database-backed catalog.',
            'product' => $productData,
            'category' => $categoryData,
            'related' => $this->products->relatedPublished(
                (int) $productData['category_id'],
                (int) $productData['id']
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogFilters(): array
    {
        return [
            'q' => trim((string) ($this->context['q'] ?? '')),
            'availability' => (string) ($this->context['availability'] ?? 'all'),
            'sort' => (string) ($this->context['sort'] ?? 'newest'),
            'page' => max(1, (int) ($this->context['page'] ?? 1)),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function filtersPayload(array $filters, ?string $categorySlug = null): array
    {
        $basePath = $categorySlug !== null && $categorySlug !== ''
            ? '/shop/categories/' . $categorySlug
            : '/shop';

        return [
            'form_action' => $basePath,
            'clear_url' => $basePath,
            'q' => trim((string) ($filters['q'] ?? '')),
            'availability' => (string) ($filters['availability'] ?? 'all'),
            'sort' => (string) ($filters['sort'] ?? 'newest'),
            'page' => max(1, (int) ($filters['page'] ?? 1)),
        ];
    }
}
