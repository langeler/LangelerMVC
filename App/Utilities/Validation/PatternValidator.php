<?php

namespace App\Utilities\Validation;

use App\Abstracts\Data\Validator;
use App\Contracts\Data\ValidatorInterface;
use App\Utilities\Traits\Rules\RuleTrait;
use App\Utilities\Traits\Patterns\ValidationPatternTrait;

/**
 * Class PatternValidator
 *
 * Extends the `Validator` abstract class to provide a robust framework for validating text-related data.
 * Utilizes `ValidationPatternTrait` to offer predefined regex-based validation methods for various input types.
 * Incorporates `RuleTrait` to validate data using customizable rules.
 *
 * **Usage of `verify` Method:**
 * - The `verify` method is the main entry point for validating data.
 * - Automatically maps validation methods (e.g., `name`, `url`) and validation rules (e.g., `min`, `max`).
 * - Avoid direct usage of the `handle` method; use the `verify` method instead.
 *
 * **Available Validation Methods**
 *
 * These methods leverage predefined regex patterns to validate various types of input data:
 *
 * - **`name(string $input)`**: Validates full names (letters, spaces, periods, apostrophes, hyphens).
 * - **`ssn(string $input)`**: Validates US Social Security Numbers (e.g., 123-45-6789).
 * - **`phoneUs(string $input)`**: Validates US phone numbers (e.g., (123) 456-7890).
 * - **`phoneIntl(string $input)`**: Validates international phone numbers (e.g., +44 1234 567890).
 * - **`hexadecimal(string $input)`**: Validates hexadecimal numbers with "0x" prefix.
 * - **`hexOnly(string $input)`**: Validates hexadecimal numbers without prefix.
 * - **`binary(string $input)`**: Validates binary numbers (0s and 1s).
 * - **`octal(string $input)`**: Validates octal numbers (digits 0-7).
 * - **`creditCard(string $input)`**: Validates credit card numbers.
 * - **`isbn10(string $input)`**: Validates ISBN-10 format.
 * - **`iban(string $input)`**: Validates IBAN format.
 * - **`bic(string $input)`**: Validates BIC/SWIFT codes.
 * - **`ethereumAddress(string $input)`**: Validates Ethereum addresses.
 * - **`bitcoinAddress(string $input)`**: Validates Bitcoin addresses.
 * - **`fileName(string $input)`**: Validates file names with extensions.
 * - **`directory(string $input)`**: Validates directory names.
 * - **`pathUnix(string $input)`**: Validates Unix file paths.
 * - **`pathWindows(string $input)`**: Validates Windows file paths.
 * - **`fileExt(string $input)`**: Validates file extensions.
 * - **`imageExt(string $input)`**: Validates image file extensions (e.g., .jpg, .png).
 * - **`audioExt(string $input)`**: Validates audio file extensions (e.g., .mp3, .wav).
 * - **`videoExt(string $input)`**: Validates video file extensions (e.g., .mp4, .avi).
 * - **`slug(string $input)`**: Validates URL slugs (lowercase letters, numbers, hyphens).
 * - **`url(string $input)`**: Validates URLs (e.g., http://example.com).
 * - **`urlPort(string $input)`**: Validates URLs with ports (e.g., http://example.com:8080).
 * - **`ipv4(string $input)`**: Validates IPv4 addresses.
 * - **`ipv6(string $input)`**: Validates IPv6 addresses.
 * - **`zipUs(string $input)`**: Validates US ZIP codes (e.g., 12345 or 12345-6789).
 * - **`zipUk(string $input)`**: Validates UK postal codes.
 * - **`intPos(string $input)`**: Validates positive integers.
 * - **`intNeg(string $input)`**: Validates negative integers.
 * - **`int(string $input)`**: Validates integers (positive or negative).
 * - **`floatPos(string $input)`**: Validates positive floating-point numbers.
 * - **`floatNeg(string $input)`**: Validates negative floating-point numbers.
 * - **`float(string $input)`**: Validates floating-point numbers.
 * - **`scientific(string $input)`**: Validates scientific notation.
 * - **`alpha(string $input)`**: Validates alphabetic input (letters only).
 * - **`alphaSpace(string $input)`**: Validates alphabetic input with spaces.
 * - **`alphaDash(string $input)`**: Validates alphabetic input with hyphens and underscores.
 * - **`alphaNum(string $input)`**: Validates alphanumeric input.
 * - **`alphaNumSpace(string $input)`**: Validates alphanumeric input with spaces.
 * - **`hashtag(string $input)`**: Validates hashtags (e.g., #example).
 * - **`twitterHandle(string $input)`**: Validates Twitter handles (e.g., @username).
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
 * #### Example 1: Validation Without Rules
 * ```php
 * $validator = new PatternValidator();
 * $data = [
 *     'name' => ['name'],
 *     'email' => ['email'],
 *     'phone' => ['phoneUs'],
 * ];
 * $validatedData = $validator->verify($data);
 * ```
 *
 * #### Example 2: Validation With Rules
 * ```php
 * $validator = new PatternValidator();
 * $data = [
 *     'name' => ['name', ['minLength' => 3, 'maxLength' => 50]],
 *     'phone' => ['phoneUs', ['require']],
 *     'price' => ['float', ['greater' => 0.01, 'less' => 1000]],
 * ];
 * $validatedData = $validator->verify($data);
 * ```
 */
class PatternValidator extends Validator implements ValidatorInterface
{
	use RuleTrait, ValidationPatternTrait;

	/**
	 * Validates the provided data using pre-configured validation methods and rules.
	 *
	 * This method overrides the abstract `verify` method from the `Validator` class.
	 *
	 * @param mixed $data The input data to validate.
	 * @return array The validated data array.
	 */
	public function verify(mixed $data): array
	{
		return $this->handle($data);
	}
}
