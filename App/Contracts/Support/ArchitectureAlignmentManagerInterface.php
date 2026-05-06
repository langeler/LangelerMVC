<?php

declare(strict_types=1);

namespace App\Contracts\Support;

interface ArchitectureAlignmentManagerInterface
{
    /**
     * Inspect repository-wide architecture conventions and release alignment.
     *
     * @return array<string, mixed>
     */
    public function inspect(): array;

    /**
     * Return the enforced architecture rules keyed by rule identifier.
     *
     * @return array<string, array<string, string>>
     */
    public function rules(): array;

    /**
     * Return flattened errors and warnings from the current inspection.
     *
     * @return array{errors: list<string>, warnings: list<string>}
     */
    public function violations(): array;
}
