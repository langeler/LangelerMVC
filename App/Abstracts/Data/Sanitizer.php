<?php

namespace App\Abstracts\Data;

use App\Exceptions\Data\SanitationException;
use App\Utilities\Traits\TypeCheckerTrait;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\ExistenceCheckerTrait;
use Throwable;

/**
 * Abstract Class Sanitizer
 *
 * Provides a base implementation for data sanitization and validation processes.
 * Designed to handle configurable sanitization methods and validation rules.
 */
abstract class Sanitizer
{
    use TypeCheckerTrait, ArrayTrait, ExistenceCheckerTrait;

    /**
     * Entry point for the sanitization process.
     *
     * @param array $data Associative array where keys are data items, and values define sanitation methods and rules.
     * @return array The sanitized and validated data.
     * @throws SanitationException If an error occurs during sanitization.
     */
    protected function handle(array $data): array
    {
        return $this->wrapInTry(
            fn() => $this->map($data, fn($config, $key) => $this->processValue($data[$key], $this->normalizeConfig($config))),
            "Sanitization process failed."
        );
    }

    /**
     * Normalizes configurations with nested "=>" operators into a standard structure.
     *
     * @param mixed $config The raw configuration.
     * @return array Normalized configuration with separate sanitization methods and rules.
     * @throws SanitationException If the configuration is invalid.
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
                    throw new SanitationException("Invalid configuration format.")
            },
            "Failed to normalize configuration."
        );
    }

    /**
     * Processes a single value using defined sanitation methods and rules.
     *
     * @param mixed $value The value to process.
     * @param array $config Configuration for sanitation and validation.
     * @return mixed The sanitized and validated value.
     * @throws SanitationException If a rule or sanitation method fails.
     */
    protected function processValue(mixed $value, array $config): mixed
    {
        return $this->wrapInTry(
            fn() => $this->applySanitation(
                $this->applyRules($value, $config[1]),
                $config[0]
            ),
            "Failed to process value."
        );
    }

    /**
     * Applies validation rules to a value.
     *
     * @param mixed $value The value to validate.
     * @param array $rules The validation rules to apply.
     * @return mixed The validated value.
     * @throws SanitationException If validation fails.
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
                        : throw new SanitationException("Validation failed for rule '{$rule}' on value '{$carry}'."),
                $value
            ),
            "Failed to apply validation rules."
        );
    }

    /**
     * Applies sanitation methods to a value.
     *
     * @param mixed $value The value to sanitize.
     * @param array $methods The sanitation methods to apply.
     * @return mixed The sanitized value.
     * @throws SanitationException If a sanitation method fails.
     */
    protected function applySanitation(mixed $value, array $methods): mixed
    {
        return $this->wrapInTry(
            fn() => $this->reduce(
                $methods,
                fn($carry, $method) =>
                    $this->methodExists($this, $sanitizer = 'sanitize' . ucfirst($method))
                        ? $this->$sanitizer($carry)
                        : throw new SanitationException("Undefined sanitization method: '{$method}'."),
                $value
            ),
            "Failed to apply sanitation methods."
        );
    }

    /**
     * Wraps a callback in a try/catch block and handles exceptions consistently.
     *
     * @param callable $callback The callback to execute.
     * @param string $errorMessage Custom error message for exceptions.
     * @return mixed The result of the callback execution.
     * @throws SanitationException If an exception occurs.
     */
    protected function wrapInTry(callable $callback, string $errorMessage): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            throw new SanitationException("{$errorMessage}: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Abstract method to define default cleaning logic.
     *
     * @param mixed $data The data to clean.
     * @return mixed The cleaned data.
     */
    abstract protected function clean(mixed $data): mixed;
}
