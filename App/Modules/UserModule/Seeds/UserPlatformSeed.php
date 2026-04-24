<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Seeds;

use App\Abstracts\Database\Seed;
use App\Core\Database;
use App\Modules\UserModule\Repositories\PermissionRepository;
use App\Modules\UserModule\Repositories\RoleRepository;
use App\Modules\UserModule\Repositories\UserRepository;
use App\Utilities\Managers\Security\DatabaseUserProvider;

class UserPlatformSeed extends Seed
{
    public function __construct(
        UserRepository $repository,
        private readonly RoleRepository $roles,
        private readonly PermissionRepository $permissions,
        private readonly DatabaseUserProvider $userProvider,
        Database $database
    ) {
        parent::__construct($repository, $database);
    }

    public function run(): void
    {
        $permissionMap = [];

        foreach ([
            'admin.access' => 'Access the admin surface',
            'admin.system.view' => 'Inspect framework system surfaces',
            'admin.users.manage' => 'Manage platform users',
            'admin.roles.manage' => 'Manage roles and permissions',
            'content.manage' => 'Manage web pages and publishing',
            'user.profile.view' => 'View user profile data',
            'user.profile.update' => 'Update user profile data',
            'shop.catalog.manage' => 'Manage the shop catalog',
            'promotion.manage' => 'Manage promotions and coupons',
            'cart.manage' => 'Manage carts',
            'order.manage' => 'Manage orders',
        ] as $name => $description) {
            $permission = $this->permissions->findByName($name);

            if ($permission === null) {
                $permission = $this->permissions->create([
                    'name' => $name,
                    'label' => ucwords(str_replace(['.', '_'], ' ', $name)),
                    'description' => $description,
                ]);
            }

            $permissionMap[$name] = (int) $permission->getKey();
        }

        $administrator = $this->roles->findByName('administrator');

        if ($administrator === null) {
            $administrator = $this->roles->create([
                'name' => 'administrator',
                'label' => 'Administrator',
                'description' => 'Full platform administration role.',
            ]);
        }

        $customer = $this->roles->findByName('customer');

        if ($customer === null) {
            $customer = $this->roles->create([
                'name' => 'customer',
                'label' => 'Customer',
                'description' => 'Default end-user role.',
            ]);
        }

        $this->roles->syncPermissions((int) $administrator->getKey(), array_values($permissionMap));
        $this->roles->syncPermissions((int) $customer->getKey(), []);

        $admin = $this->users()->findByEmail('admin@langelermvc.test');

        if ($admin === null) {
            $admin = $this->users()->create([
                'name' => 'Platform Administrator',
                'email' => 'admin@langelermvc.test',
                'password' => $this->userProvider->hashValue('admin12345'),
                'email_verified_at' => gmdate('Y-m-d H:i:s'),
                'status' => 'active',
            ]);
        }

        $customerUser = $this->users()->findByEmail('customer@langelermvc.test');

        if ($customerUser === null) {
            $customerUser = $this->users()->create([
                'name' => 'Demo Customer',
                'email' => 'customer@langelermvc.test',
                'password' => $this->userProvider->hashValue('customer12345'),
                'email_verified_at' => gmdate('Y-m-d H:i:s'),
                'status' => 'active',
            ]);
        }

        $this->users()->syncRoles((int) $admin->getKey(), [(int) $administrator->getKey()]);
        $this->users()->syncRoles((int) $customerUser->getKey(), [(int) $customer->getKey()]);
    }

    public function defaultData(): array
    {
        return [];
    }

    private function users(): UserRepository
    {
        return $this->repository;
    }
}
