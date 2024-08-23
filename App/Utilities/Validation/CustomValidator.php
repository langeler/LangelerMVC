<?php

namespace App\Utilities\Validation;

use App\Abstracts\Validator;
use App\Utilities\Validation\Traits\{
    TextValidationTrait,
    NumericValidationTrait,
    FileValidationTrait,
    // Add other validation traits as needed
};
use App\Utilities\Rules\{
    TextRuleTrait,
    NumericRuleTrait,
    FileRuleTrait,
    // Add other rules traits as needed
};

class CustomValidator extends Validator
{
    // Include necessary traits for validation and rules
    use TextValidationTrait, NumericValidationTrait, FileValidationTrait;
    use TextRuleTrait, NumericRuleTrait, FileRuleTrait;

    /**
     * Implementation of the validate method as required by the abstract class.
     *
     * @param mixed $data The data to be validated.
     * @return array The validation result.
     */
    protected function validate(mixed $data): array
    {
        return $this->run($data);
    }
}
