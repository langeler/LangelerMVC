<?php

namespace App\Utilities\Sanitation\Traits;

trait TextSanitationTrait
{
	use BaseSanitationTrait;

	/**
	 * Filter and replace profane or abusive language in text inputs.
	 *
	 * @param string $text
	 * @param array $profanities
	 * @return string
	 */
	public function filterProfanity(string $text, array $profanities = []): string
	{
		foreach ($profanities as $profanity) {
			$text = str_ireplace($profanity, str_repeat('*', strlen($profanity)), $text);
		}
		return $text;
	}
}
