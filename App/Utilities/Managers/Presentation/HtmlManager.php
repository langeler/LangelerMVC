<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Presentation;

use App\Contracts\Presentation\HtmlManagerInterface;
use App\Exceptions\Presentation\ViewException;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\ConversionTrait;
use App\Utilities\Traits\EncodingTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Traits\TypeCheckerTrait;

final class HtmlManager implements HtmlManagerInterface
{
    use ArrayTrait;
    use ConversionTrait;
    use EncodingTrait;
    use ManipulationTrait;
    use PatternTrait;
    use TypeCheckerTrait;

    public function escape(mixed $value): string
    {
        if ($this->isArray($value)) {
            return $this->escapeHtml($this->joinStrings(', ', $this->map(
                fn(mixed $item): string => $this->stringify($item),
                $value
            )));
        }

        return $this->escapeHtml($this->stringify($value));
    }

    public function escapeUrl(mixed $value): string
    {
        return $this->encodeStringForUrl($this->stringify($value));
    }

    public function attributes(array $attributes): string
    {
        $parts = [];

        foreach ($attributes as $name => $value) {
            $name = $this->normalizeAttributeName((string) $name);

            if ($name === '' || $value === false || $value === null) {
                continue;
            }

            if ($value === true) {
                $parts[] = $this->escape($name);
                continue;
            }

            $parts[] = $this->escape($name) . '="' . $this->escape($this->stringify($value)) . '"';
        }

        return $parts === [] ? '' : ' ' . $this->joinStrings(' ', $parts);
    }

    public function classList(array|string $classes): string
    {
        $items = $this->isArray($classes) ? $classes : $this->splitString(' ', $classes);
        $resolved = [];

        foreach ($items as $key => $value) {
            if ($this->isString($key) && (bool) $value) {
                $resolved[] = $key;
                continue;
            }

            if ($this->isString($value) && $this->trimString($value) !== '') {
                $resolved[] = $this->trimString($value);
            }
        }

        return $this->escape($this->joinStrings(' ', $this->unique($resolved)));
    }

    public function csrfField(string $token, string $field = '_token'): string
    {
        $token = $this->trimString($token);
        $field = $this->trimString($field) !== '' ? $this->trimString($field) : '_token';

        if ($token === '') {
            return '';
        }

        return '<input type="hidden" name="' . $this->escape($field) . '" value="' . $this->escape($token) . '">';
    }

    public function methodField(string $method): string
    {
        $method = $this->toUpper($this->trimString($method));

        if ($method === '' || $this->any(['GET', 'POST'], fn(string $native): bool => $native === $method)) {
            return '';
        }

        return '<input type="hidden" name="_method" value="' . $this->escape($method) . '">';
    }

    public function json(mixed $value, int $flags = 0, int $depth = 512): string
    {
        $safeFlags = JSON_HEX_TAG
            | JSON_HEX_APOS
            | JSON_HEX_AMP
            | JSON_HEX_QUOT
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_THROW_ON_ERROR
            | $flags;

        try {
            return $this->toJson($value, $safeFlags, $depth);
        } catch (\Throwable $exception) {
            throw new ViewException('Unable to encode presentation data as safe JSON.', 0, $exception);
        }
    }

    private function normalizeAttributeName(string $name): string
    {
        $name = $this->toLower($this->trimString($name));
        $name = $this->replaceByPattern('/[^a-z0-9_:-]+/', '-', $name) ?? '';

        return $this->trimString($this->replaceText('_', '-', $name), '-');
    }

    private function stringify(mixed $value): string
    {
        if ($this->isString($value)) {
            return $value;
        }

        if ($this->isNull($value)) {
            return '';
        }

        if ($this->isBool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($this->isScalar($value)) {
            return (string) $value;
        }

        return '';
    }
}
