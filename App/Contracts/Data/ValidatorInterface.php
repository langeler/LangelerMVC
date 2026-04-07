<?php

namespace App\Contracts\Data;

/**
 * Interface ValidatorInterface
 *
 * Defines the contract for a validator class that processes data using
 * a schema-driven validation definition.
 */
interface ValidatorInterface
{
    /**
     * Verifies the provided values using the provided schema.
     *
     * @param array $schema Validation schema keyed by field name.
     * @param array|null $values Optional values keyed by field name.
     * @return array The validated data.
     * @throws \App\Exceptions\Data\ValidationException If validation fails.
     */
    public function verify(array $schema, ?array $values = null): array;
}
