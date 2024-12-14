<?php

declare(strict_types=1);

namespace App\Contracts\Database;

/**
 * RepositoryInterface
 *
 * Defines the contract for data retrieval and manipulation.
 * Aligns with the abstract Repository class, providing a public API
 * for CRUD operations and queries, returning model instances instead of raw data.
 */
interface RepositoryInterface
{
	/**
	 * Get the database table name associated with the repository.
	 *
	 * @return string
	 */
	public function getTable(): string;

	/**
	 * Map a raw database row to a model instance.
	 *
	 * @param array<string,mixed> $row
	 * @return object
	 */
	public function mapRowToModel(array $row): object;

	/**
	 * Find a record by its primary key.
	 *
	 * @param mixed $id
	 * @return object|null
	 */
	public function find(mixed $id): ?object;

	/**
	 * Retrieve all records.
	 *
	 * @return object[]
	 */
	public function all(): array;

	/**
	 * Retrieve a paginated list of records.
	 *
	 * @param int $perPage
	 * @param int $page
	 * @return array{
	 *     data: object[],
	 *     total: int,
	 *     per_page: int,
	 *     current_page: int
	 * }
	 */
	public function paginate(int $perPage = 15, int $page = 1): array;

	/**
	 * Create a new record in the database.
	 *
	 * @param array<string,mixed> $data
	 * @return object
	 */
	public function create(array $data): object;

	/**
	 * Update an existing record by its primary key.
	 *
	 * @param mixed $id
	 * @param array<string,mixed> $data
	 * @return bool
	 */
	public function update(mixed $id, array $data): bool;

	/**
	 * Delete a record by its primary key.
	 *
	 * @param mixed $id
	 * @return bool
	 */
	public function delete(mixed $id): bool;

	/**
	 * Find records by specific criteria.
	 *
	 * @param array<string,mixed> $criteria
	 * @return object[]
	 */
	public function findBy(array $criteria): array;

	/**
	 * Find a single record by specific criteria.
	 *
	 * @param array<string,mixed> $criteria
	 * @return object|null
	 */
	public function findOneBy(array $criteria): ?object;

	/**
	 * Count records matching specific criteria.
	 *
	 * @param array<string,mixed> $criteria
	 * @return int
	 */
	public function count(array $criteria): int;
}
