<?php

declare(strict_types=1);

namespace App\Abstracts\Presentation;

use App\Exceptions\Data\FinderException;          // Exception for errors occurring during finder operations.
use App\Exceptions\Presentation\ViewException;    // Exception for errors in presentation layer views.

use App\Helpers\TypeChecker;                      // Provides utility methods for type validation.

use App\Utilities\Finders\{
	FileFinder,      // Handles searching and managing files.
	DirectoryFinder  // Handles searching and managing directories.
};

use App\Utilities\Managers\{
	CacheManager,    // Manages caching operations and configurations.
	FileManager      // Manages file operations and configurations.
};

use App\Utilities\Sanitation\PatternSanitizer;    // Provides utilities for sanitizing data using patterns.
use App\Utilities\Validation\PatternValidator;    // Provides utilities for validating data using patterns.

/**
 * Abstract View Class
 *
 * Responsibilities:
 * - Locate and resolve template and resource file paths (layouts, pages, partials, components, assets).
 * - Provide an interface for rendering templates and caching them.
 * - Ensure all paths are sanitized, validated, and normalized.
 *
 * Boundaries:
 * - Does not handle business logic or HTTP directly; it only prepares resources for the presentation layer.
 * - Focused on filesystem interactions, template/resource location, and rendering contracts.
 *
 * Alignment with Updated Classes:
 * - Uses strict typing and typed return values.
 * - Constructor property promotion for dependencies.
 * - Utilizes custom exceptions for finder and view errors.
 */
abstract class View
{
	protected array $globals = [];
	protected string $templateExt = 'php';
	protected string $resourceExt = 'php';
	protected string $theme = 'default';

	private string $resourcesPath;
	private string $templatesPath;

	/**
	 * Constructor that injects all necessary dependencies using property promotion.
	 *
	 * @param FileFinder       $files        Finds files in directories.
	 * @param DirectoryFinder  $dirs         Finds directories.
	 * @param TypeChecker      $types        Utility for type and existence checks.
	 * @param CacheManager     $cache        Manages cached templates.
	 * @param FileManager      $fileManager  Handles file operations.
	 * @param PatternSanitizer $sanitizer    Sanitizes input paths or strings.
	 * @param PatternValidator $validator    Validates input data against given rules.
	 */
	public function __construct(
		private FileFinder $files,
		private DirectoryFinder $dirs,
		private TypeChecker $types,
		private CacheManager $cache,
		private FileManager $fileManager,
		private PatternSanitizer $sanitizer,
		private PatternValidator $validator
	) {
		$this->resourcesPath = $this->resolveBasePath('Resources');
		$this->templatesPath = $this->resolveBasePath('Templates');
	}

	/**
	 * Abstract methods for rendering various template types.
	 */
	abstract protected function renderLayout(string $layout, array $data = []): string;
	abstract protected function renderPage(string $page, array $data = []): string;
	abstract protected function renderPartial(string $partial, array $data = []): string;
	abstract protected function renderComponent(string $component, array $data = []): string;
	abstract protected function renderAsset(string $type, string $asset): string;

	/**
	 * Abstract methods for managing globals and cached templates.
	 */
	abstract protected function setGlobals(array $variables): void;
	abstract protected function getGlobals(): array;
	abstract protected function cacheTemplate(string $key, string $content, ?int $ttl = null): void;
	abstract protected function fetchCachedTemplate(string $key): ?string;

	/**
	 * Resolve the base directory for resources or templates.
	 *
	 * @param string $dirName The directory name to resolve.
	 * @return string The resolved and validated base path.
	 */
	private function resolveBasePath(string $dirName): string
	{
		return $this->wrapInTry(fn(): string =>
			$this->getValidPath(
				$this->dirs->find(['name' => $dirName])[0] ?? null,
				"Base directory '{$dirName}' not found."
			)
		);
	}

	/**
	 * Resolve subdirectories within base directories.
	 *
	 * @param string $basePath The base directory path.
	 * @param string $subDir   The subdirectory name.
	 * @return string The resolved subdirectory path.
	 */
	private function resolveSubDirPath(string $basePath, string $subDir): string
	{
		return $this->wrapInTry(fn(): string =>
			$this->getValidPath(
				$this->dirs->find(['name' => $subDir], $basePath)[0] ?? null,
				"Subdirectory '{$subDir}' not found in '{$basePath}'."
			)
		);
	}

