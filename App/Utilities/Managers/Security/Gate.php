<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Security;

use App\Contracts\Auth\AuthenticatableInterface;
use App\Exceptions\AuthException;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;

class Gate
{
    use ArrayTrait, CheckerTrait, ManipulationTrait, TypeCheckerTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    public function __construct(
        private readonly SessionGuard $guard,
        private readonly DatabaseUserProvider $provider,
        private readonly PermissionRegistry $registry,
        private readonly PolicyResolver $policies
    ) {
    }

    public function define(string $ability, callable $callback): void
    {
        $this->policies->define($ability, $callback);
    }

    public function policy(string $class, object|string $policy): void
    {
        $this->policies->registerPolicy($class, $policy);
    }

    public function allows(string $ability, mixed ...$arguments): bool
    {
        $user = $this->guard->user();

        if (!$user instanceof AuthenticatableInterface) {
            return false;
        }

        $callback = $this->policies->resolve($ability, $arguments[0] ?? null);

        if ($callback !== null) {
            return (bool) $callback($user, ...$arguments);
        }

        $normalized = $this->normalizeAbility($ability);

        if ($this->startsWith($normalized, 'role:')) {
            return $this->isInArray(
                $this->substring($normalized, 5),
                $this->provider->rolesFor($user),
                true
            );
        }

        $permissions = $this->provider->permissionsFor($user);

        if ($this->registry->has($normalized)) {
            return $this->isInArray($normalized, $permissions, true);
        }

        return $this->isInArray($normalized, $permissions, true);
    }

    public function denies(string $ability, mixed ...$arguments): bool
    {
        return !$this->allows($ability, ...$arguments);
    }

    /**
     * @param array<int, string> $abilities
     */
    public function any(array $abilities, mixed ...$arguments): bool
    {
        foreach ($abilities as $ability) {
            if ($this->allows($ability, ...$arguments)) {
                return true;
            }
        }

        return false;
    }

    public function authorize(string $ability, mixed ...$arguments): void
    {
        if (!$this->guard->check()) {
            throw new AuthException('Authentication is required to access this resource.');
        }

        if ($this->denies($ability, ...$arguments)) {
            throw new AuthException(sprintf('You are not authorized to perform [%s].', $ability));
        }
    }

    private function normalizeAbility(string $ability): string
    {
        return $this->toLowerString($this->trimString($ability));
    }
}
