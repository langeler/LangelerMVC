<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Core\SeedRunner;

class SeedCommand extends Command
{
    public function __construct(private readonly SeedRunner $seeds)
    {
    }

    public function name(): string
    {
        return 'seed';
    }

    public function description(): string
    {
        return 'Run framework or module seed classes.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        $module = $arguments[0] ?? ($options['module'] ?? null);
        $seed = $arguments[1] ?? ($options['class'] ?? null);
        $executed = $this->seeds->run(
            $this->isString($module) ? $module : null,
            $this->isString($seed) ? $seed : null
        );

        if ($executed === []) {
            $this->info('No seeds matched the given selection.');
            return 0;
        }

        foreach ($executed as $name) {
            $this->info('Seeded: ' . $name);
        }

        return 0;
    }
}
