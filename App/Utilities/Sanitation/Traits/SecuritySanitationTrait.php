
<?php

namespace App\Utilities\Sanitation\Traits;

trait SecuritySanitationTrait
{
	use BaseSanitationTrait;

	/**
	 * Encrypt sensitive data.
	 *
	 * @param string $data
	 * @param string $encryptionKey
	 * @return string
	 */
	public function encryptData(string $data, string $encryptionKey): string
	{
		return openssl_encrypt($data, 'AES-256-CBC', $encryptionKey, 0, substr(hash('sha256', $encryptionKey), 0, 16));
	}

	/**
	 * Mask sensitive data.
	 *
	 * @param string $data
	 * @return string
	 */
	public function maskData(string $data): string
	{
		return str_repeat('*', strlen($data) - 4) . substr($data, -4);
	}

	/**
	 * Sanitize logs by removing sensitive information.
	 *
	 * @param string $log
	 * @return string
	 */
	public function sanitizeLog(string $log): string
	{
		return preg_replace('/(\d{1,3}\.){3}\d{1,3}/', '***.***.***.***', $log);
	}

	/**
	 * Securely handle security tokens and authentication credentials.
	 *
	 * @param string $token
	 * @return string
	 */
	public function sanitizeSecurityToken(string $token): string
	{
		return $this->sanitizeText($token);
	}

	/**
	 * Sanitize data to comply with regulatory requirements (e.g., GDPR).
	 *
	 * @param array $data
	 * @return array
	 */
	public function sanitizeForCompliance(array $data): array
	{
		array_walk_recursive($data, function (&$item) {
			$item = $this->maskData($item);
		});
		return $data;
	}
}
