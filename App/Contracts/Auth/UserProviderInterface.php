<?php

declare(strict_types=1);

namespace App\Contracts\Auth;

interface UserProviderInterface
{
    public function retrieveById(mixed $identifier): ?AuthenticatableInterface;

    /**
     * @param array<string, mixed> $credentials
     */
    public function retrieveByCredentials(array $credentials): ?AuthenticatableInterface;

    /**
     * @param array<string, mixed> $credentials
     */
    public function validateCredentials(AuthenticatableInterface $user, array $credentials): bool;

    public function updateRememberToken(AuthenticatableInterface $user, ?string $token): void;

    /**
     * @return list<string>
     */
    public function rolesFor(AuthenticatableInterface $user): array;

    /**
     * @return list<string>
     */
    public function permissionsFor(AuthenticatableInterface $user): array;
}
