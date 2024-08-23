<?php

namespace App\Abstracts;

use App\Contracts\SanitizerInterface;
use App\Helpers\Traits\TypeCheckTrait;
use App\Helpers\Traits\ExistenceCheckTrait;
use App\Helpers\Traits\ArrayUtilsTrait;
use App\Helpers\Traits\LoopUtilsTrait;
use App\Exceptions\SanitizationException;

/**
 * Abstract class Sanitizer
 *
 * Provides the foundation for sanitizing data with support for applying rules.
 */
abstract class Sanitizer implements SanitizerInterface
{
    use TypeCheckTrait, ExistenceCheckTrait, ArrayUtilsTrait, LoopUtilsTrait;

    /**
     * @var array $context Contextual data used during the sanitization process.
     */
    protected array $context = [
        'methodPrefix' => 'sanitize',   // Prefix for sanitization methods
        'rulePrefix'   => 'rule',       // Suffix for rule methods
        'method'       => '',           // Current sanitization method
        'rule'         => '',           // Current rule method
        'data'         => [             // Data to be sanitized
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
        'result'       => [],           // Sanitization results
        'errors'       => [],           // Errors encountered during sanitization
    ];

    /**
     * Abstract method to be implemented by the extending class for sanitization.
     *
     * @param mixed $data The data to be sanitized.
     * @return array The sanitized result.
     */
    abstract protected function sanitize(mixed $data): array;

    /**
     * Main processing function to execute sanitization.
     *
     * @param mixed $data The data to be sanitized.
     * @return array The sanitized result.
     */
    protected function run(mixed $data): array
    {
        try {
            $this->prepare($data);
            $this->sanitizeAll();
        } catch (SanitizationException $e) {
            $this->context['errors'][] = $e->getMessage();
        }

        return $this->context['result'];
    }

    /**
     * Prepare and organize the data before sanitization.
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
     * Iterate through all fields and apply sanitization.
     *
     * @return void
     */
    protected function sanitizeAll(): void
    {
        $this->iterate($this->context['data']['fields'], function ($field) {
            $this->context['current']['field'] = $field;
            $this->context['current']['value'] = $this->context['data']['values'][$field];
            $this->context['current']['type']  = $this->context['data']['types'][$field];

            $this->sanitizeField();
        });
    }

    /**
     * Sanitize a single field and apply any associated rules.
     *
     * @return void
     */
    protected function sanitizeField(): void
    {
        try {
            $this->context['current']['value'] = $this->callSanitizeMethod();

            if ($this->rulesExist()) {
                $this->applyRules();
            }

            $this->context['result'][$this->context['current']['field']] = $this->context['current']['value'];
        } catch (SanitizationException $e) {
            $this->context['errors'][] = $e->getMessage();
            $this->context['result'][$this->context['current']['field']] = $this->context['current']['value'];
        }
    }

    /**
     * Dynamically call the appropriate sanitization method based on the type.
     *
     * @return mixed The sanitized value.
     * @throws SanitizationException if the method does not exist.
     */
    protected function callSanitizeMethod()
    {
        $this->context['method'] = $this->context['methodPrefix'] . ucfirst($this->context['current']['type']);

        if ($this->methodExists($this, $this->context['method'])) {
            return $this->{$this->context['method']}($this->context['current']['value']);
        }

        throw new SanitizationException("Sanitization method '{$this->context['method']}' not found.");
    }

    /**
     * Apply the relevant rules to the sanitized value.
     *
     * @return void
     * @throws SanitizationException if the rule method does not exist.
     */
    protected function applyRules(): void
    {
        $this->iterate($this->context['data']['rules'][$this->context['current']['field']], function ($rule, $ruleValue) {
            $this->context['rule'] = $this->context['rulePrefix'] . ucfirst($rule);

            if ($this->methodExists($this, $this->context['rule'])) {
                $this->context['current']['value'] = $this->{$this->context['rule']}($this->context['current']['value'], $ruleValue);
            } else {
                throw new SanitizationException("Rule method '{$this->context['rule']}' not found.");
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
     * Retrieve any errors encountered during the sanitization process.
     *
     * @return array The array of error messages.
     */
    protected function getErrors(): array
    {
        return $this->context['errors'];
    }
}
