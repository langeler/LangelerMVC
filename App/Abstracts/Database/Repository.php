<?php

declare(strict_types=1);

namespace App\Abstracts\Database;

use App\Core\Database;

/**
 * Abstract Repository Class
 *
 * Responsibilities:
 * - Encapsulate CRUD operations and data retrieval logic using the provided Database instance.
 * - Return model instances rather than raw arrays.
 * - Provide a uniform interface for data operations, enforced by abstract methods.
 *
 * Boundaries:
 * - Does NOT handle HTTP requests, responses, or presentation logic.
 * - Does NOT contain business logic; it only interacts with the database.
 * - Concrete repositories must implement table association and data mapping.
 */
abstract class Repository
{
	/**
	 * The model instance or a prototype model used for instantiation.
	 * The Database instance for executing queries.
	 */
	public function __construct(
		protected object $model,
		protected Database $db
	) {
		// All necessary properties are already set via constructor property promotion.
	}

	/**
	 * Get the database table name associated with this repository.
	 * Concrete implementations must specify which table they operate on.
	 *
	 * @return string The table name.
	 */
	abstract protected function getTable(): string;

	/**
	 * Map a raw database row to a model instance.
	 * Ensures that data returned from the database is converted into a model object.
	 *
	 * @param array $row The raw database row.
	 * @return object A model instance representing the row.
	 */
	abstract protected function mapRowToModel(array $row): object;

	/**
	 * Find a record by its primary key.
	 *
	 * @param mixed $id The primary key value.
	 * @return object|null The found model instance, or null if not found.
	 */
	abstract protected function find(mixed $id): ?object;

	/**
	 * Retrieve all records from the associated table.
	 *
	 * @return object[] An array of model instances.
	 */
	abstract protected function all(): array;

	/**
	 * Retrieve a paginated list of records.
	 *
	 * @param int $perPage Number of records per page.
	 * @param int $page    The current page number.
	 * @return array {
	 *     'data' => object[],   // The page of model instances
	 *     'total' => int,       // Total number of records
	 *     'per_page' => int,    // Records per page
	 *     'current_page' => int // Current page number
	 * }
	 */
	abstract protected function paginate(int $perPage = 15, int $page = 1): array;

	/**
	 * Create a new record in the database.
	 *
	 * @param array $data The data to create the record.
	 * @return object The created model instance.
	 */
	abstract protected function create(array $data): object;

	/**
	 * Update an existing record by its primary key.
	 *
	 * @param mixed $id   The primary key value of the record.
	 * @param array $data The data to update.
	 * @return bool True if the update was successful, false otherwise.
	 */
	abstract protected function update(mixed $id, array $data): bool;

	/**
	 * Delete a record by its primary key.
	 *
	 * @param mixed $id The primary key value of the record.
	 * @return bool True if the deletion was successful, false otherwise.
	 */
	abstract protected function delete(mixed $id): bool;

	/**
	 * Find records by specific criteria.
	 *
	 * @param array $criteria Key-value pairs for filtering.
	 * @return object[] An array of model instances matching the criteria.
	 */
	abstract protected function findBy(array $criteria): array;

	/**
	 * Find a single record by specific criteria.
	 *
	 * @param array $criteria Key-value pairs for filtering.
	 * @return object|null The found model instance, or null if not found.
	 */
	abstract protected function findOneBy(array $criteria): ?object;

	/**
	 * Count records matching specific criteria.
	 *
	 * @param array $criteria Key-value pairs for filtering.
	 * @return int The number of matching records.
	 */
	abstract protected function count(array $criteria): int;
}
