<?php

declare(strict_types=1);

namespace App\Abstracts\Data;

use Throwable;
use App\Exceptions\Data\ValidationException;

/**
 * Abstract Class Validator
 *
 * Base implementation for schema-driven validation.
 */
abstract class Validator extends SchemaProcessor
{
    protected function createProcessingException(string $message, ?Throwable $previous = null): Throwable
    {
        return new ValidationException($message, 0, $previous);
    }

    protected function getProcessFailureMessage(): string
    {
        return 'Validation process failed';
    }

    protected function processLeafValue(mixed $value, array $definition): mixed
    {
        return $this->wrapProcessing(
            fn(): mixed => $this->applyRules(
                $this->applyValidation($value, $definition['methods'], $definition['options']),
                $definition['rules']
            ),
            'Failed to process value'
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
        if ($methods === []) {
            return $value;
        }

        return $this->wrapProcessing(
            function () use ($value, $methods, $options): mixed {
                foreach ($methods as $method) {
                    $validator = 'validate' . ucfirst((string) $method);

                    if (!$this->methodExists($this, $validator)) {
                        throw $this->createProcessingException("Undefined validation method: '{$method}'.");
                    }

                    if (!$this->invokeConfiguredMethod($validator, $value, $options)) {
                        throw $this->createProcessingException("Validation failed for method '{$method}'.");
                    }
                }

                return $value;
            },
            'Failed to apply validation methods'
        );
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
