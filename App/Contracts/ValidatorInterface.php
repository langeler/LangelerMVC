<?php

namespace App\Contracts;

interface ValidatorInterface
{
    /**
     * Main function to trigger the validation process.
     *
     * @param mixed $data The data to be validated.
     * @return array The validation result.
     */
    public function validate(mixed $data): array;

    /**
     * Function to prepare and organize the data before validation.
     *
     * @param mixed $data The data to be prepared.
     * @return void
     */
    public function prepare(mixed $data): void;

    /**
     * Function to iterate over all fields and validate them.
     *
     * @return void
     */
    public function validateAll(): void;

    /**
     * Function to validate a single field.
     *
     * @return void
     */
    public function validateField(): void;

    /**
     * Function to call the appropriate validation method dynamically.
     *
     * @return bool True if validation is successful, false otherwise.
     */
    public function callValidateMethod(): bool;

    /**
     * Function to apply rules to the validated value.
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
     * Function to get any errors encountered during the validation process.
     *
     * @return array The array of error messages.
     */
    public function getErrors(): array;
}