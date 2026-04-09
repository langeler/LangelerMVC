<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Exceptions\Data\ValidationException;
use App\Utilities\Sanitation\GeneralSanitizer;
use App\Utilities\Validation\GeneralValidator;
use App\Utilities\Validation\PatternValidator;
use PHPUnit\Framework\TestCase;

class ValidationAndSanitizationTest extends TestCase
{
    public function testGeneralValidatorSupportsSchemaAndValuesSeparately(): void
    {
        $validator = new GeneralValidator();

        $validated = $validator->verify(
            ['uri' => ['url', ['pathRequired']]],
            ['uri' => 'https://example.com/test']
        );

        self::assertSame('https://example.com/test', $validated['uri']);
    }

    public function testGeneralSanitizerPassesFlagsToSanitizationMethods(): void
    {
        $sanitizer = new GeneralSanitizer();

        $sanitized = $sanitizer->clean(
            ['value' => ['string', ['stripLow', 'stripHigh']]],
            ['value' => "hello\x01"]
        );

        self::assertSame('hello', $sanitized['value']);
    }

    public function testPatternValidatorAppliesNumericRulesToStringInput(): void
    {
        $validator = new PatternValidator();

        $validated = $validator->verify(
            ['id' => ['intPos', ['between' => [1, 10]]]],
            ['id' => '5']
        );

        self::assertSame('5', $validated['id']);
    }

    public function testGeneralValidatorDoesNotApplyAllFilterFlagsByDefault(): void
    {
        $validator = new GeneralValidator();

        $validated = $validator->verify(
            ['url' => ['url']],
            ['url' => 'https://example.com']
        );

        self::assertSame('https://example.com', $validated['url']);
    }

    public function testGeneralSanitizerSupportsNestedNamedSchemasAndDefaults(): void
    {
        $sanitizer = new GeneralSanitizer();

        $sanitized = $sanitizer->clean(
            [
                'user' => [
                    'schema' => [
                        'email' => ['methods' => 'email'],
                        'bio' => [
                            'methods' => 'string',
                            'options' => ['stripLow'],
                            'rules' => ['notEmpty'],
                        ],
                        'nickname' => [
                            'methods' => 'string',
                            'default' => ' Guest ',
                        ],
                    ],
                ],
                'tags' => [
                    'each' => [
                        'methods' => 'string',
                        'rules' => ['notEmpty'],
                    ],
                    'rules' => ['arrayNotEmpty'],
                ],
            ],
            [
                'user' => [
                    'email' => 'person@example.com',
                    'bio' => "Hello\x01 world",
                ],
                'tags' => ['news', 'updates'],
            ]
        );

        self::assertSame('person@example.com', $sanitized['user']['email']);
        self::assertSame('Hello world', $sanitized['user']['bio']);
        self::assertSame(' Guest ', $sanitized['user']['nickname']);
        self::assertSame(['news', 'updates'], $sanitized['tags']);
    }

    public function testGeneralValidatorSupportsOptionalNullableAndInlineValues(): void
    {
        $validator = new GeneralValidator();

        $validated = $validator->verify([
            'name' => [
                'methods' => 'regexp',
                'options' => ['pattern' => '/^[A-Z][a-z]+$/'],
                'value' => 'Alice',
            ],
            'website' => [
                'methods' => 'url',
                'required' => false,
            ],
            'profile' => [
                'schema' => [
                    'nickname' => [
                        'rules' => ['notEmpty'],
                        'nullable' => true,
                    ],
                ],
                'value' => ['nickname' => null],
            ],
        ]);

        self::assertSame('Alice', $validated['name']);
        self::assertArrayNotHasKey('website', $validated);
        self::assertNull($validated['profile']['nickname']);
    }

    public function testPatternValidatorRejectsInvalidIpv4Addresses(): void
    {
        $validator = new PatternValidator();

        $this->expectException(ValidationException::class);

        $validator->verify(
            ['ip' => ['ipv4']],
            ['ip' => '999.999.999.999']
        );
    }

    public function testRuleSequentialRequiresOrderedSequenceUnlessGapsAreAllowed(): void
    {
        $validator = new PatternValidator();

        $validated = $validator->verify(
            [
                'ordered' => [
                    'rules' => ['sequential'],
                ],
                'gapped' => [
                    'rules' => ['sequential' => [true]],
                ],
            ],
            [
                'ordered' => [1, 2, 3],
                'gapped' => [1, 3, 5],
            ]
        );

        self::assertSame([1, 2, 3], $validated['ordered']);
        self::assertSame([1, 3, 5], $validated['gapped']);
    }
}
