<?php

namespace App\Utilities\Sanitation;

use App\Abstracts\Data\Sanitizer;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Traits\Patterns\Sanitation\MediaSanitationPatternsTrait;

/**
 * Class MediaSanitizer
 *
 * Provides sanitation methods for media-related fields using regex patterns.
 */
class MediaSanitizer extends Sanitizer
{
	use PatternTrait, MediaSanitationPatternsTrait;

	/**
	 * === ENTRY POINT: sanitize method (Do not modify) ===
	 *
	 * @param mixed $data The data to be sanitized.
	 * @return array The sanitized data array.
	 */
	protected function clean(mixed $data): array
	{
		return $this->handle($data);
	}

	// === Basic Sanitation Using Patterns ===

	/**
	 * Sanitize a file name by removing invalid characters.
	 *
	 * @param string $input The input file name.
	 * @return string The sanitized file name.
	 */
	public function sanitizeFileName(string $input): string
	{
		return $this->replace($this->getPattern('file_name'), '', $input);
	}

	/**
	 * Sanitize a directory name by removing invalid characters.
	 *
	 * @param string $input The input directory name.
	 * @return string The sanitized directory name.
	 */
	public function sanitizeDirectoryName(string $input): string
	{
		return $this->replace($this->getPattern('directory_name'), '', $input);
	}

	/**
	 * Sanitize a Unix file path by removing invalid characters.
	 *
	 * @param string $input The input Unix file path.
	 * @return string The sanitized Unix file path.
	 */
	public function sanitizeFilePathUnix(string $input): string
	{
		return $this->replace($this->getPattern('file_path_unix'), '', $input);
	}

	/**
	 * Sanitize a Windows file path by removing invalid characters.
	 *
	 * @param string $input The input Windows file path.
	 * @return string The sanitized Windows file path.
	 */
	public function sanitizeFilePathWindows(string $input): string
	{
		return $this->replace($this->getPattern('file_path_windows'), '', $input);
	}

	/**
	 * Sanitize a file extension by removing invalid characters.
	 *
	 * @param string $input The input file extension.
	 * @return string The sanitized file extension.
	 */
	public function sanitizeFileExtension(string $input): string
	{
		return $this->replace($this->getPattern('file_extension'), '', $input);
	}

	/**
	 * Sanitize an image file extension by removing invalid characters.
	 *
	 * @param string $input The input image file extension.
	 * @return string The sanitized image file extension.
	 */
	public function sanitizeImageFileExtension(string $input): string
	{
		return $this->replace($this->getPattern('image_file_extension'), '', $input);
	}

	/**
	 * Sanitize an audio file extension by removing invalid characters.
	 *
	 * @param string $input The input audio file extension.
	 * @return string The sanitized audio file extension.
	 */
	public function sanitizeAudioFileExtension(string $input): string
	{
		return $this->replace($this->getPattern('audio_file_extension'), '', $input);
	}

	/**
	 * Sanitize a video file extension by removing invalid characters.
	 *
	 * @param string $input The input video file extension.
	 * @return string The sanitized video file extension.
	 */
	public function sanitizeVideoFileExtension(string $input): string
	{
		return $this->replace($this->getPattern('video_file_extension'), '', $input);
	}
}
