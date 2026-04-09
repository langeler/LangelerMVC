<?php

declare(strict_types=1);

namespace App\Abstracts\Data;

use ReflectionMethod;
use ReflectionNamedType;
use Throwable;
use App\Utilities\Traits\{
    ArrayTrait,
    ConversionTrait,
    ErrorTrait,
    ExistenceCheckerTrait,
    TypeCheckerTrait
};

/**
 * Shared schema engine for sanitation and validation pipelines.
 *
 * Supports:
 * - legacy positional definitions
 * - explicit named definitions
 * - nested object schemas
 * - per-item collection schemas
 * - required / nullable / default / inline value metadata
 */
abstract class SchemaProcessor
{
    use ErrorTrait {
        wrapInTry as private wrapWithErrorHandling;
    }
    use ArrayTrait {
        replaceElements as private;
        replaceElements as protected arrayReplace;
    }
    use TypeCheckerTrait, ExistenceCheckerTrait, ConversionTrait;

    private const META_KEYS = [
        'method',
        'methods',
        'option',
        'options',
        'rule',
        'rules',
        'required',
        'nullable',
        'default',
        'schema',
        'fields',
        'each',
        'value',
    ];

    /**
     * Cached reflected parameter lists for configured methods.
     *
     * @var array<string, array>
     */
    private array $methodParameterCache = [];

    /**
     * Processes a schema against an optional payload.
     *
     * When no external payload is provided, explicit field definitions may still
     * provide inline values through the `value` metadata key.
     *
     * @param array $schema
     * @param array|null $values
     * @return array
     */
    protected function handle(array $schema, ?array $values = null): array
    {
        return $this->wrapProcessing(
            fn(): array => $this->processSchema($schema, $values ?? []),
            $this->getProcessFailureMessage()
        );
    }

    /**
     * Creates the processor-specific exception type.
     *
     * @param string $message
     * @param Throwable|null $previous
     * @return Throwable
     */
    abstract protected function createProcessingException(string $message, ?Throwable $previous = null): Throwable;

    /**
     * Processes a scalar leaf value after schema normalization.
     *
     * @param mixed $value
     * @param array $definition
     * @return mixed
     */
    abstract protected function processLeafValue(mixed $value, array $definition): mixed;

    /**
     * The top-level failure message for the processor.
     *
     * @return string
     */
    abstract protected function getProcessFailureMessage(): string;

    /**
     * Runs a processing callback and consistently wraps any failure.
     *
     * @param callable $callback
     * @param string $message
     * @return mixed
     */
    protected function wrapProcessing(callable $callback, string $message): mixed
    {
        return $this->wrapWithErrorHandling(
            $callback,
            fn(Throwable $caught): Throwable => $this->createProcessingException(
                "{$message}: {$caught->getMessage()}",
                $caught
            )
        );
    }

    /**
     * Applies configured rules to a value.
     *
     * @param mixed $value
     * @param array $rules
     * @return mixed
     */
    protected function applyRules(mixed $value, array $rules): mixed
    {
        if ($rules === []) {
            return $value;
        }

        return $this->wrapProcessing(
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
                        throw $this->createProcessingException("Validation failed for rule '{$ruleName}'.");
                    }
                }

