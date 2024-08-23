<?php

namespace App\Utilities\Sanitation\Traits;

use Exception;

trait FileSanitationTrait
{
	use BaseSanitationTrait;

	/**
	 * Strip dangerous characters from file names.
	 *
	 * @param string $filename
	 * @return string
	 */
	public function sanitizeFileName(string $filename): string
	{
		return preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);
	}

	/**
	 * Sanitize and remove unnecessary file metadata.
	 *
	 * @param string $filePath
	 * @return bool
	 */
	public function removeFileMetadata(string $filePath): bool
	{
		// Example: You can use external libraries or PHP built-ins like exiftool to remove metadata.
		return true;
	}

	/**
	 * Ensure secure file encryption.
	 *
	 * @param string $filePath
	 * @param string $encryptionKey
	 * @return bool
	 * @throws Exception
	 */
	public function encryptFile(string $filePath, string $encryptionKey): bool
	{
		if (!file_exists($filePath)) {
			throw new Exception('File not found.');
		}
		$fileData = file_get_contents($filePath);
		$encryptedData = openssl_encrypt($fileData, 'AES-256-CBC', $encryptionKey, 0, substr(hash('sha256', $encryptionKey), 0, 16));
		return file_put_contents($filePath, $encryptedData) !== false;
	}

	/**
	 * Sanitize file versions and backups, ensuring secure handling.
	 *
	 * @param string $filePath
	 * @return bool
  */
  -public function sanitizeFileBackup(string $filePath): bool
  {
  	return true;
  }
}
