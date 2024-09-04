<?php

namespace App\Utilities\Handlers;

use JsonSerializable;
use DOMDocument;
use SimpleXMLElement;
use LibXMLError;
use Normalizer;
use Transliterator;
use Locale;
use NumberFormatter;

/**
 * Class DataHandler
 *
 * Provides utility methods for encoding/decoding, normalization, transliteration, working with XML/JSON, locale management, and number formatting.
 */
class DataHandler
{
	private ?Transliterator $transliterator;
	private string $locale;

	public function __construct(string $locale = 'en_US')
	{
		$this->transliterator = null;
		$this->locale = $locale;
	}

	// JSON Methods

	/**
	 * Encode data into JSON format.
	 *
	 * @param mixed $value Data to be encoded.
	 * @param int $options JSON encoding options.
	 * @param int $depth Maximum depth to traverse.
	 * @return string JSON encoded string.
	 */
	public function jsonEncode($value, int $options = 0, int $depth = 512): string
	{
		return json_encode($value, $options, $depth);
	}

	/**
	 * Decode a JSON string into PHP data.
	 *
	 * @param string $json JSON string to decode.
	 * @param bool $assoc Whether to return an associative array.
	 * @param int $depth Recursion depth.
	 * @param int $options Decoding options.
	 * @return mixed Decoded PHP data.
	 */
	public function jsonDecode(string $json, bool $assoc = false, int $depth = 512, int $options = 0)
	{
		return json_decode($json, $assoc, $depth, $options);
	}

	/**
	 * Get the last JSON error code.
	 *
	 * @return int JSON error code.
	 */
	public function getLastJsonError(): int
	{
		return json_last_error();
	}

	/**
	 * Get the last JSON error message.
	 *
	 * @return string JSON error message.
	 */
	public function getLastJsonErrorMessage(): string
	{
		return json_last_error_msg();
	}

	/**
	 * Encode an object implementing JsonSerializable.
	 *
	 * @param JsonSerializable $object The object to encode.
	 * @return string JSON encoded string.
	 */
	public function encodeJsonSerializable(JsonSerializable $object): string
	{
		return json_encode($object);
	}

	// Serialization Methods

	/**
	 * Serialize PHP data into a string.
	 *
	 * @param mixed $data Data to serialize.
	 * @return string Serialized string.
	 */
	public function serializeData($data): string
	{
		return serialize($data);
	}

	/**
	 * Unserialize a serialized string into PHP data.
	 *
	 * @param string $data Serialized string.
	 * @return mixed Unserialized data.
	 */
	public function unserializeData(string $data)
	{
		return unserialize($data);
	}

	// XML Methods

	/**
	 * Create a new DOMDocument instance.
	 *
	 * @param string $version The XML version.
	 * @param string $encoding The XML encoding.
	 * @return DOMDocument The new DOMDocument instance.
	 */
	public function createDomDocument(string $version = '1.0', string $encoding = 'UTF-8'): DOMDocument
	{
		return new DOMDocument($version, $encoding);
	}

	/**
	 * Load an XML file into a DOMDocument.
	 *
	 * @param DOMDocument $dom The DOMDocument instance.
	 * @param string $filename The XML file path.
	 * @return bool True on success, false on failure.
	 */
	public function loadXmlFile(DOMDocument $dom, string $filename): bool
	{
		return $dom->load($filename);
	}

	/**
	 * Save a DOMDocument instance to an XML file.
	 *
	 * @param DOMDocument $dom The DOMDocument instance.
	 * @param string $filename The file path to save to.
	 * @return bool True on success, false on failure.
	 */
	public function saveXmlToFile(DOMDocument $dom, string $filename): bool
	{
		return $dom->save($filename) !== false;
	}

	/**
	 * Create a DOM element.
	 *
	 * @param DOMDocument $dom The DOMDocument instance.
	 * @param string $name The name of the element.
	 * @param string $value The value of the element.
	 * @return \DOMElement The created DOM element.
	 */
	public function createElement(DOMDocument $dom, string $name, string $value = ''): \DOMElement
	{
		return $dom->createElement($name, $value);
	}

	/**
	 * Append a child node to a parent node in a DOMDocument.
	 *
	 * @param DOMDocument $dom The DOMDocument instance.
	 * @param \DOMNode $parent The parent node.
	 * @param \DOMNode $child The child node.
	 * @return \DOMNode The appended child node.
	 */
	public function appendChild(DOMDocument $dom, \DOMNode $parent, \DOMNode $child): \DOMNode
	{
		return $parent->appendChild($child);
	}

