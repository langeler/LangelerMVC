<?php

declare(strict_types=1);

namespace App\Modules\AdminModule\Services;

use App\Abstracts\Http\Service;
use App\Core\Config;
use App\Core\Router;
use App\Modules\UserModule\Repositories\PermissionRepository;
use App\Modules\UserModule\Repositories\RoleRepository;
use App\Modules\UserModule\Repositories\UserRepository;
use App\Utilities\Managers\CacheManager;
use App\Utilities\Managers\Data\ModuleManager;
use App\Utilities\Managers\Data\SessionManager;
use App\Utilities\Managers\Security\AuthManager;

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
        private readonly ModuleManager $modules,
        private readonly CacheManager $cache,
        private readonly SessionManager $sessionManager,
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
            'system' => $this->systemPage(),
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
                'routes' => $this->router->listRoutes(),
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
}
