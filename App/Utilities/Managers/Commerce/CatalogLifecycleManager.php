<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Commerce;

use App\Contracts\Async\EventDispatcherInterface;
use App\Contracts\Support\AuditLoggerInterface;
use App\Modules\CartModule\Repositories\CartItemRepository;
use App\Modules\OrderModule\Repositories\OrderItemRepository;
use App\Modules\ShopModule\Models\Category;
use App\Modules\ShopModule\Models\Product;
use App\Modules\ShopModule\Repositories\CategoryRepository;
use App\Modules\ShopModule\Repositories\ProductRepository;
use App\Utilities\Managers\Security\AuthManager;

class CatalogLifecycleManager
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly ProductRepository $products,
        private readonly CartItemRepository $cartItems,
        private readonly OrderItemRepository $orderItems,
        private readonly EventDispatcherInterface $events,
        private readonly AuditLoggerInterface $audit,
        private readonly AuthManager $auth
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function setCategoryPublication(int $categoryId, bool $published): array
    {
        $category = $this->categories->find($categoryId);

        if (!$category instanceof Category) {
            return [
                'successful' => false,
                'status' => 404,
                'title' => 'Category not found',
                'message' => 'The requested category could not be found.',
            ];
        }

        $this->categories->update($categoryId, ['is_published' => $published ? 1 : 0]);
        $fresh = $this->categories->find($categoryId);
        $name = (string) ($fresh?->getAttribute('name') ?? $category->getAttribute('name') ?? 'Category');
        $slug = (string) ($fresh?->getAttribute('slug') ?? $category->getAttribute('slug') ?? '');
        $action = $published ? 'published' : 'unpublished';

        $this->audit->record('admin.catalog.category.' . $action, [
            'actor_id' => $this->actorId(),
            'category_id' => (string) $categoryId,
            'slug' => $slug,
            'published' => $published,
        ], 'admin');
        $this->events->dispatch('shop.category.saved', [
            'actor_id' => $this->actorIdInt(),
            'entity' => 'category',
            'entity_id' => $categoryId,
            'action' => $action,
            'name' => $name,
            'slug' => $slug,
            'state' => $published ? 'published' : 'draft',
            'message' => sprintf('Category "%s" was %s in the admin catalog.', $name, $action),
        ]);

        return [
            'successful' => true,
            'status' => 200,
            'title' => 'Category updated',
            'message' => sprintf('Category "%s" is now %s.', $name, $published ? 'published' : 'hidden from the storefront'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteCategory(int $categoryId): array
    {
        $category = $this->categories->find($categoryId);

        if (!$category instanceof Category) {
            return [
                'successful' => false,
                'status' => 404,
                'title' => 'Category not found',
                'message' => 'The requested category could not be found.',
            ];
        }

        $productCount = $this->products->count(['category_id' => $categoryId]);

        if ($productCount > 0) {
            return [
                'successful' => false,
                'status' => 409,
                'title' => 'Category deletion blocked',
                'message' => 'This category still contains products. Move, archive, or delete those products before removing the category.',
            ];
        }

        $name = (string) ($category->getAttribute('name') ?? 'Category');
        $slug = (string) ($category->getAttribute('slug') ?? '');
        $this->categories->deleteModel($category);

        $this->audit->record('admin.catalog.category.deleted', [
            'actor_id' => $this->actorId(),
            'category_id' => (string) $categoryId,
            'slug' => $slug,
        ], 'admin');
        $this->events->dispatch('shop.category.saved', [
            'actor_id' => $this->actorIdInt(),
            'entity' => 'category',
            'entity_id' => $categoryId,
            'action' => 'deleted',
            'name' => $name,
            'slug' => $slug,
            'state' => 'deleted',
            'message' => sprintf('Category "%s" was deleted from the admin catalog.', $name),
        ]);

        return [
            'successful' => true,
            'status' => 200,
            'title' => 'Category deleted',
            'message' => sprintf('Category "%s" was deleted successfully.', $name),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function setProductVisibility(int $productId, string $visibility): array
    {
        $product = $this->products->find($productId);

        if (!$product instanceof Product) {
            return [
                'successful' => false,
                'status' => 404,
                'title' => 'Product not found',
                'message' => 'The requested product could not be found.',
            ];
        }

        $visibility = in_array($visibility, ['published', 'draft', 'archived'], true) ? $visibility : 'draft';
        $this->products->update($productId, ['visibility' => $visibility]);
        $fresh = $this->products->find($productId);
        $name = (string) ($fresh?->getAttribute('name') ?? $product->getAttribute('name') ?? 'Product');
        $slug = (string) ($fresh?->getAttribute('slug') ?? $product->getAttribute('slug') ?? '');

        $this->audit->record('admin.catalog.product.visibility', [
            'actor_id' => $this->actorId(),
            'product_id' => (string) $productId,
            'slug' => $slug,
            'visibility' => $visibility,
        ], 'admin');
        $this->events->dispatch('shop.product.saved', [
            'actor_id' => $this->actorIdInt(),
            'entity' => 'product',
            'entity_id' => $productId,
            'action' => $visibility,
            'name' => $name,
            'slug' => $slug,
            'state' => $visibility,
            'message' => sprintf('Product "%s" is now %s in the admin catalog.', $name, $visibility),
        ]);

        return [
            'successful' => true,
            'status' => 200,
            'title' => 'Product updated',
            'message' => sprintf('Product "%s" is now %s.', $name, $visibility),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteProduct(int $productId): array
    {
        $product = $this->products->find($productId);

        if (!$product instanceof Product) {
            return [
                'successful' => false,
                'status' => 404,
                'title' => 'Product not found',
                'message' => 'The requested product could not be found.',
            ];
        }

        if ($this->orderItems->count(['product_id' => $productId]) > 0) {
            return [
                'successful' => false,
                'status' => 409,
                'title' => 'Product deletion blocked',
                'message' => 'This product is already referenced by at least one order. Archive it instead of deleting it.',
            ];
        }

        if ($this->cartItems->count(['product_id' => $productId]) > 0) {
            return [
                'successful' => false,
                'status' => 409,
                'title' => 'Product deletion blocked',
                'message' => 'This product is still referenced by one or more carts. Remove it from carts or archive it instead.',
            ];
        }

        $name = (string) ($product->getAttribute('name') ?? 'Product');
        $slug = (string) ($product->getAttribute('slug') ?? '');
        $this->products->deleteModel($product);

        $this->audit->record('admin.catalog.product.deleted', [
            'actor_id' => $this->actorId(),
            'product_id' => (string) $productId,
            'slug' => $slug,
        ], 'admin');
        $this->events->dispatch('shop.product.saved', [
            'actor_id' => $this->actorIdInt(),
            'entity' => 'product',
            'entity_id' => $productId,
            'action' => 'deleted',
            'name' => $name,
            'slug' => $slug,
            'state' => 'deleted',
            'message' => sprintf('Product "%s" was deleted from the admin catalog.', $name),
        ]);

        return [
            'successful' => true,
            'status' => 200,
            'title' => 'Product deleted',
            'message' => sprintf('Product "%s" was deleted successfully.', $name),
        ];
    }

    private function actorId(): ?string
    {
        return $this->auth->check() ? (string) $this->auth->id() : null;
    }

    private function actorIdInt(): int
    {
        return $this->auth->check() ? (int) $this->auth->id() : 0;
    }
}
