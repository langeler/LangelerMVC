<?php

declare(strict_types=1);

namespace App\Abstracts\Http;

use App\Contracts\Http\ResponseInterface;
use App\Utilities\Handlers\{
	DataHandler        // Handles general data processing tasks.
};

use App\Utilities\Managers\DateTimeManager;
use App\Exceptions\Http\ResponseException;

use App\Utilities\Traits\{
	ArrayTrait,         // Provides utility methods for array operations.
	ErrorTrait,         // Provides framework-aligned exception wrapping.
	ManipulationTrait,  // Adds support for data manipulation tasks.
	EncodingTrait,      // Facilitates encoding and decoding operations.
	ConversionTrait     // Provides utilities for data type and format conversions.
};
use App\Utilities\Traits\Patterns\PatternTrait;

/**
 * Abstract Response Class
 *
 * Responsibilities:
 * - Represent and manipulate the HTTP response, including status, headers, and content.
 * - Validate and set an appropriate HTTP status code.
 * - Manage and normalize headers, including content-related and date-related headers.
 * - Determine the content type based on the content structure (string, array, XML).
 * - Transform the content into JSON, XML, or another format before sending.
 * - Provide an abstract `send()` method for subclasses to define how the response is actually sent.
 *
 * Alignment with Updated Classes:
 * - Uses `declare(strict_types=1);`
 * - Constructor property promotion and typed parameters/returns.
 * - Strict typed methods, matching modern PHP best practices.
 */
abstract class Response implements ResponseInterface
{
	use ErrorTrait,
		ArrayTrait,
		ManipulationTrait,
		EncodingTrait,
		ConversionTrait,
		PatternTrait {
		ManipulationTrait::pad insteadof ArrayTrait;
		ManipulationTrait::replace insteadof ArrayTrait, PatternTrait;
		ManipulationTrait::reverse insteadof ArrayTrait;
		ManipulationTrait::shuffle insteadof ArrayTrait;
		PatternTrait::split insteadof ManipulationTrait;
		ArrayTrait::pad as arrayPad;
		ArrayTrait::replace as arrayReplace;
		ArrayTrait::reverse as arrayReverse;
		ArrayTrait::shuffle as arrayShuffle;
		PatternTrait::match as private matchPattern;
	}

	/**
	 * Tracks whether Content-Type was explicitly set by caller code.
	 */
	protected bool $contentTypeExplicitlySet = false;

	/**
	 * Constructor to inject all dependencies.
	 *
	 * @param int             $status          Initial HTTP status code.
	 * @param array           $headers         Initial headers.
	 * @param mixed           $content         The response content (string, array, or object).
	 * @param DataHandler     $dataHandler     Utility for encoding data (JSON/XML).
	 * @param DateTimeManager $dateTimeManager Utility for formatting date/time headers.
	 */
	public function __construct(
		protected DataHandler $dataHandler,
		protected DateTimeManager $dateTimeManager,
		protected int $status = 200,
		protected array $headers = [],
		protected mixed $content = null
	) {
		$this->contentTypeExplicitlySet = $this->hasHeader('Content-Type', $this->headers);
		$this->initializeDefaultHeaders();
	}

	/**
	 * Initialize default HTTP headers.
	 *
	 * Sets default headers like Content-Type, Cache-Control, and Expires.
	 */
	protected function initializeDefaultHeaders(): void
	{
		$defaults = [
			'Content-Type' => 'text/html; charset=UTF-8',
			'Cache-Control' => 'no-cache, must-revalidate',
			'Expires' => $this->dateTimeManager->formatDateTime(
				$this->dateTimeManager->createDateTime('now -1 day'),
				\DateTime::RFC7231
			),
		];

		$this->headers = $this->arrayReplace($defaults, $this->headers);
	}

	/**
	 * Set the HTTP status code after validating it.
	 *
	 * @param int $status The desired HTTP status code.
	 * @return void
	 */
	public function setStatus(int $status): void
	{
		$this->status = $this->wrapInTry(
			fn(): int => $this->validateStatus($status),
			ResponseException::class
		);
	}

	/**
	 * Get the current HTTP status code.
	 */
	public function getStatus(): int
	{
		return $this->status;
	}

