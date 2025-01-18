<?php

declare(strict_types=1);

namespace App\Abstracts\Presentation;

use RuntimeException; // Exception thrown if an error occurs that can only be found at runtime.
use Throwable;        // Base interface for all errors and exceptions in PHP.

use App\Utilities\Handlers\{
	DataHandler,       // Handles general data operations and processing.
	DateTimeHandler    // Provides utilities for handling and manipulating date and time.
};

use App\Utilities\Traits\{
	ArrayTrait,         // Provides utility methods for array operations.
	TypeCheckerTrait,   // Offers utilities for validating and checking data types.
	ManipulationTrait,  // Adds support for data manipulation tasks.
	MetricsTrait,       // Includes methods for measuring and analyzing data metrics.
	ConversionTrait     // Facilitates data type and format conversions.
};

/**
 * Abstract Presenter Class
 *
 * Responsibilities:
 * - Transform raw data into a presentation-ready format.
 * - Add computed properties, append metadata, and prepare a final payload for the view layer.
 * - Utilize DataHandler and DateTimeHandler for formatting and timestamps.
 * - Offer optional metric computations and data normalization.
 *
 * Boundaries:
 * - Does not handle HTTP requests, responses, or business logic.
 * - Strictly focused on data transformation for presentation purposes.
 *
 * Aligns with Updated Classes:
 * - Uses strict typing and typed return values for abstract methods.
 * - Constructor property promotion and typed properties where appropriate.
 */
abstract class Presenter
{
	use ArrayTrait;
	use TypeCheckerTrait;
	use ManipulationTrait;
	use MetricsTrait;
	use ConversionTrait;

	/**
	 * Constructor for initializing dependencies and raw data.
	 *
	 * @param array<string,mixed> $data          The raw data to prepare and transform.
	 * @param DataHandler         $dataHandler   Utility for advanced data processing.
	 * @param DateTimeHandler     $dateTimeHandler Utility for handling date/time formats.
	 */
	public function __construct(
		protected array $data = [],
		protected DataHandler $dataHandler,
		protected DateTimeHandler $dateTimeHandler
	) {
		$this->initializeState();
	}

	/**
	 * Shared state for data during the preparation lifecycle.
	 *
	 * @var array<string,mixed>
	 */
	protected array $state = [];

	/**
	 * Metadata appended to the prepared data.
	 *
	 * @var array<string,mixed>
	 */
	protected array $metadata = [];

	/**
	 * Abstract Protected Methods
	 */

	/**
	 * Transform raw data into a structure suitable for presentation.
	 *
	 * @return array<string,mixed>
	 */
	abstract protected function transform(): array;

	/**
	 * Add computed or derived properties to the transformed data.
	 *
	 * @return array<string,mixed>
	 */
	abstract protected function addComputedProperties(): array;

	/**
	 * Append metadata (e.g., pagination, timestamps) to the data.
	 *
	 * @return array<string,mixed>
	 */
	abstract protected function addMetadata(): array;

	/**
	 * Finalize and prepare the data for the view.
	 *
	 * @return array<string,mixed>
	 */
	abstract protected function prepare(): array;

	/**
	 * Retrieve a specific value from the prepared data or state.
	 *
	 * @param string     $key
	 * @param mixed|null $default Default value if the key is not found.
	 * @return mixed
	 */
	abstract protected function get(string $key, mixed $default = null): mixed;

	/**
	 * Centralized Protected Methods
	 */

	/**
	 * Initialize the presenter's state.
	 * Copies the initial raw data into $this->state.
	 *
	 * @return void
	 */
	protected function initializeState(): void
	{
		$this->state = $this->reduce(
			$this->data,
			fn(array $carry, mixed $value, string $key): array => $carry + [$key => $value],
			[]
		);
	}

	/**
	 * Handle consistent error handling with try-catch.
	 *
	 * @param callable $operation Operation to execute.
	 * @return mixed
	 * @throws RuntimeException On failure.
	 */
	protected function wrapInTry(callable $operation): mixed
	{
		try {
			return $operation();
		} catch (Throwable $e) {
			throw new RuntimeException("An error occurred: {$e->getMessage()}", $e->getCode(), $e);
		}
	}

	/**
	 * Format and process data using the DataHandler.
	 *
	 * @param array<string,mixed> $data   Data to format.
	 * @param string              $format The desired format (e.g., 'json', 'xml').
	 * @return mixed
	 */
	protected function formatData(array $data, string $format = 'json'): mixed
	{
		return $this->wrapInTry(fn(): mixed =>
			match ($format) {
				'json' => $this->dataHandler->jsonEncode($data),
				'xml' => $this->dataHandler->toXml($data),
				default => $data
			}
		);
	}

	/**
	 * Add metadata to the state.
	 *
	 * @param array<string,mixed> $additionalMetadata Metadata to merge.
	 * @return void
	 */
	protected function appendMetadata(array $additionalMetadata): void
	{
		$this->metadata = $this->merge($this->metadata, $additionalMetadata);
	}

	/**
	 * Add timestamps to the metadata using DateTimeHandler.
	 *
	 * @return void
	 */
	protected function addTimestamps(): void
	{
		$this->appendMetadata([
			'createdAt' => $this->dateTimeHandler->formatDateTime(
				$this->dateTimeHandler->createDateTime('now'),
				\DateTime::RFC3339
			),
		]);
	}

	/**
	 * Compute similarity or distance for data analysis.
	 *
	 * @param string $metric The type of computation ('similarity', 'distance').
	 * @param mixed  $value  The value to compute against.
	 * @return mixed The computed metric result.
	 */
	protected function computeMetric(string $metric, mixed $value): mixed
	{
		return $this->wrapInTry(fn(): mixed =>
			match ($metric) {
				'similarity' => $this->similarityScore($this->data[$value] ?? '', $value),
				'distance'   => $this->distance($this->data[$value] ?? '', $value),
				default       => null
			}
		);
	}

	/**
	 * Normalize the prepared data by removing null values and standardizing structure.
	 *
	 * @return array<string,mixed>
	 */
	protected function normalizeData(): array
	{
		return $this->filter($this->state, fn(mixed $value): bool => !$this->isNull($value));
	}

	/**
	 * Extract specific keys from the state.
	 *
	 * @param string[] $keys The keys to extract.
	 * @return array<string,mixed> Filtered array containing only the requested keys.
	 */
	protected function extractKeys(array $keys): array
	{
		return $this->wrapInTry(fn(): array =>
			$this->filter($this->state, fn(mixed $value, string $key): bool => $this->inArray($key, $keys), ARRAY_FILTER_USE_BOTH)
		);
	}

	/**
	 * Retrieve metadata appended during the preparation lifecycle.
	 *
	 * @return array<string,mixed>
	 */
	protected function getMetadata(): array
	{
		return $this->metadata;
	}
}
