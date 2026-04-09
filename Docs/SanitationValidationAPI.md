# Sanitation & Validation API

LangelerMVC exposes two schema-driven data pipelines:

- `App\Utilities\Sanitation\GeneralSanitizer`
- `App\Utilities\Sanitation\PatternSanitizer`
- `App\Utilities\Validation\GeneralValidator`
- `App\Utilities\Validation\PatternValidator`

All four now share the same field-definition contract.

## Supported field definitions

### 1. Legacy positional syntax

```php
[
    'email' => ['email'],
    'price' => ['float', ['allowFraction'], ['min' => 1]],
]
```

### 2. Explicit named syntax

```php
[
    'email' => [
        'methods' => 'email',
    ],
    'slug' => [
        'methods' => ['string'],
        'rules' => ['notEmpty'],
    ],
]
```

### 3. Nested object schema

```php
[
    'user' => [
        'schema' => [
            'email' => ['methods' => 'email'],
            'nickname' => [
                'methods' => 'string',
                'nullable' => true,
            ],
        ],
    ],
]
```

### 4. Collection item schema

```php
[
    'tags' => [
        'each' => [
            'methods' => 'string',
            'rules' => ['notEmpty'],
        ],
        'rules' => ['arrayNotEmpty'],
    ],
]
```

## Supported metadata

- `methods` / `method`: one method name or a list of method names
- `options` / `option`: method arguments, either list-based or named by parameter
- `rules` / `rule`: one rule or a rule map
- `required`: defaults to `true`; when `false`, missing keys are skipped
- `nullable`: defaults to `false`; when `true`, `null` bypasses method/rule processing
- `default`: fallback value used when the key is missing
- `value`: inline field value used when no external payload is passed
- `schema` / `fields`: nested object definition
- `each`: per-item definition for arrays

## Processing behavior

- Sanitizers apply methods first, then rules.
- Validators apply validation methods first, then rules.
- Nested `schema` and `each` definitions may use container-level rules.
- `methods` cannot be combined with `schema` or `each` on the same field.

## Example

```php
$validator->verify(
    [
        'profile' => [
            'schema' => [
                'email' => ['methods' => 'email'],
                'website' => [
                    'methods' => 'url',
                    'required' => false,
                ],
            ],
        ],
        'tags' => [
            'each' => [
                'methods' => 'string',
                'rules' => ['notEmpty'],
            ],
        ],
    ],
    [
        'profile' => ['email' => 'person@example.com'],
        'tags' => ['news', 'updates'],
    ]
);
```
