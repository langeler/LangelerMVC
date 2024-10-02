<?php

namespace App\Abstracts\Data;

use AppSanitationExceptions\Data\SanitizationException;
use App\Contracts\Data\SanitizerInterface;
use App\Utilities\Managers\ReflectionManager;
use App\Helpers\ArrayHelper;
use App\Helpers\ExistenceChecker;

/**
 * Class Sanitizer
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
     * Sanitizer constructor.
     *
     * @param array|null $data Data to sanitize.
     * @param ReflectionManager|null $reflectionManager ReflectionManager instance (optional).
     * @param ArrayHelper|null $arrayHelper ArrayHelper instance (optional).
     * @param ExistenceChecker|null $existenceChecker ExistenceChecker instance (optional).
     */
    public function __construct(
        ?array $data = [],
        ?ReflectionManager $reflectionManager = null,
        ?ArrayHelper $arrayHelper = null,
        ?ExistenceChecker $existenceChecker = null
    ) {
        $this->data = $data ?? [];
        $this->reflectionManager = $reflectionManager ?? new ReflectionManager();
        $this->arrayHelper = $arrayHelper ?? new ArrayHelper();
        $this->existenceChecker = $existenceChecker ?? new ExistenceChecker();
    }

    /**
     * Handle sanitization by invoking methods starting with "sanitize".
     *
     * @throws SanitizationException
     */
    protected function handle(mixed $data): array
    {
        try {
            $methods = $this->arrayHelper->filter(
                $this->reflectionManager->getClassMethods($this->reflectionManager->getClassInfo($this)),
                fn($method) => strpos($method->getName(), 'sanitize') === 0
            );

            return $this->invokeSanitizationMethods($methods);
        } catch (\Exception $e) {
            throw new SanitizationException("Error during sanitization: " . $e->getMessage());
        }
    }

    /**
     * Invoke the sanitization methods.
     *
     * @param array $methods List of reflection methods to invoke.
     * @throws SanitizationException If method invocation fails.
     */
    private function invokeSanitizationMethods(array $methods): array
    {
        foreach ($methods as $method) {
            if ($this->existenceChecker->methodExists($this, $method->getName())) {
                try {
                    $this->reflectionManager->invokeMethod($method, $this, $this->data);
                } catch (\Exception $e) {
                    throw new SanitizationException("Sanitization method {$method->getName()} failed: " . $e->getMessage());
                }
            }
        }

        return $this->data;
    }

    // === Basic default sanitization methods ===

    /**
     * Trim whitespace from a string.
     *
     * @param string $data
     * @return string
     */
    protected function trim(string $data): string
    {
        return trim($data);
    }

    /**
     * Escape HTML entities to prevent XSS attacks.
     *
     * @param string $data
     * @return string
     */
    protected function escapeHTML(string $data): string
    {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize an entire array using basic sanitization functions.
     *
     * @param array $data
     * @return array
     */
    protected function sanitizeArray(array $data): array
    {
        return filter_var_array($data, FILTER_SANITIZE_STRING);
    }

    /**
     * Abstract sanitize method to be implemented by subclasses.
     *
     * @return array
     */
    abstract protected function clean(mixed $data): array;
}