                return $value;
            },
            'Failed to apply rules'
        );
    }

    /**
     * Invokes a configured processor method while adapting schema options to its signature.
     *
     * @param string $method
     * @param mixed $value
     * @param array $options
     * @return mixed
     */
    protected function invokeConfiguredMethod(string $method, mixed $value, array $options): mixed
    {
        $arguments = [$value];
        $parameters = $this->getMethodParameters($method);

        if ($parameters !== []) {
            $arguments = $this->merge($arguments, $this->buildMethodArguments($parameters, $options));
        }

        return (new ReflectionMethod($this, $method))->invokeArgs($this, $arguments);
    }

    /**
     * Processes a schema recursively.
     *
     * @param array $schema
     * @param array $payload
     * @param string $path
     * @return array
     */
    private function processSchema(array $schema, array $payload, string $path = ''): array
    {
        $processed = [];

        foreach ($schema as $key => $config) {
            $fieldPath = $this->buildPath($path, $key);
            $definition = $this->normalizeFieldDefinition($config);
            $resolved = $this->resolveFieldValue($payload, $definition, $fieldPath, $key);

            if (!$resolved['include']) {
                continue;
            }

            $processed[$key] = $this->processDefinitionValue(
                $resolved['value'],
                $definition,
                $fieldPath
            );
        }

        return $processed;
    }

    /**
     * Resolves an actual field value from payload, inline metadata, or defaults.
     *
     * @param array $payload
     * @param array $definition
     * @param string $fieldPath
     * @param int|string $key
     * @return array{include: bool, value?: mixed}
     */
    private function resolveFieldValue(array $payload, array $definition, string $fieldPath, int|string $key): array
    {
        if ($this->keyExists($payload, $key)) {
            return ['include' => true, 'value' => $payload[$key]];
        }

        if ($definition['hasValue']) {
            return ['include' => true, 'value' => $this->resolveConfiguredValue($definition['value'])];
        }

        if ($definition['hasDefault']) {
            return ['include' => true, 'value' => $this->resolveConfiguredValue($definition['default'])];
        }

        if (!$definition['required']) {
            return ['include' => false];
        }

        throw $this->createProcessingException("Missing value for key '{$fieldPath}'.");
    }

    /**
     * Processes a single normalized field definition.
     *
     * @param mixed $value
     * @param array $definition
     * @param string $fieldPath
     * @return mixed
     */
    private function processDefinitionValue(mixed $value, array $definition, string $fieldPath): mixed
    {
        if ($this->isNull($value)) {
            if ($definition['nullable']) {
                return null;
            }

            throw $this->createProcessingException("Null value is not allowed for key '{$fieldPath}'.");
        }

        if ($definition['schema'] !== null) {
            if (!$this->isArray($value)) {
                throw $this->createProcessingException("Key '{$fieldPath}' must contain an array.");
            }

            return $this->processSchema(
                $definition['schema'],
                $this->applyRules($value, $definition['rules']),
                $fieldPath
            );
        }

        if ($definition['each'] !== null) {
            if (!$this->isArray($value)) {
                throw $this->createProcessingException("Key '{$fieldPath}' must contain an array.");
            }

            $normalizedItemDefinition = $this->normalizeFieldDefinition($definition['each']);
            $items = [];

            foreach ($this->applyRules($value, $definition['rules']) as $itemKey => $itemValue) {
                $items[$itemKey] = $this->processDefinitionValue(
                    $itemValue,
                    $normalizedItemDefinition,
                    $this->buildPath($fieldPath, $itemKey)
                );
            }

            return $items;
        }

        return $this->processLeafValue($value, $definition);
    }

    /**
     * Normalizes a field configuration into a consistent internal definition.
     *
     * @param mixed $config
     * @return array
     */
    private function normalizeFieldDefinition(mixed $config): array
    {
        return $this->wrapProcessing(
            function () use ($config): array {
                if ($this->isString($config)) {
                    return $this->buildDefinition(['methods' => [$config]]);
                }

                if (!$this->isArray($config) || $config === []) {
                    throw $this->createProcessingException('Invalid configuration format.');
                }

                if ($this->isExplicitDefinition($config)) {
                    return $this->normalizeExplicitDefinition($config);
                }

                if (!$this->isListArray($config)) {
                    return $this->buildDefinition(['schema' => $config]);
                }

                return $this->normalizeLegacyDefinition($config);
            },
            'Failed to normalize configuration'
        );
    }

    /**
     * Normalizes the original positional schema syntax.
     *
     * @param array $config
     * @return array
     */
    private function normalizeLegacyDefinition(array $config): array
    {
        $parts = $this->getValues($config);
        $methods = $this->normalizeMethods($parts[0] ?? null);
        $options = [];
        $rules = [];

        foreach (array_slice($parts, 1) as $part) {
            if (!$this->isArray($part)) {
                throw $this->createProcessingException('Configuration sections must be arrays.');
            }

            if ($options === [] && !$this->isRuleConfig($part)) {
                $options = $part;
                continue;
            }

            $rules = $this->arrayReplace($rules, $part);
        }

        return $this->buildDefinition([
            'methods' => $methods,
            'options' => $options,
            'rules' => $rules,
        ]);
    }

    /**
     * Normalizes the explicit named schema syntax.
     *
     * @param array $config
     * @return array
     */
    private function normalizeExplicitDefinition(array $config): array
    {
        $schema = $this->firstDefinedValue($config, ['schema', 'fields']);
        $methods = $this->normalizeOptionalMethods($this->firstDefinedValue($config, ['methods', 'method']));
        $options = $this->normalizeOptions($this->firstDefinedValue($config, ['options', 'option']));
        $rules = $this->normalizeRules($this->firstDefinedValue($config, ['rules', 'rule']));
        $required = $this->keyExists($config, 'required') ? (bool) $config['required'] : true;
        $nullable = $this->keyExists($config, 'nullable') ? (bool) $config['nullable'] : false;
        $each = $this->keyExists($config, 'each') ? $config['each'] : null;
        $hasDefault = $this->keyExists($config, 'default');
        $hasValue = $this->keyExists($config, 'value');

        if (!$this->isNull($schema) && !$this->isArray($schema)) {
            throw $this->createProcessingException('Nested schema configuration must be an array.');
        }

        return $this->buildDefinition([
            'methods' => $methods,
            'options' => $options,
            'rules' => $rules,
            'required' => $required,
            'nullable' => $nullable,
            'hasDefault' => $hasDefault,
            'default' => $hasDefault ? $config['default'] : null,
            'schema' => $schema,
            'each' => $each,
            'hasValue' => $hasValue,
            'value' => $hasValue ? $config['value'] : null,
        ]);
    }

    /**
     * Creates the final normalized definition and validates incompatible combinations.
     *
     * @param array $definition
     * @return array
     */
    private function buildDefinition(array $definition): array
    {
        $normalized = [
            'methods' => $definition['methods'] ?? [],
            'options' => $definition['options'] ?? [],
            'rules' => $definition['rules'] ?? [],
            'required' => $definition['required'] ?? true,
            'nullable' => $definition['nullable'] ?? false,
            'hasDefault' => $definition['hasDefault'] ?? false,
            'default' => $definition['default'] ?? null,
            'schema' => $definition['schema'] ?? null,
            'each' => $definition['each'] ?? null,
            'hasValue' => $definition['hasValue'] ?? false,
            'value' => $definition['value'] ?? null,
        ];

        if ($normalized['schema'] !== null && $normalized['each'] !== null) {
            throw $this->createProcessingException('A field configuration cannot define both schema and each.');
        }

        if (($normalized['schema'] !== null || $normalized['each'] !== null) && $normalized['methods'] !== []) {
            throw $this->createProcessingException('Method-based processing cannot be combined with nested schema definitions.');
        }

        return $normalized;
    }

    /**
     * Determines whether an associative array is using named field metadata.
     *
     * @param array $config
     * @return bool
     */
    private function isExplicitDefinition(array $config): bool
    {
        return $this->any(
            $this->getKeys($config),
            fn(mixed $key): bool => $this->isString($key) && $this->isInArray($key, self::META_KEYS, true)
        );
    }

    /**
     * Determines whether a config segment should be interpreted as a rule map.
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
     * Normalizes the configured method list.
     *
     * @param mixed $methods
     * @return array
     */
    private function normalizeMethods(mixed $methods): array
    {
        $normalized = $this->normalizeOptionalMethods($methods);

        if ($normalized === []) {
            throw $this->createProcessingException('Invalid methods configuration.');
        }

        return $normalized;
    }

    /**
     * Normalizes an optional method list.
     *
     * @param mixed $methods
     * @return array
     */
    private function normalizeOptionalMethods(mixed $methods): array
    {
        if ($this->isNull($methods)) {
            return [];
        }

        if ($this->isString($methods)) {
            return [$methods];
        }

        if ($this->isArray($methods) && $this->all($methods, fn(mixed $method): bool => $this->isString($method))) {
            return $this->getValues($methods);
        }

        throw $this->createProcessingException('Invalid methods configuration.');
    }

    /**
     * Normalizes an optional options map.
     *
     * @param mixed $options
     * @return array
     */
    private function normalizeOptions(mixed $options): array
    {
        if ($this->isNull($options)) {
            return [];
        }

        if ($this->isArray($options)) {
            return $options;
        }

        throw $this->createProcessingException('Invalid options configuration.');
    }

    /**
     * Normalizes an optional rules definition.
     *
     * @param mixed $rules
     * @return array
     */
    private function normalizeRules(mixed $rules): array
    {
        if ($this->isNull($rules)) {
            return [];
        }

        if ($this->isString($rules)) {
            return [$rules];
        }

        if ($this->isArray($rules)) {
            return $rules;
        }

        throw $this->createProcessingException('Invalid rules configuration.');
    }

    /**
     * Returns the first configured value among a set of aliases.
     *
     * @param array $config
     * @param array $keys
     * @return mixed
     */
    private function firstDefinedValue(array $config, array $keys): mixed
    {
        foreach ($keys as $key) {
            if ($this->keyExists($config, $key)) {
                return $config[$key];
            }
        }

        return null;
    }

    /**
     * Resolves a configured default or inline value.
     *
     * @param mixed $value
     * @return mixed
     */
    private function resolveConfiguredValue(mixed $value): mixed
    {
        return $this->wrapProcessing(
            fn(): mixed => $this->isCallable($value) ? $value() : $value,
            'Failed to resolve configured value'
        );
    }

    /**
     * Builds method arguments from schema options and reflected parameters.
     *
     * @param array $parameters
     * @param array $options
     * @return array
     */
    private function buildMethodArguments(array $parameters, array $options): array
    {
        if ($this->countElements($parameters) === 1) {
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
                    : throw $this->createProcessingException(
                        "Missing option '{$parameter->getName()}' for configured method."
                    )),
            $parameters
        );
    }

    /**
     * Fetches cached reflected parameters for a configured method.
     *
     * @param string $method
     * @return array
     */
    private function getMethodParameters(string $method): array
    {
        if (!$this->keyExists($this->methodParameterCache, $method)) {
            $this->methodParameterCache[$method] = array_slice(
                (new ReflectionMethod($this, $method))->getParameters(),
                1
            );
        }

        return $this->methodParameterCache[$method];
    }

    /**
     * Compatibility helper for list detection.
     *
     * @param array $value
     * @return bool
     */
    private function isListArray(array $value): bool
    {
        return $this->isList($value);
    }

    /**
     * Normalizes values passed into numeric and sequencing rules.
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
            return $this->coerceNumericValue($value);
        }

        if ($ruleName === 'sequential' && $this->isArray($value)) {
            return $this->map(
                fn(mixed $item): mixed => $this->isString($item) && $this->isNumeric($item)
                    ? $this->coerceNumericValue($item)
                    : $item,
                $value
            );
        }

        return $value;
    }

    /**
     * Coerces numeric strings into native numbers.
     *
     * @param string $value
     * @return float|int
     */
    private function coerceNumericValue(string $value): float|int
    {
        return str_contains($value, '.') || stripos($value, 'e') !== false
            ? $this->toFloat($value)
            : $this->toInt($value);
    }

    /**
     * Builds a dot-delimited path for nested field errors.
     *
     * @param string $base
     * @param int|string $segment
     * @return string
     */
    private function buildPath(string $base, int|string $segment): string
    {
        return $base === ''
            ? (string) $segment
            : "{$base}.{$segment}";
    }
}
