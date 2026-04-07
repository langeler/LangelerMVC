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
                if (
                    $this->propertyExists($this, 'errorManager')
                    && $this->isObject($this->errorManager)
                    && $this->methodExists($this->errorManager, 'resolveException')
                ) {
                    throw $this->errorManager->resolveException(
                        $wrapException,
                        $caught->getMessage(),
                        $caught->getCode(),
                        $caught
                    );
                }

                if (
                    $this->classExists($wrapException)
                    && is_subclass_of($wrapException, Throwable::class, true)
                ) {
                    throw new $wrapException($caught->getMessage(), $caught->getCode(), $caught);
                }
            }

            throw new UnexpectedValueException('Invalid wrapException type or unresolvable exception.', 0, $caught);
        }
    }
}
