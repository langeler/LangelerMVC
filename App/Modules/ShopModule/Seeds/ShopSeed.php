<?php

declare(strict_types=1);

namespace App\Modules\ShopModule\Seeds;

use App\Abstracts\Database\Seed;
use App\Core\Database;
use App\Modules\ShopModule\Repositories\CategoryRepository;
use App\Modules\ShopModule\Repositories\ProductRepository;

class ShopSeed extends Seed
{
    public function __construct(
        ProductRepository $repository,
        private readonly CategoryRepository $categories,
        Database $database
    ) {
        parent::__construct($repository, $database);
    }

    public function run(): void
    {
        $catalog = [
            'framework-tools' => [
                'name' => 'Framework Tools',
                'description' => 'Operational tools and utilities that complement LangelerMVC.',
                'products' => [
                    [
                        'name' => 'Starter Platform License',
                        'slug' => 'starter-platform-license',
                        'description' => 'A demo storefront item used to exercise the catalog, cart, and order workflows.',
                        'price_minor' => 9900,
                        'currency' => 'SEK',
                        'stock' => 50,
                        'media' => $this->toJson(['/assets/images/starter-platform-license.svg'], JSON_THROW_ON_ERROR),
                    ],
                    [
                        'name' => 'Admin Operations Pack',
                        'slug' => 'admin-operations-pack',
                        'description' => 'A packaged admin operations template for the framework demo storefront.',
                        'price_minor' => 14900,
                        'currency' => 'SEK',
                        'stock' => 20,
                        'media' => $this->toJson(['/assets/images/admin-operations-pack.svg'], JSON_THROW_ON_ERROR),
                    ],
                ],
            ],
            'developer-extras' => [
                'name' => 'Developer Extras',
                'description' => 'Additional sample content showing category and product separation.',
                'products' => [
                    [
                        'name' => 'Queue Visibility Dashboard',
                        'slug' => 'queue-visibility-dashboard',
                        'description' => 'A sample product tied to the queue and async management surfaces.',
                        'price_minor' => 7900,
                        'currency' => 'SEK',
                        'stock' => 35,
                        'media' => $this->toJson(['/assets/images/queue-visibility-dashboard.svg'], JSON_THROW_ON_ERROR),
                    ],
                ],
            ],
        ];

        foreach ($catalog as $categorySlug => $categoryData) {
            $category = $this->categories->findBySlug($categorySlug);

            if ($category === null) {
                $category = $this->categories->create([
                    'name' => $categoryData['name'],
                    'slug' => $categorySlug,
                    'description' => $categoryData['description'],
                    'is_published' => 1,
                ]);
            }

            foreach ($categoryData['products'] as $productData) {
                if ($this->products()->findBySlug($productData['slug']) !== null) {
                    continue;
                }

                $this->products()->create([
                    'category_id' => (int) $category->getKey(),
                    'name' => $productData['name'],
                    'slug' => $productData['slug'],
                    'description' => $productData['description'],
                    'price_minor' => $productData['price_minor'],
                    'currency' => $productData['currency'],
                    'visibility' => 'published',
                    'media' => $productData['media'],
                    'stock' => $productData['stock'],
                ]);
            }
        }
    }

    public function defaultData(): array
    {
        return [];
    }

    private function products(): ProductRepository
    {
        return $this->repository;
    }
}
