<?php

declare(strict_types=1);

namespace App\Contracts\Presentation;

interface HtmlManagerInterface
{
    public function escape(mixed $value): string;

    public function escapeUrl(mixed $value): string;

    /**
     * @param array<string, mixed> $attributes
     */
    public function attributes(array $attributes): string;

    /**
     * @param array<int|string, mixed>|string $classes
     */
    public function classList(array|string $classes): string;

    public function csrfField(string $token, string $field = '_token'): string;

    public function methodField(string $method): string;

    public function json(mixed $value, int $flags = 0, int $depth = 512): string;
}
