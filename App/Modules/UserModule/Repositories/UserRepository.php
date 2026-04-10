<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\UserModule\Models\User;

class UserRepository extends Repository
{
    protected string $modelClass = User::class;

    public function findByEmail(string $email): ?User
    {
        $user = $this->findOneBy(['email' => $email]);

        return $user instanceof User ? $user : null;
    }

    public function updateRememberToken(mixed $userId, ?string $token): void
    {
        $this->updateRow((int) $userId, ['remember_token' => $token]);
    }

    public function markEmailVerified(mixed $userId, ?string $verifiedAt = null): void
    {
        $this->updateRow((int) $userId, ['email_verified_at' => $verifiedAt ?? $this->freshTimestamp()]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function updateProfile(mixed $userId, array $attributes): User
    {
        $this->updateRow((int) $userId, $attributes);

        return $this->find((int) $userId);
    }

    public function updatePassword(mixed $userId, string $passwordHash): void
    {
        $this->updateRow((int) $userId, ['password' => $passwordHash]);
    }

    public function saveOtpConfiguration(
        mixed $userId,
        ?string $secretCipher,
        ?string $recoveryCipher,
        ?string $confirmedAt
    ): void {
        $this->updateRow((int) $userId, [
            'otp_secret' => $secretCipher,
            'otp_recovery_codes' => $recoveryCipher,
            'otp_confirmed_at' => $confirmedAt,
        ]);
    }

    /**
     * @return list<string>
     */
    public function rolesForUser(mixed $userId): array
    {
        $query = $this->db
            ->dataQuery('user_roles')
            ->select(['roles.name'])
            ->joinTable('roles', [['user_roles.role_id', '=', ['column' => 'roles.id']]], ['roles.name'])
            ->where('user_roles.user_id', '=', $userId)
            ->orderBy('roles.name')
            ->toExecutable();

        return array_values(array_unique(array_map(
            static fn(array $row): string => (string) ($row['name'] ?? ''),
            $this->db->fetchAll($query['sql'], $query['bindings'])
        )));
    }

    /**
     * @return list<string>
     */
    public function permissionsForUser(mixed $userId): array
    {
        $query = $this->db
            ->dataQuery('user_roles')
            ->select(['permissions.name'])
            ->joinTable(
                'role_permissions',
                [['user_roles.role_id', '=', ['column' => 'role_permissions.role_id']]],
                []
            )
            ->joinTable(
                'permissions',
                [['role_permissions.permission_id', '=', ['column' => 'permissions.id']]],
                ['permissions.name']
            )
            ->where('user_roles.user_id', '=', $userId)
            ->orderBy('permissions.name')
            ->toExecutable();

        $names = array_map(
            static fn(array $row): string => (string) ($row['name'] ?? ''),
            $this->db->fetchAll($query['sql'], $query['bindings'])
        );

        $names = array_values(array_filter(array_unique($names), static fn(string $name): bool => $name !== ''));

        return $names;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function allWithRoles(): array
    {
        $users = [];

        foreach ($this->all() as $user) {
            if (!$user instanceof User) {
                continue;
            }

            $users[] = [
                'id' => $user->getAuthIdentifier(),
                'name' => (string) $user->getAttribute('name'),
                'email' => (string) $user->getAttribute('email'),
                'status' => (string) ($user->getAttribute('status') ?? 'active'),
                'email_verified' => $user->isEmailVerified(),
                'roles' => $this->rolesForUser($user->getAuthIdentifier()),
                'permissions' => $this->permissionsForUser($user->getAuthIdentifier()),
                'otp_enabled' => $user->hasOtpEnabled(),
            ];
        }

        return $users;
    }

    /**
     * @param array<int, int> $roleIds
     */
    public function syncRoles(mixed $userId, array $roleIds): void
    {
        $userId = (int) $userId;
        $roleIds = array_values(array_unique(array_map('intval', $roleIds)));

        $this->db->beginTransaction();

        try {
            $delete = $this->db
                ->dataQuery('user_roles')
                ->delete('user_roles')
                ->where('user_id', '=', $userId)
                ->toExecutable();

            $this->db->execute($delete['sql'], $delete['bindings']);

            foreach ($roleIds as $roleId) {
                $insert = $this->db
                    ->dataQuery('user_roles')
                    ->insert('user_roles', [
                        'user_id' => $userId,
                        'role_id' => $roleId,
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

    /**
     * @param array<string, mixed> $attributes
     */
    private function updateRow(int $userId, array $attributes): void
    {
        $query = $this->db
            ->dataQuery($this->getTable())
            ->update($this->getTable(), array_merge(
                $attributes,
                ['updated_at' => $this->freshTimestamp()]
            ))
            ->where($this->getPrimaryKey(), '=', $userId)
            ->toExecutable();

        $this->db->execute($query['sql'], $query['bindings']);
    }
}
