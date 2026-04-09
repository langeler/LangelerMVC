<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\LoopTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\MetricsTrait;
use App\Utilities\Traits\TypeCheckerTrait;
use App\Utilities\Traits\Patterns\PatternTrait;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class TraitSurfaceTest extends TestCase
{
    public function testTraitPublicMethodsAreUniqueAndDoNotOverlapPhpInternals(): void
    {
        $declaredMethods = [];
        $internalFunctions = array_flip(get_defined_functions()['internal']);

        foreach ($this->traitDeclarations() as $traitDeclaration) {
            foreach ($traitDeclaration['methods'] as $methodName) {
                $lowerMethodName = strtolower($methodName);

                self::assertArrayNotHasKey(
                    $methodName,
                    $declaredMethods,
                    sprintf(
                        'Trait method [%s] in [%s] duplicates [%s].',
                        $methodName,
                        $traitDeclaration['class'],
                        $declaredMethods[$methodName] ?? 'unknown'
                    )
                );

                self::assertArrayNotHasKey(
                    $lowerMethodName,
                    $internalFunctions,
                    sprintf(
                        'Trait method [%s] in [%s] overlaps PHP internal function naming.',
                        $methodName,
                        $traitDeclaration['class']
                    )
                );

                $declaredMethods[$methodName] = $traitDeclaration['class'];
            }
        }
    }

    public function testCollisionHeavyTraitsComposeWithExplicitMethodNames(): void
    {
        $helper = new class {
            use ArrayTrait, ManipulationTrait, PatternTrait, CheckerTrait, TypeCheckerTrait, LoopTrait, MetricsTrait;
        };

        $array = ['first' => 1, 'second' => 2];
        $iterations = [];

        self::assertSame(['alpha', 'beta'], $helper->splitString(',', 'alpha,beta'));
        self::assertSame(['alpha', 'beta'], $helper->splitByPattern('/,/', 'alpha,beta'));
        self::assertSame(['first' => 1, 'third' => 3], $helper->replaceElements(['first' => 1], ['third' => 3]));
        self::assertSame('abxyef', $helper->replaceText('cd', 'xy', 'abcdef'));
        self::assertSame('abcd', $helper->padString('abcd', 4));
        self::assertSame(['first' => 1, 'second' => 2, 0 => 0], $helper->padArray($array, 3, 0));
        self::assertSame(2, $helper->countElements($array));
        self::assertTrue($helper->isDigitString('12345'));
        self::assertTrue($helper->isNumeric('123.45'));
        self::assertTrue($helper->hasSoundexMatch('Robert', 'Rupert'));

        $helper->repeatLoop(3, static function (int $index) use (&$iterations): void {
            $iterations[] = $index;
        });

        self::assertSame([0, 1, 2], $iterations);
    }

    /**
     * @return list<array{class: class-string, methods: list<string>}>
     */
    private function traitDeclarations(): array
    {
        $declarations = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(dirname(__DIR__, 2) . '/App/Utilities/Traits')
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $source = file_get_contents($file->getPathname());

            if (!is_string($source)) {
                continue;
            }

            $namespace = null;
            $traitName = null;
            $publicMethods = [];
            $tokens = token_get_all($source);
            $count = count($tokens);

            for ($index = 0; $index < $count; $index++) {
                $token = $tokens[$index];

                if (!is_array($token)) {
                    continue;
                }

                if ($token[0] === T_NAMESPACE) {
                    $namespace = '';

                    for ($cursor = $index + 1; $cursor < $count; $cursor++) {
                        $part = $tokens[$cursor];

                        if (is_string($part) && $part === ';') {
                            break;
                        }

                        if (
                            is_array($part)
                            && in_array($part[0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)
                        ) {
                            $namespace .= $part[1];
                        }
                    }
                }

                if ($token[0] === T_TRAIT) {
                    for ($cursor = $index + 1; $cursor < $count; $cursor++) {
                        $part = $tokens[$cursor];

                        if (is_array($part) && $part[0] === T_STRING) {
                            $traitName = $part[1];
                            break;
                        }
                    }

                    break;
                }
            }

            if ($namespace === null || $traitName === null) {
                continue;
            }

            for ($index = 0; $index < $count; $index++) {
                $token = $tokens[$index];

                if (!is_array($token) || $token[0] !== T_FUNCTION) {
                    continue;
                }

                $isPublic = false;

                for ($cursor = $index - 1; $cursor >= 0; $cursor--) {
                    $part = $tokens[$cursor];

                    if (
                        is_array($part)
                        && in_array($part[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_ATTRIBUTE], true)
                    ) {
                        continue;
                    }

                    if (is_array($part) && $part[0] === T_PUBLIC) {
                        $isPublic = true;
                    }

                    break;
                }

                if (!$isPublic) {
                    continue;
                }

                $candidateName = null;

                for ($cursor = $index + 1; $cursor < $count; $cursor++) {
                    $part = $tokens[$cursor];

                    if (is_array($part) && in_array($part[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                        continue;
                    }

                    if ($candidateName === null && is_string($part) && $part === '&') {
                        continue;
                    }

                    if ($candidateName === null) {
                        $candidateName = is_array($part) ? $part[1] : (is_string($part) ? $part : null);
                        continue;
                    }

                    if (is_string($part) && $part === '(' && $candidateName !== null && $candidateName !== '') {
                        $publicMethods[] = $candidateName;
                        break;
                    }
                }
            }

            $declarations[] = [
                'class' => trim($namespace) . '\\' . trim($traitName),
                'methods' => $publicMethods,
            ];
        }

        usort(
            $declarations,
            static fn(array $left, array $right): int => strcmp($left['class'], $right['class'])
        );

        return $declarations;
    }
}
