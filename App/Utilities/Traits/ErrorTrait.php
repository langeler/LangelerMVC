<?php

namespace App\Utilities\Traits;

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
    public function wrapInTry(callable $callback, string|Throwable|callable|object|null $wrapException = null): mixed
    {
        return $this->isCallable($callback)
            ? (try {
                return $callback();
            } catch (Throwable $caught) {
                throw $this->isNull($wrapException) ? $caught
                    : ($this->isObject($wrapException) && $this->isSubclassOf($wrapException, Throwable::class) ? $wrapException
                        : ($this->isCallable($wrapException) ? $wrapException($caught)
                            : ($this->isObject($wrapException) && $this->methodExists($wrapException, 'resolveException') ? $wrapException->resolveException($caught)
                                : ($this->isString($wrapException) && $this->classExists($wrapException) && $this->isSubclassOf($wrapException, Throwable::class)
                                    ? new $wrapException($caught->getMessage(), $caught->getCode(), $caught)
                                    : throw new UnexpectedValueException('Invalid wrapException type or unresolvable exception.')))));
            })
            : throw new UnexpectedValueException('Provided callback is not callable.');
    }
}
