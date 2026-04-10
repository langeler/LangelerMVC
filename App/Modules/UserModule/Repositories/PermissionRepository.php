<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\UserModule\Models\Permission;

class PermissionRepository extends Repository
{
    protected string $modelClass = Permission::class;

    public function findByName(string $name): ?Permission
    {
        $permission = $this->findOneBy(['name' => $name]);

        return $permission instanceof Permission ? $permission : null;
    }

    /**
     * @param array<int, string> $names
     * @return list<Permission>
     */
    public function findManyByNames(array $names): array
    {
        $names = array_values(array_filter(array_unique($names), static fn(string $name): bool => $name !== ''));

        if ($names === []) {
            return [];
        }

        $query = $this->db
            ->dataQuery($this->getTable())
            ->select(['*'])
            ->in('name', $names)
            ->orderBy('name')
            ->toExecutable();

        return array_values(array_filter(
            $this->hydrateMany($this->db->fetchAll($query['sql'], $query['bindings'])),
            static fn(mixed $permission): bool => $permission instanceof Permission
        ));
    }
}
