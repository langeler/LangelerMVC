<?php

namespace App\Utilities\Sanitation\Traits;

trait LogSanitationTrait
{
	use BaseSanitationTrait;

	/**
	 * Redact sensitive information from logs.
	 *
	 * @param string $log
	 * @return string
	 */
	public function redactLog(string $log): string
	{
		$log = preg_replace('/(\d{1,3}\.){3}\d{1,3}/', '***.***.***.***', $log);
		$log = preg_replace('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', '***@***.***', $log);
		return $log;
	}

	/**
	 * Sanitize and secure metadata.
	 *
	 * @param array $metadata
	 * @return array
	 */
	public function sanitizeMetadata(array $metadata): array
	{
		return $this->sanitizeNestedData($metadata);
	}

	/**
	 * Encrypt logs for security and compliance.
	 *
	 * @param string $log
	 * @param string $encryptionKey
	 * @return string
	 */
	public function encryptLog(string $log, string $encryptionKey): string
	{
		return $this->encryptData($log, $encryptionKey);
	}

	/**
	 * Clean and standardize log formats for consistency.
	 *
	 * @param string $log
	 * @return string
	 */
	public function sanitizeLogFormat(string $log): string
	{
		return json_encode(['log' => $log]);
	}

	/**
	 * Securely archive logs for long-term storage.
	 *
	 * @param string $log
	 * @param string $archivePath
	 * @return bool
	 */
	public function archiveLog(string $log, string $archivePath): bool
	{
		return file_put_contents($archivePath, $log) !== false;
	}
}
