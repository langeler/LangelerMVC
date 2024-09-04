<?php

namespace App\Utilities\Managers;

use SplFileObject;
use SplFileInfo;
use Imagick;

/**
 * Class FileManager
 *
 * Provides utility methods for file and directory operations, file streams, and image processing using Imagick.
 */
class FileManager
{
	// Basic File Operations

	/**
	 * Get the base name of a file (with optional suffix removal).
	 *
	 * @param string $path The file path.
	 * @param string $suffix Optional. A suffix to remove from the file name.
	 * @return string The base name of the file.
	 */
	public function getBaseName(string $path, string $suffix = ""): string
	{
		return basename($path, $suffix);
	}

	/**
	 * Copy a file from source to destination.
	 *
	 * @param string $source The source file path.
	 * @param string $dest The destination file path.
	 * @return bool True on success, false on failure.
	 */
	public function copy(string $source, string $dest): bool
	{
		return copy($source, $dest);
	}

	/**
	 * Read a file into an array, where each element represents a line.
	 *
	 * @param string $filename The file to read.
	 * @return array An array of file lines.
	 */
	public function readFile(string $filename): array
	{
		return file($filename);
	}

	/**
	 * Read the contents of a file into a string.
	 *
	 * @param string $filename The file to read.
	 * @return string The file contents.
	 */
	public function readFileContents(string $filename): string
	{
		return file_get_contents($filename);
	}

	/**
	 * Write data to a file.
	 *
	 * @param string $filename The file to write to.
	 * @param string $data The data to write.
	 * @return int The number of bytes written.
	 */
	public function writeFileContents(string $filename, string $data): int
	{
		return file_put_contents($filename, $data);
	}

	/**
	 * Create a new directory.
	 *
	 * @param string $pathname The directory path.
	 * @param int $mode Optional. The mode (permissions) to apply (default: 0777).
	 * @param bool $recursive Optional. Whether to create directories recursively (default: false).
	 * @return bool True on success, false on failure.
	 */
	public function createDir(string $pathname, int $mode = 0777, bool $recursive = false): bool
	{
		return mkdir($pathname, $mode, $recursive);
	}

	/**
	 * Move an uploaded file to a new location.
	 *
	 * @param string $filename The temporary file name (as stored by PHP).
	 * @param string $destination The destination file path.
	 * @return bool True on success, false on failure.
	 */
	public function moveUploadedFile(string $filename, string $destination): bool
	{
		return move_uploaded_file($filename, $destination);
	}

	/**
	 * Delete a file.
	 *
	 * @param string $filename The file to delete.
	 * @return bool True on success, false on failure.
	 */
	public function deleteFile(string $filename): bool
	{
		return unlink($filename);
	}

	// SplFileObject Methods

	/**
	 * Open a file using SplFileObject.
	 *
	 * @param string $filename The file to open.
	 * @param string $mode Optional. The mode to open the file in (default: 'r').
	 * @return SplFileObject The SplFileObject instance.
	 */
	public function openFile(string $filename, string $mode = 'r'): SplFileObject
	{
		return new SplFileObject($filename, $mode);
	}

	/**
	 * Read a line from a file.
	 *
	 * @param SplFileObject $file The file object to read from.
	 * @return string The line read from the file.
	 */
	public function readLine(SplFileObject $file): string
	{
		return $file->fgets();
	}

	/**
	 * Write a line to a file.
	 *
	 * @param SplFileObject $file The file object to write to.
	 * @param string $content The content to write.
	 * @return int The number of bytes written.
	 */
	public function writeLine(SplFileObject $file, string $content): int
	{
		return $file->fwrite($content);
	}

	/**
	 * Lock or unlock a file.
	 *
	 * @param SplFileObject $file The file object to lock.
	 * @param int $operation The lock operation (e.g., LOCK_SH for shared lock).
	 * @return bool True on success, false on failure.
	 */
	public function lock(SplFileObject $file, int $operation): bool
	{
		return $file->flock($operation);
	}

	/**
	 * Get the file size in bytes.
	 *
	 * @param SplFileObject $file The file object.
	 * @return int The file size.
	 */
	public function getFileSize(SplFileObject $file): int
	{
		return $file->getSize();
	}

	/**
	 * Get the real path of a file.
	 *
	 * @param SplFileObject $file The file object.
	 * @return string The real path of the file.
	 */
	public function getRealPath(SplFileObject $file): string
	{
		return $file->getRealPath();
	}

	/**
	 * Rewind the file pointer to the beginning.
	 *
	 * @param SplFileObject $file The file object.
	 * @return void
	 */
	public function rewind(SplFileObject $file): void
	{
		$file->rewind();
	}

	// Imagick Methods

	/**
	 * Read an image file into an Imagick object.
	 *
	 * @param string $filename The image file path.
	 * @return Imagick The Imagick object.
	 */
	public function readImage(string $filename): Imagick
	{
		$image = new Imagick();
		$image->readImage($filename);
		return $image;
	}

	/**
	 * Write an Imagick object to a file.
	 *
	 * @param Imagick $image The Imagick object.
	 * @param string $filename The destination file path.
	 * @return bool True on success, false on failure.
	 */
	public function writeImage(Imagick $image, string $filename): bool
	{
		return $image->writeImage($filename);
	}

	/**
	 * Resize an image using Imagick.
	 *
	 * @param Imagick $image The Imagick object.
	 * @param int $width The new width.
	 * @param int $height The new height.
	 * @param int $filter Optional. The filter to use for resizing (default: Lanczos).
	 * @param float $blur Optional. The blur factor (default: 1).
	 * @param bool $bestfit Optional. Whether to maintain aspect ratio (default: false).
	 * @return bool True on success, false on failure.
	 */
	public function resizeImage(Imagick $image, int $width, int $height, int $filter = Imagick::FILTER_LANCZOS, float $blur = 1, bool $bestfit = false): bool
	{
		return $image->resizeImage($width, $height, $filter, $blur, $bestfit);
	}

	/**
	 * Crop an image using Imagick.
	 *
	 * @param Imagick $image The Imagick object.
	 * @param int $width The crop width.
	 * @param int $height The crop height.
	 * @param int $x The x-coordinate for the crop.
	 * @param int $y The y-coordinate for the crop.
	 * @return bool True on success, false on failure.
	 */
	public function cropImage(Imagick $image, int $width, int $height, int $x, int $y): bool
	{
		return $image->cropImage($width, $height, $x, $y);
	}

	/**
	 * Strip image metadata using Imagick.
	 *
	 * @param Imagick $image The Imagick object.
	 * @return bool True on success, false on failure.
	 */
	public function stripImage(Imagick $image): bool
	{
		return $image->stripImage();
	}

	/**
	 * Clear an Imagick object from memory.
	 *
	 * @param Imagick $image The Imagick object to clear.
	 * @return void
	 */
	public function clearImage(Imagick $image): void
	{
		$image->clear();
	}

	/**
	 * Validate whether an Imagick object contains a valid image.
	 *
	 * @param Imagick $image The Imagick object.
	 * @return bool True if valid, false otherwise.
	 */
	public function isValidImage(Imagick $image): bool
	{
		return $image->valid();
	}
}
