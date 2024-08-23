<?php

namespace App\Utilities\Sanitation\Traits;

trait MediaSanitationTrait
{
	use BaseSanitationTrait;

	/**
	 * Remove unnecessary metadata from images.
	 *
	 * @param string $imagePath
	 * @return bool
	 */
	public function sanitizeImageMetadata(string $imagePath): bool
	{
		if (!extension_loaded('imagick')) {
			return false;
		}
		$image = new \Imagick($imagePath);
		$image->stripImage();
		return $image->writeImage($imagePath);
	}

	/**
	 * Compress media files for storage efficiency.
	 *
	 * @param string $filePath
	 * @param int $quality
	 * @return bool
	 */
	public function compressMediaFile(string $filePath, int $quality = 75): bool
	{
		if (!extension_loaded('imagick')) {
			return false;
		}
		$image = new \Imagick($filePath);
		$image->setImageCompressionQuality($quality);
		return $image->writeImage($filePath);
	}

	/**
	 * Sanitize media format, converting to standard representation.
	 *
	 * @param string $filePath
	 * @param string $targetFormat
	 * @return bool
	 */
	public function convertMediaFormat(string $filePath, string $targetFormat): bool
	{
		if (!extension_loaded('imagick')) {
			return false;
		}
		$image = new \Imagick($filePath);
		$image->setImageFormat($targetFormat);
		return $image->writeImage($filePath);
	}

	/**
	 * Apply watermark to images for protection.
	 *
	 * @param string $imagePath
	 * @param string $watermarkText
	 * @return bool
	 */
	public function applyWatermark(string $imagePath, string $watermarkText): bool
	{
		if (!extension_loaded('imagick')) {
			return false;
		}
		$image = new \Imagick($imagePath);
		$draw = new \ImagickDraw();
		$draw->setFillColor('gray');
		$draw->setFontSize(30);
		$draw->setGravity(\Imagick::GRAVITY_CENTER);
		$image->annotateImage($draw, 10, 10, 0, $watermarkText);
		return $image->writeImage($imagePath);
	}
}
