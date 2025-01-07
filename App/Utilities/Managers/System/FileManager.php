<?php

namespace App\Utilities\Managers;

use SplFileInfo;
use SplFileObject;
use Imagick;
use Throwable;

class FileManager
{
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

	// Basic File Operations

	public function getBaseName(string $path, string $suffix = ""): ?string
	{
		return $this->getFileInfo($path)?->getBasename($suffix);
	}

	public function copyFile(string $source, string $dest): bool
	{
		try {
			return $this->getFileInfo($source)?->isFile() && copy($source, $dest);
		} catch (Throwable) {
			return false;
		}
	}

	public function readLines(string $filename): ?array
	{
		try {
			return iterator_to_array($this->getFileObject($filename, 'r')?->rewind());
		} catch (Throwable) {
			return null;
		}
	}

	public function readContents(string $filename): ?string
	{
		try {
			return ($file = $this->getFileObject($filename, 'r'))
				&& ($size = $this->getFileInfo($filename)?->getSize() ?? 0) > 0
				? $file->fread($size)
				: '';
		} catch (Throwable) {
			return null;
		}
	}

	public function writeContents(string $filename, string $data): int|false
	{
		try {
			return ($file = $this->getFileObject($filename, 'w'))
				&& $file->flock(LOCK_EX)
				? ($bytesWritten = $file->fwrite($data)) && $file->flock(LOCK_UN)
					? $bytesWritten
					: false
				: false;
		} catch (Throwable) {
			return false;
		}
	}

	public function createDirectory(string $pathname, int $mode = 0777, bool $recursive = false): bool
	{
		try {
			return $this->getFileInfo($pathname)?->isDir() ?: mkdir($pathname, $mode, $recursive);
		} catch (Throwable) {
			return false;
		}
	}

	public function moveFile(string $filename, string $destination): bool
	{
		try {
			return $this->getFileObject($filename)?->isFile() && move_uploaded_file($filename, $destination);
		} catch (Throwable) {
			return false;
		}
	}

	public function deleteFile(string $filename): bool
	{
		try {
			return $this->getFileInfo($filename)?->isFile() && unlink($filename);
		} catch (Throwable) {
			return false;
		}
	}

