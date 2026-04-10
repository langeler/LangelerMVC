<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Core\MigrationRunner;

class MigrateStatusCommand extends Command
{
    public function __construct(private readonly MigrationRunner $migrations)
    {
    }

    public function name(): string
    {
        return 'migrate:status';
    }

    public function description(): string
    {
        return 'Show framework migration status.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        $module = $arguments[0] ?? ($options['module'] ?? null);

        foreach ($this->migrations->status($this->isString($module) ? $module : null) as $migration) {
            $state = $migration['batch'] > 0 ? 'up' : 'pending';
            $this->line(sprintf(
                '%-28s %-12s %-8s %s',
                $migration['name'],
                $migration['module'],
                $state,
                $migration['ran_at'] ?? '-'
            ));
        }

        return 0;
    }
}
