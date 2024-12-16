<?php

namespace App\Utilities\Validation;

use App\Abstracts\Data\Validator;
use App\Contracts\Data\ValidatorInterface;
use App\Utilities\Traits\Filters\ValidationTrait;
use App\Utilities\Traits\Rules\RuleTrait;

/**
 * Class GeneralValidator
 *
 * Extends the `Validator` abstract class, implementing predefined validation methods and rules.
 * Leverages `ValidationTrait` for robust PHP filter-based validation and `RuleTrait` for additional validation rules.
 * Automatically maps method calls (e.g., `email` or `min`) using the `Validator` base class.
 *
 * **Usage of `verify` Method:**
 * - The `verify` method is the primary entry point for validating data.
 * - While the `handle` method is inherited from the abstract `Validator` class, **you should not call `handle` directly**.
 * - Use the `verify` method instead, which wraps the functionality of `handle` for better usability.
 *
 * **Available Validation Methods:**
 * - **`boolean(mixed $input)`**: Validates boolean values (true or false).
 * - **`email(string $input)`**: Validates an email address.
 * - **`float(string $input, array $flags = [])`**: Validates a floating-point number, optionally with specific flags.
 * - **`int(string $input, array $flags = [])`**: Validates an integer, optionally with specific flags.
 * - **`ip(string $input, array $flags = [])`**: Validates an IP address (IPv4/IPv6), optionally with specific flags.
 * - **`mac(string $input)`**: Validates a MAC address.
 * - **`regexp(string $input, string $pattern)`**: Validates a string against a custom regular expression.
 * - **`url(string $input, array $flags = [])`**: Validates a URL, optionally with specific flags.
 * - **`domain(string $input)`**: Validates a domain name.
 *
 * **Available Validation Flags:**
 * - **`allowFraction`**: Allows decimal fractions in numbers (used with `float` validation).
 * - **`allowScientific`**: Allows scientific notation in numbers (used with `float` validation).
 * - **`allowThousand`**: Allows thousand separators in numbers (used with `float` or `int` validation).
 * - **`ipv4`**: Restricts IP validation to IPv4.
 * - **`ipv6`**: Restricts IP validation to IPv6.
 * - **`noResRange`**: Excludes reserved IP ranges (used with `ip` validation).
 * - **`noPrivRange`**: Excludes private IP ranges (used with `ip` validation).
 * - **`pathRequired`**: Requires a path in URLs (used with `url` validation).
 * - **`queryRequired`**: Requires a query in URLs (used with `url` validation).
 *
 * **Available Validation Rules:**
 * - **`require(mixed $input)`**: Ensures a value is not null.
 * - **`notEmpty(string $input)`**: Ensures a string is not empty after trimming.
 * - **`min(float|int $input, float|int $min)`**: Validates if a numeric value meets a minimum threshold.
 * - **`max(float|int $input, float|int $max)`**: Validates if a numeric value does not exceed a maximum threshold.
 * - **`between(float|int $input, float|int $min, float|int $max)`**: Validates if a numeric value is within a range.
 * - **`less(float|int $input, float|int $threshold)`**: Validates if a numeric value is less than a threshold.
 * - **`greater(float|int $input, float|int $threshold)`**: Validates if a numeric value is greater than a threshold.
 * - **`minLength(string $input, int $min)`**: Validates if a string's length meets a minimum value.
 * - **`maxLength(string $input, int $max)`**: Validates if a string's length does not exceed a maximum value.
 * - **`lengthBetween(string $input, int $min, int $max)`**: Validates if a string's length is within a range.
 * - **`startsWith(string $input, string $prefix)`**: Validates if a string starts with a specific prefix.
 * - **`endsWith(string $input, string $suffix)`**: Validates if a string ends with a specific suffix.
 * - **`inArray(mixed $input, array $array)`**: Validates if a value exists in an array.
 * - **`notInArray(mixed $input, array $array)`**: Validates if a value does not exist in an array.
 * - **`arraySize(array $input, int $min, int $max)`**: Validates if an array's size is within a range.
 * - **`arrayUnique(array $input)`**: Validates if all elements in an array are unique.
 * - **`arrayNotEmpty(array $input)`**: Ensures the array is not empty.
 * - **`isAssociativeArray(array $input)`**: Validates if an array is associative.
 * - **`sequential(array $numbers, bool $allowGaps = false)`**: Validates if numbers in an array are sequential.
 *
 * **Example Usage:**
 *
 * #### Example 1: Validation Without Flags
 * ```php
 * $validator = new GeneralValidator();
 * $data = [
 *     'email' => ['email'],
 *     'username' => ['string', ['notEmpty', 'minLength' => 3]],
 * ];
 * $validatedData = $validator->verify($data);
 * ```
 *
 * #### Example 2: Validation With Flags
 * ```php
 * $validator = new GeneralValidator();
 * $data = [
 *     'ipAddress' => ['ip', ['ipv4', 'noPrivRange']],
 *     'price' => ['float', ['allowFraction']],
 * ];
 * $validatedData = $validator->verify($data);
 * ```
 *
 * #### Example 3: Validation With Regular Expressions
 * ```php
 * $validator = new GeneralValidator();
 * $data = [
 *     'username' => ['regexp', ['pattern' => '/^[a-zA-Z0-9_]{3,20}$/']],
 * ];
 * $validatedData = $validator->verify($data);
 * ```
 */
 class GeneralValidator extends Validator implements ValidatorInterface
{
	use RuleTrait, ValidationTrait;

	/**
	 * Validates the provided data using pre-configured validation methods, flags, and rules.
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
