<?php

declare(strict_types=1);

namespace App\Contracts\Auth;

interface AuthenticatableInterface
{
    public function getAuthIdentifierName(): string;

    public function getAuthIdentifier(): mixed;

    public function getAuthPassword(): ?string;

    public function getRememberToken(): ?string;

    public function setRememberToken(?string $token): void;

    public function isEmailVerified(): bool;

    public function getEmailForVerification(): ?string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
