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
            default => $this->catalog((int) ($this->context['page'] ?? 1)),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function catalog(int $page): array
    {
        $pagination = $this->products->paginatePublished(12, max(1, $page));

        return [
            'template' => 'ShopCatalog',
            'status' => 200,
            'title' => 'Shop catalog',
            'headline' => 'Browse the storefront catalog',
            'summary' => 'Database-backed categories and products rendered through the framework storefront pipeline.',
            'products' => $pagination['data'],
            'categories' => $this->categories->publishedSummaries(),
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
                'related' => [],
            ];
        }

        $productData = $this->products->mapProductData($product);

        return [
            'template' => 'ShopProduct',
            'status' => 200,
            'title' => (string) $productData['name'],
            'headline' => (string) $productData['name'],
            'summary' => 'Product details resolved from the database-backed catalog.',
            'product' => $productData,
            'related' => $this->products->relatedPublished(
                (int) $productData['category_id'],
                (int) $productData['id']
            ),
        ];
    }
}
