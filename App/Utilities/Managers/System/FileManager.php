<?php

namespace App\Utilities\Managers\System;

use SplFileInfo;
use SplFileObject;
use Imagick;
use ImagickDraw;
use ImagickPixel;
use Throwable;
use App\Utilities\Traits\{
	ArrayTrait,
	CheckerTrait,
	ManipulationTrait,
	TypeCheckerTrait
};
use App\Utilities\Traits\Patterns\PatternTrait;

class FileManager
{
	use ArrayTrait, CheckerTrait, ManipulationTrait, PatternTrait, TypeCheckerTrait;

	/**
	 * Persistent file handles used by cursor-aware and locking operations.
	 *
	 * @var array<string, SplFileObject>
	 */
	private array $persistentFileObjects = [];

	public function __destruct()
	{
		$this->persistentFileObjects = [];
	}

	// Method to get SplFileInfo instance
	private function getFileInfo(string $path): ?SplFileInfo
	{
		try {
			return new SplFileInfo($path);
		} catch (Throwable) {
			return null;
		}
	}

	// Method to get SplFileObject instance
	private function getFileObject(string $filename, string $mode = 'r'): ?SplFileObject
	{
		try {
			return new SplFileObject($filename, $mode);
		} catch (Throwable) {
			return null;
		}
	}

	private function getPersistentFileObject(string $filename, bool $create = false): ?SplFileObject
	{
		$normalized = $this->normalizePath($filename);

		if ($normalized === '') {
			return null;
		}

		if ($this->keyExists($this->persistentFileObjects, $normalized)) {
			return $this->persistentFileObjects[$normalized];
		}

		if (!$create && !$this->fileExists($normalized)) {
			return null;
		}

		if ($create) {
			$directory = dirname($normalized);

			if (!$this->isDirectory($directory) && !$this->createDirectory($directory, 0777, true)) {
				return null;
			}
		}

		$file = $this->getFileObject($normalized, $create ? 'c+' : 'r+');

		if (!$file instanceof SplFileObject) {
			return null;
		}

		$this->persistentFileObjects[$normalized] = $file;

		return $file;
	}

	private function releasePersistentFileObject(string $filename): void
	{
		unset($this->persistentFileObjects[$this->normalizePath($filename)]);
	}

	// Basic File Operations

	public function getBaseName(string $path, string $suffix = ""): ?string
	{
		return $this->getFileInfo($path)?->getBasename($suffix);
	}

	public function copyFile(string $source, string $dest): bool
	{
		try {
			$normalizedSource = $this->normalizePath($source);
			$normalizedDestination = $this->normalizePath($dest);
			$directory = dirname($normalizedDestination);

			if (
				!$this->getFileInfo($normalizedSource)?->isFile()
				|| (
					!$this->isDirectory($directory)
					&& !$this->createDirectory($directory, 0777, true)
				)
			) {
				return false;
			}

			return copy($normalizedSource, $normalizedDestination);
		} catch (Throwable) {
			return false;
		}
	}

	public function readLines(string $filename): ?array
	{
		try {
			$file = $this->getFileObject($filename, 'r');

			if (!$file) {
				return null;
			}

			$file->rewind();
			$lines = [];

			while (!$file->eof()) {
				$line = $file->fgets();

				if ($line === '' && $file->eof()) {
					break;
				}

				$lines[] = $line;
			}

			return $lines;
		} catch (Throwable) {
			return null;
		}
	}

	public function readContents(string $filename): ?string
	{
		try {
			$file = $this->getFileObject($filename, 'r');
			$size = $this->getFileInfo($filename)?->getSize() ?? 0;

			if (!$file) {
				return null;
			}

			$file->rewind();

			return $size > 0 ? $file->fread($size) : '';
		} catch (Throwable) {
			return null;
		}
	}

