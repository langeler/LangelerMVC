<?php

namespace App\Utilities\Validation\Traits;

trait TextValidationTrait
{
	use BaseValidationTrait;

	/**
	 * Validate that a string is alphanumeric.
	 *
	 * @param string $text
	 * @return bool
	 */
	public function validateAlphanumeric(string $text): bool
	{
		return ctype_alnum($text);
	}

	/**
	 * Validate that a string is a valid email.
	 *
	 * @param string $email
	 * @return bool
	 */
	public function validateEmail(string $email): bool
	{
		return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
	}

	/**
	 * Validate that a string is a valid URL.
	 *
	 * @param string $url
	 * @return bool
	 */
	public function validateUrl(string $url): bool
	{
		return filter_var($url, FILTER_VALIDATE_URL) !== false;
	}
}
