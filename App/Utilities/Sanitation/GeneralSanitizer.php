<?php

namespace App\Utilities\Sanitation;

use App\Abstracts\Data\Sanitizer;
use App\Utilities\Traits\Rules\RuleTrait;
use App\Utilities\Traits\Filters\SanitationFilterTrait;

/**
 * Class GeneralSanitizer
 *
 * Provides general sanitization methods using traits for filtration, sanitation, and filter flags.
 */
class GeneralSanitizer extends Sanitizer
{
	use RuleTrait, SanitationFilterTrait;

	/**
	 * === ENTRY POINT: sanitize method (Do not modify) ===
	 *
	 * @param mixed $data The data to be sanitized.
	 * @return array The sanitized data array.
	 */
	protected function clean(mixed $data): array
	{
		return $this->handle($data);
	}
}