	public function writeContents(string $filename, string $data): int|false
	{
		try {
			$normalized = $this->normalizePath($filename);
			$directory = dirname($normalized);

			if (!$this->isDirectory($directory) && !$this->createDirectory($directory, 0777, true)) {
				return false;
			}

			$tempFile = tempnam($directory, 'langeler-write-');

			if (!$this->isString($tempFile)) {
				return false;
			}

			$file = $this->getFileObject($tempFile, 'w');

			if (!$file || !$file->flock(LOCK_EX)) {
				if (is_file($tempFile)) {
					unlink($tempFile);
				}

				return false;
			}

			try {
				$bytes = $file->fwrite($data);
			} finally {
				$file->flock(LOCK_UN);
			}

			if ($bytes === false) {
				unlink($tempFile);
				return false;
			}

			$file = null;

			if (!$this->replaceFile($tempFile, $normalized, true)) {
				if (is_file($tempFile)) {
					unlink($tempFile);
				}

				return false;
			}

			return $bytes;
		} catch (Throwable) {
			return false;
		}
	}

	public function createDirectory(string $pathname, int $mode = 0777, bool $recursive = false): bool
	{
		try {
			return $this->getFileInfo($pathname)?->isDir() ?: @mkdir($pathname, $mode, $recursive);
		} catch (Throwable) {
			return false;
		}
	}

	public function moveFile(string $filename, string $destination): bool
	{
		try {
			$source = $this->normalizePath($filename);
			$target = $this->normalizePath($destination);
			$targetDirectory = dirname($target);

			if (!$this->fileExists($source)) {
				return false;
			}

			if (
				!$this->isDirectory($targetDirectory)
				&& !$this->createDirectory($targetDirectory, 0777, true)
			) {
				return false;
			}

			if (is_uploaded_file($source)) {
				$this->releasePersistentFileObject($source);
				$this->releasePersistentFileObject($target);
				return move_uploaded_file($source, $target);
			}

			return $this->replaceFile($source, $target, true);
		} catch (Throwable) {
			return false;
		}
	}

	public function deleteFile(string $filename): bool
	{
		try {
			$normalized = $this->normalizePath($filename);
			$deleted = $this->getFileInfo($normalized)?->isFile() && unlink($normalized);

			if ($deleted) {
				$this->releasePersistentFileObject($normalized);
			}

			return $deleted;
		} catch (Throwable) {
			return false;
		}
	}

	public function fileExists(string $filename): bool
	{
		return $this->getFileInfo($filename)?->isFile() ?? false;
	}

	public function isDirectory(string $path): bool
	{
		return $this->getFileInfo($path)?->isDir() ?? false;
	}

	/**
	 * Normalize a filesystem path using the current platform separator.
	 *
	 * @param string $path
	 * @return string
	 */
	public function normalizePath(string $path): string
	{
		$path = $this->replaceText(['\\', '/'], DIRECTORY_SEPARATOR, $this->trimString($path));

		if ($path === '') {
			return '';
		}

		$prefix = '';

		if ($this->match('/^[A-Za-z]:' . $this->quote(DIRECTORY_SEPARATOR, '/') . '/', $path) === 1) {
			$prefix = $this->substring($path, 0, 2);
			$path = $this->substring($path, 2);
		} elseif ($this->startsWith($path, DIRECTORY_SEPARATOR)) {
			$prefix = DIRECTORY_SEPARATOR;
			$path = ltrim($path, DIRECTORY_SEPARATOR);
		}

		$segments = [];

		foreach ($this->splitByPattern('#[\\\\/]#', $path) ?: [] as $segment) {
			if ($segment === '' || $segment === '.') {
				continue;
			}

			if ($segment === '..') {
				$this->pop($segments);
				continue;
			}

			$segments[] = $segment;
		}

		$normalized = $this->joinStrings(DIRECTORY_SEPARATOR, $segments);

		if ($prefix === DIRECTORY_SEPARATOR) {
			return $prefix . $normalized;
		}

		if ($prefix !== '') {
			return $normalized === ''
				? $prefix . DIRECTORY_SEPARATOR
				: $prefix . DIRECTORY_SEPARATOR . $normalized;
		}

		return $normalized;
	}