	/**
	 * Add or update a response header.
	 *
	 * @param string $key   Header name.
	 * @param string $value Header value.
	 * @return void
	 */
	public function addHeader(string $key, string $value): void
	{
		$normalizedKey = $this->trim($key);

		if ($this->toLower($normalizedKey) === 'content-type') {
			$this->contentTypeExplicitlySet = true;
		}

		$this->headers[$normalizedKey] = $this->trim($value);
	}

	/**
	 * Retrieve all headers in normalized form.
	 *
	 * @return array The normalized headers.
	 */
	public function getHeaders(): array
	{
		return $this->normalizeHeaders();
	}

	/**
	 * Set the response content.
	 *
	 * @param mixed $content The response content (string, array, or object).
	 * @return void
	 * @throws \RuntimeException If validation fails.
	 */
	public function setContent(mixed $content): void
	{
		$this->wrapInTry(function () use ($content): void {
			$this->validateContent($content);
			$this->content = $content;
		}, ResponseException::class);
	}

	/**
	 * Get the current response content.
	 *
	 * @return mixed The response content.
	 */
	public function getContent(): mixed
	{
		return $this->content;
	}

	/**
	 * Abstract method to send the response.
	 * Concrete subclasses must define how headers and content are actually sent.
	 *
	 * @return void
	 */
	abstract public function send(): void;

	/**
	 * Validate the HTTP status code.
	 *
	 * @param int $status The HTTP status code to validate.
	 * @return int The validated status code.
	 * @throws \InvalidArgumentException If invalid status code.
	 */
	protected function validateStatus(int $status): int
	{
		return match (true) {
			$status >= 100 && $status < 600 => $status,
			default => throw new \InvalidArgumentException("Invalid HTTP status code: {$status}."),
		};
	}

	/**
	 * Validate the response content.
	 *
	 * @param mixed $content The content to validate.
	 * @return void
	 * @throws \InvalidArgumentException If content type is invalid.
	 */
	protected function validateContent(mixed $content): void
	{
		if (
			!$this->isNull($content)
			&& !$this->isString($content)
			&& !$this->isArray($content)
			&& !$this->isObject($content)
			&& !$this->isBool($content)
			&& !$this->isInt($content)
			&& !$this->isFloat($content)
		) {
			throw new \InvalidArgumentException("Content must be a string, array, or object.");
		}
	}

	/**
	 * Normalize headers for consistency.
	 * Converts keys to lowercase and trims values.
	 *
	 * @return array The normalized headers.
	 */
	protected function normalizeHeaders(): array
	{
		$normalized = [];

		foreach ($this->headers as $key => $value) {
			$normalized[$this->toLower((string) $key)] = $this->trim((string) $value);
		}

		return $normalized;
	}

	/**
	 * Determine the content type based on the content structure.
	 *
	 * @return string The MIME type.
	 */
	protected function determineContentType(): string
	{
		return match (true) {
			$this->isXmlFormat($this->content) => 'application/xml',
			$this->isArray($this->content) || $this->isObject($this->content) => 'application/json',
			$this->isHtmlFormat($this->content) => 'text/html; charset=UTF-8',
			$this->isString($this->content) => 'text/plain; charset=UTF-8',
			default => 'application/octet-stream',
		};
	}

	/**
	 * Transform the content to the appropriate output format.
	 *
	 * @return string The transformed content.
	 */
	protected function transformContent(): string
	{
		return match ($this->determineContentType()) {
			'application/json' => $this->dataHandler->jsonEncode($this->content),
			'application/xml' => $this->isString($this->content)
				? $this->content
				: $this->dataHandler->toXml($this->content),
			default => $this->isNull($this->content) ? '' : (string) $this->content,
		};
	}

	/**
	 * Get the standard reason phrase for the current HTTP status code.
	 *
	 * @return string A human-readable status message.
	 */
	protected function getStatusMessage(): string
	{
		return match ($this->status) {
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			204 => 'No Content',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			403 => 'Forbidden',
			404 => 'Not Found',
			500 => 'Internal Server Error',
			503 => 'Service Unavailable',
			default => 'Unknown Status',
		};
	}

