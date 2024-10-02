<?php

namespace App\Contracts\Data;

use App\Exceptions\Data\SanitationException;

/**
 * Interface SanitizerInterface
 *
 * This interface outlines the methods required for sanitization logic.
 */
interface SanitizerInterface
{
    /**
 * Constructor to set the data for sanitation.
 *
 * @param array|null $data Data to sanitize.
 */
    public function __construct(?array $data = []);
}