	public function fileExists(string $filename): bool
	{
		return $this->getFileInfo($filename)?->isFile() ?? false;
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
			return $this->getFileObject($filename, 'r')?->eof() ? false : $this->getFileObject($filename, 'r')?->fgets();
		} catch (Throwable) {
			return false;
		}
	}

	public function writeLine(string $filename, string $content): int|false
	{
		try {
			return $this->getFileObject($filename, 'a')?->fwrite($content);
		} catch (Throwable) {
			return false;
		}
	}

	public function lockFile(string $filename, int $operation): bool
	{
		try {
			return $this->getFileObject($filename)?->flock($operation);
		} catch (Throwable) {
			return false;
		}
	}

	public function endOfFile(string $filename): bool
	{
		try {
			return $this->getFileObject($filename)?->eof();
		} catch (Throwable) {
			return false;
		}
	}

	public function resetPointer(string $filename): void
	{
		try {
			$this->getFileObject($filename)?->rewind();
		} catch (Throwable) {
			// Do nothing
		}
	}

	public function moveToLine(string $filename, int $line): void
	{
		try {
			$this->getFileObject($filename)?->seek($line);
		} catch (Throwable) {
			// Do nothing
		}
	}

	public function getLineNumber(string $filename): int
	{
		try {
			return $this->getFileObject($filename)?->key() ?? 0;
		} catch (Throwable) {
			return 0;
		}
	}

	public function getMaxLineLength(string $filename): int
	{
		try {
			return $this->getFileObject($filename)?->getMaxLineLen() ?? 0;
		} catch (Throwable) {
			return 0;
		}
	}

	public function setMaxLineLength(string $filename, int $length): void
	{
		try {
			$this->getFileObject($filename)?->setMaxLineLen($length);
		} catch (Throwable) {
			// Do nothing
		}
	}

	public function readCsv(string $filename, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): array|false
	{
		try {
			return $this->getFileObject($filename)?->fgetcsv($delimiter, $enclosure, $escape) ?? false;
		} catch (Throwable) {
			return false;
		}
	}

	public function writeCsv(string $filename, array $fields, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): int|false
	{
		try {
			return $this->getFileObject($filename, 'a')?->fputcsv($fields, $delimiter, $enclosure, $escape);
		} catch (Throwable) {
			return false;
		}
	}

	public function readBytes(string $filename, int $length): string|false
	{
		try {
			return $this->getFileObject($filename)?->fread($length);
		} catch (Throwable) {
			return false;
		}
	}

	public function writeBytes(string $filename, string $content): int|false
	{
		try {
			return $this->getFileObject($filename, 'a')?->fwrite($content);
		} catch (Throwable) {
			return false;
		}
	}

	public function getPosition(string $filename): int|false
	{
		try {
			return $this->getFileObject($filename)?->ftell();
		} catch (Throwable) {
			return false;
		}
	}

	public function setPosition(string $filename, int $offset, int $whence = SEEK_SET): int
	{
		try {
			return $this->getFileObject($filename)?->fseek($offset, $whence) ?? -1;
		} catch (Throwable) {
			return -1;
		}
	}

	public function getStats(string $filename): array|false
	{
		try {
			return $this->getFileObject($filename)?->fstat();
		} catch (Throwable) {
			return false;
		}
	}

	public function flush(string $filename): bool
	{
		try {
			return $this->getFileObject($filename)?->fflush();
		} catch (Throwable) {
			return false;
		}
	}

	public function truncate(string $filename, int $size): bool
	{
		try {
			return $this->getFileObject($filename, 'r+')?->ftruncate($size);
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
		return $this->getImagick($filename)?->writeImage($outputPath) ?? false;
	}

	public function resizeImage(string $filename, int $width, int $height, int $filter = Imagick::FILTER_LANCZOS, float $blur = 1.0): bool
	{
		return $this->getImagick($filename)?->resizeImage($width, $height, $filter, $blur) ?? false;
	}

	public function cropImage(string $filename, int $width, int $height, int $x, int $y): bool
	{
		return $this->getImagick($filename)?->cropImage($width, $height, $x, $y) ?? false;
	}

	public function rotateImage(string $filename, float $angle, string $backgroundColor = 'white'): bool
	{
		return $this->getImagick($filename)?->rotateImage(new ImagickPixel($backgroundColor), $angle) ?? false;
	}

	public function flipImage(string $filename): bool
	{
		return $this->getImagick($filename)?->flipImage() ?? false;
	}

	public function flopImage(string $filename): bool
	{
		return $this->getImagick($filename)?->flopImage() ?? false;
	}

	public function sharpenImage(string $filename, float $radius, float $sigma): bool
	{
		return $this->getImagick($filename)?->sharpenImage($radius, $sigma) ?? false;
	}

	public function blurImage(string $filename, float $radius, float $sigma): bool
	{
		return $this->getImagick($filename)?->blurImage($radius, $sigma) ?? false;
	}

	public function addText(string $filename, string $text, int $x = 10, int $y = 10, string $color = 'black', int $size = 12): bool
	{
		return $this->getImagick($filename)?->annotateImage((new ImagickDraw())->setFillColor(new ImagickPixel($color))->setFontSize($size), $x, $y, 0, $text) ?? false;
	}

	public function setCompressionQuality(string $filename, int $quality): bool
	{
		return $this->getImagick($filename)?->setImageCompressionQuality($quality) ?? false;
	}

	public function stripMetadata(string $filename): bool
	{
		return $this->getImagick($filename)?->stripImage() ?? false;
	}

	public function getFormat(string $filename): ?string
	{
		return $this->getImagick($filename)?->getImageFormat();
	}

	public function setFormat(string $filename, string $format): bool
	{
		return $this->getImagick($filename)?->setImageFormat($format) ?? false;
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
		return $this->getImagick($filename)?->evaluateImage(Imagick::EVALUATE_MULTIPLY, $opacity, Imagick::CHANNEL_ALPHA) ?? false;
	}

	public function compositeImage(string $filename, string $overlayFile, int $x = 0, int $y = 0, int $compositeType = Imagick::COMPOSITE_DEFAULT): bool
	{
		return $this->getImagick($filename)?->compositeImage(
			$this->getImagick($overlayFile),
			$compositeType,
			$x,
			$y
		) ?? false;
	}

	public function trimImage(string $filename, float $fuzz = 0.1): bool
	{
		return $this->getImagick($filename)?->trimImage($fuzz) ?? false;
	}

	public function addBorder(string $filename, string $color, int $width, int $height): bool
	{
		return $this->getImagick($filename)?->borderImage(new ImagickPixel($color), $width, $height) ?? false;
	}

	public function thumbnailImage(string $filename, int $width, int $height, bool $bestFit = false): bool
	{
		return $this->getImagick($filename)?->thumbnailImage($width, $height, $bestFit) ?? false;
	}

	public function getResolution(string $filename): ?array
	{
		return $this->getImagick($filename)?->getImageResolution();
	}

	public function setResolution(string $filename, float $xResolution, float $yResolution): bool
	{
		return $this->getImagick($filename)?->setImageResolution($xResolution, $yResolution) ?? false;
	}

	public function modulateImage(string $filename, float $brightness, float $saturation, float $hue): bool
	{
		return $this->getImagick($filename)?->modulateImage($brightness, $saturation, $hue) ?? false;
	}

	public function negateImage(string $filename, bool $grayscale = false): bool
	{
		return $this->getImagick($filename)?->negateImage($grayscale) ?? false;
	}

	public function setGamma(string $filename, float $gamma): bool
	{
		return $this->getImagick($filename)?->gammaImage($gamma) ?? false;
	}

	public function despeckleImage(string $filename): bool
	{
		return $this->getImagick($filename)?->despeckleImage() ?? false;
	}

	public function oilPaintImage(string $filename, float $radius): bool
	{
		return $this->getImagick($filename)?->oilPaintImage($radius) ?? false;
	}

	public function charcoalImage(string $filename, float $radius, float $sigma): bool
	{
		return $this->getImagick($filename)?->charcoalImage($radius, $sigma) ?? false;
	}

	public function embossImage(string $filename, float $radius, float $sigma): bool
	{
		return $this->getImagick($filename)?->embossImage($radius, $sigma) ?? false;
	}

	public function getColorspace(string $filename): ?int
	{
		return $this->getImagick($filename)?->getImageColorspace();
	}

	public function setColorspace(string $filename, int $colorspace): bool
	{
		return $this->getImagick($filename)?->setImageColorspace($colorspace) ?? false;
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
}
