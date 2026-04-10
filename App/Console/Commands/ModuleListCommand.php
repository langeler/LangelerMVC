<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Utilities\Managers\Data\ModuleManager;

class ModuleListCommand extends Command
{
    public function __construct(private readonly ModuleManager $modules)
    {
    }

    public function name(): string
    {
        return 'module:list';
    }

    public function description(): string
    {
        return 'List discovered application modules.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        foreach ($this->modules->getModules() as $name => $path) {
            $this->line(sprintf('%-18s %s', $name, $path));
        }

        return 0;
    }
}
