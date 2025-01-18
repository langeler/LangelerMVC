<?php

declare(strict_types=1);

namespace App\Abstracts\Http;

use RuntimeException; // Exception thrown if an error occurs at runtime.
use Throwable;        // Base interface for all errors and exceptions in PHP.

use App\Utilities\Sanitation\{
	GeneralSanitizer,  // Provides general data sanitation utilities.
	PatternSanitizer   // Facilitates pattern-based data sanitation.
};

use App\Utilities\Validation\{
	GeneralValidator,  // Provides general data validation utilities.
	PatternValidator   // Facilitates pattern-based data validation.
};

use App\Utilities\Managers\FileManager;         // Manages file operations and configurations.
use App\Utilities\Finders\DirectoryFinder;      // Handles searching and managing directories.

use App\Utilities\Traits\{
	ArrayTrait,        // Provides utility methods for array operations.
	TypeCheckerTrait   // Offers utilities for validating and checking data types.
};

/**
 * Abstract Request Class
 *
 * Responsibilities:
 * - Represent and store HTTP request data (queries, POST fields, files, headers).
 * - Provide hooks and utilities for data sanitation and validation.
 * - Offer methods for file processing, including validation, moving, resizing, and metadata stripping.
 * - Locate and use a designated storage directory for file uploads.
 *
 * Aligns with Updated Classes:
 * - Uses strict types.
 * - Property promotion and typed return values.
 * - Abstract methods now include explicit return types to match modern best practices.
 */
abstract class Request
{
	use ArrayTrait;
	use TypeCheckerTrait;

	/**
	 * The storage directory path for uploaded files.
	 */
	protected string $storage;

	/**
	 * Constructor
	 *
	 * @param GeneralSanitizer   $generalSanitizer   Utility to apply general sanitation rules.
	 * @param PatternSanitizer   $patternSanitizer   Utility to apply pattern-based sanitation.
	 * @param GeneralValidator   $generalValidator   Utility to apply general validation rules.
	 * @param PatternValidator   $patternValidator   Utility to apply pattern-based validation.
	 * @param FileManager        $fileManager        Utility to handle file operations (exists, move, size, etc.).
	 * @param DirectoryFinder    $directoryFinder    Utility to locate directories on the filesystem.
	 * @param array              $data               The request data (e.g., POST/GET parameters).
	 * @param array              $files              The uploaded files metadata array.
	 * @param array              $settings           Configuration for file handling (extensions, max size, etc.).
	 * @param array              $headers            The HTTP headers.
	 */
	public function __construct(
		protected GeneralSanitizer $generalSanitizer,
		protected PatternSanitizer $patternSanitizer,
		protected GeneralValidator $generalValidator,
		protected PatternValidator $patternValidator,
		protected FileManager $fileManager,
		protected DirectoryFinder $directoryFinder,
		protected array $data = [],
		protected array $files = [],
		protected array $settings = [
			'ext' => ['jpg', 'png', 'pdf'],
			'max' => 2048, // KB
			'resize' => ['w' => 800, 'h' => 600],
			'strip' => true,
		],
		protected array $headers = []
	) {
		$this->initialize();
	}

	/**
	 * Initialization logic:
	 * Here we determine the storage directory for uploaded files.
	 * We expect an "Uploads" directory; otherwise, throw a RuntimeException.
	 */
	protected function initialize(): void
	{
		$this->storage = $this->wrapInTry(function (): string {
			$uploads = $this->directoryFinder->find(['name' => 'Uploads']);
			return $this->isSet($uploads[0] ?? null)
				? $uploads[0]
				: throw new RuntimeException("Uploads directory not found.");
		});
	}

	/**
	 * Utility method for consistent error handling.
	 *
	 * @param callable $callback The operation to attempt.
	 * @return mixed The result of the operation.
	 * @throws RuntimeException On failure.
	 */
	protected function wrapInTry(callable $callback): mixed
	{
		try {
			return $callback();
		} catch (Throwable $e) {
			throw new RuntimeException("An error occurred: {$e->getMessage()}", 0, $e);
		}
	}

	/**
	 * Apply sanitation rules to given data.
	 *
	 * @param array  $data          The data to sanitize.
	 * @param string $sanitizerType The type of sanitizer ('general' or 'pattern').
	 * @return array Sanitized data.
	 */
	protected function applySanitation(array $data, string $sanitizerType = 'general'): array
	{
		return $this->wrapInTry(fn(): array => match ($sanitizerType) {
			'general' => $this->generalSanitizer->clean($data),
			'pattern' => $this->patternSanitizer->clean($data),
			default => throw new \InvalidArgumentException("Invalid sanitizer type: {$sanitizerType}")
		});
	}

