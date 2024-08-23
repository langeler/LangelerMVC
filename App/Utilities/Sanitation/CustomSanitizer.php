<?php

namespace App\Utilities\Sanitation;

use App\Abstracts\Sanitizer;
use App\Utilities\Sanitation\Traits\{
    TextSanitizationTrait,
    NumericSanitizationTrait,
    FileSanitizationTrait,
    // Add other sanitation traits as needed
};
use App\Utilities\Rules\{
    TextRuleTrait,
    NumericRuleTrait,
    FileRuleTrait,
    // Add other rules traits as needed
};

class CustomSanitizer extends Sanitizer
{
    // Include necessary traits for sanitation and rules
    use TextSanitizationTrait, NumericSanitizationTrait, FileSanitizationTrait;
    use TextRuleTrait, NumericRuleTrait, FileRuleTrait;

    /**
     * Implementation of the sanitize method as required by the abstract class.
     *
     * @param mixed $data The data to be sanitized.
     * @return array The sanitized result.
     */
    protected function sanitize(mixed $data): array
    {
        return $this->run($data);
    }
}
