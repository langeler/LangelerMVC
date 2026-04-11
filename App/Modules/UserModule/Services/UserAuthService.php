<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Services;

use App\Abstracts\Http\Service;
use App\Core\Config;
use App\Exceptions\AuthException;
use App\Modules\UserModule\Models\User;
use App\Modules\UserModule\Repositories\RoleRepository;
use App\Modules\UserModule\Repositories\UserAuthTokenRepository;
use App\Modules\UserModule\Repositories\UserRepository;
use App\Utilities\Managers\Data\CryptoManager;
use App\Utilities\Managers\Security\AuthManager;
use App\Utilities\Managers\Security\DatabaseUserProvider;
use App\Utilities\Managers\Security\PasswordBroker;
use App\Utilities\Managers\Support\MailManager;
use App\Utilities\Managers\Support\OtpManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;
use Throwable;

class UserAuthService extends Service
{
    use ArrayTrait, CheckerTrait, ManipulationTrait, TypeCheckerTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    private string $action = 'showRegisterForm';

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
        private readonly RoleRepository $roles,
        private readonly UserAuthTokenRepository $tokens,
        private readonly DatabaseUserProvider $provider,
        private readonly AuthManager $auth,
        private readonly PasswordBroker $passwordBroker,
        private readonly MailManager $mail,
        private readonly OtpManager $otpManager,
        private readonly CryptoManager $cryptoManager,
        private readonly Config $config,
        private readonly ErrorManager $errorManager
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
            'register' => $this->register(),
            'showLoginForm' => $this->page('UserLogin', 'Sign in', 'Access your account and platform features.'),
            'login' => $this->login(),
            'logout' => $this->logout(),
            'showForgotPasswordForm' => $this->page('UserPasswordForgot', 'Reset password', 'Request a password reset token.'),
            'sendPasswordReset' => $this->sendPasswordReset(),
            'showResetPasswordForm' => $this->showResetPasswordForm(),
            'resetPassword' => $this->resetPassword(),
            'verifyEmail' => $this->verifyEmail(),
            'resendVerification' => $this->resendVerification(),
            'enableOtp' => $this->enableOtp(),
            'verifyOtp' => $this->verifyOtp(),
            'regenerateOtpRecoveryCodes' => $this->regenerateOtpRecoveryCodes(),
            'disableOtp' => $this->disableOtp(),
            default => $this->page('UserRegister', 'Create account', 'Register the first framework-backed user account.'),
        };
    }

    private function register(): array
    {
        if (!$this->passwordsMatch()) {
            return $this->errorPage('UserRegister', 'Registration failed', 'Password confirmation does not match.', 422);
        }

        if ($this->users->findByEmail((string) $this->payload['email']) instanceof User) {
            return $this->errorPage('UserRegister', 'Registration failed', 'An account already exists for that email.', 422);
        }

        $user = $this->users->create([
            'name' => (string) $this->payload['name'],
            'email' => (string) $this->payload['email'],
            'password' => $this->provider->hashValue((string) $this->payload['password']),
            'status' => 'active',
        ]);

        $defaultRole = $this->roles->findByName((string) $this->config->get('auth', 'DEFAULT_ROLE', 'customer'));

        if ($defaultRole !== null) {
            $this->users->syncRoles((int) $user->getKey(), [(int) $defaultRole->getKey()]);
        }

        if ((bool) $this->config->get('auth', 'VERIFY_EMAIL', true)) {
            $this->sendVerificationMail($user);
        }

        $fresh = $this->users->find((int) $user->getKey());

        if ($fresh instanceof User) {
            $this->auth->login($fresh, (bool) ($this->payload['remember'] ?? false));
        }

        return [
            'template' => 'UserStatus',
            'status' => 201,
            'title' => 'Registration complete',
            'headline' => 'Your account is ready.',
            'summary' => 'LangelerMVC created a database-backed user and assigned the default role.',
            'message' => (bool) $this->config->get('auth', 'VERIFY_EMAIL', true)
                ? 'A verification message has been prepared for your email address.'
                : 'You can start using your account immediately.',
            'user' => $fresh instanceof User ? $this->userData($fresh) : null,
            'roles' => $fresh instanceof User ? $this->users->rolesForUser($fresh->getKey()) : [],
            'permissions' => $fresh instanceof User ? $this->users->permissionsForUser($fresh->getKey()) : [],
            'redirect' => '/users/profile',
        ];
    }

    private function login(): array
    {
        $user = $this->provider->retrieveByCredentials([
            'email' => (string) ($this->payload['email'] ?? ''),
        ]);

        if (!$user instanceof User || !$this->provider->validateCredentials($user, $this->payload)) {
            return $this->errorPage('UserLogin', 'Sign in failed', 'The provided credentials are invalid.', 422);
        }

        if ($user->hasOtpEnabled()) {
            $otpCode = isset($this->payload['otp_code']) ? (string) $this->payload['otp_code'] : '';
            $recoveryCode = isset($this->payload['recovery_code']) ? (string) $this->payload['recovery_code'] : '';

            if ($otpCode === '' && $recoveryCode === '') {
                return [
                    'template' => 'UserLogin',
                    'status' => 202,
                    'title' => 'OTP required',
                    'headline' => 'One more verification step is required.',
                    'summary' => 'This account has OTP enabled. Provide the current code or a recovery code to complete sign in.',
                    'message' => 'OTP verification is required for this account.',
                    'requiresOtp' => true,
                ];
            }

            $otpVerified = $otpCode !== '' && $this->verifyUserOtpCode($user, $otpCode);
            $recoveryUsed = !$otpVerified && $recoveryCode !== '' && $this->consumeUserRecoveryCode($user, $recoveryCode);

            if (!$otpVerified && !$recoveryUsed) {
                return $this->errorPage('UserLogin', 'Sign in failed', 'The provided OTP or recovery code is invalid.', 422);
            }
        }

        $this->auth->login($user, (bool) ($this->payload['remember'] ?? false));

        return [
            'template' => 'UserStatus',
            'status' => 200,
            'title' => 'Signed in',
            'headline' => 'Authentication successful.',
            'summary' => 'The session guard has authenticated the current user.',
            'message' => 'You are now signed in.',
            'user' => $this->userData($user),
            'roles' => $this->users->rolesForUser($user->getKey()),
            'permissions' => $this->users->permissionsForUser($user->getKey()),
            'redirect' => '/users/profile',
        ];
    }

    private function logout(): array
    {
        $this->auth->logout();

        return [
            'template' => 'UserStatus',
            'status' => 200,
            'title' => 'Signed out',
            'headline' => 'Your session has ended.',
            'summary' => 'The session guard invalidated the current authentication session.',
            'message' => 'You have been signed out.',
            'redirect' => '/users/login',
        ];
    }

    private function sendPasswordReset(): array
    {
        $sent = $this->passwordBroker->sendResetLink((string) ($this->payload['email'] ?? ''));

        return [
            'template' => 'UserStatus',
            'status' => $sent ? 200 : 404,
            'title' => $sent ? 'Reset link issued' : 'Email not found',
            'headline' => $sent ? 'Password reset prepared.' : 'No account matched the provided email.',
            'summary' => $sent
                ? 'A password reset message has been added to the configured mail transport.'
                : 'Password reset requests only succeed for existing accounts.',
            'message' => $sent
                ? 'Check the configured mail driver outbox or log.'
                : 'No reset message could be created for that email.',
        ];
    }

    private function showResetPasswordForm(): array
    {
        return [
            'template' => 'UserPasswordReset',
            'status' => 200,
            'title' => 'Choose a new password',
            'headline' => 'Reset your password',
            'summary' => 'Submit a new password for the selected account.',
            'form' => [
                'user' => (int) ($this->context['user'] ?? 0),
                'token' => (string) ($this->context['token'] ?? ''),
            ],
        ];
    }

    private function resetPassword(): array
    {
        if (!$this->passwordsMatch()) {
            return $this->errorPage('UserPasswordReset', 'Reset failed', 'Password confirmation does not match.', 422);
        }

        $user = $this->users->find((int) ($this->context['user'] ?? 0));

        if (!$user instanceof User) {
            return $this->errorPage('UserPasswordReset', 'Reset failed', 'The requested account could not be found.', 404);
        }

        $reset = $this->passwordBroker->reset(
            (string) $user->getAttribute('email'),
            (string) ($this->context['token'] ?? ''),
            (string) ($this->payload['password'] ?? '')
        );

        if (!$reset) {
            return $this->errorPage('UserPasswordReset', 'Reset failed', 'The reset token is invalid or expired.', 422);
        }

        return [
            'template' => 'UserStatus',
            'status' => 200,
            'title' => 'Password updated',
            'headline' => 'Your password has been changed.',
            'summary' => 'The password broker verified the reset token and updated the stored hash.',
            'message' => 'You can now sign in with your new password.',
            'redirect' => '/users/login',
        ];
    }

    private function verifyEmail(): array
    {
        $user = $this->users->find((int) ($this->context['user'] ?? 0));

        if (!$user instanceof User) {
            return $this->errorPage('UserStatus', 'Verification failed', 'The requested account could not be found.', 404);
        }

        $record = $this->passwordBroker->consumeToken(
            (int) $user->getKey(),
            'email_verification',
            (string) ($this->context['token'] ?? '')
        );

        if ($record === null) {
            return $this->errorPage('UserStatus', 'Verification failed', 'The verification token is invalid or expired.', 422);
        }

        $this->users->markEmailVerified((int) $user->getKey());
        $fresh = $this->users->find((int) $user->getKey());

        return [
            'template' => 'UserStatus',
            'status' => 200,
            'title' => 'Email verified',
            'headline' => 'Your email address is now verified.',
            'summary' => 'The verification token has been consumed and the user record updated.',
            'message' => 'Email verification is complete.',
            'user' => $fresh instanceof User ? $this->userData($fresh) : null,
        ];
    }

    private function resendVerification(): array
    {
        $user = $this->auth->user();

        if (!$user instanceof User) {
            return $this->errorPage('UserStatus', 'Verification unavailable', 'You must be signed in to request a new verification message.', 401);
        }

        $this->sendVerificationMail($user);

        return [
            'template' => 'UserStatus',
            'status' => 200,
            'title' => 'Verification sent',
            'headline' => 'A new verification message has been prepared.',
            'summary' => 'The framework issued a fresh email verification token.',
            'message' => 'Check the configured mail outbox or log driver.',
            'user' => $this->userData($user),
        ];
    }

    private function enableOtp(): array
    {
        $user = $this->auth->user();

        if (!$user instanceof User) {
            return $this->errorPage('UserStatus', 'OTP unavailable', 'You must be signed in to enable OTP.', 401);
        }

        $issuer = (string) $this->config->get('app', 'NAME', 'LangelerMVC');
        $provision = $this->otpManager->provision(
            (string) $user->getAttribute('email'),
            $issuer
        );
        $recoveryCodes = $this->otpManager->recoveryCodes((int) $this->config->get('auth', 'OTP.RECOVERY_CODES', 8));

        $this->users->saveOtpConfiguration(
            (int) $user->getKey(),
            $this->encryptSecret((string) $provision['secret']),
            $this->encryptSecret(json_encode($recoveryCodes, JSON_THROW_ON_ERROR)),
            null
        );
        $fresh = $this->users->find((int) $user->getKey());

        if ($fresh instanceof User) {
            $this->auth->syncUser($fresh, $this->auth->viaRemember());
        }

        return [
            'template' => 'UserStatus',
            'status' => 200,
            'title' => 'OTP provisioned',
            'headline' => 'Scan the provisioning URI and confirm with a code.',
            'summary' => 'LangelerMVC generated a TOTP secret and stored it encrypted.',
            'message' => 'Use your authenticator app to scan the URI, then verify with a current code.',
            'otp' => [
                'issuer' => $provision['issuer'],
                'label' => $provision['label'],
                'uri' => $provision['uri'],
                'secret' => $provision['secret'],
            ],
            'recoveryCodes' => $recoveryCodes,
            'user' => $this->userData($fresh instanceof User ? $fresh : $user),
        ];
    }

    private function verifyOtp(): array
    {
        $user = $this->auth->user();

        if (!$user instanceof User) {
            return $this->errorPage('UserStatus', 'OTP unavailable', 'You must be signed in to verify OTP.', 401);
        }

        $otpCode = (string) ($this->payload['otp_code'] ?? '');

        if (!$this->verifyUserOtpCode($user, $otpCode)) {
            return $this->errorPage('UserStatus', 'OTP verification failed', 'The provided OTP code is invalid.', 422);
        }

        $this->users->saveOtpConfiguration(
            (int) $user->getKey(),
            (string) $user->getAttribute('otp_secret'),
            (string) $user->getAttribute('otp_recovery_codes'),
            gmdate('Y-m-d H:i:s')
        );

        $fresh = $this->users->find((int) $user->getKey());

        if ($fresh instanceof User) {
            $this->auth->syncUser($fresh, $this->auth->viaRemember());
        }

        return [
            'template' => 'UserStatus',
            'status' => 200,
            'title' => 'OTP enabled',
            'headline' => 'Two-factor authentication is active.',
            'summary' => 'Future sign-ins for this account will require a current OTP code.',
            'message' => 'Store your recovery codes securely.',
            'user' => $fresh instanceof User ? $this->userData($fresh) : null,
            'recoveryCodes' => $this->decodeRecoveryCodes($fresh),
        ];
    }

    private function regenerateOtpRecoveryCodes(): array
    {
        $user = $this->auth->user();

        if (!$user instanceof User || !$user->hasOtpProvisioned()) {
            return $this->errorPage('UserStatus', 'Recovery codes unavailable', 'You must provision OTP before managing recovery codes.', 422);
        }

        $recoveryCodes = $this->otpManager->recoveryCodes((int) $this->config->get('auth', 'OTP.RECOVERY_CODES', 8));
        $this->users->saveOtpConfiguration(
            (int) $user->getKey(),
            (string) $user->getAttribute('otp_secret'),
            $this->encryptSecret(json_encode($recoveryCodes, JSON_THROW_ON_ERROR)),
            (string) $user->getAttribute('otp_confirmed_at')
        );
        $fresh = $this->users->find((int) $user->getKey());

        if ($fresh instanceof User) {
            $this->auth->syncUser($fresh, $this->auth->viaRemember());
        }

        return [
            'template' => 'UserStatus',
            'status' => 200,
            'title' => 'Recovery codes refreshed',
            'headline' => 'A fresh set of recovery codes is ready.',
            'summary' => 'Existing recovery codes were replaced with a newly generated set.',
            'message' => 'Store the new recovery codes securely.',
            'user' => $this->userData($fresh instanceof User ? $fresh : $user),
            'recoveryCodes' => $recoveryCodes,
            'redirect' => '/users/profile',
        ];
    }

    private function disableOtp(): array
    {
        $user = $this->auth->user();

        if (!$user instanceof User || !$user->hasOtpProvisioned()) {
            return $this->errorPage('UserStatus', 'OTP unavailable', 'No OTP configuration is currently active for this account.', 422);
        }

        $this->users->saveOtpConfiguration((int) $user->getKey(), null, null, null);
        $fresh = $this->users->find((int) $user->getKey());

        if ($fresh instanceof User) {
            $this->auth->syncUser($fresh, $this->auth->viaRemember());
        }

        return [
            'template' => 'UserStatus',
            'status' => 200,
            'title' => 'OTP disabled',
            'headline' => 'Two-factor authentication has been turned off.',
            'summary' => 'The stored OTP secret and recovery codes were removed from the account.',
            'message' => 'You can enable OTP again at any time.',
            'user' => $fresh instanceof User ? $this->userData($fresh) : null,
            'redirect' => '/users/profile',
        ];
    }

    private function sendVerificationMail(User $user): void
    {
        $token = $this->passwordBroker->issueToken(
            (int) $user->getKey(),
            'email_verification',
            (int) $this->config->get('auth', 'EMAIL_VERIFY_EXPIRES', 1440)
        );
        $path = sprintf('/users/email/verify/%d/%s', (int) $user->getKey(), $token);
        $address = (string) $user->getAttribute('email');

        $this->mail->send(new class($address, $path) extends \App\Abstracts\Support\Mailable {
            public function __construct(private readonly string $emailAddress, private readonly string $path)
            {
            }

            protected function build(): void
            {
                $this->to($this->emailAddress)
                    ->subject('Verify your LangelerMVC email address')
                    ->text('Verify your email by visiting: ' . $this->path)
                    ->html('<p>Verify your email by visiting:</p><pre>' . htmlspecialchars($this->path, ENT_QUOTES, 'UTF-8') . '</pre>');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function page(string $template, string $title, string $summary): array
    {
        return [
            'template' => $template,
            'status' => 200,
            'title' => $title,
            'headline' => $title,
            'summary' => $summary,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function errorPage(string $template, string $title, string $message, int $status): array
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

    private function passwordsMatch(): bool
    {
        return (string) ($this->payload['password'] ?? '') === (string) ($this->payload['password_confirmation'] ?? '');
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

    private function verifyUserOtpCode(User $user, string $otpCode): bool
    {
        $secretCipher = $user->getAttribute('otp_secret');

        if (!$this->isString($secretCipher) || $secretCipher === '' || $otpCode === '') {
            return false;
        }

        try {
            $secret = $this->decryptSecret($secretCipher);

            return $this->otpManager->verify($secret, $otpCode);
        } catch (Throwable $exception) {
            $this->errorManager->logThrowable($exception, 'auth.otp', 'userNotice');

            return false;
        }
    }

    /**
     * @return list<string>
     */
    private function decodeRecoveryCodes(?User $user): array
    {
        if (!$user instanceof User) {
            return [];
        }

        $cipher = $user->getAttribute('otp_recovery_codes');

        if (!$this->isString($cipher) || $cipher === '') {
            return [];
        }

        try {
            $decoded = json_decode($this->decryptSecret($cipher), true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? array_values(array_map('strval', $decoded)) : [];
        } catch (Throwable $exception) {
            $this->errorManager->logThrowable($exception, 'auth.otp', 'userNotice');

            return [];
        }
    }

    private function consumeUserRecoveryCode(User $user, string $code): bool
    {
        $recoveryCodes = $this->decodeRecoveryCodes($user);

        if ($recoveryCodes === [] || !$this->otpManager->verifyRecoveryCode($recoveryCodes, $code)) {
            return false;
        }

        try {
            $remaining = $this->otpManager->consumeRecoveryCode($recoveryCodes, $code);
            $this->users->saveOtpConfiguration(
                (int) $user->getKey(),
                (string) $user->getAttribute('otp_secret'),
                $this->encryptSecret(json_encode($remaining, JSON_THROW_ON_ERROR)),
                (string) $user->getAttribute('otp_confirmed_at')
            );

            return true;
        } catch (Throwable $exception) {
            $this->errorManager->logThrowable($exception, 'auth.otp', 'userNotice');

            return false;
        }
    }

    private function encryptSecret(string $value): string
    {
        $driver = $this->toLowerString($this->cryptoManager->getDriverName());
        $key = $this->cryptoManager->resolveConfiguredKey($driver);

        if ($driver === 'sodium') {
            $nonceLength = $this->cryptoManager->nonceLength('secretBox');
            $nonce = $this->cryptoManager->generateRandom('custom', $nonceLength);
            $cipher = $this->cryptoManager->encrypt('secretBox', $value, $nonce, $key);

            return base64_encode($nonce . $cipher);
        }

        $cipherMethod = $this->cryptoManager->resolveConfiguredCipher('openssl');
        $iv = $this->cryptoManager->generateRandom('generateRandomIv', $cipherMethod);
        $cipher = $this->cryptoManager->encrypt('symmetric', $value, $cipherMethod, $key, $iv);

        return base64_encode($iv . $cipher);
    }

    private function decryptSecret(string $value): string
    {
        $raw = base64_decode($value, true);

        if ($raw === false) {
            throw new AuthException('Failed to decode encrypted OTP payload.');
        }

        $driver = $this->toLowerString($this->cryptoManager->getDriverName());
        $key = $this->cryptoManager->resolveConfiguredKey($driver);

        if ($driver === 'sodium') {
            $nonceLength = $this->cryptoManager->nonceLength('secretBox');
            $nonce = substr($raw, 0, $nonceLength);
            $cipher = substr($raw, $nonceLength);

            return $this->cryptoManager->decrypt('secretBox', $cipher, $nonce, $key);
        }

        $cipherMethod = $this->cryptoManager->resolveConfiguredCipher('openssl');
        $ivLength = $this->cryptoManager->ivLength($cipherMethod);
        $iv = substr($raw, 0, $ivLength);
        $cipher = substr($raw, $ivLength);

        return $this->cryptoManager->decrypt('symmetric', $cipher, $cipherMethod, $key, $iv);
    }
}
