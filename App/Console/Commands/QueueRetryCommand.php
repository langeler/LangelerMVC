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
        return 'Retry one failed queued job, or all failed jobs when requested.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        if ($this->toBoolean($options['all'] ?? false)) {
            $retried = $this->queue->retryAll(
                isset($options['failed-queue']) ? (string) $options['failed-queue'] : null,
                (string) ($options['queue'] ?? 'default')
            );

            $this->info(sprintf('Retried %d failed queue job(s).', count($retried)));

            return 0;
        }

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
