<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Contracts\Support\HealthManagerInterface;

class HealthCheckCommand extends Command
{
    public function __construct(private readonly HealthManagerInterface $health)
    {
    }

    public function name(): string
    {
        return 'health:check';
    }

    public function description(): string
    {
        return 'Inspect framework liveness, readiness, and runtime capabilities.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        $mode = isset($arguments[0]) ? $this->toLower((string) $arguments[0]) : 'report';
        $payload = match ($mode) {
            'live', 'liveness' => $this->health->liveness(),
            'ready', 'readiness' => $this->health->readiness(),
            default => $this->health->report(),
        };

        $this->dumpJson($payload);

        return ((int) ($payload['status'] ?? 200)) >= 400 ? 1 : 0;
    }
}
