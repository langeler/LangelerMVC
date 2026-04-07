<?php

declare(strict_types=1);

namespace App\Contracts\Database;

interface SeedInterface
{
    public function run(): void;

    public function insert(array $data): ModelInterface;

    /**
     * @return ModelInterface[]
     */
    public function insertMany(array $data): array;

    public function truncate(): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function defaultData(): array;
}