	private function replaceFile(string $source, string $target, bool $deleteSource = false): bool
	{
		$this->releasePersistentFileObject($source);
		$this->releasePersistentFileObject($target);

		if (rename($source, $target)) {
			return true;
		}

		if (!copy($source, $target)) {
			return false;
		}

		return !$deleteSource || unlink($source);
	}

	// Additional SplFileInfo Methods

	public function getSize(string $filename): ?int
	{
		return $this->getFileInfo($filename)?->getSize();
	}

	public function getRealPath(string $filename): ?string
	{
		return $this->getFileInfo($filename)?->getRealPath();
	}

	public function getPath(string $filename): ?string
	{
		return $this->getFileInfo($filename)?->getPath();
	}

	public function getPathname(string $filename): ?string
	{
		return $this->getFileInfo($filename)?->getPathname();
	}

	public function getExtension(string $filename): ?string
	{
		return $this->getFileInfo($filename)?->getExtension();
	}

	public function getFilename(string $filename): ?string
	{
		return $this->getFileInfo($filename)?->getFilename();
	}

	public function isReadable(string $filename): bool
	{
		return $this->getFileInfo($filename)?->isReadable() ?? false;
	}

	public function isWritable(string $filename): bool
	{
		return $this->getFileInfo($filename)?->isWritable() ?? false;
	}

	public function isExecutable(string $filename): bool
	{
		return $this->getFileInfo($filename)?->isExecutable() ?? false;
	}

	public function getInode(string $filename): ?int
	{
		return $this->getFileInfo($filename)?->getInode();
	}

	public function getOwner(string $filename): ?int
	{
		return $this->getFileInfo($filename)?->getOwner();
	}

	public function getGroup(string $filename): ?int
	{
		return $this->getFileInfo($filename)?->getGroup();
	}

	public function getPermissions(string $filename): ?int
	{
		return $this->getFileInfo($filename)?->getPerms();
	}

	// SplFileObject Methods

	public function readLine(string $filename): string|false
	{
		try {
			$file = $this->getPersistentFileObject($filename);

			return !$file instanceof SplFileObject || $file->eof()
				? false
				: $file->fgets();
		} catch (Throwable) {
			return false;
		}
	}

	public function writeLine(string $filename, string $content): int|false
	{
		try {
			$file = $this->getPersistentFileObject($filename, true);

			if (!$file instanceof SplFileObject) {
				return false;
			}

			$file->fseek(0, SEEK_END);

			return $file->fwrite($content);
		} catch (Throwable) {
			return false;
		}
	}

	public function lockFile(string $filename, int $operation): bool
	{
		try {
			$file = $this->getPersistentFileObject($filename, $operation !== LOCK_SH);

			return $file instanceof SplFileObject
				? $file->flock($operation)
				: false;
		} catch (Throwable) {
			return false;
		}
	}

	public function endOfFile(string $filename): bool
	{
		try {
			return $this->getPersistentFileObject($filename)?->eof() ?? false;
		} catch (Throwable) {
			return false;
		}
	}

	public function resetPointer(string $filename): void
	{
		try {
			$this->getPersistentFileObject($filename)?->rewind();
		} catch (Throwable) {
			// Do nothing
		}
	}

	public function moveToLine(string $filename, int $line): void
	{
		try {
			$this->getPersistentFileObject($filename)?->seek($line);
		} catch (Throwable) {
			// Do nothing
		}
	}

	public function getLineNumber(string $filename): int
	{
		try {
			return $this->getPersistentFileObject($filename)?->key() ?? 0;
		} catch (Throwable) {
			return 0;
		}
	}

	public function getMaxLineLength(string $filename): int
	{
		try {
			return $this->getPersistentFileObject($filename)?->getMaxLineLen() ?? 0;
		} catch (Throwable) {
			return 0;
		}
	}

