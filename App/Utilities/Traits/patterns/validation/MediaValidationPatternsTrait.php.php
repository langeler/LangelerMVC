<?php

namespace App\Utilities\Traits\Patterns\Validation;

trait MediaValidationPatternsTrait
{
	public array $patterns = [
		'file_name' => "/^[^\/:*?\"<>|]+\.[a-zA-Z0-9]+$/",
		'directory_name' => "/^[^\/:*?\"<>|]+$/",
		'file_path_unix' => "/^(\/[^\/ ]*)+\/?$/",
		'file_path_windows' => "/^[a-zA-Z]:\\[\\\S|*\S]?.*$/",
		'file_extension' => "/\.[a-zA-Z0-9]+$/",
		'image_file_extension' => "/\.(jpg|jpeg|png|gif|bmp|webp)$/i",
		'audio_file_extension' => "/\.(mp3|wav|flac|aac|ogg)$/i",
		'video_file_extension' => "/\.(mp4|avi|mkv|mov)$/i",
	];

		/**
		 * Retrieve the sanitization pattern by name.
		 */
		public function getPatterns(string $name): ?string
		{
			return $this->patterns[$name] ?? null;
		}
	}
