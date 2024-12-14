<?php

namespace App\Utilities\Validation;

use App\Abstracts\Data\Validator;
use App\Utilities\Traits\Rules\RuleTrait;
use App\Utilities\Traits\Filters\ValidationFilterTrait;

/**
 * Class GeneralValidator
 *
 * Provides general validation methods using traits for filtration, validation, and filter flags.
 */
class GeneralValidator extends Validator
{
	use RuleTrait, ValidationFilterTrait;

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
