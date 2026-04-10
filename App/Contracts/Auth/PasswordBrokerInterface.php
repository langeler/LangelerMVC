<?php

declare(strict_types=1);

namespace App\Contracts\Auth;

interface PasswordBrokerInterface
{
    public function sendResetLink(string $email): bool;

    public function reset(string $email, string $token, string $password): bool;
}
