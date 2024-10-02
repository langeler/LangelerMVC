<?php

namespace App\Abstracts\Data;

use AppValidationExceptions\Data\ValidationException;
use App\Contracts\Data\ValidatorInterface;
use App\Utilities\Managers\ReflectionManager;
use App\Helpers\ArrayHelper;
use App\Helpers\ExistenceChecker;

/**
 * Class Validator
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
     * Validator constructor.
     *
     * @param array|null $data Data to validate.
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
     * Handle validation by invoking methods starting with "validate" or ending with "check".
     *
     * @throws ValidationException
     */
    protected function handle(mixed $data): array
    {
        try {
            $methods = $this->arrayHelper->filter(
                $this->reflectionManager->getClassMethods($this->reflectionManager->getClassInfo($this)),
                fn($method) => strpos($method->getName(), 'validate') === 0 || substr($method->getName(), -5) === 'check'
            );

            return $this->invokeValidationMethods($methods);
        } catch (\Exception $e) {
            throw new ValidationException("Error during validation: " . $e->getMessage());
        }
    }

    /**
     * Invoke the validation method if it exists on the current class.
     *
     * @param array $methods Reflection methods to invoke.
     * @throws ValidationException If method invocation fails.
     */
    private function invokeValidationMethods(array $methods): array
    {
        foreach ($methods as $method) {
            if ($this->existenceChecker->methodExists($this, $method->getName())) {
                try {
                    $this->reflectionManager->invokeMethod($method, $this, $this->data);
                } catch (\Exception $e) {
                    throw new ValidationException("Validation method {$method->getName()} failed: " . $e->getMessage());
                }
            }
        }

        return $this->data;
    }

    // === Basic validation methods ===

    /**
     * Validate if a string is a valid email.
     *
     * @param string $email
     * @return bool
     */
    protected function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate if a string matches a specific regular expression.
     *
     * @param string $value
     * @param string $pattern
     * @return bool
     */
    protected function validateRegex(string $value, string $pattern): bool
    {
        return preg_match($pattern, $value) === 1;
    }

    /**
     * Validate if a string is a valid URL.
     *
     * @param string $url
     * @return bool
     */
    protected function validateUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check if a value is not empty.
     *
     * @param mixed $value
     * @return bool
     */
    protected function validateRequired($value): bool
    {
        return !empty($value);
    }

    /**
     * Check if a string has a minimum length.
     *
     * @param string $value
     * @param int $minLength
     * @return bool
     */
    protected function validateMinLength(string $value, int $minLength): bool
    {
        return strlen($value) >= $minLength;
    }

    /**
     * Check if a string has a maximum length.
     *
     * @param string $value
     * @param int $maxLength
     * @return bool
     */
    protected function validateMaxLength(string $value, int $maxLength): bool
    {
        return strlen($value) <= $maxLength;
    }

    /**
     * Abstract method for custom validation logic.
     *
     * @return array
     */
    abstract protected function verify(mixed $data): array;
}
