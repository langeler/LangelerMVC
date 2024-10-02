<?php

namespace App\Utilities\Validation;

use App\Abstracts\Data\Validator;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Traits\Patterns\Validation\MediaValidationPatternsTrait;

/**
 * Class MediaValidator
 *
 * Provides validation methods for media-related fields using regex patterns.
 */
class MediaValidator extends Validator
{
	use PatternTrait, MediaValidationPatternsTrait;

	/**
	 * === ENTRY POINT: validate method (Do not modify) ===
	 *
	 * @param mixed $data The data to be validated.
	 * @return array The validated data array.
	 */
	protected function verify(mixed $data): array
	{
		return $this->handle($data);
	}

	// === Basic Validation Using Patterns ===

	/**
	 * Validate if the input is a valid file name.
	 *
	 * @param string $input The input file name.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateFileName(string $input): bool
	{
		return $this->match($this->getPatterns('file_name'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid directory name.
	 *
	 * @param string $input The input directory name.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateDirectoryName(string $input): bool
	{
		return $this->match($this->getPatterns('directory_name'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid Unix file path.
	 *
	 * @param string $input The input Unix file path.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateFilePathUnix(string $input): bool
	{
		return $this->match($this->getPatterns('file_path_unix'), $input) === 1;
	}

	/**
	 * Validate if the input is a valid Windows file path.
	 *
	 * @param string $input The input Windows file path.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateFilePathWindows(string $input): bool
	{
		return $this->match($this->getPatterns('file_path_windows'), $input) === 1;
	}

	/**
	 * Validate if the input has a valid file extension.
	 *
	 * @param string $input The input file name with extension.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateFileExtension(string $input): bool
	{
		return $this->match($this->getPatterns('file_extension'), $input) === 1;
	}

	/**
	 * Validate if the input has a valid image file extension.
	 *
	 * @param string $input The input file name with image extension.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateImageFileExtension(string $input): bool
	{
		return $this->match($this->getPatterns('image_file_extension'), $input) === 1;
	}

	/**
	 * Validate if the input has a valid audio file extension.
	 *
	 * @param string $input The input file name with audio extension.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateAudioFileExtension(string $input): bool
	{
		return $this->match($this->getPatterns('audio_file_extension'), $input) === 1;
	}

	/**
	 * Validate if the input has a valid video file extension.
	 *
	 * @param string $input The input file name with video extension.
	 * @return bool Returns true if valid, false otherwise.
	 */
	public function validateVideoFileExtension(string $input): bool
	{
		return $this->match($this->getPatterns('video_file_extension'), $input) === 1;
	}
}
