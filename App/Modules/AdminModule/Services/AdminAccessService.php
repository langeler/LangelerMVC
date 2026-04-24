<?php

declare(strict_types=1);

namespace App\Modules\AdminModule\Services;

use App\Abstracts\Http\Service;
use App\Contracts\Async\EventDispatcherInterface;
use App\Contracts\Support\AuditLoggerInterface;
use App\Contracts\Support\HealthManagerInterface;
use App\Core\Config;
use App\Core\Router;
use App\Modules\CartModule\Models\Cart;
use App\Modules\CartModule\Repositories\PromotionRepository;
use App\Modules\OrderModule\Models\Order;
use App\Modules\OrderModule\Repositories\OrderAddressRepository;
use App\Modules\OrderModule\Repositories\OrderItemRepository;
use App\Modules\CartModule\Repositories\CartItemRepository;
use App\Modules\CartModule\Repositories\CartRepository;
use App\Modules\OrderModule\Repositories\OrderRepository;
use App\Modules\ShopModule\Repositories\CategoryRepository;
use App\Modules\ShopModule\Repositories\ProductRepository;
use App\Support\Commerce\CatalogLifecycleManager;
use App\Support\Commerce\CartPricingManager;
use App\Support\Commerce\CommerceTotalsCalculator;
use App\Support\Commerce\EntitlementManager;
use App\Support\Commerce\OrderLifecycleManager;
use App\Support\Commerce\ShippingManager;
use App\Modules\UserModule\Repositories\PermissionRepository;
use App\Modules\UserModule\Repositories\RoleRepository;
use App\Modules\UserModule\Repositories\UserRepository;
use App\Utilities\Managers\Async\QueueManager;
use App\Utilities\Managers\CacheManager;
use App\Utilities\Managers\Data\ModuleManager;
use App\Utilities\Managers\Data\SessionManager;
use App\Utilities\Managers\Security\AuthManager;
use App\Utilities\Managers\Support\NotificationManager;
use App\Utilities\Managers\Support\PaymentManager;

class AdminAccessService extends Service
{
    private string $action = 'dashboard';

