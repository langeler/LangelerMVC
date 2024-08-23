<?php

namespace App\Utilities\Sanitation\Traits;

trait BaseSanitationTrait
{
	/**
	 * Remove harmful characters and escape special characters.
	 *
	 * @param string $input
	 * @return string
	 */
	public function sanitizeText(string $input): string
	{
		return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}

	/**
	 * Remove non-numeric characters from input.
	 *
	 * @param string $input
	 * @return string
	 */
	public function sanitizeNumeric(string $input): string
	{
		return preg_replace('/[^0-9.]/', '', $input);
	}

	/**
	 * Normalize text inputs by trimming whitespace and optionally converting to lowercase.
	 *
	 * @param string $input
	 * @param bool $toLowerCase
	 * @return string
	 */
	public function normalizeText(string $input, bool $toLowerCase = true): string
	{
		$input = trim($input);
		return $toLowerCase ? strtolower($input) : $input;
	}

	/**
	 * Sanitize email addresses, ensuring they are properly formatted.
	 *
	 * @param string $email
	 * @return string
	 */
	public function sanitizeEmail(string $email): string
	{
		return filter_var($email, FILTER_SANITIZE_EMAIL);
	}

	/**
	 * Sanitize URLs by removing dangerous characters.
	 *
	 * @param string $url
	 * @return string
	 */
	public function sanitizeUrl(string $url): string
	{
		return filter_var($url, FILTER_SANITIZE_URL);
	}

	/**
	 * Sanitize input data for safe API transmission.
	 *
	 * @param string $data
	 * @return string
	 */
	public function sanitizeApiData(string $data): string
	{
		return $this->sanitizeText($data);
	}

	/**
	 * Strip dangerous HTML tags and attributes.
	 *
	 * @param string $html
	 * @param array $allowedTags
	 * @return string
	 */
	public function sanitizeHtml(string $html, array $allowedTags = []): string
	{
		$allowed = empty($allowedTags) ? '' : '<' . implode('><', $allowedTags) . '>';
		return strip_tags($html, $allowed);
	}

	/**
	 * Sanitize and secure embedded content.
	 *
	 * @param string $content
	 * @return string
	 */
	public function sanitizeEmbeddedContent(string $content): string
	{
		return $this->sanitizeText($content);
	}

	/**
	 * Sanitize file paths by removing dangerous characters.
	 *
	 * @param string $path
	 * @return string
	 */
	public function sanitizeFilePath(string $path): string
	{
		return rtrim(preg_replace('/[^a-zA-Z0-9_\-\/\.]/', '', $path), '/');
	}
}
