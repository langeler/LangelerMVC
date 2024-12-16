<?php

namespace App\Abstracts\Data;

use App\Exceptions\Data\ValidationException;
use App\Utilities\Traits\TypeCheckerTrait;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\ExistenceCheckerTrait;
use Throwable;

/**
 * Abstract Class Validator
 *
 * Provides a base implementation for data validation processes.
 * Designed to handle configurable validation methods and rules.
 */
abstract class Validator
{
    use TypeCheckerTrait, ArrayTrait, ExistenceCheckerTrait;

    /**
     * Entry point for the validation process.
     *
     * @param array $data Associative array where keys are data items, and values define validation methods and rules.
     * @return array The validated data.
     * @throws ValidationException If an error occurs during validation.
     */
    protected function handle(array $data): array
    {
        return $this->wrapInTry(
            fn() => $this->map(
                $data,
                fn($config, $key) => $this->processValue($data[$key], $this->normalizeConfig($config))
            ),
            "Validation process failed."
        );
    }

    /**
     * Normalizes configurations with nested "=>" operators into a standard structure.
     *
     * @param mixed $config The raw configuration.
     * @return array Normalized configuration with separate validation methods and rules.
     * @throws ValidationException If the configuration is invalid.
     */
    protected function normalizeConfig(mixed $config): array
    {
        return $this->wrapInTry(
            fn() => match (true) {
                $this->isString($config) || ($this->isArray($config) && $this->arrayKeyExists(0, $config)) =>
                    [$this->isArray($config) ? $config : [$config], []],
                $this->isArray($config) && $this->count($config) === 2 && $this->isArray($config[1]) =>
                    $config,
                $this->isArray($config) && $this->count($config) > 2 =>
                    [$this->filterKeys($config, fn($key) => $this->isString($key)), $this->pop($config)],
                default =>
                    throw new ValidationException("Invalid configuration format.")
            },
            "Failed to normalize configuration."
        );
    }

    /**
     * Processes a single value using defined validation methods and rules.
     *
     * @param mixed $value The value to process.
     * @param array $config Configuration for validation.
     * @return mixed The validated value.
     * @throws ValidationException If a rule or validation method fails.
     */
    protected function processValue(mixed $value, array $config): mixed
    {
        return $this->wrapInTry(
            fn() => $this->applyValidation(
                $this->applyRules($value, $config[1]),
                $config[0]
            ),
            "Failed to process value."
        );
    }

    /**
     * Applies rules to a value.
     *
     * @param mixed $value The value to validate.
     * @param array $rules The validation rules to apply.
     * @return mixed The validated value.
     * @throws ValidationException If validation fails.
     */
    protected function applyRules(mixed $value, array $rules): mixed
    {
        return $this->wrapInTry(
            fn() => $this->reduce(
                $rules,
                fn($carry, $rule, $params) =>
                    $this->methodExists($this, $method = 'rule' . ucfirst($rule)) &&
                    $this->$method($carry, $params)
                        ? $carry
                        : throw new ValidationException("Validation failed for rule '{$rule}' on value '{$carry}'."),
                $value
            ),
            "Failed to apply validation rules."
        );
    }

    /**
     * Applies validation methods to a value.
     *
     * @param mixed $value The value to validate.
     * @param array $methods The validation methods to apply.
     * @return mixed The validated value.
     * @throws ValidationException If a validation method fails.
     */
    protected function applyValidation(mixed $value, array $methods): mixed
    {
        return $this->wrapInTry(
            fn() => $this->reduce(
                $methods,
                fn($carry, $method) =>
                    $this->methodExists($this, $validator = 'validate' . ucfirst($method))
                        ? $this->$validator($carry)
                        : throw new ValidationException("Undefined validation method: '{$method}'."),
                $value
            ),
            "Failed to apply validation methods."
        );
    }

    /**
     * Wraps a callback in a try/catch block and handles exceptions consistently.
     *
     * @param callable $callback The callback to execute.
     * @param string $errorMessage Custom error message for exceptions.
     * @return mixed The result of the callback execution.
     * @throws ValidationException If an exception occurs.
     */
    protected function wrapInTry(callable $callback, string $errorMessage): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            throw new ValidationException("{$errorMessage}: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Abstract method to define default verification logic.
     *
     * @param mixed $data The data to verify.
     * @return mixed The verified data.
     */
    abstract protected function verify(mixed $data): mixed;
}
