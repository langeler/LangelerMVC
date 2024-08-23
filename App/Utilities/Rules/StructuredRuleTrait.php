<?php

namespace App\Utilities\Rules;

trait StructuredRuleTrait
{
	use BaseRuleTrait;

	/**
	 * Rule to check if JSON is valid.
	 *
	 * @param string $json
	 * @return bool
	 */
	public function ruleValidJson(string $json): bool
	{
		json_decode($json);
		return (json_last_error() === JSON_ERROR_NONE);
	}

	/**
	 * Rule to check if XML is well-formed.
	 *
	 * @param string $xml
	 * @return bool
	 */
	public function ruleWellFormedXml(string $xml): bool
	{
		$dom = new \DOMDocument();
		return @$dom->loadXML($xml) !== false;
	}
}
