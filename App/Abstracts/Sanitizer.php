<?php

namespace App\Utilities\Sanitation;

use App\Exceptions\SanitizationException;
use App\Utilities\Managers\ReflectionManager;
use App\Helpers\ArrayHelper;
use App\Helpers\ExistenceChecker;
use ReflectionMethod;

/**
 * Class AbstractSanitizer
 *
 * Abstract class for implementing sanitization logic using reflection.
 */
abstract class Sanitizer implements SanitizerInterface
{
    protected array $data;
    protected ReflectionManager $reflectionManager;
    protected ArrayHelper $arrayHelper;
    protected ExistenceChecker $existenceChecker;

    /**
     * AbstractSanitizer constructor.
     *
     * @param array $data Data to sanitize.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->reflectionManager = new ReflectionManager();
        $this->arrayHelper = new ArrayHelper();
        $this->existenceChecker = new ExistenceChecker();

        try {
            $this->handle();
        } catch (SanitizationException $e) {
            // Handle sanitization exception
            throw $e;
        }
    }

    /**
     * Handle sanitization by invoking methods starting with "sanitize".
     *
     * @throws SanitizationException
     */
    public function handle(): void
    {
        $reflectionClass = $this->reflectionManager->getClassInfo($this);

        // Get sanitization methods
        $sanitizationMethods = $this->getPrefixedMethods($reflectionClass, 'sanitize');

        // Apply sanitization methods
        foreach ($sanitizationMethods as $method) {
            $this->invokeMethodIfExists($method);
        }
    }

    /**
     * Get methods that start with a specific prefix (e.g., "sanitize").
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
     * Invoke the method if it exists on the current class.
     *
     * @param ReflectionMethod $method Reflection method to invoke.
     * @throws SanitizationException If method invocation fails.
     */
    private function invokeMethodIfExists(ReflectionMethod $method): void
    {
        if ($this->existenceChecker->methodExists($this, $method->getName())) {
            try {
                $this->reflectionManager->invokeMethod($method, $this, $this->data);
            } catch (\Exception $e) {
                throw new SanitizationException("Sanitization method {$method->getName()} failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Abstract sanitize method to be implemented by subclasses.
     *
     * @return void
     */
    abstract protected function sanitize(): void;
}
