<?php

declare(strict_types=1);

namespace App\Abstracts\Data;

use ReflectionMethod;
use ReflectionNamedType;
use Throwable;
use App\Exceptions\Data\ValidationException;
use App\Utilities\Traits\{
    TypeCheckerTrait,
    ArrayTrait,
    ExistenceCheckerTrait
};

/**
 * Abstract Class Validator
 *
 * Base implementation for schema-driven validation.
 */
abstract class Validator
{
    use ArrayTrait {
        replace as private;
        replace as protected arrayReplace;
    }
    use TypeCheckerTrait, ExistenceCheckerTrait;

    /**
     * Runs validation for the given schema and optional payload.
     *
     * @param array $schema
     * @param array|null $values
     * @return array
     */
    protected function handle(array $schema, ?array $values = null): array
    {
        $payload = $values ?? $schema;

        return $this->wrapInTry(
            function () use ($schema, $payload): array {
                $validated = [];

                foreach ($schema as $key => $config) {
                    if (!array_key_exists($key, $payload)) {
                        throw new ValidationException("Missing value for key '{$key}'.");
                    }

                    $validated[$key] = $this->processValue(
                        $payload[$key],
                        $this->normalizeConfig($config)
                    );
                }

                return $validated;
            },
            'Validation process failed.'
        );
    }

    /**
     * Normalizes a schema config into methods, options, and rules.
     *
     * @param mixed $config
     * @return array{0: array, 1: array, 2: array}
     */
    private function normalizeConfig(mixed $config): array
    {
        return $this->wrapInTry(
            function () use ($config): array {
                if ($this->isString($config)) {
                    return [[$config], [], []];
                }

                if (!$this->isArray($config) || $config === []) {
                    throw new ValidationException('Invalid configuration format.');
                }

                $parts = array_values($config);
                $methods = $this->normalizeMethods($parts[0] ?? null);
                $options = [];
                $rules = [];

                foreach (array_slice($parts, 1) as $part) {
                    if (!$this->isArray($part)) {
                        throw new ValidationException('Configuration sections must be arrays.');
                    }

                    if ($options === [] && !$this->isRuleConfig($part)) {
                        $options = $part;
                        continue;
                    }

                    $rules = array_replace($rules, $part);
                }

                return [$methods, $options, $rules];
            },
            'Failed to normalize configuration.'
        );
    }

    /**
     * Validates a single value against a normalized config definition.
     *
     * @param mixed $value
     * @param array $config
     * @return mixed
     */
    private function processValue(mixed $value, array $config): mixed
    {
        return $this->wrapInTry(
            fn() => $this->applyValidation(
                $this->applyRules($value, $config[2]),
                $config[0],
                $config[1]
            ),
            'Failed to process value.'
        );
    }

    /**
     * Applies configured rules to a value.
     *
     * @param mixed $value
     * @param array $rules
     * @return mixed
     */
    private function applyRules(mixed $value, array $rules): mixed
    {
        return $this->wrapInTry(
            function () use ($value, $rules): mixed {
                foreach ($rules as $rule => $params) {
                    $ruleName = is_int($rule) ? $params : $rule;
                    $arguments = is_int($rule)
                        ? []
                        : (is_array($params) ? array_values($params) : [$params]);
                    $method = 'rule' . ucfirst((string) $ruleName);

                    $ruleValue = $this->normalizeRuleInput($value, (string) $ruleName);

                    if (
                        !$this->methodExists($this, $method)
                        || !$this->$method($ruleValue, ...$arguments)
                    ) {
                        throw new ValidationException("Validation failed for rule '{$ruleName}'.");
                    }
                }

                return $value;
            },
            'Failed to apply validation rules.'
        );
    }

    /**
     * Applies configured validation methods to a value.
     *
     * @param mixed $value
     * @param array $methods
     * @param array $options
     * @return mixed
     */
    private function applyValidation(mixed $value, array $methods, array $options = []): mixed
    {
        return $this->wrapInTry(
            function () use ($value, $methods, $options): mixed {
                foreach ($methods as $method) {
                    $validator = 'validate' . ucfirst((string) $method);

                    if (!$this->methodExists($this, $validator)) {
                        throw new ValidationException("Undefined validation method: '{$method}'.");
                    }

                    if (!$this->invokeConfiguredMethod($validator, $value, $options)) {
                        throw new ValidationException("Validation failed for method '{$method}'.");
                    }
                }

                return $value;
            },
            'Failed to apply validation methods.'
        );
    }