	/**
	 * Create a SimpleXMLElement from a string.
	 *
	 * @param string $data The XML string.
	 * @return SimpleXMLElement The created SimpleXMLElement.
	 */
	public function createSimpleXmlElement(string $data): SimpleXMLElement
	{
		return new SimpleXMLElement($data);
	}

	/**
	 * Add a child to a SimpleXMLElement.
	 *
	 * @param SimpleXMLElement $element The parent element.
	 * @param string $name The name of the child element.
	 * @param string|null $value The value of the child element.
	 * @return SimpleXMLElement The created child element.
	 */
	public function addXmlChild(SimpleXMLElement $element, string $name, ?string $value = null): SimpleXMLElement
	{
		return $element->addChild($name, $value);
	}

	/**
	 * Add an attribute to a SimpleXMLElement.
	 *
	 * @param SimpleXMLElement $element The element to add the attribute to.
	 * @param string $name The attribute name.
	 * @param string $value The attribute value.
	 * @return void
	 */
	public function addXmlAttribute(SimpleXMLElement $element, string $name, string $value): void
	{
		$element->addAttribute($name, $value);
	}

	/**
	 * Save a SimpleXMLElement to a file.
	 *
	 * @param SimpleXMLElement $element The SimpleXMLElement instance.
	 * @param string $filename The file path to save to.
	 * @return bool True on success, false on failure.
	 */
	public function saveSimpleXmlElementToFile(SimpleXMLElement $element, string $filename): bool
	{
		return $element->asXML($filename);
	}

	// Libxml Error Handling Methods

	/**
	 * Clear libxml errors.
	 *
	 * @return void
	 */
	public function clearErrors(): void
	{
		libxml_clear_errors();
	}

	/**
	 * Disable external entity loader.
	 *
	 * @param bool $disable Whether to disable the loader.
	 * @return bool The previous value of the entity loader.
	 */
	public function disableEntityLoader(bool $disable = true): bool
	{
		return libxml_disable_entity_loader($disable);
	}

	/**
	 * Get all libxml errors.
	 *
	 * @return array Array of LibXMLError instances.
	 */
	public function getErrors(): array
	{
		return libxml_get_errors();
	}

	/**
	 * Get the last libxml error.
	 *
	 * @return LibXMLError|null The last error or null if no error occurred.
	 */
	public function getLastError(): ?LibXMLError
	{
		return libxml_get_last_error();
	}

	/**
	 * Use internal libxml errors.
	 *
	 * @param bool $use Whether to use internal errors.
	 * @return bool The previous value of internal error handling.
	 */
	public function useInternalErrors(bool $use = true): bool
	{
		return libxml_use_internal_errors($use);
	}

	// Base64 Methods

	/**
	 * Encode data in base64.
	 *
	 * @param string $data The data to encode.
	 * @return string The base64 encoded string.
	 */
	public function base64Encode(string $data): string
	{
		return base64_encode($data);
	}

	/**
	 * Decode base64 encoded data.
	 *
	 * @param string $data The base64 encoded string.
	 * @return string The decoded data.
	 */
	public function base64Decode(string $data): string
	{
		return base64_decode($data);
	}

	// URL Encode/Decode Methods

	/**
	 * URL encode a string.
	 *
	 * @param string $data The data to URL encode.
	 * @return string The URL encoded string.
	 */
	public function urlEncode(string $data): string
	{
		return urlencode($data);
	}

	/**
	 * URL decode a string.
	 *
	 * @param string $data The URL encoded string.
	 * @return string The decoded data.
	 */
	public function urlDecode(string $data): string
	{
		return urldecode($data);
	}

	// Hex Encode/Decode Methods

	/**
	 * Encode data to hexadecimal.
	 *
	 * @param string $data The data to encode.
	 * @return string The hexadecimal encoded string.
	 */
	public function hexEncode(string $data): string
	{
		return bin2hex($data);
	}

	/**
	 * Decode hexadecimal encoded data.
	 *
	 * @param string $data The hexadecimal encoded string.
	 * @return string The decoded data.
	 */
	public function hexDecode(string $data): string
	{
		return hex2bin($data);
	}

	// Normalization Methods