	public function setMaxLineLength(string $filename, int $length): void
	{
		try {
			$this->getPersistentFileObject($filename)?->setMaxLineLen($length);
		} catch (Throwable) {
			// Do nothing
		}
	}

	public function readCsv(string $filename, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): array|false
	{
		try {
			return $this->getPersistentFileObject($filename)?->fgetcsv($delimiter, $enclosure, $escape) ?? false;
		} catch (Throwable) {
			return false;
		}
	}

	public function writeCsv(string $filename, array $fields, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): int|false
	{
		try {
			$file = $this->getPersistentFileObject($filename, true);

			if (!$file instanceof SplFileObject) {
				return false;
			}

			$file->fseek(0, SEEK_END);

			return $file->fputcsv($fields, $delimiter, $enclosure, $escape);
		} catch (Throwable) {
			return false;
		}
	}

	public function readBytes(string $filename, int $length): string|false
	{
		try {
			return $this->getPersistentFileObject($filename)?->fread($length) ?? false;
		} catch (Throwable) {
			return false;
		}
	}

	public function writeBytes(string $filename, string $content): int|false
	{
		try {
			$file = $this->getPersistentFileObject($filename, true);

			if (!$file instanceof SplFileObject) {
				return false;
			}

			$file->fseek(0, SEEK_END);

			return $file->fwrite($content);
		} catch (Throwable) {
			return false;
		}
	}

	public function getPosition(string $filename): int|false
	{
		try {
			return $this->getPersistentFileObject($filename)?->ftell() ?? false;
		} catch (Throwable) {
			return false;
		}
	}

	public function setPosition(string $filename, int $offset, int $whence = SEEK_SET): int
	{
		try {
			return $this->getPersistentFileObject($filename)?->fseek($offset, $whence) ?? -1;
		} catch (Throwable) {
			return -1;
		}
	}

	public function getStats(string $filename): array|false
	{
		try {
			return $this->getPersistentFileObject($filename)?->fstat() ?? false;
		} catch (Throwable) {
			return false;
		}
	}

	public function flush(string $filename): bool
	{
		try {
			return $this->getPersistentFileObject($filename)?->fflush() ?? false;
		} catch (Throwable) {
			return false;
		}
	}

	public function truncate(string $filename, int $size): bool
	{
		try {
			$file = $this->getPersistentFileObject($filename, true);

			if (!$file instanceof SplFileObject) {
				return false;
			}

			$file->rewind();

			return $file->ftruncate($size);
		} catch (Throwable) {
			return false;
		}
	}

	public function closeAndFlush(SplFileObject &$file): bool
	{
		try {
			return $file->fflush();
		} finally {
			$file = null; // Ensure file handle is closed
		}
	}

	// Method to get an Imagick instance
	private function getImagick(string $filename): ?Imagick
	{
		try {
			return new Imagick($filename);
		} catch (Throwable) {
			return null;
		}
	}

	// Imagick Methods

	public function writeImage(string $filename, string $outputPath): bool
	{
		$normalizedOutputPath = $this->normalizePath($outputPath);
		$directory = dirname($normalizedOutputPath);

		if (
			!$this->isDirectory($directory)
			&& !$this->createDirectory($directory, 0777, true)
		) {
			return false;
		}

		return $this->withImagick(
			$filename,
			fn(Imagick $image): bool => $image->writeImage($normalizedOutputPath)
		);
	}

	public function resizeImage(
		string $filename,
		int $width,
		int $height,
		int $filter = Imagick::FILTER_LANCZOS,
		float $blur = 1.0
	): string|false
	{
		return $this->mutateImage(
			$filename,
			fn(Imagick $image): bool => $image->resizeImage($width, $height, $filter, $blur)
		);
	}

