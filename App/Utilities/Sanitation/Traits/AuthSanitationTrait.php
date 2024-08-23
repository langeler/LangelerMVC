<?php

namespace App\Utilities\Sanitation\Traits;

trait AuthSanitationTrait
{
	use BaseSanitationTrait;

	/**
	 * Securely sanitize authentication tokens.
	 *
	 * @param string $token
	 * @return string
	 */
	public function sanitizeAuthToken(string $token): string
	{
		return $this->sanitizeText($token);
	}

	/**
	 * Remove or invalidate expired or compromised credentials.
	 *
	 * @param string $credential
	 * @return string
	 */
	public function invalidateCredential(string $credential): string
	{
		return str_repeat('*', strlen($credential));
	}

	/**
	 * Mask sensitive authentication data in logs.
	 *
	 * @param string $data
	 * @return string
	 */
	public function maskAuthData(string $data): string
	{
		return $this->maskData($data);
	}

	/**
	 * Sanitize session data related to authentication.
	 *
	 * @param array $sessionData
	 * @return array
	 */
	public function sanitizeAuthSessionData(array $sessionData): array
	{
		return $this->sanitizeNestedData($sessionData);
	}

	/**
	 * Sanitize OAuth and SSO tokens for secure storage and transmission.
	 *
	 * @param string $token
	 * @return string
	 */
	public function sanitizeOAuthToken(string $token): string
	{
		return $this->sanitizeAuthToken($token);
	}
}
