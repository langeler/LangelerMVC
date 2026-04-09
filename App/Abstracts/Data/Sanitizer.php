<?php

declare(strict_types=1);

namespace App\Abstracts\Data;

use Throwable;
use App\Exceptions\Data\SanitizationException;

/**
 * Abstract Class Sanitizer
 *
 * Base implementation for schema-driven sanitization.
 */
abstract class Sanitizer extends SchemaProcessor
{
    protected function createProcessingException(string $message, ?Throwable $previous = null): Throwable
    {
        return new SanitizationException($message, 0, $previous);
    }

    protected function getProcessFailureMessage(): string
    {
        return 'Sanitization process failed';
    }

    protected function processLeafValue(mixed $value, array $definition): mixed
    {
        return $this->wrapProcessing(
            fn(): mixed => $this->applyRules(
                $this->applySanitation($value, $definition['methods'], $definition['options']),
                $definition['rules']
            ),
            'Failed to process value'
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
        if ($methods === []) {
            return $value;
        }

        return $this->wrapProcessing(
            function () use ($value, $methods, $options): mixed {
                return $this->reduce(
                    $methods,
                    function (mixed $carry, string $method) use ($options): mixed {
                        $sanitizer = 'sanitize' . ucfirst($method);

                        if (!$this->methodExists($this, $sanitizer)) {
                            throw $this->createProcessingException("Undefined sanitization method: '{$method}'.");
                        }

                        return $this->invokeConfiguredMethod($sanitizer, $carry, $options);
                    },
                    $value
                );
            },
            'Failed to apply sanitation methods'
        );
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
