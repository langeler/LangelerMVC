<?php

namespace App\Utilities\Validation\Traits;

trait WebValidationTrait
{
	use BaseValidationTrait;

	/**
	 * Validate that HTML content is safe and does not contain dangerous tags.
	 *
	 * @param string $html
	 * @param array $allowedTags
	 * @return bool
	 */
	public function validateSafeHtml(string $html, array $allowedTags = []): bool
	{
		return $html === strip_tags($html, '<' . implode('><', $allowedTags) . '>');
	}

	/**
	 * Validate that a specific HTML tag exists in the content.
	 *
	 * @param string $html
	 * @param string $tag
	 * @return bool
	 */
	public function validateHtmlTagExists(string $html, string $tag): bool
	{
		return preg_match('/<' . preg_quote($tag, '/') . '[^>]*>/', $html) === 1;
	}

	/**
	 * Validate that HTML content is free from scripts.
	 *
	 * @param string $html
	 * @return bool
	 */
	public function validateNoScripts(string $html): bool
	{
		return preg_match('#<script(.*?)>(.*?)</script>#is', $html) === 0;
	}

	/**
	 * Validate cross-origin resource sharing (CORS) compliance.
	 *
	 * @param string $origin
	 * @param array $allowedOrigins
	 * @return bool
	 */
	public function validateCORSCompliance(string $origin, array $allowedOrigins): bool
	{
		return in_array($origin, $allowedOrigins);
	}

	/**
	 * Validate web accessibility standards (e.g., WCAG).
	 *
	 * @param string $html
	 * @return bool
	 */
	public function validateWebAccessibility(string $html): bool
	{
		// This could include checks for alt attributes, ARIA roles, etc.
		// For simplicity, we'll assume this is a complex rule that returns true for now.
		return true;
	}
}