	public function cropImage(string $filename, int $width, int $height, int $x, int $y): bool
	{
		return $this->mutateImageInPlace(
			$filename,
			fn(Imagick $image): bool => $image->cropImage($width, $height, $x, $y)
		);
	}

	public function rotateImage(string $filename, float $angle, string $backgroundColor = 'white'): bool
	{
		return $this->mutateImageInPlace(
			$filename,
			fn(Imagick $image): bool => $image->rotateImage(new ImagickPixel($backgroundColor), $angle)
		);
	}

	public function flipImage(string $filename): bool
	{
		return $this->mutateImageInPlace(
			$filename,
			fn(Imagick $image): bool => $image->flipImage()
		);
	}

	public function flopImage(string $filename): bool
	{
		return $this->mutateImageInPlace(
			$filename,
			fn(Imagick $image): bool => $image->flopImage()
		);
	}

	public function sharpenImage(string $filename, float $radius, float $sigma): bool
	{
		return $this->mutateImageInPlace(
			$filename,
			fn(Imagick $image): bool => $image->sharpenImage($radius, $sigma)
		);
	}

	public function blurImage(string $filename, float $radius, float $sigma): bool
	{
		return $this->mutateImageInPlace(
			$filename,
			fn(Imagick $image): bool => $image->blurImage($radius, $sigma)
		);
	}

	public function addText(string $filename, string $text, int $x = 10, int $y = 10, string $color = 'black', int $size = 12): bool
	{
		return $this->mutateImageInPlace($filename, function (Imagick $image) use ($text, $x, $y, $color, $size): bool {
			$draw = new ImagickDraw();
			$draw->setFillColor(new ImagickPixel($color));
			$draw->setFontSize($size);

			return $image->annotateImage($draw, $x, $y, 0, $text);
		});
	}

	public function setCompressionQuality(string $filename, int $quality): bool
	{
		return $this->mutateImageInPlace(
			$filename,
			fn(Imagick $image): bool => $image->setImageCompressionQuality($quality)
		);
	}

	public function stripMetadata(string $filename): string|false
	{
		return $this->mutateImage(
			$filename,
			fn(Imagick $image): bool => $image->stripImage()
		);
	}

	public function getFormat(string $filename): ?string
	{
		return $this->getImagick($filename)?->getImageFormat();
	}

	public function setFormat(string $filename, string $format): bool
	{
		return $this->mutateImageInPlace(
			$filename,
			fn(Imagick $image): bool => $image->setImageFormat($format)
		);
	}

	public function getWidth(string $filename): ?int
	{
		return $this->getImagick($filename)?->getImageWidth();
	}

	public function getHeight(string $filename): ?int
	{
		return $this->getImagick($filename)?->getImageHeight();
	}

	public function setOpacity(string $filename, float $opacity): bool
	{
		return $this->mutateImageInPlace(
			$filename,
			fn(Imagick $image): bool => $image->evaluateImage(
				Imagick::EVALUATE_MULTIPLY,
				$opacity,
				Imagick::CHANNEL_ALPHA
			)
		);
	}

	public function compositeImage(string $filename, string $overlayFile, int $x = 0, int $y = 0, int $compositeType = Imagick::COMPOSITE_DEFAULT): bool
	{
		$overlay = $this->getImagick($overlayFile);

		if (!$overlay instanceof Imagick) {
			return false;
		}

		try {
			return $this->mutateImageInPlace(
				$filename,
				fn(Imagick $image): bool => $image->compositeImage($overlay, $compositeType, $x, $y)
			);
		} finally {
			$overlay->clear();
			$overlay->destroy();
		}
	}

	public function trimImage(string $filename, float $fuzz = 0.1): bool
	{
		return $this->mutateImageInPlace(
			$filename,
			fn(Imagick $image): bool => $image->trimImage($fuzz)
		);
	}

	public function addBorder(string $filename, string $color, int $width, int $height): bool
	{
		return $this->mutateImageInPlace(
			$filename,
			fn(Imagick $image): bool => $image->borderImage(new ImagickPixel($color), $width, $height)
		);
	}

