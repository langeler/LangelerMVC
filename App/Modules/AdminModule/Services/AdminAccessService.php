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
use App\Modules\OrderModule\Models\Order;
use App\Modules\OrderModule\Repositories\OrderAddressRepository;
use App\Modules\OrderModule\Repositories\OrderItemRepository;
use App\Modules\CartModule\Repositories\CartItemRepository;
use App\Modules\CartModule\Repositories\CartRepository;
use App\Modules\OrderModule\Repositories\OrderRepository;
use App\Modules\ShopModule\Repositories\CategoryRepository;
use App\Modules\ShopModule\Repositories\ProductRepository;
use App\Support\Commerce\CatalogLifecycleManager;
use App\Support\Commerce\CommerceTotalsCalculator;
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
        private readonly OrderRepository $orders,
        private readonly OrderItemRepository $orderItems,
        private readonly OrderAddressRepository $orderAddresses,
        private readonly CatalogLifecycleManager $catalogLifecycle,
        private readonly CommerceTotalsCalculator $totals,
        private readonly OrderLifecycleManager $lifecycle,
        private readonly ShippingManager $shipping,
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
        ], 'admin');
        $this->events->dispatch('shop.product.saved', [
            'actor_id' => $this->auth->check() ? (int) $this->auth->id() : 0,
            'entity' => 'product',
            'entity_id' => $productId,
            'action' => $isUpdate ? 'updated' : 'created',
            'name' => $name,
            'slug' => $slug,
            'state' => (string) $attributes['visibility'],
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
            'store_path' => '/admin/catalog/products',
        ];
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
            $totals = $this->totals->calculate($items, (string) ($cart->getAttribute('currency') ?? 'SEK'));

            return [
                'id' => (int) $cart->getKey(),
                'user_id' => $cart->getAttribute('user_id') ?? '-',
                'session_key' => (string) ($cart->getAttribute('session_key') ?? ''),
                'status' => (string) ($cart->getAttribute('status') ?? 'active'),
                'currency' => (string) ($cart->getAttribute('currency') ?? 'SEK'),
                'items' => count($items),
                'subtotal' => (string) ($totals['subtotal'] ?? ''),
                'total' => (string) ($totals['total'] ?? ''),
            ];
        }, array_values(array_filter($this->carts->all(), static fn(mixed $cart): bool => $cart instanceof Cart)));
    }
}
