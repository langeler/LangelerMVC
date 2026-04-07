<?php

declare(strict_types=1);

namespace App\Abstracts\Http;

use App\Contracts\Http\RequestInterface;
use App\Utilities\Sanitation\{
	GeneralSanitizer,  // Provides general data sanitation utilities.
	PatternSanitizer   // Facilitates pattern-based data sanitation.
};

use App\Utilities\Validation\{
	GeneralValidator,  // Provides general data validation utilities.
	PatternValidator   // Facilitates pattern-based data validation.
};

use App\Exceptions\Http\RequestException;

use App\Utilities\Managers\FileManager;         // Manages file operations and configurations.
use App\Utilities\Finders\DirectoryFinder;      // Handles searching and managing directories.

use App\Utilities\Traits\{
	ArrayTrait,        // Provides utility methods for array operations.
	ErrorTrait         // Offers framework-aligned exception wrapping.
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
abstract class Request implements RequestInterface
{
	use ArrayTrait, ErrorTrait;

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
			$path = is_array($uploads) && $uploads !== []
				? array_key_first($uploads)
				: null;

			if ($this->isString($path) && $this->fileManager->isDirectory($path)) {
				return $path;
			}

			$fallback = $this->fileManager->normalizePath(
				(realpath(dirname(__DIR__, 3)) ?: dirname(__DIR__, 3)) . '/Storage/Uploads'
			);

			if (
				$this->fileManager->isDirectory($fallback)
				|| $this->fileManager->createDirectory($fallback, 0777, true)
			) {
				return $fallback;
			}

			throw new RequestException('Uploads directory not found.');
		}, RequestException::class);
	}

	/**
	 * Sanitize request data using framework-defined rules.
	 *
	 * @return void
	 */
	public function sanitize(): void
	{
		$this->wrapInTry(function (): void {
			$rules = $this->sanitizationRules();

			if ($rules !== []) {
				$this->data = $this->sanitizeData($this->data, $rules);
			}
		}, RequestException::class);
	}

	/**
	 * Validate request data using framework-defined rules.
	 *
	 * @return void
	 */
	public function validate(): void
	{
		$this->wrapInTry(function (): void {
			$rules = $this->validationRules();

			if ($rules !== []) {
				$this->validateData($this->data, $rules);
			}
		}, RequestException::class);
	}

	/**
	 * Transform request data after sanitation and validation.
	 *
	 * @return void
	 */
	public function transform(): void
	{
		$this->wrapInTry(function (): void {
			$transformed = $this->transformInput($this->data);

			if (!$this->isArray($transformed)) {
				throw new RequestException('Request transformation must return an array.');
			}

			$this->data = $transformed;
		}, RequestException::class);
	}

	/**
	 * Execute the full request lifecycle.
	 *
	 * @return void
	 */
	public function handle(): void
	{
		$this->wrapInTry(function (): void {
			$this->sanitize();
			$this->validate();
			$this->transform();
		}, RequestException::class);
	}

	/**
	 * Retrieve a request input value using dot notation.
	 */
	public function input(string $key, mixed $default = null): mixed
	{
		return $this->getNestedValue($this->data, $key, $default);
	}

	/**
	 * Retrieve the full request input payload.
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array
	{
		return $this->data;
	}

	/**
	 * Retrieve a file entry or all uploaded files.
	 */
	public function file(?string $key = null): mixed
	{
		if ($key === null || $key === '') {
			return $this->files;
		}

		return $this->getNestedValue($this->files, $key);
	}

	/**
	 * Retrieve a normalized request header.
	 */
	public function header(string $key, mixed $default = null): mixed
	{
		$headers = $this->headers();
		$normalizedKey = strtolower(trim($key));

		return array_key_exists($normalizedKey, $headers) ? $headers[$normalizedKey] : $default;
	}

	/**
	 * Retrieve all normalized request headers.
	 *
	 * @return array<string, mixed>
	 */
	public function headers(): array
	{
		return $this->wrapInTry(function (): array {
			$normalized = [];

			foreach ($this->headers as $key => $value) {
				$normalized[strtolower(trim((string) $key))] = $value;
			}

			return $normalized;
		}, RequestException::class);
	}

	/**
	 * Apply sanitation rules to given data.
	 *
	 * @param array  $data          The data to sanitize.
	 * @param string $sanitizerType The type of sanitizer ('general' or 'pattern').
	 * @return array Sanitized data.
	 */
	protected function applySanitation(array $schema, ?array $values = null, string $sanitizerType = 'general'): array
	{
		return $this->wrapInTry(fn(): array => match ($sanitizerType) {
			'general' => $this->generalSanitizer->clean($schema, $values),
			'pattern' => $this->patternSanitizer->clean($schema, $values),
			default => throw new \InvalidArgumentException("Invalid sanitizer type: {$sanitizerType}")
		}, RequestException::class);
	}

	/**
	 * Apply validation rules to given data.
	 *
	 * @param array  $data          The data to validate.
	 * @param string $validatorType The type of validator ('general' or 'pattern').
	 * @return array Validated data.
	 */
	protected function applyValidation(array $schema, ?array $values = null, string $validatorType = 'general'): array
	{
		return $this->wrapInTry(fn(): array => match ($validatorType) {
			'general' => $this->generalValidator->verify($schema, $values),
			'pattern' => $this->patternValidator->verify($schema, $values),
			default => throw new \InvalidArgumentException("Invalid validator type: {$validatorType}")
		}, RequestException::class);
	}

	/**
	 * Sanitize data using general sanitization on each key/value pair.
	 *
	 * @param array $data Input data to sanitize.
	 * @return array Sanitized data.
	 */
	protected function sanitizeData(array $data, ?array $schema = null): array
	{
		$schema ??= array_reduce(
			array_keys($data),
			fn(array $carry, string $key): array => $carry + [$key => ['string']],
			[]
		);

		return $this->applySanitation($schema, $data, 'general');
	}

	/**
	 * Validate data using general validation rules on each key/value pair.
	 *
	 * @param array $data Input data to validate.
	 * @return array Validated data.
	 */
	protected function validateData(array $data, array $schema): array
	{
		return $this->applyValidation($schema, $data, 'general');
	}

	/**
	 * Validate a specific uploaded file based on allowed extensions, size, etc.
	 *
	 * @param string $key The file key in $this->files.
	 * @return bool True if the file passes all checks; otherwise, throws on failure.
	 */
	protected function validateFile(string $key): bool
	{
		return $this->wrapInTry(function () use ($key): bool {
			$file = $this->files[$key] ?? null;

			if (
				!$this->isArray($file)
				|| !$this->isString($file['tmp_name'] ?? null)
				|| !$this->fileManager->fileExists($file['tmp_name'])
			) {
				return false;
			}

			$extensionSource = $this->isString($file['name'] ?? null)
				? $file['name']
				: $file['tmp_name'];
			$extension = strtolower((string) $this->fileManager->getExtension($extensionSource));
			$allowedExtensions = $this->map(
				fn(mixed $value): string => strtolower((string) $value),
				$this->unique($this->settings['ext'] ?? [])
			);

			return $this->isInArray($extension, $allowedExtensions, true)
				&& ($this->fileManager->getSize($file['tmp_name']) ?? 0) <= (($this->settings['max'] ?? 0) * 1024);
		}, RequestException::class);
	}

	/**
	 * Process a validated file by moving it to the storage directory with a sanitized filename.
	 *
	 * @param string $key The file key to process.
	 * @return string The normalized path of the moved file.
	 */
	protected function processFile(string $key): string
	{
		return $this->wrapInTry(function () use ($key): string {
			if (!$this->validateFile($key)) {
				throw new RequestException("File '{$key}' did not pass validation.");
			}

			$file = $this->files[$key];
			$targetPath = $this->fileManager->normalizePath(
				$this->storage . DIRECTORY_SEPARATOR . $this->sanitizeFileName((string) ($file['name'] ?? $key))
			);

			if (!$this->fileManager->moveFile((string) $file['tmp_name'], $targetPath)) {
				throw new RequestException("Failed to save file '{$key}'.");
			}

			return $targetPath;
		}, RequestException::class);
	}

	/**
	 * Sanitize the file name before storing it, avoiding problematic characters.
	 *
	 * @param string $fileName The original filename.
	 * @return string Sanitized filename.
	 */
	protected function sanitizeFileName(string $fileName): string
	{
		return $this->wrapInTry(function () use ($fileName): string {
			$sanitized = $this->patternSanitizer->sanitizeFileName(basename($fileName)) ?? '';
			$sanitized = trim($sanitized, '.');

			if ($sanitized === '' || !$this->patternValidator->validateFileName($sanitized)) {
				throw new RequestException("Invalid file name '{$fileName}'.");
			}

			return $sanitized;
		}, RequestException::class);
	}

	/**
	 * Process an image by resizing and optionally stripping metadata.
	 *
	 * @param string $key The file key to process as an image.
	 * @return string The path to the processed image.
	 */
	protected function processImage(string $key): string
	{
		return $this->wrapInTry(function () use ($key): string {
			$path = $this->processFile($key);

			$path = $this->fileManager->resizeImage(
				$path,
				(int) ($this->settings['resize']['w'] ?? 0),
				(int) ($this->settings['resize']['h'] ?? 0)
			) ?: throw new RequestException("Failed to resize image '{$key}'.");

			if ($this->settings['strip'] ?? false) {
				$path = $this->fileManager->stripMetadata($path)
					?: throw new RequestException("Failed to strip metadata for image '{$key}'.");
			}

			return $path;
		}, RequestException::class);
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

	/**
	 * Override to define request sanitation rules.
	 *
	 * @return array<string, mixed>
	 */
	protected function sanitizationRules(): array
	{
		return [];
	}

	/**
	 * Override to define request validation rules.
	 *
	 * @return array<string, mixed>
	 */
	protected function validationRules(): array
	{
		return [];
	}

	/**
	 * Override to transform request data after sanitation/validation.
	 *
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	protected function transformInput(array $data): array
	{
		return $data;
	}

	/**
	 * Resolve a nested value from an array using dot notation.
	 *
	 * @param array<string|int, mixed> $source
	 */
	protected function getNestedValue(array $source, string $key, mixed $default = null): mixed
	{
		if ($key === '') {
			return $source;
		}

		$value = $source;

		foreach (explode('.', $key) as $segment) {
			if (!is_array($value) || !array_key_exists($segment, $value)) {
				return $default;
			}

			$value = $value[$segment];
		}

		return $value;
	}
}
