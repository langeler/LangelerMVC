<?php

namespace App\Abstracts\Database;

use App\Core\Database;
use App\Exceptions\Database\ModelException;
use \PDOException;

abstract class Model
{
	protected Database $db;
	protected string $table;
	protected array $fillable = [];

	public function __construct(Database $db, string $table)
	{
		$this->db = $db;
		$this->table = $table;
	}

	/**
	 * Abstract method for saving data.
	 */
	abstract protected function save(): bool;

	/**
	 * Abstract method for validating data.
	 *
	 * @param array $data
	 * @return bool
	 */
	abstract protected function validate(array $data): bool;

	/**
	 * Insert a new record into the database.
	 *
	 * @param array $data
	 * @return bool
	 * @throws ModelException
	 */
	protected function insert(array $data): bool
	{
		try {
			$columns = implode(',', array_keys($data));
			$placeholders = ':' . implode(', :', array_keys($data));

			$query = "INSERT INTO $this->table ($columns) VALUES ($placeholders)";
			$stmt = $this->db->prepare($query);

			foreach ($data as $key => $value) {
				$stmt->bindValue(":$key", $value);
			}

			return $stmt->execute();
		} catch (PDOException $e) {
			throw new ModelException("Error inserting data into $this->table: " . $e->getMessage());
		}
	}

	/**
	 * Update a record in the database.
	 *
	 * @param int $id
	 * @param array $data
	 * @return bool
	 * @throws ModelException
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
		} catch (PDOException $e) {
			throw new ModelException("Error updating record in $this->table: " . $e->getMessage());
		}
	}

	/**
	 * Delete a record from the database.
	 *
	 * @param int $id
	 * @return bool
	 * @throws ModelException
	 */
	protected function delete(int $id): bool
	{
		try {
			$query = "DELETE FROM $this->table WHERE id = :id";
			$stmt = $this->db->prepare($query);
			$stmt->bindValue(':id', $id);
			return $stmt->execute();
		} catch (PDOException $e) {
			throw new ModelException("Error deleting record from $this->table: " . $e->getMessage());
		}
	}

	/**
	 * Fill the model with data.
	 *
	 * @param array $data
	 * @throws ModelException
	 */
	protected function fill(array $data): void
	{
		foreach ($data as $key => $value) {
			if (in_array($key, $this->fillable)) {
				$this->$key = $value;
			} else {
				throw new ModelException("Field $key is not fillable.");
			}
		}
	}

	/**
	 * Find a record by its ID.
	 *
	 * @param int $id
	 * @return array|null
	 * @throws ModelException
	 */
	protected function find(int $id): ?array
	{
		try {
			return $this->db->find($this->table, $id);
		} catch (PDOException $e) {
			throw new ModelException("Error finding record with ID $id: " . $e->getMessage());
		}
	}

	/**
	 * Define a one-to-many relationship.
	 *
	 * @param string $relatedModel The related model class.
	 * @param string $foreignKey The foreign key in the related model.
	 * @param string $localKey The local key in the current model.
	 * @return array The related records.
	 * @throws ModelException
	 */
	protected function hasMany(string $relatedModel, string $foreignKey, string $localKey = 'id'): array
	{
		try {
			$relatedTable = (new $relatedModel($this->db))->getTableName();
			$query = "SELECT * FROM $relatedTable WHERE $foreignKey = :localKey";
			$stmt = $this->db->prepare($query);
			$stmt->bindValue(':localKey', $this->$localKey);
			$stmt->execute();
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			throw new ModelException("Error fetching related records: " . $e->getMessage());
		}
	}

	/**
	 * Define an inverse one-to-many (belongsTo) relationship.
	 *
	 * @param string $relatedModel The related model class.
	 * @param string $foreignKey The foreign key in the current model.
	 * @param string $ownerKey The key in the related model.
	 * @return array|null The related record.
	 * @throws ModelException
	 */
	protected function belongsTo(string $relatedModel, string $foreignKey, string $ownerKey = 'id'): ?array
	{
		try {
			$relatedTable = (new $relatedModel($this->db))->getTableName();
			$query = "SELECT * FROM $relatedTable WHERE $ownerKey = :foreignKey";
			$stmt = $this->db->prepare($query);
			$stmt->bindValue(':foreignKey', $this->$foreignKey);
			$stmt->execute();
			return $stmt->fetch();
		} catch (PDOException $e) {
			throw new ModelException("Error fetching parent record: " . $e->getMessage());
		}
	}

	/**
	 * Get the table name for the current model.
	 *
	 * @return string The table name.
	 */
	protected function getTableName(): string
	{
		return $this->table;
	}
}
