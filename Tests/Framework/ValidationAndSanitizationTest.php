<?php

declare(strict_types=1);

namespace Tests\Framework;

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
}
