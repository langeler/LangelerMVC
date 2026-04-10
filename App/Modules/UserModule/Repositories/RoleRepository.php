<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\UserModule\Models\Role;

class RoleRepository extends Repository
{
    protected string $modelClass = Role::class;

    public function findByName(string $name): ?Role
    {
        $role = $this->findOneBy(['name' => $name]);

        return $role instanceof Role ? $role : null;
    }

    /**
     * @return list<string>
     */
    public function permissionsForRole(mixed $roleId): array
    {
        $query = $this->db
            ->dataQuery('role_permissions')
            ->select(['permissions.name'])
            ->joinTable(
                'permissions',
                [['role_permissions.permission_id', '=', ['column' => 'permissions.id']]],
                ['permissions.name']
            )
            ->where('role_permissions.role_id', '=', $roleId)
            ->orderBy('permissions.name')
            ->toExecutable();

        return array_values(array_filter(array_unique(array_map(
            static fn(array $row): string => (string) ($row['name'] ?? ''),
            $this->db->fetchAll($query['sql'], $query['bindings'])
        )), static fn(string $name): bool => $name !== ''));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function allWithPermissions(): array
    {
        $roles = [];

        foreach ($this->all() as $role) {
            if (!$role instanceof Role) {
                continue;
            }

            $roles[] = [
                'id' => $role->getKey(),
                'name' => (string) $role->getAttribute('name'),
                'label' => (string) ($role->getAttribute('label') ?? ''),
                'description' => (string) ($role->getAttribute('description') ?? ''),
                'permissions' => $this->permissionsForRole($role->getKey()),
            ];
        }

        return $roles;
    }

    /**
     * @param array<int, int> $permissionIds
     */
    public function syncPermissions(mixed $roleId, array $permissionIds): void
    {
        $roleId = (int) $roleId;
        $permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));

        $this->db->beginTransaction();

        try {
            $delete = $this->db
                ->dataQuery('role_permissions')
                ->delete('role_permissions')
                ->where('role_id', '=', $roleId)
                ->toExecutable();

            $this->db->execute($delete['sql'], $delete['bindings']);

            foreach ($permissionIds as $permissionId) {
                $insert = $this->db
                    ->dataQuery('role_permissions')
                    ->insert('role_permissions', [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                        'created_at' => $this->freshTimestamp(),
                        'updated_at' => $this->freshTimestamp(),
                    ])
                    ->toExecutable();

                $this->db->execute($insert['sql'], $insert['bindings']);
            }

            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
}