    /**
     * @var array<string, mixed>
     */
    private array $payload = [];

    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    public function __construct(
        private readonly AuthManager $auth,
        private readonly UserRepository $users,
        private readonly RoleRepository $roles,
        private readonly PermissionRepository $permissions,
        private readonly ProductRepository $products,
        private readonly CategoryRepository $categories,
        private readonly CartRepository $carts,
        private readonly CartItemRepository $cartItems,
        private readonly PromotionRepository $promotions,
        private readonly OrderRepository $orders,
        private readonly OrderItemRepository $orderItems,
        private readonly OrderAddressRepository $orderAddresses,
        private readonly CatalogLifecycleManager $catalogLifecycle,
        private readonly CartPricingManager $pricing,
        private readonly CommerceTotalsCalculator $totals,
        private readonly OrderLifecycleManager $lifecycle,
        private readonly ShippingManager $shipping,
        private readonly EntitlementManager $entitlements,
        private readonly ModuleManager $modules,
        private readonly CacheManager $cache,
        private readonly SessionManager $sessionManager,
        private readonly QueueManager $queue,
        private readonly NotificationManager $notifications,
        private readonly PaymentManager $payments,
        private readonly EventDispatcherInterface $events,
        private readonly HealthManagerInterface $health,
        private readonly AuditLoggerInterface $audit,
        private readonly Router $router,
        private readonly Config $config
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     */
    public function forAction(string $action, array $payload = [], array $context = []): static
    {
        $this->action = $action;
        $this->payload = $payload;
        $this->context = $context;

        return $this;
    }

    protected function handle(): array
    {
        return match ($this->action) {
            'users' => $this->usersPage(),
            'assignRoles' => $this->assignRoles(),
            'roles' => $this->rolesPage(),
            'syncPermissions' => $this->syncPermissions(),
            'catalog' => $this->catalogPage(),
            'saveCategory' => $this->saveCategory(),
            'updateCategory' => $this->saveCategory((int) ($this->context['category'] ?? 0)),
            'publishCategory' => $this->transitionCategory((int) ($this->context['category'] ?? 0), 'publish'),
            'unpublishCategory' => $this->transitionCategory((int) ($this->context['category'] ?? 0), 'unpublish'),
            'deleteCategory' => $this->transitionCategory((int) ($this->context['category'] ?? 0), 'delete'),
            'saveProduct' => $this->saveProduct(),
            'updateProduct' => $this->saveProduct((int) ($this->context['product'] ?? 0)),
            'publishProduct' => $this->transitionProduct((int) ($this->context['product'] ?? 0), 'published'),
            'draftProduct' => $this->transitionProduct((int) ($this->context['product'] ?? 0), 'draft'),
            'archiveProduct' => $this->transitionProduct((int) ($this->context['product'] ?? 0), 'archived'),
            'deleteProduct' => $this->transitionProduct((int) ($this->context['product'] ?? 0), 'delete'),
            'promotions' => $this->promotionsPage(),
            'savePromotion' => $this->savePromotion(),
            'updatePromotion' => $this->savePromotion((int) ($this->context['promotion'] ?? 0)),
            'activatePromotion' => $this->transitionPromotion((int) ($this->context['promotion'] ?? 0), true),
            'deactivatePromotion' => $this->transitionPromotion((int) ($this->context['promotion'] ?? 0), false),
            'deletePromotion' => $this->deletePromotion((int) ($this->context['promotion'] ?? 0)),
            'carts' => $this->cartsPage(),
            'orders' => $this->ordersPage(),
            'order' => $this->orderPage((int) ($this->context['order'] ?? 0)),
            'captureOrder' => $this->transitionOrder((int) ($this->context['order'] ?? 0), 'capture'),
            'cancelOrder' => $this->transitionOrder((int) ($this->context['order'] ?? 0), 'cancel'),
            'refundOrder' => $this->transitionOrder((int) ($this->context['order'] ?? 0), 'refund'),
            'reconcileOrder' => $this->transitionOrder((int) ($this->context['order'] ?? 0), 'reconcile'),
            'packOrder' => $this->transitionFulfillment((int) ($this->context['order'] ?? 0), 'pack'),
            'shipOrder' => $this->transitionFulfillment((int) ($this->context['order'] ?? 0), 'ship'),
            'deliverOrder' => $this->transitionFulfillment((int) ($this->context['order'] ?? 0), 'deliver'),
            'activateEntitlement' => $this->transitionEntitlement(
                (int) ($this->context['order'] ?? 0),
                (int) ($this->context['entitlement'] ?? 0),
                'active'
            ),
            'revokeEntitlement' => $this->transitionEntitlement(
                (int) ($this->context['order'] ?? 0),
                (int) ($this->context['entitlement'] ?? 0),
                'revoked'
            ),
            'system' => $this->systemPage(),
            'operations' => $this->operationsPage(),
            default => $this->dashboard(),
        };
    }

    private function dashboard(): array
    {
        return [
            'template' => 'AdminDashboard',
            'status' => 200,
            'title' => 'Admin dashboard',
            'headline' => 'Framework administration overview',
            'summary' => 'Operational visibility into users, roles, modules, sessions, cache, and routes.',
            'metrics' => [
                'users' => $this->users->count([]),
                'roles' => $this->roles->count([]),
                'permissions' => $this->permissions->count([]),
                'modules' => count($this->modules->getModules()),
                'routes' => count($this->router->listRoutes()),
                'products' => $this->products->count([]),
                'promotions' => $this->promotions->count([]),
                'carts' => $this->carts->count([]),
                'orders' => $this->orders->count([]),
                'failed_jobs' => count($this->queue->failed()),
                'audit_events' => (int) ($this->audit->summary()['stored'] ?? 0),
            ],
            'users' => array_slice($this->users->allWithRoles(), 0, 5),
            'roles' => array_slice($this->roles->allWithPermissions(), 0, 5),
            'modules' => array_keys($this->modules->getModules()),
        ];
    }

    private function usersPage(): array
    {
        if (!$this->auth->hasPermission('admin.users.manage')) {
            return $this->forbidden('AdminUsers', 'User administration requires the admin.users.manage permission.');
        }

        return [
            'template' => 'AdminUsers',
            'status' => 200,
            'title' => 'Manage users',
            'headline' => 'User administration',
            'summary' => 'Inspect users, their active roles, and their effective permissions.',
            'users' => $this->users->allWithRoles(),
            'roles' => $this->roles->allWithPermissions(),
        ];
    }

    private function assignRoles(): array
    {
        if (!$this->auth->hasPermission('admin.users.manage')) {
            return $this->forbidden('AdminUsers', 'User role assignment requires the admin.users.manage permission.');
        }

        $userId = (int) ($this->context['user'] ?? 0);
        $user = $this->users->find($userId);

        if ($user === null) {
            return $this->error('AdminUsers', 'Assignment failed', 'The requested user could not be found.', 404);
        }

        $roles = array_map('strval', (array) ($this->payload['roles'] ?? []));
        $roleIds = [];

        foreach ($roles as $roleName) {
            $role = ctype_digit($roleName)
                ? $this->roles->find((int) $roleName)
                : $this->roles->findByName($roleName);

            if ($role !== null) {
                $roleIds[] = (int) $role->getKey();
            }
        }

        $this->users->syncRoles($userId, $roleIds);
        $this->audit->record('admin.user.roles.synced', [
            'actor_id' => $this->auth->check() ? (string) $this->auth->id() : null,
            'user_id' => (string) $userId,
            'roles' => $roleIds,
        ], 'admin');

        return [
            'template' => 'AdminUsers',
            'status' => 200,
            'title' => 'Roles updated',
            'headline' => 'User roles have been synchronized.',
            'summary' => 'The selected role assignments were stored through the framework repository layer.',
            'message' => 'Role assignment completed successfully.',
            'users' => $this->users->allWithRoles(),
            'roles' => $this->roles->allWithPermissions(),
            'redirect' => '/admin/users',
        ];
    }

    private function rolesPage(): array
    {
        if (!$this->auth->hasPermission('admin.roles.manage')) {
            return $this->forbidden('AdminRoles', 'Role administration requires the admin.roles.manage permission.');
        }

        return [
            'template' => 'AdminRoles',
            'status' => 200,
            'title' => 'Manage roles',
            'headline' => 'Role and permission administration',
            'summary' => 'Inspect role definitions and their permission assignments.',
            'roles' => $this->roles->allWithPermissions(),
            'permissions' => array_map(
                static fn($permission): array => [
                    'id' => (int) $permission->getKey(),
                    'name' => (string) $permission->getAttribute('name'),
                    'label' => (string) ($permission->getAttribute('label') ?? ''),
                ],
                $this->permissions->all()
            ),
        ];
    }

    private function syncPermissions(): array
    {
        if (!$this->auth->hasPermission('admin.roles.manage')) {
            return $this->forbidden('AdminRoles', 'Permission synchronization requires the admin.roles.manage permission.');
        }

        $roleId = (int) ($this->context['role'] ?? 0);
        $role = $this->roles->find($roleId);

        if ($role === null) {
            return $this->error('AdminRoles', 'Synchronization failed', 'The requested role could not be found.', 404);
        }

        $values = array_map('strval', (array) ($this->payload['permissions'] ?? []));
        $permissionIds = [];

        foreach ($values as $value) {
            $permission = ctype_digit($value)
                ? $this->permissions->find((int) $value)
                : $this->permissions->findByName($value);

            if ($permission !== null) {
                $permissionIds[] = (int) $permission->getKey();
            }
        }

        $this->roles->syncPermissions($roleId, $permissionIds);
        $this->audit->record('admin.role.permissions.synced', [
            'actor_id' => $this->auth->check() ? (string) $this->auth->id() : null,
            'role_id' => (string) $roleId,
            'permissions' => $permissionIds,
        ], 'admin');

        return [
            'template' => 'AdminRoles',
            'status' => 200,
            'title' => 'Permissions updated',
            'headline' => 'Role permissions have been synchronized.',
            'summary' => 'The selected permissions were stored through the framework repository layer.',
            'message' => 'Permission synchronization completed successfully.',
            'roles' => $this->roles->allWithPermissions(),
            'permissions' => array_map(
                static fn($permission): array => [
                    'id' => (int) $permission->getKey(),
                    'name' => (string) $permission->getAttribute('name'),
                    'label' => (string) ($permission->getAttribute('label') ?? ''),
                ],
                $this->permissions->all()
            ),
            'redirect' => '/admin/roles',
        ];
    }

    private function systemPage(): array
    {
        if (!$this->auth->hasPermission('admin.system.view')) {
            return $this->forbidden('AdminSystem', 'System inspection requires the admin.system.view permission.');
        }

        return [
            'template' => 'AdminSystem',
            'status' => 200,
            'title' => 'System visibility',
            'headline' => 'Framework operational surfaces',
            'summary' => 'Capabilities and current composition details from the implemented backend subsystems.',
            'modules' => array_keys($this->modules->getModules()),
            'system' => [
                'auth' => [
                    'guard' => (string) $this->config->get('auth', 'GUARD', 'session'),
                    'permissions' => $this->auth->availablePermissions(),
                ],
                'cache' => $this->cache->capabilities(),
                'session' => $this->sessionManager->capabilities(),
                'queue' => [
                    'driver' => $this->queue->driverName(),
                    'drivers' => $this->queue->availableDrivers(),
                ],
                'notifications' => [
                    'channels' => $this->notifications->availableChannels(),
                    'stored' => count($this->notifications->databaseNotifications()),
                ],
                'payments' => [
                    'driver' => $this->payments->driverName(),
                    'drivers' => $this->payments->availableDrivers(),
                    'methods' => $this->payments->supportedMethods(),
                    'flows' => $this->payments->supportedFlows(),
                    'capabilities' => $this->payments->capabilities(),
                    'catalog' => $this->payments->driverCatalog(),
                ],
                'commerce' => [
                    'currency' => (string) $this->config->get('commerce', 'CURRENCY', 'SEK'),
                    'shipping' => $this->config->get('commerce', 'SHIPPING', []),
                    'promotions' => $this->config->get('commerce', 'PROMOTIONS', []),
                    'database_promotions' => $this->promotions->allSummary((string) $this->config->get('commerce', 'CURRENCY', 'SEK')),
                ],
                'health' => $this->health->report(),
                'audit' => $this->audit->summary(),
                'routes' => $this->router->listRoutes(),
            ],
        ];
    }

    private function catalogPage(): array
    {
        if (!$this->auth->hasPermission('shop.catalog.manage')) {
            return $this->forbidden('AdminCatalog', 'Catalog administration requires the shop.catalog.manage permission.');
        }

        return $this->catalogResponse();
    }

    private function saveCategory(int $categoryId = 0): array
    {
        if (!$this->auth->hasPermission('shop.catalog.manage')) {
            return $this->forbidden('AdminCatalog', 'Catalog administration requires the shop.catalog.manage permission.');
        }

        $existing = $categoryId > 0 ? $this->categories->find($categoryId) : null;

        if ($categoryId > 0 && $existing === null) {
            return $this->catalogResponse(
                title: 'Category not found',
                headline: 'Unable to update category',
                summary: 'The requested category could not be resolved.',
                status: 404,
                message: 'Choose another category record and try again.',
                categoryForm: $this->payload
            );
        }

        $name = trim((string) ($this->payload['name'] ?? ''));
        $slug = $this->normalizeSlug((string) ($this->payload['slug'] ?? ''), $name);

        if ($name === '' || $slug === '') {
            return $this->catalogResponse(
                title: 'Category update failed',
                headline: 'Category details are incomplete',
                summary: 'Provide at least a category name so the admin catalog can generate a stable slug.',
                status: 422,
                message: 'Category name and slug are required.',
                categoryForm: $this->payload
            );
        }

        $collision = $this->categories->findBySlug($slug);

        if ($collision !== null && (int) $collision->getKey() !== $categoryId) {
            return $this->catalogResponse(
                title: 'Category update failed',
                headline: 'Category slug is already in use',
                summary: 'Choose a different category slug before saving.',
                status: 422,
                message: 'Category slugs must remain unique across the catalog.',
                categoryForm: $this->payload
            );
        }

        $attributes = [
            'name' => $name,
            'slug' => $slug,
            'description' => trim((string) ($this->payload['description'] ?? '')),
            'is_published' => !empty($this->payload['is_published']) ? 1 : 0,
        ];
        $isUpdate = $existing !== null;

        if ($isUpdate) {
            $this->categories->update($categoryId, $attributes);
        } else {
            $existing = $this->categories->create($attributes);
            $categoryId = (int) $existing->getKey();
        }

        $this->audit->record('admin.catalog.category.saved', [
            'actor_id' => $this->auth->check() ? (string) $this->auth->id() : null,
            'category_id' => (string) $categoryId,
            'slug' => $slug,
            'published' => (bool) $attributes['is_published'],
        ], 'admin');
        $this->events->dispatch('shop.category.saved', [
            'actor_id' => $this->auth->check() ? (int) $this->auth->id() : 0,
            'entity' => 'category',
            'entity_id' => $categoryId,
            'action' => $isUpdate ? 'updated' : 'created',
            'name' => $name,
            'slug' => $slug,
            'state' => (bool) $attributes['is_published'] ? 'published' : 'draft',
            'message' => sprintf('Category "%s" was saved in the admin catalog.', $name),
        ]);

        return [
            ...$this->catalogResponse(
                title: 'Catalog administration',
                headline: 'Category saved',
                summary: 'Category details were stored through the framework repository layer.',
                status: 200,
                message: 'Category changes saved successfully.'
            ),
            'redirect' => '/admin/catalog',
        ];
    }

    private function transitionCategory(int $categoryId, string $action): array
    {
        if (!$this->auth->hasPermission('shop.catalog.manage')) {
            return $this->forbidden('AdminCatalog', 'Catalog administration requires the shop.catalog.manage permission.');
        }

        $result = match ($action) {
            'publish' => $this->catalogLifecycle->setCategoryPublication($categoryId, true),
            'unpublish' => $this->catalogLifecycle->setCategoryPublication($categoryId, false),
            default => $this->catalogLifecycle->deleteCategory($categoryId),
        };

        return [
            ...$this->catalogResponse(
                title: (string) ($result['title'] ?? 'Catalog administration'),
                headline: 'Category lifecycle updated',
                summary: (string) ($result['message'] ?? 'The category lifecycle action completed.'),
                status: (int) ($result['status'] ?? 200),
                message: (string) ($result['message'] ?? '')
            ),
            'redirect' => '/admin/catalog',
        ];
    }

    private function saveProduct(int $productId = 0): array
    {
        if (!$this->auth->hasPermission('shop.catalog.manage')) {
            return $this->forbidden('AdminCatalog', 'Catalog administration requires the shop.catalog.manage permission.');
        }

        $existing = $productId > 0 ? $this->products->find($productId) : null;

        if ($productId > 0 && $existing === null) {
            return $this->catalogResponse(
                title: 'Product not found',
                headline: 'Unable to update product',
                summary: 'The requested product could not be resolved.',
                status: 404,
                message: 'Choose another product record and try again.',
                productForm: $this->payload
            );
        }

        $categoryId = max(0, (int) ($this->payload['category_id'] ?? 0));
        $category = $categoryId > 0 ? $this->categories->find($categoryId) : null;

        if ($category === null) {
            return $this->catalogResponse(
                title: 'Product update failed',
                headline: 'A valid category is required',
                summary: 'Associate each product with a stored category before saving.',
                status: 422,
                message: 'The selected category could not be found.',
                productForm: $this->payload
            );
        }

        $name = trim((string) ($this->payload['name'] ?? ''));
        $slug = $this->normalizeSlug((string) ($this->payload['slug'] ?? ''), $name);

        if ($name === '' || $slug === '') {
            return $this->catalogResponse(
                title: 'Product update failed',
                headline: 'Product details are incomplete',
                summary: 'Provide at least a product name so the admin catalog can generate a stable slug.',
                status: 422,
                message: 'Product name and slug are required.',
                productForm: $this->payload
            );
        }

        $collision = $this->products->findBySlug($slug);

        if ($collision !== null && (int) $collision->getKey() !== $productId) {
            return $this->catalogResponse(
                title: 'Product update failed',
                headline: 'Product slug is already in use',
                summary: 'Choose a different product slug before saving.',
                status: 422,
                message: 'Product slugs must remain unique across the catalog.',
                productForm: $this->payload
            );
        }

        $media = $this->normalizeMediaList((string) ($this->payload['media'] ?? ''));
        $fulfillmentType = $this->normalizeFulfillmentType((string) ($this->payload['fulfillment_type'] ?? 'physical_shipping'));
        $fulfillmentPolicy = $this->normalizeFulfillmentPolicy((string) ($this->payload['fulfillment_policy'] ?? ''));
        $availableAt = trim((string) ($this->payload['available_at'] ?? ''));
        $attributes = [
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $slug,
            'description' => trim((string) ($this->payload['description'] ?? '')),
            'price_minor' => max(0, (int) ($this->payload['price_minor'] ?? 0)),
            'currency' => strtoupper(trim((string) ($this->payload['currency'] ?? 'SEK'))),
            'visibility' => in_array((string) ($this->payload['visibility'] ?? 'published'), ['draft', 'published', 'archived'], true)
                ? (string) ($this->payload['visibility'] ?? 'published')
                : 'published',
            'stock' => max(0, (int) ($this->payload['stock'] ?? 0)),
            'media' => $this->toJson($media, JSON_THROW_ON_ERROR),
            'fulfillment_type' => $fulfillmentType,
            'fulfillment_policy' => $this->toJson($fulfillmentPolicy, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'available_at' => $availableAt !== '' ? $availableAt : null,
        ];
        $isUpdate = $existing !== null;

        if ($isUpdate) {
            $this->products->update($productId, $attributes);
        } else {
            $existing = $this->products->create($attributes);
            $productId = (int) $existing->getKey();
        }

        $this->audit->record('admin.catalog.product.saved', [
            'actor_id' => $this->auth->check() ? (string) $this->auth->id() : null,
            'product_id' => (string) $productId,
            'category_id' => (string) $categoryId,
            'slug' => $slug,
            'visibility' => $attributes['visibility'],
            'stock' => (int) $attributes['stock'],
            'fulfillment_type' => $fulfillmentType,
        ], 'admin');
        $this->events->dispatch('shop.product.saved', [
            'actor_id' => $this->auth->check() ? (int) $this->auth->id() : 0,
            'entity' => 'product',
            'entity_id' => $productId,
            'action' => $isUpdate ? 'updated' : 'created',
            'name' => $name,
            'slug' => $slug,
            'state' => (string) $attributes['visibility'],
            'fulfillment_type' => $fulfillmentType,
            'message' => sprintf('Product "%s" was saved in the admin catalog.', $name),
        ]);

        return [
            ...$this->catalogResponse(
                title: 'Catalog administration',
                headline: 'Product saved',
                summary: 'Product details were stored through the framework repository layer.',
                status: 200,
                message: 'Product changes saved successfully.'
            ),
            'redirect' => '/admin/catalog',
        ];
    }

    private function transitionProduct(int $productId, string $action): array
    {
        if (!$this->auth->hasPermission('shop.catalog.manage')) {
            return $this->forbidden('AdminCatalog', 'Catalog administration requires the shop.catalog.manage permission.');
        }

        $result = $action === 'delete'
            ? $this->catalogLifecycle->deleteProduct($productId)
            : $this->catalogLifecycle->setProductVisibility($productId, $action);

        return [
            ...$this->catalogResponse(
                title: (string) ($result['title'] ?? 'Catalog administration'),
                headline: 'Product lifecycle updated',
                summary: (string) ($result['message'] ?? 'The product lifecycle action completed.'),
                status: (int) ($result['status'] ?? 200),
                message: (string) ($result['message'] ?? '')
            ),
            'redirect' => '/admin/catalog',
        ];
    }

    private function promotionsPage(): array
    {
        if (!$this->canManagePromotions()) {
            return $this->forbidden('AdminPromotions', 'Promotion administration requires promotion.manage or shop.catalog.manage permission.');
        }

        return $this->promotionsResponse();
    }

    private function savePromotion(int $promotionId = 0): array
    {
        if (!$this->canManagePromotions()) {
            return $this->forbidden('AdminPromotions', 'Promotion administration requires promotion.manage or shop.catalog.manage permission.');
        }

        $existing = $promotionId > 0 ? $this->promotions->find($promotionId) : null;

        if ($promotionId > 0 && $existing === null) {
            return $this->promotionsResponse(
                title: 'Promotion not found',
                headline: 'Unable to update promotion',
                summary: 'The requested promotion could not be resolved.',
                status: 404,
                message: 'Choose another promotion record and try again.',
                promotionForm: $this->payload
            );
        }

        $code = strtoupper(trim((string) ($this->payload['code'] ?? '')));
        $label = trim((string) ($this->payload['label'] ?? ''));

        if ($code === '' || $label === '') {
            return $this->promotionsResponse(
                title: 'Promotion update failed',
                headline: 'Promotion details are incomplete',
                summary: 'Provide at least a promotion code and label before saving.',
                status: 422,
                message: 'Promotion code and label are required.',
                promotionForm: $this->payload
            );
        }

        $collision = $this->promotions->findByCode($code);

        if ($collision !== null && (int) $collision->getKey() !== $promotionId) {
            return $this->promotionsResponse(
                title: 'Promotion update failed',
                headline: 'Promotion code is already in use',
                summary: 'Choose a different promotion code before saving.',
                status: 422,
                message: 'Promotion codes must remain unique.',
                promotionForm: $this->payload
            );
        }

        $attributes = $this->promotionAttributes($this->payload);
        $promotion = $this->promotions->savePromotion($attributes, $promotionId);
        $this->audit->record('admin.promotion.saved', [
            'actor_id' => $this->auth->check() ? (string) $this->auth->id() : null,
            'promotion_id' => (string) $promotion->getKey(),
            'code' => (string) ($promotion->getAttribute('code') ?? ''),
            'type' => (string) ($promotion->getAttribute('type') ?? ''),
            'active' => (bool) ($promotion->getAttribute('active') ?? false),
        ], 'admin');
        $this->events->dispatch('promotion.saved', [
            'actor_id' => $this->auth->check() ? (int) $this->auth->id() : 0,
            'promotion_id' => (int) $promotion->getKey(),
            'code' => (string) ($promotion->getAttribute('code') ?? ''),
            'type' => (string) ($promotion->getAttribute('type') ?? ''),
            'active' => (bool) ($promotion->getAttribute('active') ?? false),
        ]);

        return [
            ...$this->promotionsResponse(
                title: 'Promotion administration',
                headline: 'Promotion saved',
                summary: 'Promotion rules were stored in the database-backed promotion catalog.',
                status: 200,
                message: 'Promotion changes saved successfully.'
            ),
            'redirect' => '/admin/promotions',
        ];
    }

    private function transitionPromotion(int $promotionId, bool $active): array
    {
        if (!$this->canManagePromotions()) {
            return $this->forbidden('AdminPromotions', 'Promotion administration requires promotion.manage or shop.catalog.manage permission.');
        }

        $promotion = $this->promotions->setActive($promotionId, $active);

        if ($promotion === null) {
            return $this->promotionsResponse(
                title: 'Promotion not found',
                headline: 'Promotion lifecycle update failed',
                summary: 'The requested promotion could not be resolved.',
                status: 404,
                message: 'Choose another promotion record and try again.'
            );
        }

        $this->audit->record('admin.promotion.' . ($active ? 'activated' : 'deactivated'), [
            'actor_id' => $this->auth->check() ? (string) $this->auth->id() : null,
            'promotion_id' => (string) $promotionId,
            'code' => (string) ($promotion->getAttribute('code') ?? ''),
        ], 'admin');

        return [
            ...$this->promotionsResponse(
                title: 'Promotion administration',
                headline: 'Promotion lifecycle updated',
                summary: $active ? 'The promotion is now active.' : 'The promotion is now inactive.',
                status: 200,
                message: $active ? 'Promotion activated.' : 'Promotion deactivated.'
            ),
            'redirect' => '/admin/promotions',
        ];
    }

    private function deletePromotion(int $promotionId): array
    {
        if (!$this->canManagePromotions()) {
            return $this->forbidden('AdminPromotions', 'Promotion administration requires promotion.manage or shop.catalog.manage permission.');
        }

        $promotion = $this->promotions->find($promotionId);

        if ($promotion === null) {
            return $this->promotionsResponse(
                title: 'Promotion not found',
                headline: 'Promotion deletion failed',
                summary: 'The requested promotion could not be resolved.',
                status: 404,
                message: 'Choose another promotion record and try again.'
            );
        }

        $code = (string) ($promotion->getAttribute('code') ?? '');
        $this->promotions->delete($promotionId);
        $this->audit->record('admin.promotion.deleted', [
            'actor_id' => $this->auth->check() ? (string) $this->auth->id() : null,
            'promotion_id' => (string) $promotionId,
            'code' => $code,
        ], 'admin');

        return [
            ...$this->promotionsResponse(
                title: 'Promotion administration',
                headline: 'Promotion deleted',
                summary: 'The promotion was removed from the database-backed promotion catalog.',
                status: 200,
                message: 'Promotion deleted successfully.'
            ),
            'redirect' => '/admin/promotions',
        ];
    }

    private function cartsPage(): array
    {
        if (!$this->auth->hasPermission('cart.manage')) {
            return $this->forbidden('AdminCarts', 'Cart inspection requires the cart.manage permission.');
        }

        return [
            'template' => 'AdminCarts',
            'status' => 200,
            'title' => 'Cart administration',
            'headline' => 'Cart visibility',
            'summary' => 'Guest and authenticated carts remain visible through the session-aware cart subsystem.',
            'carts' => $this->cartSummaries(),
        ];
    }

    private function ordersPage(): array
    {
        if (!$this->auth->hasPermission('order.manage')) {
            return $this->forbidden('AdminOrders', 'Order inspection requires the order.manage permission.');
        }

        return $this->ordersResponse();
    }

    private function orderPage(int $orderId): array
    {
        if (!$this->auth->hasPermission('order.manage')) {
            return $this->forbidden('AdminOrders', 'Order inspection requires the order.manage permission.');
        }

        $order = $this->orders->find($orderId);

        if (!$order instanceof Order) {
            return $this->ordersResponse(
                title: 'Order not found',
                headline: 'Unable to load the requested order',
                summary: 'Choose a valid order from the administrative order list.',
                status: 404,
                message: 'The requested order could not be found.'
            );
        }

        return $this->ordersResponse(
            title: 'Order administration',
            headline: 'Order ' . (string) ($order->getAttribute('order_number') ?? ''),
            summary: 'Detailed order lifecycle, stored addresses, and available management actions.',
            status: 200,
            order: $this->adminOrderDetail($order)
        );
    }

    private function transitionOrder(int $orderId, string $action): array
    {
        if (!$this->auth->hasPermission('order.manage')) {
            return $this->forbidden('AdminOrders', 'Order management requires the order.manage permission.');
        }

        $transition = $this->lifecycle->transition($orderId, $action, $this->payload);

        if (!$transition['successful']) {
            return $this->ordersResponse(
                title: (string) ($transition['title'] ?? 'Order update failed'),
                headline: 'Order lifecycle update failed',
                summary: (string) ($transition['message'] ?? 'The requested order transition could not be completed.'),
                status: (int) ($transition['status'] ?? 422),
                message: (string) ($transition['message'] ?? '')
            );
        }

        $order = $this->orders->find($orderId);

        if (!$order instanceof Order) {
            return $this->ordersResponse(
                title: 'Order not found',
                headline: 'The updated order could not be loaded',
                summary: 'The lifecycle change completed, but the order could not be reloaded afterward.',
                status: 404,
                message: 'Refresh the administrative order list and try again.'
            );
        }

        return [
            ...$this->ordersResponse(
                title: 'Order administration',
                headline: 'Order ' . (string) ($order->getAttribute('order_number') ?? ''),
                summary: 'Detailed order lifecycle, stored addresses, and available management actions.',
                status: 200,
                message: (string) ($transition['message'] ?? 'Order action completed successfully.'),
                order: $this->adminOrderDetail($order)
            ),
            'redirect' => '/admin/orders/' . $orderId,
        ];
    }

    private function transitionFulfillment(int $orderId, string $action): array
    {
        if (!$this->auth->hasPermission('order.manage')) {
            return $this->forbidden('AdminOrders', 'Order management requires the order.manage permission.');
        }

        $transition = $this->lifecycle->transitionFulfillment($orderId, $action, $this->payload);

        if (!$transition['successful']) {
            return $this->ordersResponse(
                title: (string) ($transition['title'] ?? 'Fulfillment update failed'),
                headline: 'Order fulfillment update failed',
                summary: (string) ($transition['message'] ?? 'The requested fulfillment transition could not be completed.'),
                status: (int) ($transition['status'] ?? 422),
                message: (string) ($transition['message'] ?? '')
            );
        }

        $order = $this->orders->find($orderId);

        if (!$order instanceof Order) {
            return $this->ordersResponse(
                title: 'Order not found',
                headline: 'The updated order could not be loaded',
                summary: 'The fulfillment change completed, but the order could not be reloaded afterward.',
                status: 404,
                message: 'Refresh the administrative order list and try again.'
            );
        }

        return [
            ...$this->ordersResponse(
                title: 'Order administration',
                headline: 'Order ' . (string) ($order->getAttribute('order_number') ?? ''),
                summary: 'Detailed order lifecycle, stored addresses, and available management actions.',
                status: 200,
                message: (string) ($transition['message'] ?? 'Order fulfillment updated successfully.'),
                order: $this->adminOrderDetail($order)
            ),
            'redirect' => '/admin/orders/' . $orderId,
        ];
    }

    private function transitionEntitlement(int $orderId, int $entitlementId, string $status): array
    {
        if (!$this->auth->hasPermission('order.manage')) {
            return $this->forbidden('AdminOrders', 'Entitlement management requires the order.manage permission.');
        }

        $belongsToOrder = array_filter(
            $this->entitlements->summariesForOrder($orderId),
            static fn(array $entitlement): bool => (int) ($entitlement['id'] ?? 0) === $entitlementId
        ) !== [];

        if (!$belongsToOrder) {
            return $this->ordersResponse(
                title: 'Entitlement update failed',
                headline: 'Purchased access could not be updated',
                summary: 'The requested entitlement does not belong to this order.',
                status: 404,
                message: 'Choose a valid entitlement for this order and try again.'
            );
        }

        $transition = $this->entitlements->transition($entitlementId, $status, 'admin');

        if (!$transition['successful']) {
            return $this->ordersResponse(
                title: 'Entitlement update failed',
                headline: 'Purchased access could not be updated',
                summary: (string) ($transition['message'] ?? 'The requested entitlement transition could not be completed.'),
                status: (int) ($transition['status'] ?? 422),
                message: (string) ($transition['message'] ?? '')
            );
        }

        $order = $this->orders->find($orderId);

        if (!$order instanceof Order) {
            return $this->ordersResponse(
                title: 'Order not found',
                headline: 'The updated order could not be loaded',
                summary: 'The entitlement change completed, but the order could not be reloaded afterward.',
                status: 404,
                message: 'Refresh the administrative order list and try again.'
            );
        }

        return [
            ...$this->ordersResponse(
                title: 'Order administration',
                headline: 'Order ' . (string) ($order->getAttribute('order_number') ?? ''),
                summary: 'Detailed order lifecycle, stored addresses, purchased access, and available management actions.',
                status: 200,
                message: (string) ($transition['message'] ?? 'Entitlement updated successfully.'),
                order: $this->adminOrderDetail($order)
            ),
            'redirect' => '/admin/orders/' . $orderId,
        ];
    }

    private function operationsPage(): array
    {
        if (!$this->auth->hasPermission('admin.system.view')) {
            return $this->forbidden('AdminOperations', 'Operational inspection requires the admin.system.view permission.');
        }

        return [
            'template' => 'AdminOperations',
            'status' => 200,
            'title' => 'Operations',
            'headline' => 'Async and platform operations',
            'summary' => 'Inspect queue health, notification delivery, event listeners, and payment capabilities.',
            'operations' => [
                'queue' => [
                    'driver' => $this->queue->driverName(),
                    'drivers' => $this->queue->availableDrivers(),
                    'failed_jobs' => count($this->queue->failed()),
                    'pending_default' => count($this->queue->pending()),
                    'pending_notifications' => count($this->queue->pending('notifications')),
                ],
                'notifications' => [
                    'channels' => $this->notifications->availableChannels(),
                    'stored' => count($this->notifications->databaseNotifications()),
                ],
                'events' => [
                    'registered' => array_keys($this->events->listeners()),
                    'listeners' => $this->events->listeners(),
                ],
                'payments' => [
                    'driver' => $this->payments->driverName(),
                    'drivers' => $this->payments->availableDrivers(),
                    'methods' => $this->payments->supportedMethods(),
                    'flows' => $this->payments->supportedFlows(),
                    'capabilities' => $this->payments->capabilities(),
                    'catalog' => $this->payments->driverCatalog(),
                ],
                'health' => $this->health->report(),
                'audit' => [
                    'summary' => $this->audit->summary(),
                    'recent' => $this->audit->recent(15),
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $categoryForm
     * @param array<string, mixed> $productForm
     * @return array<string, mixed>
     */
    private function catalogResponse(
        string $title = 'Catalog administration',
        string $headline = 'Shop catalog management',
        string $summary = 'Inspect and manage categories and products through the completed shop module repositories.',
        int $status = 200,
        string $message = '',
        array $categoryForm = [],
        array $productForm = []
    ): array {
        $categories = $this->categories->adminSummaries();
        $catalog = $this->products->adminCatalog();

        return [
            'template' => 'AdminCatalog',
            'status' => $status,
            'title' => $title,
            'headline' => $headline,
            'summary' => $summary,
            'message' => $message,
            'categories' => $categories,
            'catalog' => $catalog,
            'catalog_metrics' => $this->catalogMetrics($categories, $catalog),
            'category_form' => $this->categoryFormPayload($categoryForm),
            'product_form' => $this->productFormPayload($productForm, $categories),
        ];
    }

    /**
     * @param array<string, mixed> $order
     * @return array<string, mixed>
     */
    private function ordersResponse(
        string $title = 'Order administration',
        string $headline = 'Order lifecycle visibility',
        string $summary = 'Review order snapshots and payment lifecycle state from the completed order module.',
        int $status = 200,
        string $message = '',
        array $order = []
    ): array {
        return [
            'template' => 'AdminOrders',
            'status' => $status,
            'title' => $title,
            'headline' => $headline,
            'summary' => $summary,
            'message' => $message,
            'orders' => $this->adminOrderSummaries(),
            'order' => $order,
        ];
    }

    /**
     * @param array<string, mixed> $promotionForm
     * @return array<string, mixed>
     */
    private function promotionsResponse(
        string $title = 'Promotion administration',
        string $headline = 'Promotion and coupon management',
        string $summary = 'Create, audit, and operate database-backed promotions while preserving configured baseline promotions.',
        int $status = 200,
        string $message = '',
        array $promotionForm = []
    ): array {
        $currency = (string) $this->config->get('commerce', 'CURRENCY', 'SEK');
        $databasePromotions = $this->promotions->allSummary($currency);
        $configuredPromotions = $this->configuredPromotionSummaries($currency);

        return [
            'template' => 'AdminPromotions',
            'status' => $status,
            'title' => $title,
            'headline' => $headline,
            'summary' => $summary,
            'message' => $message,
            'promotions' => $databasePromotions,
            'configured_promotions' => $configuredPromotions,
            'promotion_form' => $this->promotionFormPayload($promotionForm),
            'promotion_metrics' => [
                'database_promotions' => count($databasePromotions),
                'configured_promotions' => count($configuredPromotions),
                'active_database_promotions' => count(array_filter($databasePromotions, static fn(array $promotion): bool => !empty($promotion['active']))),
                'inactive_database_promotions' => count(array_filter($databasePromotions, static fn(array $promotion): bool => empty($promotion['active']))),
            ],
        ];
    }

    /**
     * @param list<array<string, mixed>> $categories
     * @param list<array<string, mixed>> $catalog
     * @return array<string, int>
     */
    private function catalogMetrics(array $categories, array $catalog): array
    {
        return [
            'categories' => count($categories),
            'published_categories' => count(array_filter($categories, static fn(array $category): bool => !empty($category['is_published']))),
            'products' => count($catalog),
            'published_products' => count(array_filter($catalog, static fn(array $product): bool => ($product['visibility'] ?? '') === 'published')),
            'draft_products' => count(array_filter($catalog, static fn(array $product): bool => ($product['visibility'] ?? '') === 'draft')),
            'archived_products' => count(array_filter($catalog, static fn(array $product): bool => ($product['visibility'] ?? '') === 'archived')),
            'out_of_stock' => count(array_filter($catalog, static fn(array $product): bool => (int) ($product['stock'] ?? 0) <= 0)),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function categoryFormPayload(array $input = []): array
    {
        return [
            'name' => trim((string) ($input['name'] ?? '')),
            'slug' => trim((string) ($input['slug'] ?? '')),
            'description' => trim((string) ($input['description'] ?? '')),
            'is_published' => array_key_exists('is_published', $input) ? !empty($input['is_published']) : true,
            'store_path' => '/admin/catalog/categories',
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param list<array<string, mixed>> $categories
     * @return array<string, mixed>
     */
    private function productFormPayload(array $input = [], array $categories = []): array
    {
        $defaultCategoryId = (int) ($categories[0]['id'] ?? 0);

        return [
            'category_id' => max(0, (int) ($input['category_id'] ?? $defaultCategoryId)),
            'name' => trim((string) ($input['name'] ?? '')),
            'slug' => trim((string) ($input['slug'] ?? '')),
            'description' => trim((string) ($input['description'] ?? '')),
            'price_minor' => max(0, (int) ($input['price_minor'] ?? 0)),
            'currency' => strtoupper(trim((string) ($input['currency'] ?? 'SEK'))),
            'visibility' => in_array((string) ($input['visibility'] ?? 'published'), ['draft', 'published', 'archived'], true)
                ? (string) ($input['visibility'] ?? 'published')
                : 'published',
            'stock' => max(0, (int) ($input['stock'] ?? 0)),
            'media' => trim((string) ($input['media'] ?? '')),
            'fulfillment_type' => $this->normalizeFulfillmentType((string) ($input['fulfillment_type'] ?? 'physical_shipping')),
            'fulfillment_policy' => trim((string) ($input['fulfillment_policy'] ?? '')),
            'available_at' => trim((string) ($input['available_at'] ?? '')),
            'store_path' => '/admin/catalog/products',
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function promotionFormPayload(array $input = []): array
    {
        $criteria = $this->criteriaFromPayload($input);

        return [
            'code' => strtoupper(trim((string) ($input['code'] ?? ''))),
            'label' => trim((string) ($input['label'] ?? '')),
            'description' => trim((string) ($input['description'] ?? '')),
            'type' => $this->normalizePromotionType((string) ($input['type'] ?? 'fixed_amount')),
            'applies_to' => in_array((string) ($input['applies_to'] ?? 'cart_subtotal'), ['cart_subtotal', 'qualified_items'], true)
                ? (string) ($input['applies_to'] ?? 'cart_subtotal')
                : 'cart_subtotal',
            'active' => array_key_exists('active', $input) ? !empty($input['active']) : true,
            'rate_bps' => max(0, (int) ($input['rate_bps'] ?? 0)),
            'amount_minor' => max(0, (int) ($input['amount_minor'] ?? 0)),
            'shipping_rate_minor' => max(0, (int) ($input['shipping_rate_minor'] ?? 0)),
            'min_subtotal_minor' => max(0, (int) ($input['min_subtotal_minor'] ?? 0)),
            'max_subtotal_minor' => max(0, (int) ($input['max_subtotal_minor'] ?? 0)),
            'max_discount_minor' => max(0, (int) ($input['max_discount_minor'] ?? 0)),
            'min_items' => max(0, (int) ($input['min_items'] ?? 0)),
            'max_items' => max(0, (int) ($input['max_items'] ?? 0)),
            'usage_limit' => max(0, (int) ($input['usage_limit'] ?? 0)),
            'starts_at' => trim((string) ($input['starts_at'] ?? '')),
            'ends_at' => trim((string) ($input['ends_at'] ?? '')),
            'criteria' => $criteria,
            'criteria_json' => $criteria === []
                ? ''
                : $this->toJson($criteria, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'store_path' => '/admin/promotions',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function configuredPromotionSummaries(string $currency): array
    {
        $configured = $this->config->get('commerce', 'PROMOTIONS', []);

        if (!is_array($configured)) {
            return [];
        }

        return array_values(array_map(function (string|int $code, mixed $definition) use ($currency): array {
            $definition = is_array($definition) ? $definition : [];
            $type = strtolower((string) ($definition['TYPE'] ?? $definition['type'] ?? 'fixed_amount'));
            $rateBps = max(0, (int) ($definition['RATE_BPS'] ?? $definition['rate_bps'] ?? 0));
            $amountMinor = $this->promotionCurrencyAmount($definition, 'AMOUNT_MINOR_BY_CURRENCY', 'amount_minor_by_currency', $currency)
                ?? max(0, (int) ($definition['AMOUNT_MINOR'] ?? $definition['amount_minor'] ?? 0));

            return [
                'code' => strtoupper((string) ($definition['CODE'] ?? $code)),
                'label' => (string) ($definition['LABEL'] ?? $definition['label'] ?? $code),
                'description' => (string) ($definition['DESCRIPTION'] ?? $definition['description'] ?? ''),
                'type' => $type,
                'active' => (bool) ($definition['ACTIVE'] ?? $definition['active'] ?? true),
                'rate_bps' => $rateBps,
                'amount_minor' => $amountMinor,
                'amount' => $this->formatMoneyMinor($amountMinor, $currency),
                'source' => 'config',
            ];
        }, array_keys($configured), array_values($configured)));
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function promotionCurrencyAmount(array $definition, string $upperKey, string $lowerKey, string $currency): ?int
    {
        $map = $definition[$upperKey] ?? $definition[$lowerKey] ?? null;

        if (!is_array($map)) {
            return null;
        }

        foreach ($map as $key => $value) {
            if (strtoupper((string) $key) === strtoupper($currency)) {
                return max(0, (int) $value);
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function promotionAttributes(array $payload): array
    {
        return [
            'code' => strtoupper(trim((string) ($payload['code'] ?? ''))),
            'label' => trim((string) ($payload['label'] ?? '')),
            'description' => trim((string) ($payload['description'] ?? '')),
            'type' => $this->normalizePromotionType((string) ($payload['type'] ?? 'fixed_amount')),
            'applies_to' => in_array((string) ($payload['applies_to'] ?? 'cart_subtotal'), ['cart_subtotal', 'qualified_items'], true)
                ? (string) ($payload['applies_to'] ?? 'cart_subtotal')
                : 'cart_subtotal',
            'active' => !empty($payload['active']),
            'rate_bps' => max(0, (int) ($payload['rate_bps'] ?? 0)),
            'amount_minor' => max(0, (int) ($payload['amount_minor'] ?? 0)),
            'shipping_rate_minor' => max(0, (int) ($payload['shipping_rate_minor'] ?? 0)),
            'min_subtotal_minor' => max(0, (int) ($payload['min_subtotal_minor'] ?? 0)),
            'max_subtotal_minor' => max(0, (int) ($payload['max_subtotal_minor'] ?? 0)),
            'max_discount_minor' => max(0, (int) ($payload['max_discount_minor'] ?? 0)),
            'min_items' => max(0, (int) ($payload['min_items'] ?? 0)),
            'max_items' => max(0, (int) ($payload['max_items'] ?? 0)),
            'usage_limit' => max(0, (int) ($payload['usage_limit'] ?? 0)),
            'starts_at' => trim((string) ($payload['starts_at'] ?? '')),
            'ends_at' => trim((string) ($payload['ends_at'] ?? '')),
            'criteria' => $this->criteriaFromPayload($payload),
            'source' => 'database',
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function criteriaFromPayload(array $payload): array
    {
        $criteria = [];
        $criteriaPayload = $payload['criteria_json'] ?? null;

        if (!is_string($criteriaPayload) && is_string($payload['criteria'] ?? null)) {
            $criteriaPayload = $payload['criteria'];
        }

        if (is_array($payload['criteria'] ?? null)) {
            $criteria = $payload['criteria'];
        }

        $json = trim(is_string($criteriaPayload) ? $criteriaPayload : '');

        if ($json !== '') {
            try {
                $decoded = $this->fromJson($json, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $criteria = $decoded;
                }
            } catch (\JsonException) {
                $criteria['note'] = $json;
            }
        }

        foreach ([
            'allowed_currencies',
            'allowed_countries',
            'allowed_zones',
            'allowed_carriers',
            'allowed_shipping_options',
            'allowed_product_slugs',
            'allowed_fulfillment_types',
            'excluded_product_slugs',
            'excluded_fulfillment_types',
            'required_fulfillment_types',
        ] as $key) {
            $values = $this->splitList((string) ($payload[$key] ?? ''));
            if ($values !== []) {
                $criteria[$key] = $values;
            }
        }

        foreach ([
            'allowed_product_ids',
            'allowed_category_ids',
            'excluded_product_ids',
        ] as $key) {
            $values = $this->splitIntList((string) ($payload[$key] ?? ''));
            if ($values !== []) {
                $criteria[$key] = $values;
            }
        }

        if (array_key_exists('free_shipping_eligible_only', $payload)) {
            $criteria['free_shipping_eligible_only'] = !empty($payload['free_shipping_eligible_only']);
        }

        return $criteria;
    }

    /**
     * @return list<string>
     */
    private function splitList(string $input): array
    {
        $parts = preg_split('/[\s,]+/', trim($input)) ?: [];

        return array_values(array_filter(array_map(
            static fn(string $value): string => trim($value),
            $parts
        ), static fn(string $value): bool => $value !== ''));
    }

    /**
     * @return list<int>
     */
    private function splitIntList(string $input): array
    {
        return array_values(array_filter(array_map(
            static fn(string $value): int => max(0, (int) $value),
            $this->splitList($input)
        ), static fn(int $value): bool => $value > 0));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function adminOrderSummaries(): array
    {
        return array_map(function (array $order): array {
            $id = (int) ($order['id'] ?? 0);
            $reference = trim((string) ($order['payment_reference'] ?? ''));

            return [
                ...$order,
                'view_path' => '/admin/orders/' . $id,
                'public_path' => '/orders/' . $id,
                'return_path' => $reference !== '' ? '/orders/complete/' . $reference : '/orders/complete',
            ];
        }, $this->orders->allSummary());
    }

    /**
     * @return array<string, mixed>
     */
    private function adminOrderDetail(Order $order): array
    {
        $summary = $this->orders->mapSummary($order);
        $currency = (string) ($summary['currency'] ?? 'SEK');
        $orderId = (int) $order->getKey();
        $reference = trim((string) ($summary['payment_reference'] ?? ''));

        return [
            ...$summary,
            ...$this->shipping->presentation($summary),
            'items' => array_map(function (array $item) use ($currency): array {
                return [
                    ...$item,
                    'unit_price' => $this->formatMoneyMinor((int) ($item['unit_price_minor'] ?? 0), $currency),
                    'line_total' => $this->formatMoneyMinor((int) ($item['line_total_minor'] ?? 0), $currency),
                ];
            }, $this->orderItems->summaryForOrder($orderId)),
            'entitlements' => $this->entitlementSummariesForAdmin($orderId),
            'addresses' => $this->orderAddresses->summaryForOrder($orderId),
            'actions' => $this->adminOrderActions($summary),
            'available_carriers' => $this->shipping->carrierCatalog((string) ($summary['shipping_country'] ?? 'SE')),
            'view_path' => '/admin/orders/' . $orderId,
            'public_path' => '/orders/' . $orderId,
            'return_path' => $reference !== '' ? '/orders/complete/' . $reference : '/orders/complete',
            'cancelled_path' => $reference !== '' ? '/orders/cancelled/' . $reference : '/orders/cancelled',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function entitlementSummariesForAdmin(int $orderId): array
    {
        return array_map(function (array $entitlement) use ($orderId): array {
            $id = (int) ($entitlement['id'] ?? 0);

            return [
                ...$entitlement,
                'activate_path' => '/admin/orders/' . $orderId . '/entitlements/' . $id . '/activate',
                'revoke_path' => '/admin/orders/' . $orderId . '/entitlements/' . $id . '/revoke',
            ];
        }, $this->entitlements->summariesForOrder($orderId));
    }

    /**
     * @param array<string, mixed> $order
     * @return array<string, string>
     */
    private function adminOrderActions(array $order): array
    {
        $actions = [];
        $orderId = (int) ($order['id'] ?? 0);
        $nextAction = is_array($order['payment_next_action'] ?? null) ? $order['payment_next_action'] : [];
        $reference = trim((string) ($order['payment_reference'] ?? ''));

        if ($orderId <= 0) {
            return $actions;
        }

        $actions['public_view'] = '/orders/' . $orderId;
        $actions['admin_view'] = '/admin/orders/' . $orderId;

        if ($reference !== '') {
            $actions['complete_return'] = '/orders/complete/' . $reference;
            $actions['cancelled_return'] = '/orders/cancelled/' . $reference;
        }

        if (($nextAction['url'] ?? '') !== '') {
            $actions['continue_payment'] = (string) $nextAction['url'];
        }

        foreach ($this->lifecycle->availableTransitions($order) as $transition) {
            $actions[$transition] = '/admin/orders/' . $orderId . '/' . $transition;
        }

        foreach ($this->lifecycle->availableFulfillmentTransitions($order) as $transition) {
            $actions[$transition] = '/admin/orders/' . $orderId . '/' . $transition;
        }

        return $actions;
    }

    private function normalizeSlug(string $candidate, string $fallback = ''): string
    {
        $seed = trim($candidate) !== '' ? $candidate : $fallback;
        $seed = strtolower(trim($seed));
        $seed = preg_replace('/[^a-z0-9]+/', '-', $seed) ?? '';

        return trim($seed, '-');
    }

    /**
     * @return list<string>
     */
    private function normalizeMediaList(string $input): array
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", trim($input));

        if ($normalized === '') {
            return [];
        }

        $parts = preg_split('/[\n,]+/', $normalized) ?: [];

        return array_values(array_filter(array_map(
            static fn(string $item): string => trim($item),
            $parts
        ), static fn(string $item): bool => $item !== ''));
    }

    private function normalizeFulfillmentType(string $type): string
    {
        $type = strtolower(trim($type));

        return in_array($type, [
            'physical_shipping',
            'digital_download',
            'virtual_access',
            'store_pickup',
            'scheduled_pickup',
            'preorder',
            'subscription',
        ], true) ? $type : 'physical_shipping';
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeFulfillmentPolicy(string $input): array
    {
        $input = trim($input);

        if ($input === '') {
            return [];
        }

        try {
            $decoded = $this->fromJson($input, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return ['note' => $input];
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizePromotionType(string $type): string
    {
        $type = strtolower(trim($type));

        return in_array($type, ['percentage', 'fixed_amount', 'free_shipping', 'shipping_fixed', 'shipping_percentage'], true)
            ? $type
            : 'fixed_amount';
    }

    private function canManagePromotions(): bool
    {
        return $this->auth->hasPermission('promotion.manage')
            || $this->auth->hasPermission('shop.catalog.manage');
    }

    /**
     * @return array<string, mixed>
     */
    private function error(string $template, string $title, string $message, int $status): array
    {
        return [
            'template' => $template,
            'status' => $status,
            'title' => $title,
            'headline' => $title,
            'summary' => $message,
            'message' => $message,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function forbidden(string $template, string $message): array
    {
        return $this->error($template, 'Forbidden', $message, 403);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function cartSummaries(): array
    {
        return array_map(function (mixed $cart): array {
            if (!$cart instanceof Cart) {
                return [];
            }

            $items = $this->cartItems->summaryForCart((int) $cart->getKey());
            $totals = $this->pricing->price($items, (string) ($cart->getAttribute('currency') ?? 'SEK'), [
                'discount_code' => (string) ($cart->getAttribute('discount_code') ?? ''),
            ]);

            return [
                'id' => (int) $cart->getKey(),
                'user_id' => $cart->getAttribute('user_id') ?? '-',
                'session_key' => (string) ($cart->getAttribute('session_key') ?? ''),
                'status' => (string) ($cart->getAttribute('status') ?? 'active'),
                'currency' => (string) ($cart->getAttribute('currency') ?? 'SEK'),
                'items' => count($items),
                'discount_code' => (string) ($totals['discount_code'] ?? ''),
                'discount' => (string) ($totals['discount'] ?? ''),
                'subtotal' => (string) ($totals['subtotal'] ?? ''),
                'total' => (string) ($totals['total'] ?? ''),
            ];
        }, array_values(array_filter($this->carts->all(), static fn(mixed $cart): bool => $cart instanceof Cart)));
    }
}
