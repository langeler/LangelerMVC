<?php

namespace App\Utilities\Validation\Traits;

trait AuthValidationTrait
{
	use BaseValidationTrait;

	/**
	 * Validate that an OAuth token is valid.
	 *
	 * @param string $token
	 * @return bool
	 */
	public function validateOAuthToken(string $token): bool
	{
		return $this->validateLength($token, 40);
	}

	/**
	 * Validate that an API key is compliant with security standards.
	 *
	 * @param string $apiKey
	 * @return bool
	 */
	public function validateApiKeyCompliance(string $apiKey): bool
	{
		return $this->validatePattern($apiKey, '/^[A-Za-z0-9]{32,40}$/');
	}
}
