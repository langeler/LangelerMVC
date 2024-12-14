<?php

namespace App\Abstracts\Data;

use App\Exceptions\Data\SanitationException;
use Throwable;

abstract class Sanitizer
{
    /**
     * Entry point for the sanitization process.
     *
     * @param array $data Associative array where keys are data items, and values define sanitation methods and rules.
     * @return array The sanitized and validated data.
     * @throws SanitationException If an error occurs during sanitization.
     */
    public function handle(array $data): array
    {
        try {
            return array_map(
                fn($config, $value) => $this->processValue($value, $this->normalizeConfig($config)),
                $data,
                array_keys($data)
            );
        } catch (Throwable $e) {
            throw new SanitationException("Sanitization process failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Normalizes configurations with nested "=>" operators into a standard structure.
     *
     * @param mixed $config The raw configuration.
     * @return array Normalized configuration with separate sanitization methods and rules.
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
                throw new SanitationException("Invalid configuration format."),
        };
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
        return $this->applySanitation(
            $this->applyRules($value, $config[1]),
            $config[0]
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
        return array_reduce(
            array_keys($rules),
            fn($carry, $rule) =>
                method_exists($this, $method = 'rule' . ucfirst($rule)) && $this->$method($carry, $rules[$rule])
                    ? $carry
                    : throw new SanitationException("Validation failed for rule '{$rule}' on value '{$carry}'."),
            $value
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
        return array_reduce(
            $methods,
            fn($carry, $method) =>
                method_exists($this, $sanitizer = 'sanitize' . ucfirst($method))
                    ? $this->$sanitizer($carry)
                    : throw new SanitationException("Undefined sanitization method: '{$method}'."),
            $value
        );
    }

    /**
     * Abstract method to define default cleaning logic.
     *
     * @param mixed $data The data to clean.
     * @return mixed The cleaned data.
     */
    abstract protected function clean(mixed $data): mixed;
}
