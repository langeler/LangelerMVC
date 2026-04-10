<?php

declare(strict_types=1);

namespace App\Abstracts\Http;

/**
 * Shared concrete sender for framework HTML and JSON responses.
 */
abstract class StandardResponse extends Response
{
    public function send(): void
    {
        $this->prepareForSend();
        $payload = $this->toArray();

        if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg' && !headers_sent()) {
            http_response_code($payload['status']);

            foreach ($payload['headers'] as $name => $value) {
                header($this->formatHeaderName($name) . ': ' . $value, true);
            }
        }

        echo $payload['content'];
    }

    protected function formatHeaderName(string $header): string
    {
        return $this->joinStrings(
            '-',
            $this->map(
                static fn(string $segment): string => ucfirst($segment),
                $this->splitString('-', $this->toLower($header))
            )
        );
    }
}
