<?php

namespace App\Helpers;

/**
 * Class TypeChecker
 *
 * Provides utility methods to check the types and properties of various PHP entities.
 */
class TypeChecker
{
	/**
	 * Check if the given value is an array.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is an array, false otherwise.
	 */
	public function isArray($value): bool
	{
		return is_array($value);
	}

	/**
	 * Check if the given value is a boolean.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is a boolean, false otherwise.
	 */
	public function isBool($value): bool
	{
		return is_bool($value);
	}

	/**
	 * Check if the given value is callable.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is callable, false otherwise.
	 */
	public function isCallable($value): bool
	{
		return is_callable($value);
	}

	/**
	 * Check if the given value is countable.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is countable, false otherwise.
	 */
	public function isCountable($value): bool
	{
		return is_countable($value);
	}

	/**
	 * Check if the given value is a directory.
	 *
	 * @param string $value The path to check.
	 * @return bool True if the value is a directory, false otherwise.
	 */
	public function isDirectory(string $value): bool
	{
		return is_dir($value);
	}

	/**
	 * Check if the given value is a file.
	 *
	 * @param string $value The path to check.
	 * @return bool True if the value is a file, false otherwise.
	 */
	public function isFile(string $value): bool
	{
		return is_file($value);
	}

	/**
	 * Check if the given value is a float.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is a float, false otherwise.
	 */
	public function isFloat($value): bool
	{
		return is_float($value);
	}

	/**
	 * Check if the given value is an integer.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is an integer, false otherwise.
	 */
	public function isInt($value): bool
	{
		return is_int($value);
	}

	/**
	 * Check if the given value is iterable.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is iterable, false otherwise.
	 */
	public function isIterable($value): bool
	{
		return is_iterable($value);
	}

	/**
	 * Check if the given value is null.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is null, false otherwise.
	 */
	public function isNull($value): bool
	{
		return is_null($value);
	}

	/**
	 * Check if the given value is numeric.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is numeric, false otherwise.
	 */
	public function isNumeric($value): bool
	{
		return is_numeric($value);
	}

	/**
	 * Check if the given value is an object.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is an object, false otherwise.
	 */
	public function isObject($value): bool
	{
		return is_object($value);
	}

	/**
	 * Check if the given value is a resource.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is a resource, false otherwise.
	 */
	public function isResource($value): bool
	{
		return is_resource($value);
	}

	/**
	 * Check if the given value is scalar (integer, float, string, or boolean).
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is scalar, false otherwise.
	 */
	public function isScalar($value): bool
	{
		return is_scalar($value);
	}

	/**
	 * Check if the given value is an integer or null.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is an integer or null, false otherwise.
	 */
	public function isIntegerOrNull($value): bool
	{
		return is_int($value) || is_null($value);
	}

	/**
	 * Check if the given value is a string.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is a string, false otherwise.
	 */
	public function isString($value): bool
	{
		return is_string($value);
	}


	/**
	 * Check if the given value is a symbolic link.
	 *
	 * @param string $value The path to check.
	 * @return bool True if the value is a symbolic link, false otherwise.
	 */
	public function isLink(string $value): bool
	{
		return is_link($value);
	}

	/**
	 * Check if the value is a subclass of a class.
	 *
	 * @param object|string $objectOrClass The object or class name to check.
	 * @param string $className The class name to compare with.
	 * @return bool True if the object or class is a subclass of the specified class, false otherwise.
	 */
	public function isSubclassOf($objectOrClass, string $className): bool
	{
		return is_subclass_of($objectOrClass, $className);
	}

	/**
	 * Check if the file was uploaded via HTTP POST.
	 *
	 * @param string $fileName The file name to check.
	 * @return bool True if the file was uploaded via HTTP POST, false otherwise.
	 */
	public function isUploadedFile(string $fileName): bool
	{
		return is_uploaded_file($fileName);
	}

	/**
	 * Check if the specified filename is writable.
	 *
	 * @param string $fileName The file name to check.
	 * @return bool True if the file is writable, false otherwise.
	 */
	public function isWritable(string $fileName): bool
	{
		return is_writable($fileName);
	}

	/**
	 * Check if the specified filename is readable.
	 *
	 * @param string $fileName The file name to check.
	 * @return bool True if the file is readable, false otherwise.
	 */
	public function isReadable(string $fileName): bool
	{
		return is_readable($fileName);
	}

	/**
	 * Check if the given value is empty.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is empty, false otherwise.
	 */
	public function isEmpty($value): bool
	{
		return empty($value);
	}

	/**
	 * Check if the given value is set.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is set, false otherwise.
	 */
	public function isSet($value): bool
	{
		return isset($value);
	}
}
