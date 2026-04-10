<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Security;

use App\Contracts\Auth\AuthenticatableInterface;
use App\Contracts\Auth\GuardInterface;
use App\Core\Config;
use App\Core\Session;
use App\Exceptions\AuthException;
use App\Utilities\Managers\Data\CryptoManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;
use Throwable;

class SessionGuard implements GuardInterface
{
    use CheckerTrait, ManipulationTrait, TypeCheckerTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    private ?AuthenticatableInterface $resolvedUser = null;
    private bool $resolved = false;
    private bool $remembered = false;

    public function __construct(
        private readonly Session $session,
        private readonly Config $config,
        private readonly DatabaseUserProvider $provider,
        private readonly CryptoManager $cryptoManager,
        private readonly ErrorManager $errorManager
    ) {
    }

    public function check(): bool
    {
        return $this->user() instanceof AuthenticatableInterface;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function user(): ?AuthenticatableInterface
    {
        if ($this->resolved) {
            return $this->resolvedUser;
        }

        $this->session->start();

        $userId = $this->session->get($this->sessionKey());

        if ($userId !== null) {
            $this->resolvedUser = $this->provider->retrieveById($userId);
            $this->remembered = (bool) $this->session->get($this->viaRememberKey(), false);
            $this->resolved = true;

            return $this->resolvedUser;
        }

        $remembered = $this->resolveRememberedUser();

        if ($remembered instanceof AuthenticatableInterface) {
            $this->resolvedUser = $remembered;
            $this->resolved = true;
            $this->remembered = true;
            $this->session->put($this->sessionKey(), $remembered->getAuthIdentifier());
            $this->session->put($this->viaRememberKey(), true);

            return $this->resolvedUser;
        }

        $this->resolved = true;

        return null;
    }

    public function id(): mixed
    {
        return $this->user()?->getAuthIdentifier();
    }

    public function attempt(array $credentials, bool $remember = false): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if (!$user instanceof AuthenticatableInterface) {
            return false;
        }

        if (!$this->provider->validateCredentials($user, $credentials)) {
            return false;
        }

        $this->login($user, $remember);

        return true;
    }

    public function login(AuthenticatableInterface $user, bool $remember = false): void
    {
        $this->session->start();
        $this->session->regenerate(true);
        $this->session->put($this->sessionKey(), $user->getAuthIdentifier());
        $this->session->put($this->viaRememberKey(), false);
        $this->resolvedUser = $user;
        $this->resolved = true;
        $this->remembered = false;

        if ($remember) {
            $this->rememberUser($user);
            $this->session->put($this->viaRememberKey(), true);
            $this->remembered = true;
            return;
        }

        $this->forgetRememberedUser($user);
    }

    public function logout(): void
    {
        $user = $this->user();

        if ($user instanceof AuthenticatableInterface) {
            $this->forgetRememberedUser($user);
        }

        $this->session->invalidate();
        $this->resolvedUser = null;
        $this->resolved = true;
        $this->remembered = false;
    }

    public function viaRemember(): bool
    {
        $this->user();

        return $this->remembered;
    }

    public function syncUser(AuthenticatableInterface $user, ?bool $remembered = null): void
    {
        $this->resolvedUser = $user;
        $this->resolved = true;

        if ($remembered !== null) {
            $this->remembered = $remembered;
            $this->session->put($this->viaRememberKey(), $remembered);
        }

        $this->session->put($this->sessionKey(), $user->getAuthIdentifier());
    }

    public function sessionKey(): string
    {
        return (string) $this->config->get('auth', 'SESSION_KEY', 'auth.user_id');
    }

    public function viaRememberKey(): string
    {
        return (string) $this->config->get('auth', 'VIA_REMEMBER_KEY', 'auth.via_remember');
    }

    private function rememberUser(AuthenticatableInterface $user): void
    {
        $token = bin2hex($this->cryptoManager->generateRandom('default', 32));
        $hash = $this->provider->hashValue($token);
        $this->provider->updateRememberToken($user, $hash);
        $this->queueRememberCookie($user->getAuthIdentifier(), $token);
    }

    private function forgetRememberedUser(AuthenticatableInterface $user): void
    {
        $this->provider->updateRememberToken($user, null);
        $this->expireRememberCookie();
    }

    private function queueRememberCookie(mixed $userId, string $token): void
    {
        $name = $this->rememberCookieName();
        $value = (string) $userId . '|' . $token;
        $expire = time() + ($this->rememberDurationDays() * 86400);

        $_COOKIE[$name] = $value;

        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg' || headers_sent()) {
            return;
        }

        setcookie($name, $value, [
            'expires' => $expire,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => $this->isHttps(),
        ]);
    }

    private function expireRememberCookie(): void
    {
        $name = $this->rememberCookieName();
        unset($_COOKIE[$name]);

        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg' || headers_sent()) {
            return;
        }

        setcookie($name, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => $this->isHttps(),
        ]);
    }

    private function resolveRememberedUser(): ?AuthenticatableInterface
    {
        $payload = $_COOKIE[$this->rememberCookieName()] ?? null;

        if (!$this->isString($payload) || $payload === '') {
            return null;
        }

        [$id, $token] = array_pad(explode('|', $payload, 2), 2, null);

        if (!$this->isString($id) || $id === '' || !$this->isString($token) || $token === '') {
            return null;
        }

        $user = $this->provider->retrieveById($this->isNumeric($id) ? (int) $id : $id);

        if (!$user instanceof AuthenticatableInterface) {
            return null;
        }

        $hash = $user->getRememberToken();

        if (!$this->isString($hash) || $hash === '') {
            return null;
        }

        try {
            return $this->provider->verifyHash($hash, $token) ? $user : null;
        } catch (Throwable $exception) {
            $this->errorManager->logThrowable($exception, 'auth.remember', 'userNotice');
            return null;
        }
    }

    private function rememberCookieName(): string
    {
        return (string) $this->config->get('auth', 'REMEMBER_COOKIE', 'langelermvc_remember');
    }

    private function rememberDurationDays(): int
    {
        return max(1, (int) $this->config->get('auth', 'REMEMBER_ME_DAYS', 30));
    }

    private function isHttps(): bool
    {
        $https = $_SERVER['HTTPS'] ?? null;

        return $https !== null && $this->toLowerString((string) $https) !== 'off';
    }
}
