<?php

namespace App\Helpers\Traits;

trait ExistenceCheckTrait
{
    /**
     * Check if the class exists.
     *
     * @param string $className
     * @return bool
     */
    public function classExists(string $className): bool
    {
        return class_exists($className);
    }

    /**
     * Check if the interface exists.
     *
     * @param string $interfaceName
     * @return bool
     */
    public function interfaceExists(string $interfaceName): bool
    {
        return interface_exists($interfaceName);
    }

    /**
     * Check if the trait exists.
     *
     * @param string $traitName
     * @return bool
     */
    public function traitExists(string $traitName): bool
    {
        return trait_exists($traitName);
    }

    /**
     * Check if the method exists in a given class or object.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * @return bool
     */
    public function methodExists($objectOrClass, string $methodName): bool
    {
        return method_exists($objectOrClass, $methodName);
    }

    /**
     * Check if a property exists in a given class or object.
     *
     * @param object|string $objectOrClass
     * @param string $propertyName
     * @return bool
     */
    public function propertyExists($objectOrClass, string $propertyName): bool
    {
        return property_exists($objectOrClass, $propertyName);
    }

    /**
     * Check if a constant exists in a given class or object.
     *
     * @param string $className
     * @param string $constantName
     * @return bool
     */
    public function constantExists(string $className, string $constantName): bool
    {
        return defined("$className::$constantName");
    }

    /**
     * Check if a function exists.
     *
     * @param string $functionName
     * @return bool
     */
    public function functionExists(string $functionName): bool
    {
        return function_exists($functionName);
    }

    /**
     * Check if a file or directory exists.
     *
     * @param string $path
     * @return bool
     */
    public function fileExists(string $path): bool
    {
        return file_exists($path);
    }
}
