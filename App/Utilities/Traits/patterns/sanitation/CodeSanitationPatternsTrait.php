<?php

namespace App\Utilities\Traits\Patterns\Sanitation;

trait CodeSanitationPatternsTrait
{
	public array $patterns = [
		'hexadecimal' => '/[^0-9a-fA-Fx]/',
		'hex_only' => '/[^0-9a-fA-F]/',
		'binary' => '/[^01]/',
		'octal' => '/[^0-7]/',
		'html_comment' => '/<!--.*?-->/s',
	];


	/**
		 * Retrieve the sanitization pattern by name.
		 */
		public function getPattern(string $name): ?string
		{
			return $this->patterns[$name] ?? null;
		}
	}

