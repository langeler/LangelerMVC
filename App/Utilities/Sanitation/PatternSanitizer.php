<?php

namespace App\Utilities\Sanitation;

use App\Abstracts\Data\Sanitizer;
use App\Utilities\Traits\Rules\RuleTrait;
use App\Utilities\Traits\Patterns\SanitationPatternTrait;

/**
 * Class TextSanitizer
 *
 * Provides sanitation methods for text-related fields using regex patterns.
 */
class PatternSanitizer extends Sanitizer
{
	use RuleTrait, SanitationPatternTrait;

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
