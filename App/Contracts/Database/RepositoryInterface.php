<?php

declare(strict_types=1);

namespace App\Contracts\Database;

/**
 * Public persistence contract for repositories returning model instances.
 */
interface RepositoryInterface
{
    public function getTable(): string;

    public function mapRowToModel(array $row): ModelInterface;

    public function find(mixed $id): ?ModelInterface;

    /**
     * @return ModelInterface[]
     */
    public function all(): array;

    /**
     * @return array{
     *     data: ModelInterface[],
     *     total: int,
     *     per_page: int,
     *     current_page: int,
     *     last_page: int
     * }
     */
    public function paginate(int $perPage = 15, int $page = 1): array;

    public function create(array $data): ModelInterface;

    public function update(mixed $id, array $data): bool;

    public function delete(mixed $id): bool;

    /**
     * @return ModelInterface[]
     */
    public function findBy(array $criteria): array;

    public function findOneBy(array $criteria): ?ModelInterface;

    public function count(array $criteria): int;

    public function save(ModelInterface $model): ModelInterface;

    public function deleteModel(ModelInterface $model): bool;
}
