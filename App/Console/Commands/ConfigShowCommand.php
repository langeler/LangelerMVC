<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Core\Config;

class ConfigShowCommand extends Command
{
    public function __construct(private readonly Config $config)
    {
    }

    public function name(): string
    {
        return 'config:show';
    }

    public function description(): string
    {
        return 'Show the current runtime configuration or one config file.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        $file = $arguments[0] ?? null;
        $payload = $this->isString($file) && $file !== ''
            ? [$file => $this->config->get($file, null, [])]
            : $this->config->all();

        $this->dumpJson($payload);

        return 0;
    }
}
