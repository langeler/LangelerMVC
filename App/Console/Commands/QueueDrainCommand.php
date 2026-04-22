<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Utilities\Managers\Async\QueueManager;

class QueueDrainCommand extends Command
{
    public function __construct(private readonly QueueManager $queue)
    {
    }

    public function name(): string
    {
        return 'queue:drain';
    }

    public function description(): string
    {
        return 'Signal queue workers to drain pending jobs and then stop.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        if (!$this->queue->signalStop(true)) {
            $this->error('Unable to write the queue drain signal.');
            return 1;
        }

        $state = $this->queue->workerState();
        $this->info(sprintf('Queue drain signal written to [%s].', (string) ($state['control_path'] ?? 'unknown')));

        return 0;
    }
}
