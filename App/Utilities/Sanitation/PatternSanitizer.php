<?php

namespace App\Utilities\Sanitation;

use App\Abstracts\Data\Sanitizer;
use App\Contracts\Data\SanitizerInterface;
use App\Utilities\Traits\Rules\RuleTrait;
use App\Utilities\Traits\Patterns\SanitationPatternTrait;

/**
 * Class PatternSanitizer
 *
 * Extends the `Sanitizer` abstract class to provide a robust framework for sanitizing text-related data.
 * Utilizes `SanitationPatternTrait` to offer predefined regex-based sanitization methods for various input types.
 * Incorporates `RuleTrait` to validate data using customizable rules.
 *
 * **Usage of `clean` Method:**
 * - The `clean` method is the main entry point for sanitizing and validating data.
 * - Automatically maps sanitization methods (e.g., `name`, `url`) and validation rules (e.g., `min`, `max`).
 * - Avoid direct usage of the `handle` method; use the `clean` method instead.
 *
 * **Available Sanitization Methods**
 *
 * These methods leverage predefined regex patterns to remove unwanted characters from various types of input data:
 *
 * - **`name(string $input)`**: Removes invalid characters from names (letters, spaces, periods, apostrophes, hyphens).
 * - **`ssn(string $input)`**: Removes invalid characters from Social Security Numbers (digits and hyphens).
 * - **`phoneUs(string $input)`**: Removes invalid characters from US phone numbers (digits, spaces, parentheses, hyphens, plus).
 * - **`phoneIntl(string $input)`**: Removes invalid characters from international phone numbers (digits, spaces, plus).
 * - **`zipUs(string $input)`**: Removes invalid characters from US ZIP codes (digits, hyphens).
 * - **`zipUk(string $input)`**: Removes invalid characters from UK postal codes (alphanumeric, spaces).
 * - **`hex(string $input)`**: Removes invalid characters from hexadecimal numbers (digits, letters, optional "x" prefix).
 * - **`binary(string $input)`**: Removes invalid characters from binary numbers (0 and 1).
 * - **`octal(string $input)`**: Removes invalid characters from octal numbers (digits 0-7).
 * - **`creditCard(string $input)`**: Removes invalid characters from credit card numbers (digits only).
 * - **`isbn(string $input)`**: Removes invalid characters from ISBN-10 (digits, optional "X").
 * - **`currencyUsd(string $input)`**: Removes invalid characters from USD currency values (digits, ".", ",", "$").
 * - **`fileName(string $input)`**: Removes invalid characters from file names (alphanumeric, hyphens, underscores, dots).
 * - **`directory(string $input)`**: Removes invalid characters from directory names (alphanumeric, hyphens, underscores).
 * - **`pathUnix(string $input)`**: Removes invalid characters from Unix file paths (slashes, hyphens, underscores, dots).
 * - **`fileExt(string $input)`**: Removes invalid characters from file extensions (alphanumeric).
 * - **`slug(string $input)`**: Removes invalid characters from URL slugs (lowercase letters, numbers, hyphens).
 * - **`url(string $input)`**: Removes invalid characters from URLs (standard URL characters).
 * - **`ipv4(string $input)`**: Removes invalid characters from IPv4 addresses (digits, dots).
 * - **`ipv6(string $input)`**: Removes invalid characters from IPv6 addresses (hexadecimal, colons).
 * - **`intPos(string $input)`**: Removes invalid characters from positive integers (digits only).
 * - **`float(string $input)`**: Removes invalid characters from floating-point numbers (digits, period, optional negative sign).
 * - **`percent(string $input)`**: Removes invalid characters from percentages (digits, ".", "%").
 * - **`alpha(string $input)`**: Removes invalid characters from alphabetic input (letters only).
 * - **`alphaNum(string $input)`**: Removes invalid characters from alphanumeric input (letters, numbers).
 * - **`hashtag(string $input)`**: Removes invalid characters from hashtags (letters, numbers, underscores, "#").
 *
 * **Available Validation Rules**
 *
 * These rules validate data against specific conditions:
 *
 * #### **General Validation**
 * - **`require(mixed $input)`**: Ensures a value is not null.
 * - **`notEmpty(string $input)`**: Ensures a string is not empty after trimming.
 *
 * #### **Numeric Validation**
 * - **`min(float|int $input, float|int $min)`**: Validates if a numeric value meets a minimum threshold.
 * - **`max(float|int $input, float|int $max)`**: Validates if a numeric value does not exceed a maximum threshold.
 * - **`between(float|int $input, float|int $min, float|int $max)`**: Validates if a numeric value is within a range.
 * - **`less(float|int $input, float|int $threshold)`**: Validates if a numeric value is less than a threshold.
 * - **`greater(float|int $input, float|int $threshold)`**: Validates if a numeric value is greater than a threshold.
 * - **`divisibleBy(int $input, int $divisor)`**: Validates if a number is divisible by a specified value.
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
 * $sanitizer = new PatternSanitizer();
 * $data = [
 *     'name' => ['name'],
 *     'phone' => ['phoneUs'],
 *     'url' => ['url'],
 * ];
 * $sanitizedData = $sanitizer->clean($data);
 * ```
 *
 * #### Example 2: Sanitization With Validation Rules
 * ```php
 * $sanitizer = new PatternSanitizer();
 * $data = [
 *     'name' => ['name', ['minLength' => 3, 'maxLength' => 50]],
 *     'phone' => ['phoneUs', ['require']],
 *     'age' => ['intPos', ['min' => 18, 'max' => 99]],
 * ];
 * $sanitizedData = $sanitizer->clean($data);
 * ```
 */
class PatternSanitizer extends Sanitizer implements SanitizerInterface
{
	use RuleTrait, SanitationPatternTrait;

	/**
	 * Cleans the provided data using pre-configured sanitization methods and rules.
	 *
	 * This method overrides the abstract `clean` method from the `Sanitizer` class.
	 *
	 * @param mixed $data The input data to clean.
	 * @return array The sanitized data array.
	 */
	public function clean(mixed $data): array
	{
		return $this->handle($data);
	}
}
