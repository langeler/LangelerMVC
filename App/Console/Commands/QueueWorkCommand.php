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
        return 'Process queued jobs and listeners.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        $queue = (string) ($options['queue'] ?? $arguments[0] ?? 'default');
        $max = isset($options['max']) ? (int) $options['max'] : 1;
        $processed = $this->queue->work($queue, $max > 0 ? $max : 1);

        $this->info(sprintf('Processed %d queued item(s) from [%s].', $processed, $queue));

        return 0;
    }
}
