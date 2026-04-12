<?php

declare(strict_types=1);

namespace App\Abstracts\Database;

use App\Contracts\Database\ModelInterface;
use App\Contracts\Database\RepositoryInterface;
use App\Contracts\Database\SeedInterface;
use App\Core\Database;
use App\Utilities\Traits\ConversionTrait;

/**
 * Base seed abstraction backed by a repository.
 */
abstract class Seed implements SeedInterface
{
    use ConversionTrait;

    public function __construct(
        protected RepositoryInterface $repository,
        protected Database $database
    )
    {
    }

    /**
     * @return list<class-string|non-empty-string>
     */
    public static function dependencies(): array
    {
        return [];
    }

    abstract public function run(): void;

    public function insert(array $data): ModelInterface
    {
        return $this->repository->create($data);
    }

    /**
     * @return ModelInterface[]
     */
    public function insertMany(array $data): array
    {
        $inserted = [];

        foreach ($data as $row) {
            $inserted[] = $this->insert((array) $row);
        }

        return $inserted;
    }

    public function truncate(): void
    {
        $this->database->truncate($this->repository->getTable());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    abstract public function defaultData(): array;
}
