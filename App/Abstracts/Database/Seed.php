<?php

declare(strict_types=1);

namespace App\Abstracts\Database;

use App\Contracts\Database\ModelInterface;
use App\Contracts\Database\RepositoryInterface;
use App\Contracts\Database\SeedInterface;

/**
 * Base seed abstraction backed by a repository.
 */
abstract class Seed implements SeedInterface
{
    public function __construct(protected RepositoryInterface $repository)
    {
    }

    abstract public function run(): void;

    abstract public function insert(array $data): ModelInterface;

    /**
     * @return ModelInterface[]
     */
    abstract public function insertMany(array $data): array;

    abstract public function truncate(): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    abstract public function defaultData(): array;
}
