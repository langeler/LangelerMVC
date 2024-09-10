<?php

namespace App\Utilities\Traits\Patterns\Validation;

trait CodeValidationPatternsTrait
{
	public array $patterns = [
		'hexadecimal' => "/^0x[0-9a-fA-F]+$/",
		'hex_only' => "/^[0-9a-fA-F]+$/",
		'binary' => "/^[01]+$/",
		'octal' => "/^[0-7]+$/",
		'html_comment' => "/<!--(.*?)-->/",
	];

		/**
		 * Retrieve the sanitization pattern by name.
		 */
		public function getPatterns(string $name): ?string
		{
			return $this->patterns[$name] ?? null;
		}
	}
