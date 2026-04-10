<?php

declare(strict_types=1);

namespace App\Abstracts\Console;

use App\Contracts\Console\CommandInterface;
use App\Utilities\Traits\ErrorTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;

abstract class Command implements CommandInterface
{
    use ErrorTrait, ManipulationTrait, TypeCheckerTrait;

    protected function line(string $message = ''): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }

    protected function info(string $message): void
    {
        $this->line($message);
    }

    protected function warn(string $message): void
    {
        $this->line('[warn] ' . $message);
    }

    protected function error(string $message): void
    {
        fwrite(STDERR, '[error] ' . $message . PHP_EOL);
    }

    /**
     * @param array<string, mixed> $rows
     */
    protected function dumpJson(array $rows): void
    {
        $this->line((string) json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
