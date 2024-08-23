<?php

namespace App\Contracts;

interface SanitizerInterface
{
    /**
     * Main function to trigger the sanitization process.
     *
     * @param mixed $data The data to be sanitized.
     * @return array The sanitized result.
     */
    public function sanitize(mixed $data): array;

    /**
     * Function to prepare and organize the data before sanitization.
     *
     * @param mixed $data The data to be prepared.
     * @return void
     */
    public function prepare(mixed $data): void;

    /**
     * Function to iterate over all fields and sanitize them.
     *
     * @return void
     */
    public function sanitizeAll(): void;

    /**
     * Function to sanitize a single field.
     *
     * @return void
     */
    public function sanitizeField(): void;

    /**
     * Function to call the appropriate sanitization method dynamically.
     *
     * @return mixed The sanitized value.
     */
    public function callSanitizeMethod();

    /**
     * Function to apply rules to the sanitized value.
     *
     * @return void
     */
    public function applyRules(): void;

    /**
     * Function to check if rules exist for the current field.
     *
     * @return bool True if rules exist, false otherwise.
     */
    public function rulesExist(): bool;

    /**
     * Function to get any errors encountered during the sanitization process.
     *
     * @return array The array of error messages.
     */
    public function getErrors(): array;
}