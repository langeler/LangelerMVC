<?php

declare(strict_types=1);

namespace App\Contracts\Data;

/**
 * Interface SanitizerInterface
 *
 * Defines the contract for a sanitizer class that processes data using
 * a schema-driven sanitization definition.
 */
interface SanitizerInterface
{
    /**
     * Cleans the provided values using the provided schema.
     *
     * @param array $schema Sanitization schema keyed by field name.
     * @param array|null $values Optional values keyed by field name.
     * @return array The sanitized data.
     */
    public function clean(array $schema, ?array $values = null): array;
}