	/**
	 * Check if a string is normalized.
	 *
	 * @param string $string The string to check.
	 * @param string $form The normalization form.
	 * @return bool True if the string is normalized, false otherwise.
	 */
	public function isNormalized(string $string, string $form = Normalizer::FORM_C): bool
	{
		return Normalizer::isNormalized($string, $form);
	}

	/**
	 * Normalize a string.
	 *
	 * @param string $string The string to normalize.
	 * @param string $form The normalization form.
	 * @return string|null The normalized string or null if normalization fails.
	 */
	public function normalize(string $string, string $form = Normalizer::FORM_C): ?string
	{
		return Normalizer::normalize($string, $form);
	}

	// Transliteration Methods

	/**
	 * Create a Transliterator instance.
	 *
	 * @param string|null $id The transliterator identifier.
	 * @return void
	 */
	public function createTransliterator(?string $id = null): void
	{
		$this->transliterator = Transliterator::create($id);
	}

	/**
	 * Transliterate a string using the created Transliterator.
	 *
	 * @param string $subject The string to transliterate.
	 * @param int $start The start position.
	 * @param int $end The end position.
	 * @return string The transliterated string.
	 * @throws \RuntimeException If the Transliterator is not initialized.
	 */
	public function transliterate(string $subject, int $start = 0, int $end = -1): string
	{
		if (!$this->transliterator) {
			throw new \RuntimeException("Transliterator not initialized.");
		}
		return $this->transliterator->transliterate($subject, $start, $end);
	}

	// Locale Methods

	/**
	 * Get the default locale.
	 *
	 * @return string The default locale.
	 */
	public function getDefaultLocale(): string
	{
		return Locale::getDefault();
	}

	/**
	 * Set the default locale.
	 *
	 * @param string $locale The new default locale.
	 * @return bool True on success, false on failure.
	 */
	public function setDefaultLocale(string $locale): bool
	{
		return Locale::setDefault($locale);
	}

	/**
	 * Get the display language of the locale.
	 *
	 * @param string|null $locale The locale to get the language from, or the class locale.
	 * @return string The display language.
	 */
	public function getLanguage(string $locale = null): string
	{
		return Locale::getDisplayLanguage($locale ?? $this->locale);
	}

	/**
	 * Get the primary language of the locale.
	 *
	 * @param string|null $locale The locale to get the primary language from, or the class locale.
	 * @return string The primary language.
	 */
	public function getPrimaryLanguage(string $locale = null): string
	{
		return Locale::getPrimaryLanguage($locale ?? $this->locale);
	}

	/**
	 * Get the region of the locale.
	 *
	 * @param string|null $locale The locale to get the region from, or the class locale.
	 * @return string|null The region, or null if none.
	 */
	public function getRegion(string $locale = null): ?string
	{
		return Locale::getRegion($locale ?? $this->locale);
	}

	/**
	 * Get the script of the locale.
	 *
	 * @param string|null $locale The locale to get the script from, or the class locale.
	 * @return string|null The script, or null if none.
	 */
	public function getScript(string $locale = null): ?string
	{
		return Locale::getScript($locale ?? $this->locale);
	}

	// Number Formatter Methods

	/**
	 * Format a currency value for a specific locale.
	 *
	 * @param float $amount The amount to format.
	 * @param string $currency The currency code.
	 * @param string|null $locale The locale to use, or the class locale.
	 * @return string The formatted currency string.
	 */
	public function formatCurrency(float $amount, string $currency, string $locale = null): string
	{
		$formatter = new NumberFormatter($locale ?? $this->locale, NumberFormatter::CURRENCY);
		return $formatter->formatCurrency($amount, $currency);
	}

	/**
	 * Format a percentage value for a specific locale.
	 *
	 * @param float $number The percentage value.
	 * @param string|null $locale The locale to use, or the class locale.
	 * @return string The formatted percentage string.
	 */
	public function formatPercentage(float $number, string $locale = null): string
	{
		$formatter = new NumberFormatter($locale ?? $this->locale, NumberFormatter::PERCENT);
		return $formatter->format($number);
	}

	/**
	 * Parse a number for a specific locale.
	 *
	 * @param string $number The number string to parse.
	 * @param string|null $locale The locale to use, or the class locale.
	 * @return float The parsed number.
	 */
	public function parseNumber(string $number, string $locale = null): float
	{
		$formatter = new NumberFormatter($locale ?? $this->locale, NumberFormatter::DECIMAL);
		return $formatter->parse($number);
	}
}
