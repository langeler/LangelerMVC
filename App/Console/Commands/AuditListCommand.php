<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Contracts\Support\AuditLoggerInterface;

class AuditListCommand extends Command
{
    public function __construct(private readonly AuditLoggerInterface $audit)
    {
    }

    public function name(): string
    {
        return 'audit:list';
    }

    public function description(): string
    {
        return 'Inspect recent framework audit records.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        $limit = isset($options['limit']) ? max(1, (int) $options['limit']) : 25;
        $criteria = array_filter([
            'category' => $options['category'] ?? null,
            'severity' => $options['severity'] ?? null,
            'actor_id' => $options['actor'] ?? null,
        ], static fn(mixed $value): bool => $value !== null && $value !== '');
        $records = $this->audit->recent($limit, $criteria);

        if ($this->toBoolean($options['json'] ?? false)) {
            $this->dumpJson([
                'criteria' => $criteria,
                'records' => $records,
            ]);

            return 0;
        }

        if ($records === []) {
            $this->info('No audit records are available.');
            return 0;
        }

        foreach ($records as $record) {
            $this->line(sprintf(
                '%s  %-12s %-24s %-16s %s',
                gmdate('c', (int) ($record['created_at'] ?? 0)),
                (string) ($record['category'] ?? 'framework'),
                (string) ($record['event'] ?? ''),
                (string) ($record['severity'] ?? 'info'),
                isset($record['actor_id']) ? 'actor=' . (string) $record['actor_id'] : ''
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
