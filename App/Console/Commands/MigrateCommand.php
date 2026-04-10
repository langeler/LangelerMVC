<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Core\MigrationRunner;

class MigrateCommand extends Command
{
    public function __construct(private readonly MigrationRunner $migrations)
    {
    }

    public function name(): string
    {
        return 'migrate';
    }

    public function description(): string
    {
        return 'Run pending framework and module migrations.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        $module = $arguments[0] ?? ($options['module'] ?? null);
        $executed = $this->migrations->migrate($this->isString($module) ? $module : null);

        if ($executed === []) {
            $this->info('No pending migrations.');
            return 0;
        }

        foreach ($executed as $migration) {
            $this->info('Migrated: ' . $migration);
        }

        return 0;
    }
}
