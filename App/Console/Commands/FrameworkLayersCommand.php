<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Contracts\Support\FrameworkLayerManagerInterface;

class FrameworkLayersCommand extends Command
{
    public function __construct(private readonly FrameworkLayerManagerInterface $layers)
    {
    }

    public function name(): string
    {
        return 'framework:layers';
    }

    public function description(): string
    {
        return 'Inspect framework-wide layer organization and required production surfaces.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        $quiet = isset($options['quiet']) && $this->toBoolean($options['quiet']);
        $payload = $this->layers->inspect();

        if (!$quiet) {
            $this->dumpJson($payload);
        }

        return (bool) ($payload['ok'] ?? false) ? 0 : 1;
    }

    private function toBoolean(mixed $value): bool
    {
        if ($this->isBool($value)) {
            return $value;
        }

        if ($this->isInt($value)) {
            return $value !== 0;
        }

        if ($this->isString($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
        }

        return (bool) $value;
    }
}
