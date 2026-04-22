<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Utilities\Managers\Async\QueueManager;

class QueueWorkCommand extends Command
{
    public function __construct(private readonly QueueManager $queue)
    {
    }

    public function name(): string
    {
        return 'queue:work';
    }

    public function description(): string
    {
        return 'Process queued jobs and listeners with retry-aware worker controls.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        $queue = (string) ($options['queue'] ?? $arguments[0] ?? 'default');
        $daemon = $this->toBoolean($options['daemon'] ?? false);
        $max = isset($options['max']) ? (int) $options['max'] : ($daemon ? 0 : 1);
        $processed = $this->queue->work($queue, $max, [
            'stop_when_empty' => $this->toBoolean($options['stop-when-empty'] ?? !$daemon),
            'sleep' => (int) ($options['sleep'] ?? 1),
            'max_runtime' => (int) ($options['runtime'] ?? 0),
            'max_memory_mb' => (int) ($options['memory'] ?? 256),
        ]);

        $this->info(sprintf(
            'Processed %d queued item(s) from [%s]%s.',
            $processed,
            $queue,
            $daemon ? ' in worker mode' : ''
        ));

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
