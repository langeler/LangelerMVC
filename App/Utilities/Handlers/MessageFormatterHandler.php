<?php

namespace App\Utilities\Handlers;

use MessageFormatter;

/**
 * MessageFormatterHandler provides utilities for managing message formatting using PHP's MessageFormatter class.
 * It ensures consistent handling of locale-based message formatting and parsing.
 */
class MessageFormatterHandler
{
    /**
     * Constructs the handler.
     *
     * @param string $locale The locale for the formatter.
     * @param string $pattern The pattern used for formatting and parsing.
     * @param ?MessageFormatter $formatter Optional existing MessageFormatter instance.
     */
    public function __construct(
        protected string $locale,
        protected string $pattern,
        protected ?MessageFormatter $formatter = null
    ) {}

    /**
     * Wraps callable execution in a try-catch block for consistent error handling.
     *
     * @param callable $callback The function to execute.
     * @return mixed The result of the callable.
     */
    private function wrapInTry(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            throw new \Exception("An error occurred: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Initializes a new MessageFormatter instance.
     *
     * @param string $locale The locale for the formatter.
     * @param string $pattern The pattern used for formatting and parsing.
     * @return MessageFormatter
     */
    public function initialize(string $locale, string $pattern): MessageFormatter
    {
        return $this->wrapInTry(fn() => new MessageFormatter($locale, $pattern));
    }

    /**
     * Formats a message with the given values.
     *
     * @param array $values The values to format into the message.
     * @return string|false The formatted message, or false on failure.
     */
    public function format(array $values): string|false
    {
        return $this->wrapInTry(fn() => $this->initialize($this->locale, $this->pattern)->format($values));
    }

    /**
     * Retrieves the last error code.
     *
     * @return int The error code.
     */
    public function getErrorCode(): int
    {
        return $this->wrapInTry(fn() => $this->initialize($this->locale, $this->pattern)->getErrorCode());
    }

    /**
     * Retrieves the last error message.
     *
     * @return string The error message.
     */
    public function getErrorMessage(): string
    {
        return $this->wrapInTry(fn() => $this->initialize($this->locale, $this->pattern)->getErrorMessage());
    }

    /**
     * Retrieves the locale used by the formatter.
     *
     * @return string The locale.
     */
    public function getLocale(): string
    {
        return $this->wrapInTry(fn() => $this->initialize($this->locale, $this->pattern)->getLocale());
    }

    /**
     * Retrieves the pattern used by the formatter.
     *
     * @return string|false The pattern, or false on failure.
     */
    public function getPattern(): string|false
    {
        return $this->wrapInTry(fn() => $this->initialize($this->locale, $this->pattern)->getPattern());
    }

    /**
     * Parses a message into an array of values.
     *
     * @param string $message The message to parse.
     * @return array|false The parsed values, or false on failure.
     */
    public function parse(string $message): array|false
    {
        return $this->wrapInTry(fn() => $this->initialize($this->locale, $this->pattern)->parse($message));
    }

    /**
     * Sets a new pattern for the formatter.
     *
     * @param string $pattern The new pattern to set.
     * @return bool True on success, false on failure.
     */
    public function setPattern(string $pattern): bool
    {
        return $this->wrapInTry(fn() => $this->initialize($this->locale, $this->pattern)->setPattern($pattern));
    }
}