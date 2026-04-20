<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Contracts\Support\FrameworkDoctorInterface;

class FrameworkDoctorCommand extends Command
{
    public function __construct(private readonly FrameworkDoctorInterface $doctor)
    {
    }

    public function name(): string
    {
        return 'framework:doctor';
    }

    public function description(): string
    {
        return 'Run cross-layer production-readiness checks for configuration, storage, modules, and routes.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        $strict = isset($options['strict']) && $this->toBoolean($options['strict']);
        $quiet = isset($options['quiet']) && $this->toBoolean($options['quiet']);
        $payload = $this->doctor->inspect($strict);

        if (!$quiet) {
            $this->dumpJson($payload);
        }

        return ((int) ($payload['status'] ?? 200)) >= 400 ? 1 : 0;
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
            $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            return $parsed ?? true;
        }

        return (bool) $value;
    }
}
