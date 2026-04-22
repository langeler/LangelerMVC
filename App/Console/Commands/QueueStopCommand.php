<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Utilities\Managers\Async\QueueManager;

class QueueStopCommand extends Command
{
    public function __construct(private readonly QueueManager $queue)
    {
    }

    public function name(): string
    {
        return 'queue:stop';
    }

    public function description(): string
    {
        return 'Signal queue workers to stop before claiming the next job.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        if (!$this->queue->signalStop(false)) {
            $this->error('Unable to write the queue stop signal.');
            return 1;
        }

        $state = $this->queue->workerState();
        $this->info(sprintf('Queue stop signal written to [%s].', (string) ($state['control_path'] ?? 'unknown')));

        return 0;
    }
}
