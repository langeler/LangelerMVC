<?php

namespace App\Utilities\Validation\Traits;

trait FileValidationTrait
{
	use BaseValidationTrait;

	/**
	 * Validate that a file has an allowed extension.
	 *
	 * @param string $fileName
	 * @param array $allowedExtensions
	 * @return bool
	 */
	public function validateFileType(string $fileName, array $allowedExtensions): bool
	{
		$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
		return in_array($extension, $allowedExtensions);
	}

	/**
	 * Validate that a file does not exceed the maximum allowed size.
	 *
	 * @param int $fileSize
	 * @param int $maxSize
	 * @return bool
	 */
	public function validateFileSize(int $fileSize, int $maxSize): bool
	{
		return $fileSize <= $maxSize;
	}
}
