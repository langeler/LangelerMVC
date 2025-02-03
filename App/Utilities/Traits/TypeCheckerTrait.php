<?php

namespace App\Utilities\Traits;

/**
 * Trait TypeCheckerTrait
 *
 * Provides utility methods to check the types and properties of various PHP entities.
 * This trait enhances readability and simplifies type-checking operations.
 */
trait TypeCheckerTrait
{
	/**
	 * Checks if a value is an array.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is an array, false otherwise.
	 */
	public function isArray(mixed $value): bool
	{
		return is_array($value);
	}

	/**
	 * Checks if a value is a boolean.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is a boolean, false otherwise.
	 */
	public function isBool(mixed $value): bool
	{
		return is_bool($value);
	}

	/**
	 * Checks if a value is callable.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is callable, false otherwise.
	 */
	public function isCallable(mixed $value): bool
	{
		return is_callable($value);
	}

	/**
	 * Checks if a value is countable.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is countable, false otherwise.
	 */
	public function isCountable(mixed $value): bool
	{
		return is_countable($value);
	}

	/**
	 * Checks if a value is a valid directory path.
	 *
	 * @param string $value The path to check.
	 * @return bool True if the path is a directory, false otherwise.
	 */
	public function isDirectory(string $value): bool
	{
		return is_dir($value);
	}

	/**
	 * Checks if a value is a valid file path.
	 *
	 * @param string $value The path to check.
	 * @return bool True if the path is a file, false otherwise.
	 */
	public function isFile(string $value): bool
	{
		return is_file($value);
	}

	/**
	 * Checks if a value is a float.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is a float, false otherwise.
	 */
	public function isFloat(mixed $value): bool
	{
		return is_float($value);
	}

	/**
	 * Checks if a value is an integer.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is an integer, false otherwise.
	 */
	public function isInt(mixed $value): bool
	{
		return is_int($value);
	}

	/**
	 * Checks if a value is iterable.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is iterable, false otherwise.
	 */
	public function isIterable(mixed $value): bool
	{
		return is_iterable($value);
	}

	/**
	 * Checks if a value is null.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is null, false otherwise.
	 */
	public function isNull(mixed $value): bool
	{
		return is_null($value);
	}

	/**
	 * Checks if a value is numeric.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is numeric, false otherwise.
	 */
	public function isNumeric(mixed $value): bool
	{
		return is_numeric($value);
	}

	/**
	 * Checks if a value is an object.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is an object, false otherwise.
	 */
	public function isObject(mixed $value): bool
	{
		return is_object($value);
	}

	/**
	 * Checks if a value is a resource.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is a resource, false otherwise.
	 */
	public function isResource(mixed $value): bool
	{
		return is_resource($value);
	}

	/**
	 * Checks if a value is scalar.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is scalar, false otherwise.
	 */
	public function isScalar(mixed $value): bool
	{
		return is_scalar($value);
	}

	/**
	 * Checks if a value is an integer or null.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is an integer or null, false otherwise.
	 */
	public function isIntegerOrNull(mixed $value): bool
	{
		return is_int($value) || is_null($value);
	}

	/**
	 * Checks if a value is a string.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is a string, false otherwise.
	 */
	public function isString(mixed $value): bool
	{
		return is_string($value);
	}

	/**
	 * Checks if a path is a symbolic link.
	 *
	 * @param string $value The path to check.
	 * @return bool True if the path is a symbolic link, false otherwise.
	 */
	public function isLink(string $value): bool
	{
		return is_link($value);
	}

	/**
	 * Checks if an object or class is a subclass of another class.
	 *
	 * @param object|string $objectOrClass The object or class name to check.
	 * @param string $className            The parent class name.
	 * @return bool True if the object or class is a subclass, false otherwise.
	 */
	public function isSubclassOf(object|string $objectOrClass, string $className): bool
	{
		return is_subclass_of($objectOrClass, $className);
	}

	/**
	 * Checks if a file is an uploaded file.
	 *
	 * @param string $fileName The file path to check.
	 * @return bool True if the file is an uploaded file, false otherwise.
	 */
	public function isUploadedFile(string $fileName): bool
	{
		return is_uploaded_file($fileName);
	}

	/**
	 * Checks if a file is writable.
	 *
	 * @param string $fileName The file path to check.
	 * @return bool True if the file is writable, false otherwise.
	 */
	public function isWritable(string $fileName): bool
	{
		return is_writable($fileName);
	}

	/**
	 * Checks if a file is readable.
	 *
	 * @param string $fileName The file path to check.
	 * @return bool True if the file is readable, false otherwise.
	 */
	public function isReadable(string $fileName): bool
	{
		return is_readable($fileName);
	}

	/**
	 * Checks if a value is empty.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is empty, false otherwise.
	 */
	public function isEmpty(mixed $value): bool
	{
		return empty($value);
	}

	/**
	 * Checks if a variable is set.
	 *
	 * @param mixed $value The variable to check.
	 * @return bool True if the variable is set, false otherwise.
	 */
	public function isSet(mixed $value): bool
	{
		return isset($value);
	}
	
	    /**
     * Checks if a value exists within an array.
     *
     * Performs functionality similar to PHP's `in_array()` function,
     * determining whether a specific value is present in a given array.
     *
     * @param mixed $needle The value to search for in the array.
     * @param array $haystack The array to search within.
     * @param bool $strict Optional. Whether to use strict comparison (type and value).
     *                     Defaults to false.
     * @return bool True if the value exists in the array, false otherwise.
     */
    public function isInArray(mixed $needle, array $haystack, bool $strict = false): bool
    {
        return in_array($needle, $haystack, $strict);
    }
}
