<?php

declare(strict_types=1);

namespace App\Contracts\Console;

interface CommandInterface
{
    public function name(): string;

    public function description(): string;

    /**
     * @param array<int, string> $arguments
     * @param array<string, mixed> $options
     */
    public function handle(array $arguments = [], array $options = []): int;
}