	public function thumbnailImage(string $filename, int $width, int $height, bool $bestFit = false): bool
	{
		return $this->mutateImageInPlace(
			$filename,
			fn(Imagick $image): bool => $image->thumbnailImage($width, $height, $bestFit)
		);
	}

	public function getResolution(string $filename): ?array
	{
		return $this->getImagick($filename)?->getImageResolution();
	}

	public function setResolution(string $filename, float $xResolution, float $yResolution): bool
	{
		return $this->mutateImageInPlace(
			$filename,
			fn(Imagick $image): bool => $image->setImageResolution($xResolution, $yResolution)
		);
	}

	public function modulateImage(string $filename, float $brightness, float $saturation, float $hue): bool
	{
		return $this->mutateImageInPlace(
			$filename,
			fn(Imagick $image): bool => $image->modulateImage($brightness, $saturation, $hue)
		);
	}

	public function negateImage(string $filename, bool $grayscale = false): bool
	{
		return $this->mutateImageInPlace(
			$filename,
			fn(Imagick $image): bool => $image->negateImage($grayscale)
		);
	}

	public function setGamma(string $filename, float $gamma): bool
	{
		return $this->mutateImageInPlace(
			$filename,
			fn(Imagick $image): bool => $image->gammaImage($gamma)
		);
	}

	public function despeckleImage(string $filename): bool
	{
		return $this->mutateImageInPlace(
			$filename,
			fn(Imagick $image): bool => $image->despeckleImage()
		);
	}

	public function oilPaintImage(string $filename, float $radius): bool
	{
		return $this->mutateImageInPlace(
			$filename,
			fn(Imagick $image): bool => $image->oilPaintImage($radius)
		);
	}

	public function charcoalImage(string $filename, float $radius, float $sigma): bool
	{
		return $this->mutateImageInPlace(
			$filename,
			fn(Imagick $image): bool => $image->charcoalImage($radius, $sigma)
		);
	}

	public function embossImage(string $filename, float $radius, float $sigma): bool
	{
		return $this->mutateImageInPlace(
			$filename,
			fn(Imagick $image): bool => $image->embossImage($radius, $sigma)
		);
	}

	public function getColorspace(string $filename): ?int
	{
		return $this->getImagick($filename)?->getImageColorspace();
	}

	public function setColorspace(string $filename, int $colorspace): bool
	{
		return $this->mutateImageInPlace(
			$filename,
			fn(Imagick $image): bool => $image->setImageColorspace($colorspace)
		);
	}

	public function clearImage(string $filename): void
	{
		try {
			$this->getImagick($filename)?->clear();
		} catch (Throwable) {
			// Do nothing
		}
	}

	public function destroyImage(string $filename): void
	{
		try {
			$this->getImagick($filename)?->destroy();
		} catch (Throwable) {
			// Do nothing
		}
	}

	/**
	 * Apply an in-place image mutation and persist the result.
	 *
	 * @param string $filename
	 * @param callable $operation
	 * @return string|false
	 */
	private function mutateImage(string $filename, callable $operation): string|false
	{
		$image = $this->getImagick($filename);

		if (!$image) {
			return false;
		}

		try {
			if ($operation($image) === false) {
				return false;
			}

			return $image->writeImage($filename) ? $filename : false;
		} catch (Throwable) {
			return false;
		} finally {
			$image->clear();
			$image->destroy();
		}
	}

	private function mutateImageInPlace(string $filename, callable $operation): bool
	{
		return $this->mutateImage($filename, $operation) !== false;
	}

	private function withImagick(string $filename, callable $callback): mixed
	{
		$image = $this->getImagick($filename);

		if (!$image instanceof Imagick) {
			return false;
		}

		try {
			return $callback($image);
		} catch (Throwable) {
			return false;
		} finally {
			$image->clear();
			$image->destroy();
		}
	}
}