    /**
     * Normalizes the configured method list.
     *
     * @param mixed $methods
     * @return array
     */
    private function normalizeMethods(mixed $methods): array
    {
        if ($this->isString($methods)) {
            return [$methods];
        }

        if ($this->isArray($methods) && $methods !== [] && $this->all($methods, fn($method) => $this->isString($method))) {
            return array_values($methods);
        }

        throw new ValidationException('Invalid validation methods configuration.');
    }

    /**
     * Determines whether a config segment should be interpreted as rules.
     *
     * @param array $config
     * @return bool
     */
    private function isRuleConfig(array $config): bool
    {
        if ($config === []) {
            return false;
        }

        foreach ($config as $key => $value) {
            $rule = is_int($key) ? $value : $key;

            if (!$this->isString($rule) || !$this->methodExists($this, 'rule' . ucfirst($rule))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Invokes a validation method while adapting the provided options to its signature.
     *
     * @param string $method
     * @param mixed $value
     * @param array $options
     * @return mixed
     */
    private function invokeConfiguredMethod(string $method, mixed $value, array $options): mixed
    {
        $reflection = new ReflectionMethod($this, $method);
        $parameters = array_slice($reflection->getParameters(), 1);
        $arguments = [$value];

        if ($parameters !== []) {
            $arguments = array_merge($arguments, $this->buildMethodArguments($parameters, $options));
        }

        return $reflection->invokeArgs($this, $arguments);
    }

    /**
     * Builds a method argument list from the configured options.
     *
     * @param array $parameters
     * @param array $options
     * @return array
     */
    private function buildMethodArguments(array $parameters, array $options): array
    {
        if (count($parameters) === 1) {
            $parameter = $parameters[0];
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && $type->getName() === 'array') {
                return [$this->isListArray($options) ? array_values($options) : $options];
            }

            if ($options === []) {
                return $parameter->isDefaultValueAvailable() ? [$parameter->getDefaultValue()] : [];
            }

            if (!$this->isListArray($options) && array_key_exists($parameter->getName(), $options)) {
                return [$options[$parameter->getName()]];
            }

            return [array_values($options)[0]];
        }

        if ($this->isListArray($options)) {
            return array_values($options);
        }

        return array_map(
            fn($parameter) => array_key_exists($parameter->getName(), $options)
                ? $options[$parameter->getName()]
                : ($parameter->isDefaultValueAvailable()
                    ? $parameter->getDefaultValue()
                    : throw new ValidationException(
                        "Missing option '{$parameter->getName()}' for validation method."
                    )),
            $parameters
        );
    }

    /**
     * Compatibility helper for PHP versions without array_is_list.
     *
     * @param array $value
     * @return bool
     */
    private function isListArray(array $value): bool
    {
        return array_keys($value) === range(0, count($value) - 1);
    }

    /**
     * Normalizes input values for numeric rules while preserving original strings for other rules.
     *
     * @param mixed $value
     * @param string $ruleName
     * @return mixed
     */
    private function normalizeRuleInput(mixed $value, string $ruleName): mixed
    {
        if (
            is_string($value)
            && is_numeric($value)
            && in_array($ruleName, ['min', 'max', 'between', 'less', 'greater', 'divisibleBy', 'positive', 'negative', 'step'], true)
        ) {
            return strpos($value, '.') !== false || stripos($value, 'e') !== false
                ? (float) $value
                : (int) $value;
        }

        return $value;
    }

    /**
     * Wraps a callback in a try/catch block and handles exceptions consistently.
     *
     * @param callable $callback
     * @param string $errorMessage
     * @return mixed
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
     * Verifies the provided schema and optional values.
     *
     * @param array $schema
     * @param array|null $values
     * @return array
     */
    abstract public function verify(array $schema, ?array $values = null): array;
}
