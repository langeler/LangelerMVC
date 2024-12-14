<?php

namespace App\Abstracts\Data;

use App\Exceptions\Data\ValidationException;
use Throwable;

abstract class Validator
{
    /**
     * Entry point for the validation process.
     *
     * @param array $data Associative array where keys are data items, and values define validation methods and rules.
     * @return array The validated data.
     * @throws ValidationException If an error occurs during validation.
     */
    protected function handle(array $data): array
    {
        try {
            return array_map(
                fn($config, $value) => $this->processValue($value, $this->normalizeConfig($config)),
                $data,
                array_keys($data)
            );
        } catch (Throwable $e) {
            throw new ValidationException("Validation process failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Normalizes configurations with nested "=>" operators into a standard structure.
     *
     * @param mixed $config The raw configuration.
     * @return array Normalized configuration with separate validation methods and rules.
     */
    protected function normalizeConfig(mixed $config): array
    {
        return match (true) {
            is_string($config) || is_array($config) && isset($config[0]) =>
                [is_array($config) ? $config : [$config], []],
            is_array($config) && count($config) === 2 && is_array($config[1]) =>
                $config,
            is_array($config) && count($config) > 2 =>
                [array_keys(array_filter($config, 'is_string', ARRAY_FILTER_USE_KEY)), array_pop($config)],
            default =>
                throw new ValidationException("Invalid configuration format."),
        };
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
        return $this->applyValidation(
            $this->applyRules($value, $config[1]),
            $config[0]
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
        return array_reduce(
            array_keys($rules),
            fn($carry, $rule) =>
                method_exists($this, $method = 'rule' . ucfirst($rule)) && $this->$method($carry, $rules[$rule])
                    ? $carry
                    : throw new ValidationException("Validation failed for rule '{$rule}' on value '{$carry}'."),
            $value
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
        return array_reduce(
            $methods,
            fn($carry, $method) =>
                method_exists($this, $validator = 'validate' . ucfirst($method))
                    ? $this->$validator($carry)
                    : throw new ValidationException("Undefined validation method: '{$method}'."),
            $value
        );
    }

    /**
     * Abstract method to define default verification logic.
     *
     * @param mixed $data The data to verify.
     * @return mixed The verified data.
     */
    abstract protected function verify(mixed $data): mixed;
}
