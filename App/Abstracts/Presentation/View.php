<?php

namespace App\Abstracts\Presentation;

use App\Exceptions\Presentation\ViewException;

abstract class View
{
	protected string $viewPath;
	protected array $data = [];
	protected string $cacheDir = __DIR__ . '/cache';
	protected int $cacheExpiration = 3600; // Cache expiration time in seconds

	public function __construct(string $viewPath)
	{
		if (!file_exists($viewPath)) {
			throw new ViewException("View file $viewPath does not exist.");
		}
		$this->viewPath = $viewPath;

		// Create cache directory if it doesn't exist
		if (!is_dir($this->cacheDir)) {
			mkdir($this->cacheDir, 0755, true);
		}
	}

	/**
	 * Abstract method to be implemented by subclasses for rendering logic.
	 *
	 * @return string Rendered content
	 */
	abstract protected function render(): string;

	/**
	 * Set data to be passed into the view.
	 *
	 * @param array $data Data for the view
	 */
	protected function setData(array $data): void
	{
		$this->data = $data;
	}

	/**
	 * Render the view with the given data.
	 *
	 * @return string Rendered HTML content
	 */
	protected function renderView(): string
	{
		// Check if the view is cached
		$cacheKey = $this->getCacheKey($this->viewPath, $this->data);
		if ($this->isCached($cacheKey)) {
			return $this->getCachedView($cacheKey);
		}

		ob_start();
		extract($this->data);
		include $this->viewPath;
		$renderedView = ob_get_clean();

		// Cache the rendered view
		$this->cacheView($cacheKey, $renderedView);

		return $renderedView;
	}

	/**
	 * Escape output to prevent XSS attacks.
	 *
	 * @param string $data The data to escape
	 * @return string Escaped data
	 */
	protected function escape(string $data): string
	{
		return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Load and render a partial view.
	 *
	 * @param string $partialPath Path to the partial view
	 * @return string Rendered partial view
	 */
	protected function loadPartial(string $partialPath): string
	{
		if (!file_exists($partialPath)) {
			throw new ViewException("Partial view $partialPath does not exist.");
		}

		ob_start();
		include $partialPath;
		return ob_get_clean();
	}

	/**
	 * Cache the rendered view content.
	 *
	 * @param string $cacheKey Unique cache key for the view
	 * @param string $content The rendered view content
	 */
	protected function cacheView(string $cacheKey, string $content): void
	{
		$cacheFile = $this->getCacheFilePath($cacheKey);
		file_put_contents($cacheFile, $content);
	}

	/**
	 * Check if the view is cached and still valid.
	 *
	 * @param string $cacheKey Unique cache key for the view
	 * @return bool True if cached and valid, false otherwise
	 */
	protected function isCached(string $cacheKey): bool
	{
		$cacheFile = $this->getCacheFilePath($cacheKey);

		if (!file_exists($cacheFile)) {
			return false;
		}

		// Check if the cache has expired
		$fileTime = filemtime($cacheFile);
		return (time() - $fileTime) < $this->cacheExpiration;
	}

	/**
	 * Retrieve the cached view content.
	 *
	 * @param string $cacheKey Unique cache key for the view
	 * @return string Cached content
	 */
	protected function getCachedView(string $cacheKey): string
	{
		$cacheFile = $this->getCacheFilePath($cacheKey);
		return file_get_contents($cacheFile);
	}

	/**
	 * Generate a unique cache key based on the view path and data.
	 *
	 * @param string $viewPath Path to the view file
	 * @param array $data Data passed to the view
	 * @return string Unique cache key
	 */
	protected function getCacheKey(string $viewPath, array $data): string
	{
		return md5($viewPath . serialize($data));
	}

	/**
	 * Get the file path for the cached view.
	 *
	 * @param string $cacheKey Unique cache key for the view
	 * @return string Path to the cache file
	 */
	protected function getCacheFilePath(string $cacheKey): string
	{
		return $this->cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.cache';
	}

	/**
	 * Clear the cache for the view.
	 *
	 * @param string $cacheKey Unique cache key for the view
	 */
	protected function clearCache(string $cacheKey): void
	{
		$cacheFile = $this->getCacheFilePath($cacheKey);
		if (file_exists($cacheFile)) {
			unlink($cacheFile);
		}
	}
}
