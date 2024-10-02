<?php

namespace App\Abstracts\Presentation;

use App\Exceptions\Presentation\PresenterException;

abstract class Presenter
{
	protected array $data = [];

	public function __construct(array $data = [])
	{
		$this->data = $data;
	}

	/**
	 * Abstract method for formatting data.
	 *
	 * @return array Formatted data
	 */
	abstract protected function present(): array;

	/**
	 * Transforms data using the provided callbacks.
	 *
	 * @param array $transformers Array of keys and transformation callbacks.
	 * @return array Transformed data.
	 * @throws PresenterException If an error occurs during transformation.
	 */
	protected function transform(array $transformers): array
	{
		try {
			$transformed = [];
			foreach ($transformers as $key => $callback) {
				$transformed[$key] = $callback($this->data[$key] ?? null);
			}
			return $transformed;
		} catch (\Exception $e) {
			throw new PresenterException("Error transforming data: " . $e->getMessage());
		}
	}

	/**
	 * Formats data as a JSON string.
	 *
	 * @return string JSON-formatted data.
	 */
	protected function formatJson(): string
	{
		return json_encode($this->data, JSON_PRETTY_PRINT);
	}

	/**
	 * Formats data as XML.
	 *
	 * @return string XML-formatted data.
	 */
	protected function formatXml(): string
	{
		$xml = new \SimpleXMLElement('<root/>');
		array_walk_recursive($this->data, [$xml, 'addChild']);
		return $xml->asXML();
	}

	/**
	 * Returns data in array format.
	 *
	 * @return array Data as an array.
	 */
	protected function formatArray(): array
	{
		return $this->data;
	}

	/**
	 * Adds additional data to the presenter.
	 *
	 * @param array $additionalData Data to be added.
	 */
	protected function addData(array $additionalData): void
	{
		$this->data = array_merge($this->data, $additionalData);
	}

	/**
	 * Filters data by including only the specified keys.
	 *
	 * @param array $keys Keys to retain.
	 * @return array Filtered data.
	 */
	protected function filterData(array $keys): array
	{
		return array_intersect_key($this->data, array_flip($keys));
	}

	/**
	 * Filters out null values from the data.
	 *
	 * @param array $data The data to be filtered.
	 * @return array Data with null values removed.
	 */
	protected function filterNulls(array $data): array
	{
		return array_filter($data, fn($value) => $value !== null);
	}

	/**
	 * Returns only the specified keys from the data.
	 *
	 * @param array $keys Keys to retain.
	 * @return array Data with only the specified keys.
	 */
	protected function only(array $keys): array
	{
		return array_intersect_key($this->data, array_flip($keys));
	}

	/**
	 * Returns data without the specified keys.
	 *
	 * @param array $keys Keys to exclude.
	 * @return array Data with excluded keys removed.
	 */
	protected function except(array $keys): array
	{
		return array_diff_key($this->data, array_flip($keys));
	}
}
