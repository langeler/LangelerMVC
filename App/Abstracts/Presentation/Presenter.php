<?php

declare(strict_types=1);

namespace App\Abstracts\Presentation;

use App\Contracts\Presentation\PresenterInterface;
use App\Utilities\Handlers\{
	DataHandler        // Handles general data operations and processing.
};

use App\Utilities\Managers\DateTimeManager;
use App\Exceptions\Presentation\PresenterException;

use App\Utilities\Traits\{
	ArrayTrait,         // Provides utility methods for array operations.
	ErrorTrait,         // Provides framework-aligned exception wrapping.
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
 * - Utilize DataHandler and DateTimeManager for formatting and timestamps.
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
abstract class Presenter implements PresenterInterface
{
	use ErrorTrait, ArrayTrait, ManipulationTrait, MetricsTrait, ConversionTrait;

	/**
	 * Constructor for initializing dependencies and raw data.
	 *
	 * @param array<string,mixed> $data          The raw data to prepare and transform.
	 * @param DataHandler         $dataHandler   Utility for advanced data processing.
	 * @param DateTimeManager     $dateTimeManager Utility for handling date/time formats.
	 */
	public function __construct(
		protected DataHandler $dataHandler,
		protected DateTimeManager $dateTimeManager,
		protected array $data = []
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

	public function fill(array $data): static
	{
		$this->data = $data;
		$this->initializeState();
		$this->metadata = [];

		return $this;
	}

	/**
	 * Transform raw data into a structure suitable for presentation.
	 *
	 * @return array<string,mixed>
	 */
	public function transform(): array
	{
		return $this->wrapInTry(function (): array {
			$transformed = $this->transformData($this->data);

			if (!$this->isArray($transformed)) {
				throw new PresenterException('Presenter transformData() must return an array.');
			}

			$this->state = $transformed;

			return $this->state;
		}, PresenterException::class);
	}

	/**
	 * Add computed or derived properties to the transformed data.
	 *
	 * @return array<string,mixed>
	 */
	public function addComputedProperties(): array
	{
		return $this->wrapInTry(function (): array {
			$computed = $this->computeProperties($this->state);

			if (!$this->isArray($computed)) {
				throw new PresenterException('Presenter computeProperties() must return an array.');
			}

			$this->state = $this->merge($this->state, $computed);

			return $this->state;
		}, PresenterException::class);
	}

	/**
	 * Append metadata (e.g., pagination, timestamps) to the data.
	 *
	 * @return array<string,mixed>
	 */
	public function addMetadata(): array
	{
		return $this->wrapInTry(function (): array {
			$metadata = $this->buildMetadata($this->state);

			if (!$this->isArray($metadata)) {
				throw new PresenterException('Presenter buildMetadata() must return an array.');
			}

			if ($metadata !== []) {
				$this->appendMetadata($metadata);
			}

			if ($this->metadata !== []) {
				$existingMeta = $this->state['meta'] ?? [];
				$existingMeta = $this->isArray($existingMeta) ? $existingMeta : [];
				$this->state['meta'] = $this->merge($existingMeta, $this->metadata);
			}

			return $this->state;
		}, PresenterException::class);
	}

	/**
	 * Finalize and prepare the data for the view.
	 *
	 * @return array<string,mixed>
	 */
	public function prepare(): array
	{
		return $this->wrapInTry(function (): array {
			$this->initializeState();
			$this->metadata = [];
			$this->transform();
			$this->addComputedProperties();
			$this->addMetadata();

			return $this->state;
		}, PresenterException::class);
	}

	/**
	 * Retrieve a specific value from the prepared data or state.
	 *
	 * @param string     $key
	 * @param mixed|null $default Default value if the key is not found.
	 * @return mixed
	 */
	public function get(string $key, mixed $default = null): mixed
	{
		if ($key === '') {
			return $this->state !== [] ? $this->state : $this->prepare();
		}

		$current = $this->state !== [] ? $this->state : $this->prepare();

		foreach (explode('.', $key) as $segment) {
			if (!$this->isArray($current) || !$this->keyExists($current, $segment)) {
				return $default;
			}

			$current = $current[$segment];
		}

		return $current;
	}

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
		$this->state = $this->data;
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
		return $this->wrapInTry(
			fn(): mixed => match ($format) {
				'json' => $this->dataHandler->jsonEncode($data),
				'xml' => $this->dataHandler->toXml($data),
				default => $data
			},
			PresenterException::class
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
	 * Add timestamps to the metadata using DateTimeManager.
	 *
	 * @return void
	 */
	protected function addTimestamps(): void
	{
		$this->appendMetadata([
			'createdAt' => $this->dateTimeManager->formatDateTime(
				$this->dateTimeManager->createDateTime('now'),
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
		return $this->wrapInTry(
			fn(): mixed => match ($metric) {
				'similarity' => $this->similarityScore($this->data[$value] ?? '', $value),
				'distance'   => $this->distance($this->data[$value] ?? '', $value),
				default       => null
			},
			PresenterException::class
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
			$this->filter(
				$this->state,
				fn(mixed $value, string $key): bool => $this->isInArray($key, $keys, true),
				ARRAY_FILTER_USE_BOTH
			),
			PresenterException::class
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

	/**
	 * Override to transform the raw presenter data.
	 *
	 * @param array<string,mixed> $data
	 * @return array<string,mixed>
	 */
	protected function transformData(array $data): array
	{
		return $data;
	}

	/**
	 * Override to compute derived properties for the current state.
	 *
	 * @param array<string,mixed> $data
	 * @return array<string,mixed>
	 */
	protected function computeProperties(array $data): array
	{
		return [];
	}

	/**
	 * Override to build metadata for the current state.
	 *
	 * @param array<string,mixed> $data
	 * @return array<string,mixed>
	 */
	protected function buildMetadata(array $data): array
	{
		return [];
	}
}
