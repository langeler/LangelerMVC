<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Security;

use App\Contracts\Async\EventDispatcherInterface;
use App\Contracts\Auth\AuthenticatableInterface;
use App\Contracts\Auth\PasswordBrokerInterface;
use App\Contracts\Support\AuditLoggerInterface;

class AuthManager
{
    public function __construct(
        private readonly SessionGuard $guard,
        private readonly Gate $gate,
        private readonly PasswordBrokerInterface $passwordBroker,
        private readonly DatabaseUserProvider $provider,
        private readonly PermissionRegistry $permissions,
        private readonly EventDispatcherInterface $events,
        private readonly AuditLoggerInterface $audit
    ) {
    }

    public function guard(): SessionGuard
    {
        return $this->guard;
    }

    public function passwords(): PasswordBrokerInterface
    {
        return $this->passwordBroker;
    }

    public function gate(): Gate
    {
        return $this->gate;
    }

    public function check(): bool
    {
        return $this->guard->check();
    }

    public function guest(): bool
    {
        return $this->guard->guest();
    }

    public function user(): ?AuthenticatableInterface
    {
        return $this->guard->user();
    }

    public function id(): mixed
    {
        return $this->guard->id();
    }

    /**
     * @param array<string, mixed> $credentials
     */
    public function attempt(array $credentials, bool $remember = false): bool
    {
        $successful = $this->guard->attempt($credentials, $remember);

        if ($successful) {
            $user = $this->user();

            if ($user instanceof AuthenticatableInterface) {
                $this->dispatch('auth.login', [
                    'user_id' => $user->getAuthIdentifier(),
                    'remember' => $remember,
                    'via' => 'credentials',
                ]);
                $this->audit->record('auth.login', [
                    'actor_type' => $user::class,
                    'actor_id' => (string) $user->getAuthIdentifier(),
                    'remember' => $remember,
                    'via' => 'credentials',
                ], 'auth');
            }

            return true;
        }

        $this->dispatch('auth.failed', [
            'email' => isset($credentials['email']) ? (string) $credentials['email'] : '',
            'via' => 'credentials',
        ]);
        $this->audit->record('auth.failed', [
            'email' => isset($credentials['email']) ? (string) $credentials['email'] : '',
            'via' => 'credentials',
        ], 'auth', 'warning');

        return false;
    }

    public function login(AuthenticatableInterface $user, bool $remember = false): void
    {
        $this->guard->login($user, $remember);
        $this->dispatch('auth.login', [
            'user_id' => $user->getAuthIdentifier(),
            'remember' => $remember,
            'via' => 'manual',
        ]);
        $this->audit->record('auth.login', [
            'actor_type' => $user::class,
            'actor_id' => (string) $user->getAuthIdentifier(),
            'remember' => $remember,
            'via' => 'manual',
        ], 'auth');
    }

    public function syncUser(AuthenticatableInterface $user, ?bool $remembered = null): void
    {
        $this->guard->syncUser($user, $remembered);
    }

    public function logout(): void
    {
        $user = $this->user();
        $identifier = $user instanceof AuthenticatableInterface ? $user->getAuthIdentifier() : null;
        $this->guard->logout();

        if ($identifier !== null) {
            $this->dispatch('auth.logout', [
                'user_id' => $identifier,
            ]);
            $this->audit->record('auth.logout', [
                'actor_id' => (string) $identifier,
            ], 'auth');
        }
    }

    public function viaRemember(): bool
    {
        return $this->guard->viaRemember();
    }

    public function can(string $ability, mixed ...$arguments): bool
    {
        return $this->gate->allows($ability, ...$arguments);
    }

    public function authorize(string $ability, mixed ...$arguments): void
    {
        $this->gate->authorize($ability, ...$arguments);
    }

    public function hasRole(string $role): bool
    {
        return $this->gate->allows('role:' . $role);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->gate->allows($permission);
    }

    /**
     * @return list<string>
     */
    public function availablePermissions(): array
    {
        return $this->permissions->all();
    }

    /**
     * @return list<string>
     */
    public function currentRoles(): array
    {
        $user = $this->user();

        return $user instanceof AuthenticatableInterface
            ? $this->provider->rolesFor($user)
            : [];
    }

    /**
     * @return list<string>
     */
    public function currentPermissions(): array
    {
        $user = $this->user();

        return $user instanceof AuthenticatableInterface
            ? $this->provider->permissionsFor($user)
            : [];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function dispatch(string $event, array $payload): void
    {
        $this->events->dispatch($event, $payload);
    }
}
