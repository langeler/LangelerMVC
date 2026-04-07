<?php

namespace App\Utilities\Handlers;

use App\Utilities\Traits\ErrorTrait;
use NumberFormatter;

class NumberFormatterHandler
{
    use ErrorTrait;

    public function __construct(
        protected ?NumberFormatter $formatter = null,
        protected string $locale = 'en_US',
        protected int $style = NumberFormatter::DECIMAL,
        protected ?string $pattern = null
    ) {
    }

    public function initialize(string $locale, int $style, ?string $pattern = null): NumberFormatter
    {
        return $this->wrapInTry(fn() => new NumberFormatter($locale, $style, $pattern));
    }

    public function format(int|float $number, int $type = NumberFormatter::TYPE_DEFAULT): string|false
    {
        return $this->wrapInTry(fn() => $this->getFormatter()->format($number, $type));
    }

    public function formatCurrency(float $amount, string $currency): string|false
    {
        return $this->wrapInTry(
            fn() => $this->initialize($this->locale, NumberFormatter::CURRENCY, $this->pattern)
                ->formatCurrency($amount, $currency)
        );
    }

    public function getAttribute(int $attribute): int|float|false
    {
        return $this->wrapInTry(fn() => $this->getFormatter()->getAttribute($attribute));
    }

    public function setAttribute(int $attribute, int|float $value): bool
    {
        return $this->wrapInTry(fn() => $this->getFormatter()->setAttribute($attribute, $value));
    }

    public function parse(string $string, int $type = NumberFormatter::TYPE_DOUBLE): int|float|false
    {
        return $this->wrapInTry(fn() => $this->getFormatter()->parse($string, $type));
    }

    public function parseCurrency(string $string, string &$currency): float|false
    {
        return $this->wrapInTry(
            fn() => $this->initialize($this->locale, NumberFormatter::CURRENCY, $this->pattern)
                ->parseCurrency($string, $currency)
        );
    }

    public function getTextAttribute(int $attribute): string|false
    {
        return $this->wrapInTry(fn() => $this->getFormatter()->getTextAttribute($attribute));
    }

    public function setTextAttribute(int $attribute, string $value): bool
    {
        return $this->wrapInTry(fn() => $this->getFormatter()->setTextAttribute($attribute, $value));
    }

    public function getSymbol(int $symbol): string|false
    {
        return $this->wrapInTry(fn() => $this->getFormatter()->getSymbol($symbol));
    }

    public function setSymbol(int $symbol, string $value): bool
    {
        return $this->wrapInTry(fn() => $this->getFormatter()->setSymbol($symbol, $value));
    }

    public function getPattern(): string|false
    {
        return $this->wrapInTry(fn() => $this->getFormatter()->getPattern());
    }

    public function setPattern(string $pattern): bool
    {
        return $this->wrapInTry(function () use ($pattern) {
            $formatter = $this->getFormatter();
            $result = $formatter->setPattern($pattern);

            if ($result) {
                $this->pattern = $pattern;
                $this->formatter = $formatter;
            }

            return $result;
        });
    }

    public function getLocale(int $type = \ULOC_ACTUAL_LOCALE): string|false
    {
        return $this->wrapInTry(fn() => $this->getFormatter()->getLocale($type));
    }

    public function getErrorCode(): int
    {
        return $this->wrapInTry(fn() => $this->getFormatter()->getErrorCode());
    }

    public function getErrorMessage(): string
    {
        return $this->wrapInTry(fn() => $this->getFormatter()->getErrorMessage());
    }

    private function getFormatter(): NumberFormatter
    {
        return $this->formatter ??= $this->initialize($this->locale, $this->style, $this->pattern);
    }
}
