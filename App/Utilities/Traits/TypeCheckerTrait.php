<?php

namespace App\Utilities\Traits;

/**
 * Trait TypeCheckerTrait
 *
 * Provides utility methods to check the types and properties of various PHP entities.
 */
trait TypeCheckerTrait
{
	public function isArray(mixed $value): bool
	{
		return is_array($value);
	}

	public function isBool(mixed $value): bool
	{
		return is_bool($value);
	}

	public function isCallable(mixed $value): bool
	{
		return is_callable($value);
	}

	public function isCountable(mixed $value): bool
	{
		return is_countable($value);
	}

	public function isDirectory(string $value): bool
	{
		return is_dir($value);
	}

	public function isFile(string $value): bool
	{
		return is_file($value);
	}

	public function isFloat(mixed $value): bool
	{
		return is_float($value);
	}

	public function isInt(mixed $value): bool
	{
		return is_int($value);
	}

	public function isIterable(mixed $value): bool
	{
		return is_iterable($value);
	}

	public function isNull(mixed $value): bool
	{
		return is_null($value);
	}

	public function isNumeric(mixed $value): bool
	{
		return is_numeric($value);
	}

	public function isObject(mixed $value): bool
	{
		return is_object($value);
	}

	public function isResource(mixed $value): bool
	{
		return is_resource($value);
	}

	public function isScalar(mixed $value): bool
	{
		return is_scalar($value);
	}

	public function isIntegerOrNull(mixed $value): bool
	{
		return is_int($value) || is_null($value);
	}

	public function isString(mixed $value): bool
	{
		return is_string($value);
	}

	public function isLink(string $value): bool
	{
		return is_link($value);
	}

	public function isSubclassOf(object|string $objectOrClass, string $className): bool
	{
		return is_subclass_of($objectOrClass, $className);
	}

	public function isUploadedFile(string $fileName): bool
	{
		return is_uploaded_file($fileName);
	}

	public function isWritable(string $fileName): bool
	{
		return is_writable($fileName);
	}

	public function isReadable(string $fileName): bool
	{
		return is_readable($fileName);
	}

	public function isEmpty(mixed $value): bool
	{
		return empty($value);
	}

	public function isSet(mixed $value): bool
	{
		return isset($value);
	}
}
