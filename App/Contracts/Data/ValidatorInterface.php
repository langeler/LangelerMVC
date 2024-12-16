<?php

namespace App\Contracts\Data;

/**
 * Interface ValidatorInterface
 *
 * Defines the contract for a validator class that processes data using configurable validation methods and rules.
 */
interface ValidatorInterface
{
    /**
     * Verifies the provided data using pre-configured validation methods and rules.
     *
     * @param mixed $data The data to validate.
     * @return mixed The validated data.
     * @throws \App\Exceptions\Data\ValidationException If validation fails.
     */
    public function verify(mixed $data): mixed;
}
