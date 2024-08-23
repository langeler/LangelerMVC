<?php

namespace App\Utilities\Rules;

trait FileRuleTrait
{
	use BaseRuleTrait;

	// Rule for allowable file types (extensions).
	public function ruleFileType(string $fileName, array $allowedTypes): bool
	{
		$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
		return in_array($extension, $allowedTypes);
	}
}
