<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Security;

use App\Contracts\Auth\AuthenticatableInterface;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ManipulationTrait;

class PolicyResolver
{
    use ArrayTrait, CheckerTrait, ManipulationTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    /**
     * @var array<string, callable>
     */
    private array $abilities = [];

    /**
     * @var array<string, object|string>
     */
    private array $policies = [];

    public function define(string $ability, callable $callback): void
    {
        $this->abilities[$this->normalize($ability)] = $callback;
    }

    public function registerPolicy(string $class, object|string $policy): void
    {
        $this->policies[$class] = $policy;
    }

    public function resolve(string $ability, mixed $subject = null): ?callable
    {
        $normalized = $this->normalize($ability);

        if (isset($this->abilities[$normalized])) {
            return $this->abilities[$normalized];
        }

        $class = match (true) {
            is_object($subject) => $subject::class,
            is_string($subject) && $subject !== '' => $subject,
            default => null,
        };

        if ($class === null || !isset($this->policies[$class])) {
            return null;
        }

        $policy = $this->policies[$class];
        $method = $this->abilityMethod($normalized);

        if (is_object($policy) && method_exists($policy, $method)) {
            return fn(AuthenticatableInterface $user, mixed ...$arguments): bool => (bool) $policy->{$method}($user, ...$arguments);
        }

        if (is_string($policy) && class_exists($policy)) {
            $instance = new $policy();

            if (method_exists($instance, $method)) {
                return fn(AuthenticatableInterface $user, mixed ...$arguments): bool => (bool) $instance->{$method}($user, ...$arguments);
            }
        }

        return null;
    }

    private function normalize(string $ability): string
    {
        return $this->toLowerString($this->trimString($ability));
    }

    private function abilityMethod(string $ability): string
    {
        $segments = preg_split('/[^a-z0-9]+/i', $ability) ?: [];
        $segments = array_values(array_filter($segments, static fn(string $segment): bool => $segment !== ''));

        if ($segments === []) {
            return 'handle';
        }

        $method = array_shift($segments);

        foreach ($segments as $segment) {
            $method .= ucfirst($segment);
        }

        return $method;
    }
}
