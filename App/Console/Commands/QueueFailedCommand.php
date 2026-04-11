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
        $failed = $this->queue->failed();

        if ($failed === []) {
            $this->info('No failed queue jobs.');
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
}
