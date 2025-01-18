<?php

namespace App\Utilities\Sanitation;

use App\Abstracts\Data\Sanitizer;                // Abstract base class for data sanitization processes.
use App\Contracts\Data\SanitizerInterface;       // Interface defining the contract for sanitizer implementations.

use App\Utilities\Traits\{
    Filters\SanitationTrait, // Provides methods for applying data sanitation filters.
    Rules\RulesTrait         // Adds support for defining and enforcing validation rules.
};

/**
 * Class GeneralSanitizer
 *
 * Extends the `Sanitizer` abstract class, implementing predefined sanitization methods and validation rules.
 * Leverages `SanitationTrait` for sanitization and `RulesTrait` for validation.
 * Automatically maps method calls (e.g., `email` or `min`) using the `Sanitizer` base class.
 *
 * **Usage of `clean` Method:**
 * - The `clean` method is the primary entry point for sanitizing and validating data.
 * - While the `handle` method is inherited from the abstract `Sanitizer` class, **you should not call `handle` directly**.
 * - Instead, override or use the `clean` method, which wraps the functionality of `handle` for better usability.
 *
 * **Available Sanitization Methods:**
 * - **`encoded(string $input, array $flags = [])`**: Encodes a string (e.g., URL-encoding).
 * - **`string(string $input, array $flags = [])`**: Escapes HTML special characters.
 * - **`email(string $input)`**: Removes invalid characters from email addresses.
 * - **`url(string $input)`**: Removes invalid characters from URLs.
 * - **`int(string $input, array $flags = [])`**: Sanitizes integers, optionally allowing specific formats.
 * - **`float(string $input, array $flags = [])`**: Sanitizes floating-point numbers (allows fractions or scientific notation).
 * - **`addSlashes(string $input)`**: Adds slashes to escape special characters.
 * - **`fullSpecialChars(string $input, array $flags = [])`**: Escapes all HTML special characters.
 *
 * **Available Sanitization Flags:**
 * The following flags can be used to modify sanitization behavior for specific methods:
 * - **`allowFraction`**: Allows decimal fractions in numbers (used with `float` sanitization).
 * - **`allowScientific`**: Allows scientific notation in numbers (used with `float` sanitization).
 * - **`allowThousand`**: Allows thousand separators in numbers (used with `float` or `int` sanitization).
 * - **`noEncodeQuotes`**: Prevents encoding of quotes (used with `string` sanitization).
 * - **`stripLow`**: Strips ASCII control characters with a value less than 32.
 * - **`stripHigh`**: Strips characters with a value greater than 127.
 * - **`encodeAmp`**: Encodes ampersands (`&`) (used with `encoded` sanitization).
 * - **`stripBacktick`**: Strips backticks from the input.
 *
 * **Available Validation Rules:**
 * - **`require(mixed $input)`**: Ensures a value is not null.
 * - **`notEmpty(string $input)`**: Ensures a string is not empty after trimming.
 *
 * #### **Numeric Validation**
 * - **`min(float|int $input, float|int $min)`**: Validates if a numeric value meets a minimum threshold.
 * - **`max(float|int $input, float|int $max)`**: Validates if a numeric value does not exceed a maximum threshold.
 * - **`between(float|int $input, float|int $min, float|int $max)`**: Validates if a numeric value is within a range.
 * - **`less(float|int $input, float|int $threshold)`**: Validates if a numeric value is less than a threshold.
 * - **`greater(float|int $input, float|int $threshold)`**: Validates if a numeric value is greater than a threshold.
 * - **`divisibleBy(int $input, int $divisor)`**: Validates if a value is divisible by another value.
 * - **`positive(float|int $input)`**: Ensures the value is positive.
 * - **`negative(float|int $input)`**: Ensures the value is negative.
 * - **`step(float|int $input, float|int $step, float|int $base = 0)`**: Validates if a value matches a step.
 *
 * #### **String Validation**
 * - **`minLength(string $input, int $min)`**: Validates if a string's length meets a minimum value.
 * - **`maxLength(string $input, int $max)`**: Validates if a string's length does not exceed a maximum value.
 * - **`lengthBetween(string $input, int $min, int $max)`**: Validates if a string's length is within a range.
 * - **`startsWith(string $input, string $prefix)`**: Validates if a string starts with a specific prefix.
 * - **`endsWith(string $input, string $suffix)`**: Validates if a string ends with a specific suffix.
 *
 * #### **Array Validation**
 * - **`inArray(mixed $input, array $array)`**: Validates if a value exists in an array.
 * - **`notInArray(mixed $input, array $array)`**: Validates if a value does not exist in an array.
 * - **`arraySize(array $input, int $min, int $max)`**: Validates if an array's size is within a range.
 * - **`arrayUnique(array $input)`**: Validates if all elements in an array are unique.
 * - **`arrayNotEmpty(array $input)`**: Ensures the array is not empty.
 * - **`isAssociativeArray(array $input)`**: Validates if an array is associative.
 *
 * #### **Sequential and Order Validation**
 * - **`sequential(array $numbers, bool $allowGaps = false)`**: Validates if numbers in an array are sequential.
 *
 * **Example Usage**
 *
 * #### Example 1: Sanitization Without Rules
 * ```php
 * $sanitizer = new GeneralSanitizer();
 * $data = [
 *     'email' => ['email'],
 *     'url' => ['url', 'string'],
 * ];
 * $sanitizedData = $sanitizer->clean($data);
 * ```
 *
 * #### Example 2: Sanitization With Flags
 * ```php
 * $sanitizer = new GeneralSanitizer();
 * $data = [
 *     'price' => ['float', ['allowFraction', 'allowThousand']],
 *     'description' => ['string', ['noEncodeQuotes', 'stripLow']],
 * ];
 * $sanitizedData = $sanitizer->clean($data);
 * ```
 *
 * #### Example 3: Sanitization With Flags and Validation Rules
 * ```php
 * $sanitizer = new GeneralSanitizer();
 * $data = [
 *     'price' => ['float', ['allowFraction', 'allowThousand'], ['min' => 1, 'max' => 1000]],
 *     'username' => ['string', ['stripLow', 'stripHigh'], ['minLength' => 3, 'maxLength' => 20]],
 * ];
 * $sanitizedData = $sanitizer->clean($data);
 * ```
 *
 * #### Example 4: Nested Data Sanitization
 * ```php
 * $data = [
 *     'user' => [
 *         'email' => ['email', ['notEmpty']],
 *         'age' => ['int', ['min' => 18, 'max' => 120]],
 *     ],
 *     'product' => [
 *         'price' => ['float', ['allowFraction'], ['min' => 0.01]],
 *     ],
 * ];
 * $sanitizedData = $sanitizer->clean($data);
 * ```
 */
 class GeneralSanitizer extends Sanitizer implements SanitizerInterface
 {

	use SanitationTrait, RulesTrait;

	/**
	 * Cleans the provided data using pre-configured sanitization methods and rules.
	 *
	 * This method overrides the abstract `clean` method from the `Sanitizer` class.
	 *
	 * @param mixed $data The input data to clean.
	 * @return mixed The sanitized data.
	 */
	public function clean(mixed $data): mixed
	{
		return $this->handle($data);
	}
}
