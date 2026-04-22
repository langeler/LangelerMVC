<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Contracts\Support\AuditLoggerInterface;

class AuditPruneCommand extends Command
{
    public function __construct(private readonly AuditLoggerInterface $audit)
    {
    }

    public function name(): string
    {
        return 'audit:prune';
    }

    public function description(): string
    {
        return 'Prune retained framework audit records.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        $hours = isset($options['hours']) ? max(0, (int) $options['hours']) : null;
        $before = $hours === null ? null : time() - ($hours * 3600);
        $criteria = array_filter([
            'category' => $options['category'] ?? null,
            'severity' => $options['severity'] ?? null,
            'actor_id' => $options['actor'] ?? null,
        ], static fn(mixed $value): bool => $value !== null && $value !== '');

        $deleted = $this->audit->prune($before, $criteria);

        $this->info(sprintf(
            'Pruned %d audit record(s)%s.',
            $deleted,
            $hours === null ? '' : sprintf(' older than %d hour(s)', $hours)
        ));

        return 0;
    }
}
