<?php

declare(strict_types=1);

namespace App\Utilities\Traits;

use ReflectionObject;
use Throwable;
use UnexpectedValueException;

trait ErrorTrait
{
    use ExistenceCheckerTrait, TypeCheckerTrait;

    /**
     * Wraps a callable in a try-catch. Transforms exceptions based on the provided alias, callable, or object.
     *
     * @param callable $callback The callback to execute.
     * @param string|Throwable|callable|object|null $wrapException The alias, FQCN, callable, or object for transforming exceptions.
     * @return mixed The result of the callback if successful.
     * @throws Throwable The original or transformed exception if the callback fails.
     */
    public function wrapInTry(callable $callback, string|Throwable|callable|null $wrapException = null): mixed
    {
        if (!$this->isCallable($callback)) {
            throw new UnexpectedValueException('Provided callback is not callable.');
        }

        try {
            return $callback();
        } catch (Throwable $caught) {
            if ($this->isNull($wrapException)) {
                throw $caught;
            }

            if ($this->isObject($wrapException) && $wrapException instanceof Throwable) {
                throw $wrapException;
            }

            if ($this->isCallable($wrapException)) {
                throw $wrapException($caught);
            }

            if ($this->isObject($wrapException) && $this->methodExists($wrapException, 'resolveException')) {
                throw $wrapException->resolveException($caught);
            }

            if ($this->isString($wrapException)) {
                $resolvedErrorManager = $this->resolveErrorManagerInstance();

                if (
                    $resolvedErrorManager !== null
                    && $this->isObject($resolvedErrorManager)
                    && $this->methodExists($resolvedErrorManager, 'resolveException')
                ) {
                    $code = is_int($caught->getCode())
                        ? $caught->getCode()
                        : (is_numeric($caught->getCode()) ? (int) $caught->getCode() : 0);

                    throw $resolvedErrorManager->resolveException(
                        $wrapException,
                        $caught->getMessage(),
                        $code,
                        $caught
                    );
                }

                if (
                    $this->classExists($wrapException)
                    && is_subclass_of($wrapException, Throwable::class, true)
                ) {
                    $code = is_int($caught->getCode())
                        ? $caught->getCode()
                        : (is_numeric($caught->getCode()) ? (int) $caught->getCode() : 0);

                    throw new $wrapException($caught->getMessage(), $code, $caught);
                }
            }

            throw new UnexpectedValueException('Invalid wrapException type or unresolvable exception.', 0, $caught);
        }
    }

    private function resolveErrorManagerInstance(): ?object
    {
        if (!$this->propertyExists($this, 'errorManager')) {
            return null;
        }

        try {
            $reflection = new ReflectionObject($this);

            while ($reflection !== false) {
                if ($reflection->hasProperty('errorManager')) {
                    $property = $reflection->getProperty('errorManager');
                    $property->setAccessible(true);
                    $value = $property->getValue($this);

                    return $this->isObject($value) ? $value : null;
                }

                $reflection = $reflection->getParentClass() ?: false;
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }
}
