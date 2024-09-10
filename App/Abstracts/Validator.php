<?php

namespace App\Utilities\Validation;

use App\Exceptions\ValidationException;
use App\Utilities\Managers\ReflectionManager;
use App\Helpers\ArrayHelper;
use App\Helpers\ExistenceChecker;
use ReflectionMethod;

/**
 * Class AbstractValidator
 *
 * Abstract class for implementing validation logic using reflection.
 */
abstract class Validator implements ValidatorInterface
{
    protected array $data;
    protected ReflectionManager $reflectionManager;
    protected ArrayHelper $arrayHelper;
    protected ExistenceChecker $existenceChecker;

    /**
     * AbstractValidator constructor.
     *
     * @param array $data Data to validate.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->reflectionManager = new ReflectionManager();
        $this->arrayHelper = new ArrayHelper();
        $this->existenceChecker = new ExistenceChecker();

        try {
            $this->handle();
        } catch (ValidationException $e) {
            // Handle validation exception (you can log, rethrow, or perform other actions)
            throw $e;
        }
    }

    /**
     * Handle validation by invoking methods starting with "validate" or ending with "check".
     *
     * @throws ValidationException
     */
    public function handle(): void
    {
        $reflectionClass = $this->reflectionManager->getClassInfo($this);

        // Get validation methods and rule checks
        $validationMethods = $this->getPrefixedMethods($reflectionClass, 'validate');
        $checkMethods = $this->getSuffixedMethods($reflectionClass, 'check');

        // Apply validation methods
        foreach ($validationMethods as $method) {
            $this->invokeMethodIfExists($method);
        }

        // Apply rule-check methods
        foreach ($checkMethods as $method) {
            $this->invokeMethodIfExists($method);
        }
    }

    /**
     * Get methods that start with a specific prefix (e.g., "validate").
     *
     * @param object $reflectionClass ReflectionClass instance of the current class.
     * @param string $prefix Prefix to search for.
     * @return array Filtered methods.
     */
    private function getPrefixedMethods($reflectionClass, string $prefix): array
    {
        $methods = $this->reflectionManager->getClassMethods($reflectionClass);
        return $this->arrayHelper->filter($methods, function ($method) use ($prefix) {
            return strpos($method->getName(), $prefix) === 0;
        });
    }

    /**
     * Get methods that end with a specific suffix (e.g., "check").
     *
     * @param object $reflectionClass ReflectionClass instance of the current class.
     * @param string $suffix Suffix to search for.
     * @return array Filtered methods.
     */
    private function getSuffixedMethods($reflectionClass, string $suffix): array
    {
        $methods = $this->reflectionManager->getClassMethods($reflectionClass);
        return $this->arrayHelper->filter($methods, function ($method) use ($suffix) {
            return substr($method->getName(), -strlen($suffix)) === $suffix;
        });
    }

    /**
     * Invoke the method if it exists on the current class.
     *
     * @param ReflectionMethod $method Reflection method to invoke.
     * @throws ValidationException If method invocation fails.
     */
    private function invokeMethodIfExists(ReflectionMethod $method): void
    {
        if ($this->existenceChecker->methodExists($this, $method->getName())) {
            try {
                $this->reflectionManager->invokeMethod($method, $this, $this->data);
            } catch (\Exception $e) {
                throw new ValidationException("Validation method {$method->getName()} failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Abstract validate method to be implemented by subclasses.
     *
     * @return void
     */
    abstract protected function validate(): void;
}
