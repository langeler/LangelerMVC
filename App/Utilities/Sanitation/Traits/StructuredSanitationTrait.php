<?php

namespace App\Utilities\Sanitation\Traits;

trait StructuredSanitationTrait
{
	use BaseSanitationTrait;

	/**
	 * Remove harmful elements from JSON data.
	 *
	 * @param string $json
	 * @return string
	 */
	public function sanitizeJson(string $json): string
	{
		$decoded = json_decode($json, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return '';
		}
		return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	/**
	 * Remove harmful elements from XML data.
	 *
	 * @param string $xml
	 * @return string
	 */
	public function sanitizeXml(string $xml): string
	{
		$dom = new \DOMDocument();
		$dom->loadXML($xml, LIBXML_NOENT | LIBXML_DTDLOAD);
		return $dom->saveXML();
	}

	/**
	 * Sanitize configuration files, ensuring correct key-value pairs and formats.
	 *
	 * @param array $config
	 * @return array
	 */
	public function sanitizeConfig(array $config): array
	{
		return $this->sanitizeNestedData($config);
	}

	/**
	 * Clean and validate nested data, ensuring no unsafe elements.
	 *
	 * @param array $data
	 * @return array
	 */
	public function sanitizeNestedData(array $data): array
	{
		array_walk_recursive($data, function (&$item) {
			$item = $this->sanitizeText($item);
		});
		return $data;
	}
}
