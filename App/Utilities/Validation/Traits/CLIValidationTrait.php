<?php

namespace App\Utilities\Validation\Traits;

trait CLIValidationTrait
{
	use BaseValidationTrait;

	/**
	 * Validate that command-line input does not contain shell injection.
	 *
	 * @param string $input
	 * @return bool
	 */
	public function validateNoShellInjection(string $input): bool
	{
		return $input === escapeshellarg($input);
	}
}
