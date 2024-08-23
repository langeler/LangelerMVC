<?php

namespace App\Utilities\Validation\Traits;

trait StructuredValidationTrait
{
	use BaseValidationTrait;

	/**
	 * Validate that a JSON string is properly formatted.
	 *
	 * @param string $json
	 * @return bool
	 */
	public function validateJson(string $json): bool
	{
		json_decode($json);
		return (json_last_error() === JSON_ERROR_NONE);
	}

	/**
	 * Validate that an XML string is well-formed.
	 *
	 * @param string $xml
	 * @return bool
	 */
	public function validateXml(string $xml): bool
	{
		$dom = new \DOMDocument();
		return @$dom->loadXML($xml) !== false;
	}
}
