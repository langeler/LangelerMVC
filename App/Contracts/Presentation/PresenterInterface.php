<?php

declare(strict_types=1);

namespace App\Contracts\Presentation;

/**
 * PresenterInterface
 *
 * Defines the contract for transforming raw data into a presentation-ready format.
 * Aligns with the abstract Presenter class, providing a public API that
 * controllers or services can rely on.
 */
interface PresenterInterface
{
	/**
	 * Transform raw data into a structure suitable for presentation.
	 *
	 * @return array<string,mixed> The transformed data.
	 */
	public function transform(): array;

	/**
	 * Add computed or derived properties to the transformed data.
	 *
	 * @return array<string,mixed> The data after adding computed properties.
	 */
	public function addComputedProperties(): array;

	/**
	 * Append metadata (e.g., pagination, timestamps) to the data.
	 *
	 * @return array<string,mixed> The data after appending metadata.
	 */
	public function addMetadata(): array;

	/**
	 * Finalize and prepare the data for the view.
	 *
	 * @return array<string,mixed> The fully prepared data.
	 */
	public function prepare(): array;

	/**
	 * Retrieve a specific value from the prepared data.
	 *
	 * @param string     $key
	 * @param mixed|null $default Default value if the key is not found.
	 * @return mixed
	 */
	public function get(string $key, mixed $default = null): mixed;
}
