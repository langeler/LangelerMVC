<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Core\Config;

class ConfigShowCommand extends Command
{
    public function __construct(private readonly Config $config)
    {
    }

    public function name(): string
    {
        return 'config:show';
    }

    public function description(): string
    {
        return 'Show the current runtime configuration or one config file.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        $file = $arguments[0] ?? null;
        $payload = $this->isString($file) && $file !== ''
            ? [$file => $this->config->get($file, null, [])]
            : $this->config->all();

        if (!$this->optionEnabled($options['raw'] ?? false)) {
            $payload = $this->redactPayload($payload);
        }

        $this->dumpJson(is_array($payload) ? $payload : []);

        return 0;
    }

    protected function optionEnabled(mixed $value): bool
    {
        return match (true) {
            is_bool($value) => $value,
            is_int($value) => $value !== 0,
            is_string($value) => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            default => false,
        };
    }

    /**
     * @param array<string|int, mixed> $payload
     * @param list<string> $path
     * @return array<string|int, mixed>
     */
    protected function redactPayload(array $payload, array $path = []): array
    {
        $redacted = [];

        foreach ($payload as $key => $value) {
            $segment = is_string($key) ? $key : (string) $key;
            $currentPath = array_merge($path, [$segment]);

            if (is_array($value)) {
                $redacted[$key] = $this->redactPayload($value, $currentPath);
                continue;
            }

            $redacted[$key] = $this->isSensitivePath($currentPath)
                ? '[redacted]'
                : $value;
        }

        return $redacted;
    }

    /**
     * @param list<string> $path
     */
    protected function isSensitivePath(array $path): bool
    {
        foreach ($path as $segment) {
            $normalized = $this->toLower($segment);

            if (
                $normalized === 'key'
                || str_ends_with($normalized, '_key')
                || str_contains($normalized, 'password')
                || str_contains($normalized, 'secret')
                || str_contains($normalized, 'token')
            ) {
                return true;
            }
        }

        return false;
    }
}
