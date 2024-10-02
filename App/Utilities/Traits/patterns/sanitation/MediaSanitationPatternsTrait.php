<?php

namespace App\Utilities\Traits\Patterns\Sanitation;

trait MediaSanitationPatternsTrait
{
	public array $patterns = [
		'file_name' => '/[^a-zA-Z0-9\-_\.]/',
		'directory_name' => '/[^a-zA-Z0-9\-_]/',
		'file_path_unix' => '/[^a-zA-Z0-9_\/\-\.]/',
		'file_path_windows' => '/[^a-zA-Z0-9_\\\-]/',
		'file_extension' => '/[^a-zA-Z0-9]/',
		'image_file_extension' => '/[^a-zA-Z0-9\._]/',
		'audio_file_extension' => '/[^a-zA-Z0-9\._]/',
		'video_file_extension' => '/[^a-zA-Z0-9\._]/',
	];

	/**
		 * Retrieve the sanitization pattern by name.
		 */
		public function getPattern(string $name): ?string
		{
			return $this->patterns[$name] ?? null;
		}
	}

