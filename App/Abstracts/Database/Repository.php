<?php

namespace App\Abstracts\Database;

use App\Core\Database;
use App\Exceptions\Database\RepositoryException;

abstract class Repository
{
	protected Database $db;
	protected string $table;

	public function __construct(Database $db, string $table)
	{
		$this->db = $db;
		$this->table = $table;
	}

	/**
	 * Abstract method to fetch all records.
	 *
	 * @return array
	 */
	abstract protected function all(): array;

	/**
	 * Abstract method to find a record by its ID.
	 *
	 * @param int $id
	 * @return array|null
	 */
	abstract protected function findById(int $id): ?array;

	/**
	 * Find a record by a specific column value.
	 *
	 * @param string $column
	 * @param mixed $value
	 * @return array|null
	 * @throws RepositoryException
	 */
	protected function findBy(string $column, $value): ?array
	{
		try {
			$query = "SELECT * FROM $this->table WHERE $column = :value";
			$stmt = $this->db->prepare($query);
			$stmt->bindParam(':value', $value);
			$stmt->execute();

			return $stmt->fetch() ?: null;
		} catch (\PDOException $e) {
			throw new RepositoryException("Error finding by $column: " . $e->getMessage());
		}
	}

	/**
	 * Create a new record in the database.
	 *
	 * @param array $data
	 * @return bool
	 * @throws RepositoryException
	 */
	protected function create(array $data): bool
	{
		try {
			$keys = array_keys($data);
			$fields = implode(',', $keys);
			$placeholders = ':' . implode(',:', $keys);

			$query = "INSERT INTO $this->table ($fields) VALUES ($placeholders)";
			$stmt = $this->db->prepare($query);
			foreach ($data as $key => $value) {
				$stmt->bindValue(":$key", $value);
			}

			return $stmt->execute();
		} catch (\PDOException $e) {
			throw new RepositoryException("Error creating record: " . $e->getMessage());
		}
	}

	/**
	 * Update an existing record in the database.
	 *
	 * @param int $id
	 * @param array $data
	 * @return bool
	 * @throws RepositoryException
	 */
	protected function update(int $id, array $data): bool
	{
		try {
			$fields = implode(', ', array_map(fn($key) => "$key = :$key", array_keys($data)));
			$query = "UPDATE $this->table SET $fields WHERE id = :id";
			$stmt = $this->db->prepare($query);
			$stmt->bindValue(':id', $id);
			foreach ($data as $key => $value) {
				$stmt->bindValue(":$key", $value);
			}

			return $stmt->execute();
		} catch (\PDOException $e) {
			throw new RepositoryException("Error updating record: " . $e->getMessage());
		}
	}

	/**
	 * Delete a record from the database by its ID.
	 *
	 * @param int $id
	 * @return bool
	 * @throws RepositoryException
	 */
	protected function delete(int $id): bool
	{
		try {
			$query = "DELETE FROM $this->table WHERE id = :id";
			$stmt = $this->db->prepare($query);
			$stmt->bindValue(':id', $id);
			return $stmt->execute();
		} catch (\PDOException $e) {
			throw new RepositoryException("Error deleting record: " . $e->getMessage());
		}
	}

	/**
	 * Get a paginated set of records from the database.
	 *
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 * @throws RepositoryException
	 */
	protected function paginate(int $limit, int $offset = 0): array
	{
		try {
			$query = "SELECT * FROM $this->table LIMIT :limit OFFSET :offset";
			$stmt = $this->db->prepare($query);
			$stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
			$stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
			$stmt->execute();

			return $stmt->fetchAll();
		} catch (\PDOException $e) {
			throw new RepositoryException("Error paginating records: " . $e->getMessage());
		}
	}

	/**
	 * Filter records based on specific conditions.
	 *
	 * @param array $conditions
	 * @return array
	 * @throws RepositoryException
	 */
	protected function filter(array $conditions): array
	{
		try {
			$whereClauses = [];
			foreach ($conditions as $column => $value) {
				$whereClauses[] = "$column = :$column";
			}
			$where = implode(' AND ', $whereClauses);

			$query = "SELECT * FROM $this->table WHERE $where";
			$stmt = $this->db->prepare($query);

			foreach ($conditions as $column => $value) {
				$stmt->bindValue(":$column", $value);
			}

			$stmt->execute();
			return $stmt->fetchAll();
		} catch (\PDOException $e) {
			throw new RepositoryException("Error filtering records: " . $e->getMessage());
		}
	}
}
