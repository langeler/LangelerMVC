<?php

declare(strict_types=1);

namespace App\Abstracts\Http;

use App\Utilities\Handlers\DataHandler;
use App\Utilities\Handlers\DateTimeHandler;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\TypeCheckerTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\EncodingTrait;
use App\Utilities\Traits\ConversionTrait;
use Throwable;

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
abstract class Response
{
	use ArrayTrait;
	use TypeCheckerTrait;
	use ManipulationTrait;
	use EncodingTrait;
	use ConversionTrait;

	/**
	 * Constructor to inject all dependencies.
	 *
	 * @param int             $status          Initial HTTP status code.
	 * @param array           $headers         Initial headers.
	 * @param mixed           $content         The response content (string, array, or object).
	 * @param DataHandler     $dataHandler     Utility for encoding data (JSON/XML).
	 * @param DateTimeHandler $dateTimeHandler Utility for formatting date/time headers.
	 */
	public function __construct(
		protected int $status = 200,
		protected array $headers = [],
		protected mixed $content = null,
		protected DataHandler $dataHandler,
		protected DateTimeHandler $dateTimeHandler
	) {
		$this->initializeDefaultHeaders();
	}

	/**
	 * Initialize default HTTP headers.
	 *
	 * Sets default headers like Content-Type, Cache-Control, and Expires.
	 */
	protected function initializeDefaultHeaders(): void
	{
		$this->headers = [
			'Content-Type' => 'text/html; charset=UTF-8',
			'Cache-Control' => 'no-cache, must-revalidate',
			'Expires' => $this->dateTimeHandler->formatDateTime(
				$this->dateTimeHandler->createDateTime('now -1 day'),
				\DateTime::RFC7231
			),
		];
	}

	/**
	 * Centralized error handling for callable operations.
	 *
	 * @param callable $callback The operation to execute.
	 * @return mixed The result of the operation.
	 * @throws \RuntimeException On failure.
	 */
	protected function wrapInTry(callable $callback): mixed
	{
		try {
			return $callback();
		} catch (Throwable $e) {
			throw new \RuntimeException("An error occurred: {$e->getMessage()}", $e->getCode(), $e);
		}
	}

	/**
	 * Set the HTTP status code after validating it.
	 *
	 * @param int $status The desired HTTP status code.
	 * @return void
	 */
	protected function setStatus(int $status): void
	{
		$this->status = $this->wrapInTry(fn(): int => $this->validateStatus($status));
	}

	/**
	 * Get the current HTTP status code.
	 */
	protected function getStatus(): int
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
	protected function addHeader(string $key, string $value): void
	{
		$this->headers[$this->trim($key)] = $this->trim($value);
	}

	/**
	 * Retrieve all headers in normalized form.
	 *
	 * @return array The normalized headers.
	 */
	protected function getHeaders(): array
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
	protected function setContent(mixed $content): void
	{
		$this->wrapInTry(function () use ($content): void {
			$this->validateContent($content);
			$this->content = $content;
		});
	}

	/**
	 * Get the current response content.
	 *
	 * @return mixed The response content.
	 */
	protected function getContent(): mixed
	{
		return $this->content;
	}

	/**
	 * Abstract method to send the response.
	 * Concrete subclasses must define how headers and content are actually sent.
	 *
	 * @return void
	 */
	abstract protected function send(): void;

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
		if (!$this->isString($content) && !$this->isArray($content) && !$this->isObject($content)) {
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
		return $this->map(
			fn($key, $value): array => [$this->toLowerCase($key) => $this->trim($value)],
			$this->headers
		);
	}

	/**
	 * Determine the content type based on the content structure.
	 *
	 * @return string The MIME type.
	 */
	protected function determineContentType(): string
	{
		return match (true) {
			$this->isString($this->content) => 'text/plain; charset=UTF-8',
			$this->isArray($this->content) => 'application/json',
			$this->isXmlFormat($this->content) => 'application/xml',
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
			'application/xml' => $this->dataHandler->toXml($this->content),
			default => (string)$this->content,
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
		$this->addHeader('Date', $this->dateTimeHandler->formatDateTime(
			$this->dateTimeHandler->createDateTime('now'),
			\DateTime::RFC7231
		));
		$this->addHeader('Last-Modified', $this->dateTimeHandler->formatDateTime(
			$this->dateTimeHandler->createDateTime('now -1 day'),
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
		return $this->wrapInTry(fn(): bool => $this->dataHandler->isXmlFormat($content));
	}

	/**
	 * Prepare the response for sending by setting Content-Type and date-related headers.
	 *
	 * @return void
	 */
	protected function prepareForSend(): void
	{
		$this->addHeader('Content-Type', $this->determineContentType());
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
	protected function toArray(): array
	{
		return [
			'status' => $this->status,
			'headers' => $this->normalizeHeaders(),
			'content' => $this->transformContent(),
		];
	}
}