	/**
	 * Apply validation rules to given data.
	 *
	 * @param array  $data          The data to validate.
	 * @param string $validatorType The type of validator ('general' or 'pattern').
	 * @return array Validated data.
	 */
	protected function applyValidation(array $data, string $validatorType = 'general'): array
	{
		return $this->wrapInTry(fn(): array => match ($validatorType) {
			'general' => $this->generalValidator->verify($data),
			'pattern' => $this->patternValidator->verify($data),
			default => throw new \InvalidArgumentException("Invalid validator type: {$validatorType}")
		});
	}

	/**
	 * Sanitize data using general sanitization on each key/value pair.
	 *
	 * @param array $data Input data to sanitize.
	 * @return array Sanitized data.
	 */
	protected function sanitizeData(array $data): array
	{
		return $this->reduce(
			$data,
			fn(array $carry, mixed $value, string $key): array =>
				$carry + [$key => $this->applySanitation([$key => $value], 'general')],
			[]
		);
	}

	/**
	 * Validate data using general validation rules on each key/value pair.
	 *
	 * @param array $data Input data to validate.
	 * @return array Validated data.
	 */
	protected function validateData(array $data): array
	{
		return $this->reduce(
			$data,
			fn(array $carry, mixed $value, string $key): array =>
				$carry + [$key => $this->applyValidation([$key => $value], 'general')],
			[]
		);
	}

	/**
	 * Validate a specific uploaded file based on allowed extensions, size, etc.
	 *
	 * @param string $key The file key in $this->files.
	 * @return bool True if the file passes all checks; otherwise, throws on failure.
	 */
	protected function validateFile(string $key): bool
	{
		return $this->wrapInTry(fn(): bool =>
			$this->isSet($this->files[$key] ?? null)
			&& $this->fileManager->fileExists($this->files[$key]['tmp_name'])
			&& $this->inArray(
				$this->fileManager->getExtension($this->files[$key]['tmp_name']),
				$this->getUnique($this->settings['ext'])
			)
			&& $this->fileManager->getSize($this->files[$key]['tmp_name']) <= ($this->settings['max'] * 1024)
		);
	}

	/**
	 * Process a validated file by moving it to the storage directory with a sanitized filename.
	 *
	 * @param string $key The file key to process.
	 * @return string The normalized path of the moved file.
	 */
	protected function processFile(string $key): string
	{
		return $this->wrapInTry(fn(): string =>
			$this->validateFile($key)
				? ($this->fileManager->moveFile(
					$this->files[$key]['tmp_name'],
					$this->fileManager->normalizePath("{$this->storage}/{$this->sanitizeFileName($this->files[$key]['name'])}")
				  )
					? $this->fileManager->normalizePath("{$this->storage}/{$this->sanitizeFileName($this->files[$key]['name'])}")
					: throw new RuntimeException("Failed to save file '{$key}'."))
				: throw new RuntimeException("File '{$key}' did not pass validation.")
		);
	}

	/**
	 * Sanitize the file name before storing it, avoiding problematic characters.
	 *
	 * @param string $fileName The original filename.
	 * @return string Sanitized filename.
	 */
	protected function sanitizeFileName(string $fileName): string
	{
		return $this->applySanitation(['fileName' => $fileName], 'general')['fileName'];
	}

	/**
	 * Process an image by resizing and optionally stripping metadata.
	 *
	 * @param string $key The file key to process as an image.
	 * @return string The path to the processed image.
	 */
	protected function processImage(string $key): string
	{
		return $this->wrapInTry(fn(): string =>
			$this->settings['strip']
				? $this->fileManager->stripMetadata(
					$this->fileManager->resizeImage(
						$this->processFile($key),
						$this->settings['resize']['w'],
						$this->settings['resize']['h']
					)
				)
				: $this->fileManager->resizeImage(
					$this->processFile($key),
					$this->settings['resize']['w'],
					$this->settings['resize']['h']
				)
		);
	}

	/**
	 * Retrieve file metadata such as name, size, extension, and path.
	 *
	 * @param string $key The file key.
	 * @return array<string,mixed> Associative array with file metadata.
	 */
	protected function getFileMetadata(string $key): array
	{
		return $this->filter(
			[
				'name' => $this->fileManager->getBaseName($this->files[$key]['tmp_name']),
				'size' => $this->fileManager->getSize($this->files[$key]['tmp_name']),
				'ext' => $this->fileManager->getExtension($this->files[$key]['tmp_name']),
				'path' => $this->fileManager->getRealPath($this->files[$key]['tmp_name']),
			],
			fn(mixed $value): bool => $this->isSet($value)
		);
	}
}
