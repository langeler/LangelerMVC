<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Utilities\Managers\Async\QueueManager;

class QueueRetryCommand extends Command
{
    public function __construct(private readonly QueueManager $queue)
    {
    }

    public function name(): string
    {
        return 'queue:retry';
    }

    public function description(): string
    {
        return 'Retry a failed queued job.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        $id = (string) ($arguments[0] ?? $options['id'] ?? '');

        if ($id === '') {
            $this->error('A failed job id is required.');
            return 1;
        }

        $retried = $this->queue->retry($id, (string) ($options['queue'] ?? 'default'));

        if ($retried === null) {
            $this->error(sprintf('Failed queue record [%s] was not found.', $id));
            return 1;
        }

        $this->info(sprintf('Retried failed job [%s] as [%s].', $id, $retried));

        return 0;
    }
}
