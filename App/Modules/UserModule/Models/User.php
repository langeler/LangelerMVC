<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Models;

use App\Abstracts\Database\Model;
use App\Contracts\Auth\AuthenticatableInterface;
use App\Contracts\Support\NotifiableInterface;

class User extends Model implements AuthenticatableInterface, NotifiableInterface
{
    protected string $table = 'users';

    protected array $fillable = [
        'name',
        'email',
        'password',
        'remember_token',
        'email_verified_at',
        'otp_secret',
        'otp_recovery_codes',
        'otp_confirmed_at',
        'status',
        'created_at',
        'updated_at',
    ];

    public function getAuthIdentifierName(): string
    {
        return $this->getPrimaryKey();
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getAuthPassword(): ?string
    {
        $value = $this->getAttribute('password');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function getRememberToken(): ?string
    {
        $value = $this->getAttribute('remember_token');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function setRememberToken(?string $token): void
    {
        $this->setAttribute('remember_token', $token);
    }

    public function isEmailVerified(): bool
    {
        $value = $this->getAttribute('email_verified_at');

        return is_string($value) && $value !== '';
    }

    public function getEmailForVerification(): ?string
    {
        $value = $this->getAttribute('email');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function hasOtpEnabled(): bool
    {
        $secret = $this->getAttribute('otp_secret');
        $confirmed = $this->getAttribute('otp_confirmed_at');

        return is_string($secret) && $secret !== '' && is_string($confirmed) && $confirmed !== '';
    }

    public function hasOtpProvisioned(): bool
    {
        $secret = $this->getAttribute('otp_secret');

        return is_string($secret) && $secret !== '';
    }

    public function notificationType(): string
    {
        return static::class;
    }

    public function notificationIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function routeNotificationFor(string $channel): mixed
    {
        return match ($channel) {
            'mail' => $this->getEmailForVerification(),
            'database' => $this->getKey(),
            default => null,
        };
    }
}
