<?php

declare(strict_types=1);

namespace App\Modules\ShopModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\ShopModule\Models\Category;

class CategoryRepository extends Repository
{
    protected string $modelClass = Category::class;

    public function findBySlug(string $slug): ?Category
    {
        $category = $this->findOneBy(['slug' => $slug]);

        return $category instanceof Category ? $category : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function publishedSummaries(): array
    {
        return array_map(
            static fn(Category $category): array => [
                'id' => (int) $category->getKey(),
                'name' => (string) $category->getAttribute('name'),
                'slug' => (string) $category->getAttribute('slug'),
                'description' => (string) ($category->getAttribute('description') ?? ''),
            ],
            array_values(array_filter(
                $this->findBy(['is_published' => ['>' => 0]]),
                static fn(mixed $category): bool => $category instanceof Category
            ))
        );
    }
}
