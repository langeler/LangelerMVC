<?php

declare(strict_types=1);

namespace App\Modules\AdminModule\Services;

use App\Abstracts\Http\Service;
use App\Contracts\Async\EventDispatcherInterface;
use App\Core\Config;
use App\Core\Router;
use App\Modules\CartModule\Models\Cart;
use App\Modules\CartModule\Repositories\CartItemRepository;
use App\Modules\CartModule\Repositories\CartRepository;
use App\Modules\OrderModule\Repositories\OrderRepository;
use App\Modules\ShopModule\Repositories\CategoryRepository;
use App\Modules\ShopModule\Repositories\ProductRepository;
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
        private readonly ModuleManager $modules,
        private readonly CacheManager $cache,
        private readonly SessionManager $sessionManager,
        private readonly QueueManager $queue,
        private readonly NotificationManager $notifications,
        private readonly PaymentManager $payments,
        private readonly EventDispatcherInterface $events,
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
            'carts' => $this->cartsPage(),
            'orders' => $this->ordersPage(),
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
                    'capabilities' => $this->payments->capabilities(),
                ],
                'routes' => $this->router->listRoutes(),
            ],
        ];
    }

    private function catalogPage(): array
    {
        if (!$this->auth->hasPermission('shop.catalog.manage')) {
            return $this->forbidden('AdminCatalog', 'Catalog administration requires the shop.catalog.manage permission.');
        }

        return [
            'template' => 'AdminCatalog',
            'status' => 200,
            'title' => 'Catalog administration',
            'headline' => 'Shop catalog management',
            'summary' => 'Inspect products and categories through the completed shop module repositories.',
            'categories' => $this->categories->publishedSummaries(),
            'catalog' => $this->products->adminCatalog(),
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

        return [
            'template' => 'AdminOrders',
            'status' => 200,
            'title' => 'Order administration',
            'headline' => 'Order lifecycle visibility',
            'summary' => 'Review order snapshots and payment lifecycle state from the completed order module.',
            'orders' => $this->orders->allSummary(),
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
                    'capabilities' => $this->payments->capabilities(),
                ],
            ],
        ];
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
            $subtotal = array_reduce(
                $items,
                static fn(int $carry, array $item): int => $carry + (int) ($item['line_total_minor'] ?? 0),
                0
            );

            return [
                'id' => (int) $cart->getKey(),
                'user_id' => $cart->getAttribute('user_id') ?? '-',
                'session_key' => (string) ($cart->getAttribute('session_key') ?? ''),
                'status' => (string) ($cart->getAttribute('status') ?? 'active'),
                'currency' => (string) ($cart->getAttribute('currency') ?? 'SEK'),
                'items' => count($items),
                'subtotal' => $this->formatMoney($subtotal, (string) ($cart->getAttribute('currency') ?? 'SEK')),
            ];
        }, array_values(array_filter($this->carts->all(), static fn(mixed $cart): bool => $cart instanceof Cart)));
    }

    private function formatMoney(int $amount, string $currency): string
    {
        return strtoupper($currency) . ' ' . number_format($amount / 100, 2, '.', ' ');
    }
}
