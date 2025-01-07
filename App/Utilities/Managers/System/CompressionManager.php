<?php

namespace App\Utilities\Managers;

use ZipArchive;

/**
 * Class CompressionManager
 *
 * Provides utility methods for working with ZIP archives. This class simplifies common ZIP operations such as creating archives, adding files, extracting, and managing ZIP files.
 */
class CompressionManager
{
	/**
	 * Open a ZIP archive.
	 *
	 * @param string $filename The name of the ZIP file.
	 * @param int $flags Flags for opening the ZIP file (e.g., create if it doesn't exist).
	 * @return ZipArchive The opened ZipArchive instance.
	 */
	public function openZip(string $filename, int $flags = ZipArchive::CREATE): ZipArchive
	{
		$zip = new ZipArchive();
		if ($zip->open($filename, $flags) !== true) {
			throw new \RuntimeException("Unable to open ZIP file: $filename");
		}
		return $zip;
	}

	/**
	 * Add a file to a ZIP archive.
	 *
	 * @param ZipArchive $zip The opened ZipArchive instance.
	 * @param string $filePath The file to add.
	 * @param string|null $localName Optional. The local name inside the ZIP archive.
	 * @return bool True on success, false on failure.
	 */
	public function addFile(ZipArchive $zip, string $filePath, ?string $localName = null): bool
	{
		return $zip->addFile($filePath, $localName);
	}

	/**
	 * Extract files from a ZIP archive.
	 *
	 * @param ZipArchive $zip The opened ZipArchive instance.
	 * @param string $destination The directory to extract the files to.
	 * @return bool True on success, false on failure.
	 */
	public function extractZip(ZipArchive $zip, string $destination): bool
	{
		return $zip->extractTo($destination);
	}

	/**
	 * Close a ZIP archive.
	 *
	 * @param ZipArchive $zip The opened ZipArchive instance.
	 * @return bool True on success, false on failure.
	 */
	public function closeZip(ZipArchive $zip): bool
	{
		return $zip->close();
	}

	/**
	 * Delete a file from a ZIP archive.
	 *
	 * @param ZipArchive $zip The opened ZipArchive instance.
	 * @param string $name The name of the file to delete from the archive.
	 * @return bool True on success, false on failure.
	 */
	public function deleteFile(ZipArchive $zip, string $name): bool
	{
		return $zip->deleteName($name);
	}

	/**
	 * List all files in a ZIP archive.
	 *
	 * @param ZipArchive $zip The opened ZipArchive instance.
	 * @return array The array of file names inside the archive.
	 */
	public function listFiles(ZipArchive $zip): array
	{
		$files = [];
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$files[] = $zip->getNameIndex($i);
		}
		return $files;
	}

	/**
	 * Get the status of the ZIP archive.
	 *
	 * @param ZipArchive $zip The opened ZipArchive instance.
	 * @return int The status of the ZIP archive.
	 */
	public function getStatus(ZipArchive $zip): int
	{
		return $zip->status;
	}

	/**
	 * Set a comment for the ZIP archive.
	 *
	 * @param ZipArchive $zip The opened ZipArchive instance.
	 * @param string $comment The comment to set.
	 * @return bool True on success, false on failure.
	 */
	public function setComment(ZipArchive $zip, string $comment): bool
	{
		return $zip->setArchiveComment($comment);
	}

	/**
	 * Get the comment of the ZIP archive.
	 *
	 * @param ZipArchive $zip The opened ZipArchive instance.
	 * @return string The comment of the archive.
	 */
	public function getComment(ZipArchive $zip): string
	{
		return $zip->getArchiveComment();
	}
}
