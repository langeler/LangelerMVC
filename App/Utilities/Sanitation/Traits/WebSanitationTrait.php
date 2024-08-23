<?php

namespace App\Utilities\Sanitation\Traits;

trait WebSanitationTrait
{
	use BaseSanitationTrait;

	/**
	 * Sanitize embedded scripts within HTML content.
	 *
	 * @param string $html
	 * @return string
	 */
	public function sanitizeScripts(string $html): string
	{
		return preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);
	}

	/**
	 * Sanitize CSS within HTML content.
	 *
	 * @param string $html
	 * @return string
	 */
	public function sanitizeCss(string $html): string
	{
		return preg_replace('#<style(.*?)>(.*?)</style>#is', '', $html);
	}

	/**
	 * Sanitize cross-origin resources to prevent CORS issues.
	 *
	 * @param string $resource
	 * @return string
	 */
	public function sanitizeCrossOriginResource(string $resource): string
	{
		return $this->sanitizeText($resource);
	}
}
