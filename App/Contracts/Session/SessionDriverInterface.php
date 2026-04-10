<?php

declare(strict_types=1);

namespace App\Contracts\Session;

interface SessionDriverInterface
{
    public function driverName(): string;

    /**
     * @return array<string, mixed>
     */
    public function capabilities(): array;

    public function supports(string $feature): bool;
}
