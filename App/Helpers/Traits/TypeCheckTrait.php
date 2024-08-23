<?php

namespace App\Helpers\Traits;

trait TypeCheckTrait
{
    /**
     * Check if the given value is an array.
     *
     * @param mixed $value
     * @return bool
     */
    public function isArray($value): bool
    {
        return is_array($value);
    }

    /**
     * Check if the given value is a string.
     *
     * @param mixed $value
     * @return bool
     */
    public function isString($value): bool
    {
        return is_string($value);
    }

    /**
     * Check if the given value is empty.
     *
     * @param mixed $value
     * @return bool
     */
    public function isEmpty($value): bool
    {
        return empty($value);
    }

    /**
     * Check if the given value is null.
     *
     * @param mixed $value
     * @return bool
     */
    public function isNull($value): bool
    {
        return is_null($value);
    }

    /**
     * Check if the given value is set.
     *
     * @param mixed $value
     * @return bool
     */
    public function isSet($value): bool
    {
        return isset($value);
    }

    /**
     * Check if the given value is scalar (integer, float, string, or boolean).
     *
     * @param mixed $value
     * @return bool
     */
    public function isScalar($value): bool
    {
        return is_scalar($value);
    }

    /**
     * Check if the given value is numeric.
     *
     * @param mixed $value
     * @return bool
     */
    public function isNumeric($value): bool
    {
        return is_numeric($value);
    }

    /**
     * Check if the given value is an object.
     *
     * @param mixed $value
     * @return bool
     */
    public function isObject($value): bool
    {
        return is_object($value);
    }

    /**
     * Check if the given value is callable.
     *
     * @param mixed $value
     * @return bool
     */
    public function isCallable($value): bool
    {
        return is_callable($value);
    }

    /**
     * Check if the given value is an integer.
     *
     * @param mixed $value
     * @return bool
     */
    public function isInt($value): bool
    {
        return is_int($value);
    }

    /**
     * Check if the given value is a float.
     *
     * @param mixed $value
     * @return bool
     */
    public function isFloat($value): bool
    {
        return is_float($value);
    }

    /**
     * Check if the given value is a boolean.
     *
     * @param mixed $value
     * @return bool
     */
    public function isBool($value): bool
    {
        return is_bool($value);
    }

    /**
     * Check if the given value is a resource.
     *
     * @param mixed $value
     * @return bool
     */
    public function isResource($value): bool
    {
        return is_resource($value);
    }

    /**
     * Check if the given value is iterable.
     *
     * @param mixed $value
     * @return bool
     */
    public function isIterable($value): bool
    {
        return is_iterable($value);
    }

    /**
     * Check if the given value is a directory.
     *
     * @param string $value
     * @return bool
     */
    public function isDirectory(string $value): bool
    {
        return is_dir($value);
    }

    /**
     * Check if the given value is a file.
     *
     * @param string $value
     * @return bool
     */
    public function isFile(string $value): bool
    {
        return is_file($value);
    }

    /**
     * Check if the given value is a symbolic link.
     *
     * @param string $value
     * @return bool
     */
    public function isLink(string $value): bool
    {
        return is_link($value);
    }

    /**
     * Check if the value is a subclass of a class.
     *
     * @param object|string $objectOrClass
     * @param string $className
     * @return bool
     */
    public function isSubclassOf($objectOrClass, string $className): bool
    {
        return is_subclass_of($objectOrClass, $className);
    }

    /**
     * Check if the file was uploaded via HTTP POST.
     *
     * @param string $fileName
     * @return bool
     */
    public function isUploadedFile(string $fileName): bool
    {
        return is_uploaded_file($fileName);
    }

    /**
     * Check if the specified filename is writable.
     *
     * @param string $fileName
     * @return bool
     */
    public function isWritable(string $fileName): bool
    {
        return is_writable($fileName);
    }

    /**
     * Check if the specified filename is readable.
     *
     * @param string $fileName
     * @return bool
     */
    public function isReadable(string $fileName): bool
    {
        return is_readable($fileName);
    }

    /**
     * Check if the given value is countable.
     *
     * @param mixed $value
     * @return bool
     */
    public function isCountable($value): bool
    {
        return is_countable($value);
    }
}
