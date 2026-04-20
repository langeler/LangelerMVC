<?php

declare(strict_types=1);

namespace App\Contracts\Support;

interface FrameworkDoctorInterface
{
    /**
     * Build a framework readiness report across layers, modules, and runtime surfaces.
     *
     * @return array<string, mixed>
     */
    public function inspect(bool $strict = false): array;
}
