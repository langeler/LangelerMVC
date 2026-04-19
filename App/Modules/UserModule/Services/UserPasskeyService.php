<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Services;

use App\Abstracts\Http\Service;
use App\Contracts\Support\AuditLoggerInterface;
use App\Exceptions\AuthException;
use App\Modules\UserModule\Models\User;
use App\Modules\UserModule\Repositories\UserPasskeyRepository;
use App\Modules\UserModule\Repositories\UserRepository;
use App\Utilities\Managers\Security\AuthManager;
use App\Utilities\Managers\Support\PasskeyManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;
use Throwable;

class UserPasskeyService extends Service
{
    use ArrayTrait, CheckerTrait, ManipulationTrait, TypeCheckerTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    private string $action = 'beginAuthentication';

    /**
     * @var array<string, mixed>
     */
    private array $payload = [];

    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    public function __construct(
        private readonly UserRepository $users,
        private readonly UserPasskeyRepository $passkeys,
        private readonly AuthManager $auth,
        private readonly PasskeyManager $passkeyManager,
        private readonly ErrorManager $errorManager,
        private readonly AuditLoggerInterface $audit
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     */
    public function forAction(string $action, array $payload = [], array $context = []): static
    {
        $this->action = $action;
        $this->payload = $payload;
        $this->context = $context;

        return $this;
    }

    protected function handle(): array
    {
        return match ($this->action) {
            'beginRegistration' => $this->beginRegistration(),
            'finishRegistration' => $this->finishRegistration(),
            'beginAuthentication' => $this->beginAuthentication(),
            'finishAuthentication' => $this->finishAuthentication(),
            'deletePasskey' => $this->deletePasskey(),
            default => $this->error('UserStatus', 'Passkey flow unavailable', 'The requested passkey action is not supported.', 404),
        };
    }

    private function beginRegistration(): array
    {
        $user = $this->currentUser();
        $name = $this->resolvePasskeyName((int) $user->getKey());
        $options = $this->passkeyManager->beginRegistration(
            (int) $user->getKey(),
            (string) $user->getAttribute('email'),
            (string) $user->getAttribute('name'),
            $this->passkeys->descriptorsForUser((int) $user->getKey()),
            ['name' => $name]
        );

        return [
            'template' => 'UserStatus',
            'status' => 200,
            'title' => 'Passkey registration ready',
            'headline' => 'Create a passkey for this account.',
            'summary' => 'The framework generated a WebAuthn registration challenge for the current account.',
            'message' => 'Use your browser or device to complete passkey registration.',
            'passkey' => [
                'name' => $name,
                'flow' => $options['flow'],
                'options' => $options['options'],
                'expiresAt' => $options['expiresAt'],
                'driver' => $options['driver'],
            ],
        ];
    }

    private function finishRegistration(): array
    {
        $user = $this->currentUser();

        try {
            $result = $this->passkeyManager->finishRegistration($this->payload['credential'] ?? $this->payload);
        } catch (Throwable $exception) {
            $this->errorManager->logThrowable($exception, 'auth.passkeys.register', 'userNotice');

            return $this->error('UserStatus', 'Passkey registration failed', 'The passkey response could not be verified.', 422);
        }

        $contextUserId = (int) ($result['context']['user_id'] ?? 0);

        if ($contextUserId !== (int) $user->getKey()) {
            return $this->error('UserStatus', 'Passkey registration failed', 'The passkey challenge no longer matches the current account.', 409);
        }

        $name = (string) ($result['context']['name'] ?? $this->resolvePasskeyName((int) $user->getKey()));
        $credential = (array) ($result['credential'] ?? []);
        $source = is_array($credential['source'] ?? null) ? $credential['source'] : [];
        $stored = $this->passkeys->storeCredential((int) $user->getKey(), $name, $source);
        $this->audit->record('auth.passkey.registered', [
            'actor_type' => $user::class,
            'actor_id' => (string) $user->getKey(),
            'passkey_id' => (string) $stored->getKey(),
            'name' => $name,
        ], 'auth');

        return [
            'template' => 'UserStatus',
            'status' => 201,
            'title' => 'Passkey registered',
            'headline' => 'The passkey is now linked to your account.',
            'summary' => 'LangelerMVC verified the passkey attestation and persisted the credential source.',
            'message' => 'You can now sign in with this passkey.',
            'user' => $this->userData($user),
            'passkeys' => $this->passkeys->allForUserData((int) $user->getKey()),
            'passkey' => [
                'id' => (int) $stored->getKey(),
                'name' => (string) ($stored->getAttribute('name') ?? $name),
            ],
            'redirect' => '/users/profile',
        ];
    }

    private function beginAuthentication(): array
    {
        $email = isset($this->payload['email']) && $this->isString($this->payload['email'])
            ? $this->toLowerString($this->trimString((string) $this->payload['email']))
            : '';
        $remember = (bool) ($this->payload['remember'] ?? false);
        $allowed = [];

        if ($email !== '') {
            $user = $this->users->findByEmail($email);

            if ($user instanceof User) {
                $allowed = $this->passkeys->descriptorsForUser((int) $user->getKey());
            }
        }

        $options = $this->passkeyManager->beginAuthentication($allowed, [
            'email' => $email,
            'remember' => $remember,
        ]);

        return [
            'template' => 'UserLogin',
            'status' => 200,
            'title' => 'Passkey authentication ready',
            'headline' => 'Use your passkey to sign in.',
            'summary' => 'The framework generated a WebAuthn authentication challenge.',
            'message' => 'Your browser may now prompt for a saved passkey.',
            'passkey' => [
                'flow' => $options['flow'],
                'options' => $options['options'],
                'expiresAt' => $options['expiresAt'],
                'driver' => $options['driver'],
            ],
        ];
    }

    private function finishAuthentication(): array
    {
        $credentialPayload = $this->payload['credential'] ?? $this->payload;
        $credentialId = $this->passkeyManager->extractCredentialId($credentialPayload);
        $record = $this->passkeys->findByCredentialId($credentialId);

        if (!$record instanceof \App\Modules\UserModule\Models\UserPasskey) {
            return $this->error('UserLogin', 'Passkey sign in failed', 'The submitted passkey is not registered for this platform.', 422);
        }

        try {
            $result = $this->passkeyManager->finishAuthentication($credentialPayload, [
                'source' => $record->sourceData(),
            ]);
        } catch (Throwable $exception) {
            $this->errorManager->logThrowable($exception, 'auth.passkeys.authenticate', 'userNotice');

            return $this->error('UserLogin', 'Passkey sign in failed', 'The passkey assertion could not be verified.', 422);
        }

        $user = $this->users->find((int) ($record->getAttribute('user_id') ?? 0));

        if (!$user instanceof User) {
            return $this->error('UserLogin', 'Passkey sign in failed', 'The user linked to this passkey could not be found.', 404);
        }

        $requestedEmail = isset($result['context']['email']) && $this->isString($result['context']['email'])
            ? $this->toLowerString((string) $result['context']['email'])
            : '';

        if ($requestedEmail !== '' && $requestedEmail !== $this->toLowerString((string) $user->getAttribute('email'))) {
            return $this->error('UserLogin', 'Passkey sign in failed', 'The passkey does not belong to the requested account.', 422);
        }

        $credential = (array) ($result['credential'] ?? []);
        $source = is_array($credential['source'] ?? null) ? $credential['source'] : [];
        $this->passkeys->refreshAssertion((int) $record->getKey(), $source);
        $this->auth->login($user, (bool) ($result['context']['remember'] ?? false));
        $this->audit->record('auth.passkey.authenticated', [
            'actor_type' => $user::class,
            'actor_id' => (string) $user->getKey(),
            'passkey_id' => (string) $record->getKey(),
        ], 'auth');

        return [
            'template' => 'UserStatus',
            'status' => 200,
            'title' => 'Signed in',
            'headline' => 'Passkey authentication successful.',
            'summary' => 'The passkey assertion was verified and the session guard authenticated the user.',
            'message' => 'You are now signed in with a passkey.',
            'user' => $this->userData($user),
            'roles' => $this->users->rolesForUser((int) $user->getKey()),
            'permissions' => $this->users->permissionsForUser((int) $user->getKey()),
            'redirect' => '/users/profile',
        ];
    }

    private function deletePasskey(): array
    {
        $user = $this->currentUser();
        $passkeyId = (int) ($this->context['passkey'] ?? 0);
        $deleted = $passkeyId > 0 && $this->passkeys->deleteForUser((int) $user->getKey(), $passkeyId);

        if ($deleted) {
            $this->audit->record('auth.passkey.deleted', [
                'actor_type' => $user::class,
                'actor_id' => (string) $user->getKey(),
                'passkey_id' => (string) $passkeyId,
            ], 'auth');
        }

        return [
            'template' => 'UserProfile',
            'status' => $deleted ? 200 : 404,
            'title' => $deleted ? 'Passkey removed' : 'Passkey not found',
            'headline' => $deleted ? 'The passkey has been removed.' : 'No passkey matched the request.',
            'summary' => $deleted
                ? 'The passkey is no longer linked to your account.'
                : 'Only passkeys linked to the current account can be removed.',
            'message' => $deleted
                ? 'You can register another passkey at any time.'
                : 'No passkey was removed.',
            'user' => $this->userData($user),
            'roles' => $this->users->rolesForUser((int) $user->getKey()),
            'permissions' => $this->users->permissionsForUser((int) $user->getKey()),
            'passkeys' => $this->passkeys->allForUserData((int) $user->getKey()),
            'redirect' => '/users/profile',
        ];
    }

    private function currentUser(): User
    {
        $user = $this->auth->user();

        if (!$user instanceof User) {
            throw new AuthException('Authenticated user is required for this passkey action.');
        }

        return $user;
    }

    private function resolvePasskeyName(int $userId): string
    {
        $name = isset($this->payload['passkey_name']) && $this->isString($this->payload['passkey_name'])
            ? $this->trimString((string) $this->payload['passkey_name'])
            : '';

        if ($name !== '') {
            return $name;
        }

        return 'Passkey ' . ($this->passkeys->count(['user_id' => $userId]) + 1);
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
