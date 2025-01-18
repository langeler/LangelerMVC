<?php

declare(strict_types=1);

namespace App\Abstracts\Data;

use Throwable; // Base interface for exceptions and errors in PHP.
use App\Exceptions\Data\SanitationException; // Custom exception for data sanitization errors.
use App\Utilities\Traits\{
    TypeCheckerTrait,       // Offers utilities for validating and checking data types.
    ArrayTrait,             // Provides utility methods for array operations.
    ExistenceCheckerTrait   // Adds methods to verify the existence of classes, methods, properties, etc.
};

/**
 * Abstract Class Sanitizer
 *
 * Provides a base implementation for data sanitization and validation processes.
 * Designed to handle configurable sanitization methods and validation rules.
 *
 * Key Features:
 * - Configurable sanitization and validation methods.
 * - Dynamic resolution of validation rules.
 * - Error handling with exception throwing for invalid data.
 *
 * Traits Used:
 * - **TypeCheckerTrait**: Validates and ensures correct data types.
 * - **ArrayTrait**: Utility methods for handling array operations.
 * - **ExistenceCheckerTrait**: Provides methods to verify existence of various elements in PHP.
 *
 * @package App\Abstracts\Data
 * @abstract
 */
abstract class Sanitizer
{
    use TypeCheckerTrait,       // Ensures and validates data types.
        ArrayTrait,             // Handles array operations and transformations.
        ExistenceCheckerTrait;  // Verifies existence of classes, methods, and other PHP elements.

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
    private function normalizeConfig(mixed $config): array
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
    private function processValue(mixed $value, array $config): mixed
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
    private function applyRules(mixed $value, array $rules): mixed
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
    private function applySanitation(mixed $value, array $methods): mixed
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
    abstract public function clean(mixed $data): mixed;
}
