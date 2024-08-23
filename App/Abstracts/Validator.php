<?php

namespace App\Abstracts;

use App\Contracts\ValidatorInterface;
use App\Helpers\Traits\TypeCheckTrait;
use App\Helpers\Traits\ExistenceCheckTrait;
use App\Helpers\Traits\ArrayUtilsTrait;
use App\Helpers\Traits\LoopUtilsTrait;
use App\Exceptions\ValidationException;

/**
 * Abstract class Validator
 *
 * Provides the foundation for validating data with support for applying rules.
 */
abstract class Validator implements ValidatorInterface
{
    use TypeCheckTrait, ExistenceCheckTrait, ArrayUtilsTrait, LoopUtilsTrait;

    /**
     * @var array $context Contextual data used during the validation process.
     */
    protected array $context = [
        'methodPrefix' => 'validate',   // Prefix for validation methods
        'rulePrefix'   => 'rule',       // Suffix for rule methods
        'method'       => '',           // Current validation method
        'rule'         => '',           // Current rule method
        'data'         => [             // Data to be validated
            'fields' => [],             // Fields of the data
            'values' => [],             // Values associated with the fields
            'types'  => [],             // Types of the fields
            'rules'  => [],             // Rules applied to the fields
        ],
        'current'      => [             // Current field being processed
            'field' => '',              // Field name
            'value' => null,            // Field value
            'type'  => '',              // Field type
        ],
        'result'       => [],           // Validation results
        'errors'       => [],           // Errors encountered during validation
    ];

    /**
     * Abstract method to be implemented by the extending class for validation.
     *
     * @param mixed $data The data to be validated.
     * @return array The validation result.
     */
    abstract protected function validate(mixed $data): array;

    /**
     * Main processing function to execute validation.
     *
     * @param mixed $data The data to be validated.
     * @return array The validation result.
     */
    protected function run(mixed $data): array
    {
        try {
            $this->prepare($data);
            $this->validateAll();
        } catch (ValidationException $e) {
            $this->context['errors'][] = $e->getMessage();
        }

        return $this->context['result'];
    }

    /**
     * Prepare and organize the data before validation.
     *
     * @param mixed $data The data to be prepared.
     * @return void
     */
    protected function prepare(mixed $data): void
    {
        if ($this->isArray($data)) {
            $this->context['data']['fields'] = $this->getKeys($data);
            $this->context['data']['values'] = $this->extractColumn($data, 'value');
            $this->context['data']['types']  = $this->extractColumn($data, 'type');
            $this->context['data']['rules']  = $this->extractColumn($data, 'rules', []);
        } elseif ($this->isString($data)) {
            $this->context['data']['fields'] = ['default'];
            $this->context['data']['values'] = [$data];
            $this->context['data']['types']  = ['string'];
            $this->context['data']['rules']  = [[]];
        } elseif ($this->isInt($data)) {
            $this->context['data']['fields'] = ['default'];
            $this->context['data']['values'] = [$data];
            $this->context['data']['types']  = ['int'];
            $this->context['data']['rules']  = [[]];
        }
    }

    /**
     * Iterate through all fields and apply validation.
     *
     * @return void
     */
    protected function validateAll(): void
    {
        $this->iterate($this->context['data']['fields'], function ($field) {
            $this->context['current']['field'] = $field;
            $this->context['current']['value'] = $this->context['data']['values'][$field];
            $this->context['current']['type']  = $this->context['data']['types'][$field];

            $this->validateField();
        });
    }

    /**
     * Validate a single field and apply any associated rules.
     *
     * @return void
     */
    protected function validateField(): void
    {
        try {
            if (!$this->callValidateMethod()) {
                $this->context['result'][$this->context['current']['field']] = false;
                return;
            }

            if ($this->rulesExist()) {
                $this->applyRules();
            }

            $this->context['result'][$this->context['current']['field']] = true;
        } catch (ValidationException $e) {
            $this->context['errors'][] = $e->getMessage();
            $this->context['result'][$this->context['current']['field']] = false;
        }
    }

    /**
     * Dynamically call the appropriate validation method based on the type.
     *
     * @return bool True if validation is successful, false otherwise.
     * @throws ValidationException if the method does not exist.
     */
    protected function callValidateMethod(): bool
    {
        $this->context['method'] = $this->context['methodPrefix'] . ucfirst($this->context['current']['type']);

        if ($this->methodExists($this, $this->context['method'])) {
            return $this->{$this->context['method']}($this->context['current']['value']);
        }

        throw new ValidationException("Validation method '{$this->context['method']}' not found.");
    }

    /**
     * Apply the relevant rules to the validated value.
     *
     * @return void
     * @throws ValidationException if the rule method does not exist.
     */
    protected function applyRules(): void
    {
        $this->iterate($this->context['data']['rules'][$this->context['current']['field']], function ($rule, $ruleValue) {
            $this->context['rule'] =  $this->context['rulePrefix'] . ucfirst($rule);

            if ($this->methodExists($this, $this->context['rule'])) {
                if (!$this->{$this->context['rule']}($this->context['current']['value'], $ruleValue)) {
                    throw new ValidationException("Rule '{$this->context['rule']}' failed for field '{$this->context['current']['field']}'.");
                }
            } else {
                throw new ValidationException("Rule method '{$this->context['rule']}' not found.");
            }
        });
    }

    /**
     * Check if rules are defined for the current field.
     *
     * @return bool True if rules exist, false otherwise.
     */
    protected function rulesExist(): bool
    {
        return $this->isArray($this->context['data']['rules'][$this->context['current']['field']]);
    }

    /**
     * Retrieve any errors encountered during the validation process.
     *
     * @return array The array of error messages.
     */
    protected function getErrors(): array
    {
        return $this->context['errors'];
    }
}
