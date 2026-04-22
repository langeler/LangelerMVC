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

    public function findPublishedBySlug(string $slug): ?Category
    {
        $category = $this->findOneBy([
            'slug' => $slug,
            'is_published' => ['>' => 0],
        ]);

        return $category instanceof Category ? $category : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function publishedSummaries(?string $activeSlug = null): array
    {
        return array_map(
            fn(Category $category): array => $this->mapCategoryData($category, $activeSlug),
            array_values(array_filter(
                $this->findBy(['is_published' => ['>' => 0]]),
                static fn(mixed $category): bool => $category instanceof Category
            ))
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function adminSummaries(): array
    {
        return array_map(
            fn(Category $category): array => $this->mapAdminCategoryData($category),
            array_values(array_filter(
                $this->all(),
                static fn(mixed $category): bool => $category instanceof Category
            ))
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function mapCategoryData(Category $category, ?string $activeSlug = null): array
    {
        $slug = (string) $category->getAttribute('slug');

        return [
            'id' => (int) $category->getKey(),
            'name' => (string) $category->getAttribute('name'),
            'slug' => $slug,
            'description' => (string) ($category->getAttribute('description') ?? ''),
            'is_active' => $activeSlug !== null && $activeSlug !== '' && strcasecmp($activeSlug, $slug) === 0,
            'url' => '/shop/categories/' . $slug,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function mapAdminCategoryData(Category $category): array
    {
        $slug = (string) $category->getAttribute('slug');
        $isPublished = (bool) ($category->getAttribute('is_published') ?? false);

        return [
            'id' => (int) $category->getKey(),
            'name' => (string) $category->getAttribute('name'),
            'slug' => $slug,
            'description' => (string) ($category->getAttribute('description') ?? ''),
            'is_published' => $isPublished,
            'status' => $isPublished ? 'Published' : 'Draft',
            'storefront_path' => '/shop/categories/' . $slug,
            'update_path' => '/admin/catalog/categories/' . (int) $category->getKey() . '/update',
            'publish_path' => '/admin/catalog/categories/' . (int) $category->getKey() . '/publish',
            'unpublish_path' => '/admin/catalog/categories/' . (int) $category->getKey() . '/unpublish',
            'delete_path' => '/admin/catalog/categories/' . (int) $category->getKey() . '/delete',
        ];
    }
}
