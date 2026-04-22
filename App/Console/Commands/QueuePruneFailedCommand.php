<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Utilities\Managers\Async\QueueManager;

class QueuePruneFailedCommand extends Command
{
    public function __construct(private readonly QueueManager $queue)
    {
    }

    public function name(): string
    {
        return 'queue:prune-failed';
    }

    public function description(): string
    {
        return 'Prune failed queue jobs older than the configured or provided retention window.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        $hours = isset($options['hours']) ? max(0, (int) $options['hours']) : null;
        $failedBefore = $hours === null
            ? null
            : (time() - ($hours * 3600));
        $pruned = $this->queue->pruneFailed($failedBefore);

        $this->info($hours === null
            ? sprintf('Pruned %d failed queue job(s).', $pruned)
            : sprintf('Pruned %d failed queue job(s) older than %d hour(s).', $pruned, $hours));

        return 0;
    }
}
