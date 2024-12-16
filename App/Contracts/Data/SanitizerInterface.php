<?php

namespace App\Contracts\Data;

/**
 * Interface SanitizerInterface
 *
 * Defines the contract for a sanitizer class that processes data using configurable sanitization methods.
 */
interface SanitizerInterface
{
    /**
     * Cleans the provided data using pre-configured sanitization methods and rules.
     *
     * @param mixed $data The data to sanitize.
     * @return mixed The sanitized data.
     */
    public function clean(mixed $data): mixed;
}
