<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Core\MigrationRunner;

class MigrateRollbackCommand extends Command
{
    public function __construct(private readonly MigrationRunner $migrations)
    {
    }

    public function name(): string
    {
        return 'migrate:rollback';
    }

    public function description(): string
    {
        return 'Rollback the most recent migration batch.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        $steps = isset($arguments[0]) && $this->isNumeric($arguments[0]) ? (int) $arguments[0] : 1;
        $module = $options['module'] ?? null;
        $rolledBack = $this->migrations->rollback($steps, $this->isString($module) ? $module : null);

        if ($rolledBack === []) {
            $this->info('No migrations were rolled back.');
            return 0;
        }

        foreach ($rolledBack as $migration) {
            $this->warn('Rolled back: ' . $migration);
        }

        return 0;
    }
}
