<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Utilities\Managers\Async\QueueManager;

class QueueFailedCommand extends Command
{
    public function __construct(private readonly QueueManager $queue)
    {
    }

    public function name(): string
    {
        return 'queue:failed';
    }

    public function description(): string
    {
        return 'Inspect failed queued jobs.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        $queue = isset($options['queue']) ? (string) $options['queue'] : null;
        $failed = $this->queue->failed($queue);

        if ($this->toBoolean($options['json'] ?? false)) {
            $this->dumpJson([
                'queue' => $queue,
                'count' => count($failed),
                'jobs' => $failed,
            ]);

            return 0;
        }

        if ($failed === []) {
            $this->info($queue === null
                ? 'No failed queue jobs.'
                : sprintf('No failed queue jobs for [%s].', $queue));
            return 0;
        }

        foreach ($failed as $job) {
            $this->line(sprintf(
                '%s  %-12s %-36s %s',
                (string) ($job['id'] ?? ''),
                (string) ($job['queue'] ?? 'default'),
                (string) ($job['class'] ?? ''),
                (string) ($job['exception'] ?? '')
            ));
        }

        return 0;
    }

    private function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        if (is_string($value)) {
            $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            return $parsed ?? true;
        }

        return (bool) $value;
    }
}
