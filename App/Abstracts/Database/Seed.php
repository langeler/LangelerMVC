<?php

namespace App\Abstracts\Database;

use App\Core\Database;
use App\Exceptions\Database\SeedException;

abstract class Seed
{
	protected Database $db;
	protected string $table;

	public function __construct(Database $db, string $table)
	{
		$this->db = $db;
		$this->table = $table;
	}

	/**
	 * Abstract method to define seeding logic.
	 *
	 * @return bool
	 */
	abstract protected function seed(): bool;

	/**
	 * Insert data into the table.
	 *
	 * @param array $data
	 * @return bool
	 * @throws SeedException
	 */
	protected function insert(array $data): bool
	{
		try {
			$columns = implode(', ', array_keys($data));
			$placeholders = ':' . implode(', :', array_keys($data));
			$query = "INSERT INTO $this->table ($columns) VALUES ($placeholders)";
			$stmt = $this->db->prepare($query);

			foreach ($data as $key => $value) {
				$stmt->bindValue(":$key", $value);
			}

			return $stmt->execute();
		} catch (\PDOException $e) {
			throw new SeedException("Error seeding data into $this->table: " . $e->getMessage());
		}
	}

	/**
	 * Truncate the table before seeding.
	 *
	 * @return bool
	 * @throws SeedException
	 */
	protected function truncate(): bool
	{
		try {
			return $this->db->exec("TRUNCATE TABLE $this->table") !== false;
		} catch (\PDOException $e) {
			throw new SeedException("Error truncating table $this->table: " . $e->getMessage());
		}
	}

	/**
	 * Perform seeding operations within a database transaction.
	 *
	 * @return bool
	 * @throws SeedException
	 */
	protected function applySeed(): bool
	{
		try {
			$this->db->beginTransaction();
			$result = $this->seed();
			$this->db->commit();
			return $result;
		} catch (\Exception $e) {
			$this->db->rollBack();
			throw new SeedException("Seeding failed, rolled back changes: " . $e->getMessage());
		}
	}
}
