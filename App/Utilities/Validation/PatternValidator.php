<?php

namespace App\Utilities\Validation;

use App\Abstracts\Data\Validator;
use App\Utilities\Traits\Rules\RuleTrait;
use App\Utilities\Traits\Patterns\ValidationPatternTrait;

/**
 * Class TextValidator
 *
 * Provides validation methods for various text formats using regex patterns.
 */
class PatternValidator extends Validator
{
	use RuleTrait, ValidationPatternTrait;

	/**
	 * === ENTRY POINT: validate method (Do not modify) ===
	 *
	 * @param mixed $data The data to be validated.
	 * @return array The validated data array.
	 */
	protected function verify(mixed $data): array
	{
		return $this->handle($data);
	}
}
