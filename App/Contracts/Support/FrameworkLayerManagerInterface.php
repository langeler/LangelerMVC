<?php

declare(strict_types=1);

namespace App\Contracts\Support;

interface FrameworkLayerManagerInterface
{
    /**
     * Inspect framework layer completeness for release/readiness reporting.
     *
     * @return array<string, mixed>
     */
    public function inspect(): array;

    /**
     * Return the configured framework layer map with detected path state.
     *
     * @return array<string, array<string, mixed>>
     */
    public function layers(): array;

    /**
     * Return missing required paths grouped by layer key.
     *
     * @return array<string, list<string>>
     */
    public function missingRequiredPaths(): array;
}
