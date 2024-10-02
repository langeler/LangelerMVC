<?php

namespace App\Contracts\Data;

use App\Exceptions\Data\ValidationException;

/**
 * Interface ValidatorInterface
 *
 * Defines the contract for a validator class that handles data validation using reflection.
 */
interface ValidatorInterface
{
    /**
     * Constructor to set the data for validation.
     *
     * @param array|null $data Data to validate.
     */
    public function __construct(?array $data = []);
}