	/**
	 * Resolve file paths within directories.
	 *
	 * @param string      $basePath The base path in which to find the file.
	 * @param string      $fileName The file name.
	 * @param string|null $ext      Optional extension to append.
	 * @return string The resolved file path.
	 */
	private function resolveFilePath(string $basePath, string $fileName, ?string $ext = null): string
	{
		return $this->wrapInTry(fn(): string =>
			$this->getValidPath(
				$this->sanitizeAndValidate(
					['path' => $basePath . DIRECTORY_SEPARATOR . $fileName . ($ext ? ".{$ext}" : '')],
					['path' => ['notEmpty' => true]]
				)['path'],
				"File '{$fileName}' not found in '{$basePath}'.",
				isFileCheck: true
			)
		);
	}

	/**
	 * Validate and normalize a path.
	 *
	 * @param string|null $path
	 * @param string      $errorMessage
	 * @param bool        $isFileCheck  If true, validates that $path is a file; otherwise checks for directory.
	 * @return string The validated and normalized path.
	 * @throws FinderException If the path is invalid.
	 */
	private function getValidPath(?string $path, string $errorMessage, bool $isFileCheck = false): string
	{
		if (
			!$this->types->isSet($path)
			|| ($isFileCheck && !$this->fileManager->fileExists($path))
			|| (!$isFileCheck && !$this->types->isDirectory($path))
		) {
			throw new FinderException($errorMessage);
		}

		return $this->normalizePath($path);
	}

	/**
	 * Sanitize and validate input data.
	 *
	 * @param array<string,mixed> $data  The data to sanitize and validate.
	 * @param array<string,array<string,mixed>> $rules Validation rules.
	 * @return array<string,mixed> The sanitized and validated data.
	 */
	private function sanitizeAndValidate(array $data, array $rules): array
	{
		return $this->validator->verify($this->sanitizer->clean($data), $rules);
	}

	/**
	 * Normalize file paths to a consistent format.
	 *
	 * @param string $path The path to normalize.
	 * @return string The normalized path.
	 */
	private function normalizePath(string $path): string
	{
		return $this->fileManager->getRealPath($path) ?? str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
	}

	/**
	 * Wrapper for handling exceptions with a consistent exception type.
	 *
	 * @param callable $callback The operation to attempt.
	 * @return mixed Result of the operation.
	 * @throws ViewException If an error occurs during operation.
	 */
	private function wrapInTry(callable $callback): mixed
	{
		try {
			return $callback();
		} catch (\Throwable $e) {
			throw new ViewException("Error occurred: {$e->getMessage()}", 0, $e);
		}
	}

	/**
	 * Fetch resource paths (e.g., CSS, JS, images).
	 */
	protected function getCssPath(string $file): string
	{
		return $this->resolveFilePath($this->resolveSubDirPath($this->resourcesPath, 'css'), $file);
	}

	protected function getJsPath(string $file): string
	{
		return $this->resolveFilePath($this->resolveSubDirPath($this->resourcesPath, 'js'), $file);
	}

	protected function getImagePath(string $file): string
	{
		return $this->resolveFilePath($this->resolveSubDirPath($this->resourcesPath, 'images'), $file);
	}

	/**
	 * Fetch template paths (layouts, pages, partials, components).
	 */
	protected function getLayoutPath(string $file): string
	{
		return $this->resolveFilePath($this->resolveSubDirPath($this->templatesPath, 'layouts'), $file, $this->templateExt);
	}

	protected function getPagePath(string $file): string
	{
		return $this->resolveFilePath($this->resolveSubDirPath($this->templatesPath, 'pages'), $file, $this->templateExt);
	}

	protected function getPartialPath(string $file): string
	{
		return $this->resolveFilePath($this->resolveSubDirPath($this->templatesPath, 'partials'), $file, $this->templateExt);
	}

	protected function getComponentPath(string $file): string
	{
		return $this->resolveFilePath($this->resolveSubDirPath($this->templatesPath, 'components'), $file, $this->templateExt);
	}
}
