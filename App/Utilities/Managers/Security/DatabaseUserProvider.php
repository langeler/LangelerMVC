<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Security;

use App\Contracts\Auth\AuthenticatableInterface;
use App\Contracts\Auth\UserProviderInterface;
use App\Core\Config;
use App\Core\Database;
use App\Exceptions\AuthException;
use App\Modules\UserModule\Repositories\UserRepository;
use App\Utilities\Managers\Data\CryptoManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\HashingTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;
use Throwable;

class DatabaseUserProvider implements UserProviderInterface
{
    use ArrayTrait, CheckerTrait, HashingTrait, ManipulationTrait, TypeCheckerTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    private ?UserRepository $repository = null;

    public function __construct(
        private readonly Config $config,
        private readonly Database $database,
        private readonly CryptoManager $cryptoManager,
        private readonly ErrorManager $errorManager
    ) {
    }

    public function retrieveById(mixed $identifier): ?AuthenticatableInterface
    {
        $user = $this->repository()->find($identifier);

        return $user instanceof AuthenticatableInterface ? $user : null;
    }

    public function retrieveByCredentials(array $credentials): ?AuthenticatableInterface
    {
        $lookup = [];

        foreach ($credentials as $key => $value) {
            $normalizedKey = $this->toLowerString((string) $key);

            if ($this->isInArray($normalizedKey, ['password', 'password_confirmation', 'current_password', 'remember', 'otp_code', 'recovery_code', 'passkey_name', 'credential'], true)) {
                continue;
            }

            if ($this->isScalar($value) && $this->trimString((string) $value) !== '') {
                $lookup[(string) $key] = $value;
            }
        }

        if ($lookup === []) {
            return null;
        }

        if ($this->isString($lookup['email'] ?? null)) {
            return $this->repository()->findByEmail((string) $lookup['email']);
        }

        $user = $this->repository()->findOneBy($lookup);

        return $user instanceof AuthenticatableInterface ? $user : null;
    }

    public function validateCredentials(AuthenticatableInterface $user, array $credentials): bool
    {
        $password = isset($credentials['password']) ? (string) $credentials['password'] : '';
        $hash = $user->getAuthPassword();

        if ($password === '' || !$this->isString($hash) || $hash === '') {
            return false;
        }

        $verified = $this->verifyHash($hash, $password);

        if ($verified && $this->needsRehash($hash)) {
            $newHash = $this->hashValue($password);
            $this->repository()->updatePassword($user->getAuthIdentifier(), $newHash);
        }

        return $verified;
    }

    public function updateRememberToken(AuthenticatableInterface $user, ?string $token): void
    {
        $this->repository()->updateRememberToken($user->getAuthIdentifier(), $token);
        $user->setRememberToken($token);
    }

    public function rolesFor(AuthenticatableInterface $user): array
    {
        return $this->repository()->rolesForUser($user->getAuthIdentifier());
    }

    public function permissionsFor(AuthenticatableInterface $user): array
    {
        return $this->repository()->permissionsForUser($user->getAuthIdentifier());
    }

    public function hashValue(string $value): string
    {
        $algorithm = (string) $this->config->get('auth', 'PASSWORD_HASHER', 'default');

        try {
            $hash = $this->cryptoManager->passwordHash($algorithm, $value);

            if (is_string($hash) && $hash !== '') {
                return $hash;
            }
        } catch (Throwable $exception) {
            $this->errorManager->logThrowable($exception, 'auth.hash', 'userNotice');
        }

        $fallback = $this->passwordHash($value, PASSWORD_DEFAULT);

        if ($fallback === false) {
            throw new AuthException('Failed to hash the provided value for authentication.');
        }

        return $fallback;
    }

    public function verifyHash(string $hash, string $value): bool
    {
        try {
            if ($this->cryptoManager->passwordVerify($hash, $value)) {
                return true;
            }
        } catch (Throwable $exception) {
            $this->errorManager->logThrowable($exception, 'auth.verify', 'userNotice');
        }

        return $this->verifyPassword($value, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        $algorithm = (string) $this->config->get('auth', 'PASSWORD_HASHER', 'default');

        try {
            return $this->cryptoManager->passwordNeedsRehash($hash, $algorithm);
        } catch (Throwable $exception) {
            $this->errorManager->logThrowable($exception, 'auth.rehash', 'userNotice');
        }

        return $this->passwordNeedsRehash($hash, PASSWORD_DEFAULT);
    }

    private function repository(): UserRepository
    {
        if ($this->repository instanceof UserRepository) {
            return $this->repository;
        }

        $class = $this->config->get('auth', 'USER_REPOSITORY', UserRepository::class);

        if (
            !$this->isString($class)
            || !class_exists($class)
            || ($class !== UserRepository::class && !is_subclass_of($class, UserRepository::class))
        ) {
            throw new AuthException('Configured user repository is invalid.');
        }

        $this->repository = new $class($this->database);

        return $this->repository;
    }
}
