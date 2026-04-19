<?php

declare(strict_types=1);

namespace App\Contracts\Support;

interface HealthManagerInterface
{
    /**
     * @return array<string, mixed>
     */
    public function liveness(): array;

    /**
     * @return array<string, mixed>
     */
    public function readiness(): array;

    /**
     * @return array<string, mixed>
     */
    public function capabilities(): array;

    /**
     * @return array<string, mixed>
     */
    public function report(): array;
}
