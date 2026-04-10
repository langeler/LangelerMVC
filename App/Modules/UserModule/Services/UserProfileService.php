<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Services;

use App\Abstracts\Http\Service;
use App\Core\Config;
use App\Modules\UserModule\Models\User;
use App\Modules\UserModule\Repositories\UserPasskeyRepository;
use App\Modules\UserModule\Repositories\UserRepository;
use App\Utilities\Managers\Security\AuthManager;
use App\Utilities\Managers\Security\DatabaseUserProvider;
use App\Utilities\Managers\Support\PasskeyManager;

class UserProfileService extends Service
{
    private string $action = 'showProfile';

    /**
     * @var array<string, mixed>
     */
    private array $payload = [];

    public function __construct(
        private readonly UserRepository $users,
        private readonly UserPasskeyRepository $passkeys,
        private readonly DatabaseUserProvider $provider,
        private readonly AuthManager $auth,
        private readonly Config $config,
        private readonly PasskeyManager $passkeyManager
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function forAction(string $action, array $payload = []): static
    {
        $this->action = $action;
        $this->payload = $payload;

        return $this;
    }

    protected function handle(): array
    {
        return match ($this->action) {
            'updateProfile' => $this->updateProfile(),
            'changePassword' => $this->changePassword(),
            default => $this->showProfile(),
        };
    }

    private function showProfile(): array
    {
        $user = $this->currentUser();

        return [
            'template' => 'UserProfile',
            'status' => 200,
            'title' => 'Your profile',
            'headline' => 'Manage your identity data',
            'summary' => 'Profile, password, role, and permission information from the framework auth subsystem.',
            'user' => $this->userData($user),
            'roles' => $this->users->rolesForUser($user->getKey()),
            'permissions' => $this->users->permissionsForUser($user->getKey()),
            'passkeys' => $this->passkeys->allForUserData((int) $user->getKey()),
            'passkeySupport' => [
                'driver' => $this->passkeyManager->driverName(),
                'available' => $this->passkeyManager->supports('flows.registration'),
            ],
        ];
    }

    private function updateProfile(): array
    {
        $user = $this->currentUser();
        $email = (string) ($this->payload['email'] ?? '');
        $existing = $this->users->findByEmail($email);

        if ($existing instanceof User && (int) $existing->getKey() !== (int) $user->getKey()) {
            return $this->error('UserProfile', 'Update failed', 'That email address is already in use.', 422);
        }

        $emailChanged = $email !== (string) $user->getAttribute('email');

        $updated = $this->users->updateProfile((int) $user->getKey(), [
            'name' => (string) ($this->payload['name'] ?? $user->getAttribute('name')),
            'email' => $email,
            'email_verified_at' => $emailChanged ? null : $user->getAttribute('email_verified_at'),
        ]);

        if ($emailChanged && (bool) $this->config->get('auth', 'VERIFY_EMAIL', true)) {
            $updated->markAsExisting();
        }

        return [
            'template' => 'UserProfile',
            'status' => 200,
            'title' => 'Profile updated',
            'headline' => 'Your profile changes have been saved.',
            'summary' => $emailChanged
                ? 'The email address changed, so verification is required again.'
                : 'The profile update completed successfully.',
            'message' => $emailChanged
                ? 'Verify the new email address to fully reactivate email-verified features.'
                : 'Profile data is up to date.',
            'user' => $this->userData($updated),
            'roles' => $this->users->rolesForUser($updated->getKey()),
            'permissions' => $this->users->permissionsForUser($updated->getKey()),
            'passkeys' => $this->passkeys->allForUserData((int) $updated->getKey()),
            'passkeySupport' => [
                'driver' => $this->passkeyManager->driverName(),
                'available' => $this->passkeyManager->supports('flows.registration'),
            ],
            'redirect' => '/users/profile',
        ];
    }

    private function changePassword(): array
    {
        $user = $this->currentUser();

        if (!$this->provider->validateCredentials($user, ['password' => (string) ($this->payload['current_password'] ?? '')])) {
            return $this->error('UserProfile', 'Password change failed', 'The current password is incorrect.', 422);
        }

        if ((string) ($this->payload['password'] ?? '') !== (string) ($this->payload['password_confirmation'] ?? '')) {
            return $this->error('UserProfile', 'Password change failed', 'Password confirmation does not match.', 422);
        }

        $this->users->updatePassword((int) $user->getKey(), $this->provider->hashValue((string) $this->payload['password']));
        $fresh = $this->users->find((int) $user->getKey());

        return [
            'template' => 'UserProfile',
            'status' => 200,
            'title' => 'Password updated',
            'headline' => 'Your password has been changed.',
            'summary' => 'The framework stored a fresh password hash for this account.',
            'message' => 'Use the new password on your next sign-in.',
            'user' => $fresh instanceof User ? $this->userData($fresh) : $this->userData($user),
            'roles' => $this->users->rolesForUser($user->getKey()),
            'permissions' => $this->users->permissionsForUser($user->getKey()),
            'passkeys' => $this->passkeys->allForUserData((int) $user->getKey()),
            'passkeySupport' => [
                'driver' => $this->passkeyManager->driverName(),
                'available' => $this->passkeyManager->supports('flows.registration'),
            ],
            'redirect' => '/users/profile',
        ];
    }

    private function currentUser(): User
    {
        $user = $this->auth->user();

        if (!$user instanceof User) {
            throw new \RuntimeException('Authenticated user is required.');
        }

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function userData(User $user): array
    {
        return [
            'id' => (int) $user->getKey(),
            'name' => (string) $user->getAttribute('name'),
            'email' => (string) $user->getAttribute('email'),
            'status' => (string) ($user->getAttribute('status') ?? 'active'),
            'emailVerified' => $user->isEmailVerified(),
            'otpEnabled' => $user->hasOtpEnabled(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function error(string $template, string $title, string $message, int $status): array
    {
        return [
            'template' => $template,
            'status' => $status,
            'title' => $title,
            'headline' => $title,
            'summary' => $message,
            'message' => $message,
        ];
    }
}
