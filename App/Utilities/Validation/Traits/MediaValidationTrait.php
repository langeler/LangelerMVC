<?php

namespace App\Utilities\Validation\Traits;

trait MediaValidationTrait
{
	use BaseValidationTrait;

	/**
	 * Validate that an image meets minimum and maximum dimensions.
	 *
	 * @param string $imagePath
	 * @param int $minWidth
	 * @param int $minHeight
	 * @param int|null $maxWidth
	 * @param int|null $maxHeight
	 * @return bool
	 */
	public function validateImageDimensions(string $imagePath, int $minWidth, int $minHeight, ?int $maxWidth = null, ?int $maxHeight = null): bool
	{
		if (!extension_loaded('imagick')) {
			return false;
		}
		$image = new \Imagick($imagePath);
		$width = $image->getImageWidth();
		$height = $image->getImageHeight();

		$valid = $width >= $minWidth && $height >= $minHeight;
		if ($maxWidth !== null) {
			$valid = $valid && $width <= $maxWidth;
		}
		if ($maxHeight !== null) {
			$valid = $valid && $height <= $maxHeight;
		}

		return $valid;
	}

	/**
	 * Validate that a media file has an allowed format.
	 *
	 * @param string $filePath
	 * @param array $allowedFormats
	 * @return bool
	 */
	public function validateMediaFormat(string $filePath, array $allowedFormats): bool
	{
		if (!extension_loaded('imagick')) {
			return false;
		}
		$image = new \Imagick($filePath);
		$format = strtolower($image->getImageFormat());

		return in_array($format, $allowedFormats);
	}

	/**
	 * Validate that a video does not exceed the maximum allowed duration.
	 *
	 * @param string $videoPath
	 * @param int $maxDuration
	 * @return bool
	 */
	public function validateVideoDuration(string $videoPath, int $maxDuration): bool
	{
		if (!extension_loaded('ffmpeg')) {
			return false;
		}
		$ffprobe = \FFMpeg\FFProbe::create();
		$duration = $ffprobe->format($videoPath)->get('duration');

		return $duration <= $maxDuration;
	}

	/**
	 * Validate that a media file does not exceed the maximum allowed size.
	 *
	 * @param int $fileSize
	 * @param int $maxSize
	 * @return bool
	 */
	public function validateMediaFileSize(int $fileSize, int $maxSize): bool
	{
		return $fileSize <= $maxSize;
	}
}
