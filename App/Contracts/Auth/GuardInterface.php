<?php

declare(strict_types=1);

namespace App\Contracts\Auth;

interface GuardInterface
{
    public function check(): bool;

    public function guest(): bool;

    public function user(): ?AuthenticatableInterface;

    public function id(): mixed;

    /**
     * @param array<string, mixed> $credentials
     */
    public function attempt(array $credentials, bool $remember = false): bool;

    public function login(AuthenticatableInterface $user, bool $remember = false): void;

    public function logout(): void;

    public function viaRemember(): bool;
}