	/**
	 * Add standard date-related headers to the response.
	 *
	 * @return void
	 */
	protected function addDateHeaders(): void
	{
		$this->addHeader('Date', $this->dateTimeManager->formatDateTime(
			$this->dateTimeManager->createDateTime('now'),
			\DateTime::RFC7231
		));
		$this->addHeader('Last-Modified', $this->dateTimeManager->formatDateTime(
			$this->dateTimeManager->createDateTime('now -1 day'),
			\DateTime::RFC7231
		));
	}

	/**
	 * Check if the content is in XML format.
	 *
	 * @param mixed $content The content to check.
	 * @return bool True if XML format is detected, false otherwise.
	 */
	protected function isXmlFormat(mixed $content): bool
	{
		return $this->wrapInTry(
			fn(): bool => $this->dataHandler->isXmlFormat($content),
			ResponseException::class
		);
	}

	/**
	 * Prepare the response for sending by setting Content-Type and date-related headers.
	 *
	 * @return void
	 */
	public function prepareForSend(): void
	{
		if (!$this->contentTypeExplicitlySet) {
			$this->headers['Content-Type'] = $this->determineContentType();
		}

		$this->addDateHeaders();
	}

	/**
	 * Convert the response to an array format for debugging or testing.
	 *
	 * @return array {
	 *     "status": int,
	 *     "headers": array<string,string>,
	 *     "content": string
	 * }
	 */
	public function toArray(): array
	{
		return [
			'status' => $this->status,
			'headers' => $this->normalizeHeaders(),
			'content' => $this->transformContent(),
		];
	}

	/**
	 * Fluently update the response status.
	 */
	public function withStatus(int $status): static
	{
		$this->setStatus($status);

		return $this;
	}

	/**
	 * Fluently add a single header.
	 */
	public function withHeader(string $key, string $value): static
	{
		$this->addHeader($key, $value);

		return $this;
	}

	/**
	 * Fluently add multiple headers.
	 *
	 * @param array<string, string> $headers
	 */
	public function withHeaders(array $headers): static
	{
		foreach ($headers as $key => $value) {
			$this->addHeader((string) $key, (string) $value);
		}

		return $this;
	}

	/**
	 * Fluently update the response content.
	 */
	public function withContent(mixed $content): static
	{
		$this->setContent($content);

		return $this;
	}

	/**
	 * Mark the response as HTML.
	 */
	public function asHtml(string $content, int $status = 200, array $headers = []): static
	{
		return $this
			->withStatus($status)
			->withHeaders($headers)
			->withHeader('Content-Type', 'text/html; charset=UTF-8')
			->withContent($content);
	}

	/**
	 * Mark the response as plain text.
	 */
	public function asText(string $content, int $status = 200, array $headers = []): static
	{
		return $this
			->withStatus($status)
			->withHeaders($headers)
			->withHeader('Content-Type', 'text/plain; charset=UTF-8')
			->withContent($content);
	}

	/**
	 * Mark the response as JSON.
	 */
	public function asJson(array|object $content, int $status = 200, array $headers = []): static
	{
		return $this
			->withStatus($status)
			->withHeaders($headers)
			->withHeader('Content-Type', 'application/json')
			->withContent($content);
	}

	/**
	 * Mark the response as XML.
	 */
	public function asXml(mixed $content, int $status = 200, array $headers = []): static
	{
		return $this
			->withStatus($status)
			->withHeaders($headers)
			->withHeader('Content-Type', 'application/xml')
			->withContent($content);
	}

	/**
	 * Check if the current content looks like HTML.
	 */
	protected function isHtmlFormat(mixed $content): bool
	{
		if (!$this->isString($content) || $content === '') {
			return false;
		}

		return $this->matchPattern('/<([a-z][a-z0-9]*)\b[^>]*>/i', $content) === 1;
	}

	/**
	 * Determine if a header already exists, case-insensitively.
	 *
	 * @param array<string, mixed>|null $headers
	 */
	protected function hasHeader(string $header, ?array $headers = null): bool
	{
		$headers ??= $this->headers;
		$needle = $this->toLower($header);

		foreach ($this->getKeys($headers) as $key) {
			if ($this->toLower((string) $key) === $needle) {
				return true;
			}
		}

		return false;
	}
}
