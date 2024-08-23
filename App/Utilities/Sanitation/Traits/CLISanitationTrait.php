<?php

namespace App\Utilities\Sanitation\Traits;

trait CLISanitationTrait
{
	use BaseSanitationTrait;

	/**
	 * Sanitize command-line inputs to prevent shell injection.
	 *
	 * @param string $input
	 * @return string
	 */
	public function sanitizeCliInput(string $input): string
	{
		return escapeshellarg($input);
	}

	/**
	 * Remove unsafe characters from CLI arguments.
	 *
	 * @param string $argument
	 * @return string
	 */
	public function sanitizeCliArgument(string $argument): string
	{
		return preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $argument);
	}

	/**
	 * Securely handle script execution from the command line.
	 *
	 * @param string $script
	 * @return string
	 */
	public function sanitizeScriptExecution(string $script): string
	{
		return $this->sanitizeText($script);
	}

	/**
	 * Sanitize command aliases and shortcuts.
	 *
	 * @param string $alias
	 * @return string
	 */
	public function sanitizeCommandAlias(string $alias): string
	{
		return $this->sanitizeCliArgument($alias);
	}
}
