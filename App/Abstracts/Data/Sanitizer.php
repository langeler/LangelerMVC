<?php

declare(strict_types=1);

namespace App\Abstracts\Data;

use ReflectionMethod;
use ReflectionNamedType;
use Throwable;
use App\Exceptions\Data\SanitizationException;
use App\Utilities\Traits\{
    TypeCheckerTrait,
    ArrayTrait,
    ExistenceCheckerTrait
};

/**
 * Abstract Class Sanitizer
 *
 * Base implementation for schema-driven sanitization.
 */
abstract class Sanitizer
{
    use ArrayTrait {
        replaceElements as private;
        replaceElements as protected arrayReplace;
    }
    use TypeCheckerTrait, ExistenceCheckerTrait;

    /**
     * Runs sanitization for the given schema and optional payload.
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
                $sanitized = [];

                foreach ($schema as $key => $config) {
                    if (!$this->keyExists($payload, $key)) {
                        throw new SanitizationException("Missing value for key '{$key}'.");
                    }

                    $sanitized[$key] = $this->processValue(
                        $payload[$key],
                        $this->normalizeConfig($config)
                    );
                }

                return $sanitized;
            },
            'Sanitization process failed.'
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
                    throw new SanitizationException('Invalid configuration format.');
                }

                $parts = $this->getValues($config);
                $methods = $this->normalizeMethods($parts[0] ?? null);
                $options = [];
                $rules = [];

                foreach (array_slice($parts, 1) as $part) {
                    if (!$this->isArray($part)) {
                        throw new SanitizationException('Configuration sections must be arrays.');
                    }

                    if ($options === [] && !$this->isRuleConfig($part)) {
                        $options = $part;
                        continue;
                    }

                    $rules = $this->arrayReplace($rules, $part);
                }

                return [$methods, $options, $rules];
            },
            'Failed to normalize configuration.'
        );
    }

    /**
     * Sanitizes a single value against a normalized config definition.
     *
     * @param mixed $value
     * @param array $config
     * @return mixed
     */
    private function processValue(mixed $value, array $config): mixed
    {
        return $this->wrapInTry(
            fn() => $this->applySanitation(
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
                    $ruleName = $this->isInt($rule) ? $params : $rule;
                    $arguments = $this->isInt($rule)
                        ? []
                        : ($this->isArray($params) ? $this->getValues($params) : [$params]);
                    $method = 'rule' . ucfirst((string) $ruleName);

                    $ruleValue = $this->normalizeRuleInput($value, (string) $ruleName);

                    if (
                        !$this->methodExists($this, $method)
                        || !$this->$method($ruleValue, ...$arguments)
                    ) {
                        throw new SanitizationException("Validation failed for rule '{$ruleName}'.");
                    }
                }

                return $value;
            },
            'Failed to apply validation rules.'
        );
    }

    /**
     * Applies configured sanitization methods to a value.
     *
     * @param mixed $value
     * @param array $methods
     * @param array $options
     * @return mixed
     */
    private function applySanitation(mixed $value, array $methods, array $options = []): mixed
    {
        return $this->wrapInTry(
            function () use ($value, $methods, $options): mixed {
                return $this->reduce(
                    $methods,
                    function (mixed $carry, string $method) use ($options): mixed {
                        $sanitizer = 'sanitize' . ucfirst($method);

                        if (!$this->methodExists($this, $sanitizer)) {
                            throw new SanitizationException("Undefined sanitization method: '{$method}'.");
                        }

                        return $this->invokeConfiguredMethod($sanitizer, $carry, $options);
                    },
                    $value
                );
            },
            'Failed to apply sanitation methods.'
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
            return $this->getValues($methods);
        }

        throw new SanitizationException('Invalid sanitization methods configuration.');
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
            $rule = $this->isInt($key) ? $value : $key;

            if (!$this->isString($rule) || !$this->methodExists($this, 'rule' . ucfirst($rule))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Invokes a sanitization method while adapting the provided options to its signature.
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
            $arguments = $this->merge($arguments, $this->buildMethodArguments($parameters, $options));
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
                return [$this->isListArray($options) ? $this->getValues($options) : $options];
            }

            if ($options === []) {
                return $parameter->isDefaultValueAvailable() ? [$parameter->getDefaultValue()] : [];
            }

            if (!$this->isListArray($options) && $this->keyExists($options, $parameter->getName())) {
                return [$options[$parameter->getName()]];
            }

            return [$this->getValues($options)[0]];
        }

        if ($this->isListArray($options)) {
            return $this->getValues($options);
        }

        return $this->map(
            fn($parameter) => $this->keyExists($options, $parameter->getName())
                ? $options[$parameter->getName()]
                : ($parameter->isDefaultValueAvailable()
                    ? $parameter->getDefaultValue()
                    : throw new SanitizationException(
                        "Missing option '{$parameter->getName()}' for sanitization method."
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
        return $this->isList($value);
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
            $this->isString($value)
            && $this->isNumeric($value)
            && $this->isInArray($ruleName, ['min', 'max', 'between', 'less', 'greater', 'divisibleBy', 'positive', 'negative', 'step'], true)
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
            throw new SanitizationException("{$errorMessage}: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Cleans the provided schema and optional values.
     *
     * @param array $schema
     * @param array|null $values
     * @return array
     */
    abstract public function clean(array $schema, ?array $values = null): array;
}
