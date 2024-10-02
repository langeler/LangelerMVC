<?php

namespace App\Abstracts\Database;

use App\Core\Database;
use App\Exceptions\Database\MigrationException;

abstract class Migration
{
	protected Database $db;
	protected string $table;

	public function __construct(Database $db, string $table)
	{
		$this->db = $db;
		$this->table = $table;
	}

	/**
	 * Abstract method to apply the migration.
	 *
	 * @return bool
	 */
	abstract protected function up(): bool;

	/**
	 * Abstract method to rollback the migration.
	 *
	 * @return bool
	 */
	abstract protected function down(): bool;

	/**
	 * Execute a query as part of the migration.
	 *
	 * @param string $query
	 * @return bool
	 * @throws MigrationException
	 */
	protected function executeQuery(string $query): bool
	{
		try {
			return $this->db->exec($query) !== false;
		} catch (\Exception $e) {
			throw new MigrationException("Migration query failed: " . $e->getMessage());
		}
	}

	/**
	 * Check if a table exists in the database.
	 *
	 * @param string $table
	 * @return bool
	 */
	protected function tableExists(string $table): bool
	{
		$query = "SHOW TABLES LIKE '$table'";
		$stmt = $this->db->query($query);
		return $stmt->rowCount() > 0;
	}

	/**
	 * Drop a table from the database.
	 *
	 * @param string $table
	 * @return bool
	 * @throws MigrationException
	 */
	protected function dropTable(string $table): bool
	{
		if ($this->tableExists($table)) {
			$query = "DROP TABLE $table";
			return $this->executeQuery($query);
		}
		throw new MigrationException("Table $table does not exist.");
	}

	/**
	 * Create a table in the database with specified columns.
	 *
	 * @param array $columns
	 * @return bool
	 * @throws MigrationException
	 */
	protected function createTable(array $columns): bool
	{
		try {
			$columnDefinitions = implode(', ', $columns);
			$query = "CREATE TABLE IF NOT EXISTS $this->table ($columnDefinitions)";
			return $this->db->exec($query) !== false;
		} catch (\PDOException $e) {
			throw new MigrationException("Error creating table $this->table: " . $e->getMessage());
		}
	}

	/**
	 * Drop the table associated with this migration.
	 *
	 * @return bool
	 * @throws MigrationException
	 */
	protected function dropTable(): bool
	{
		try {
			$query = "DROP TABLE IF EXISTS $this->table";
			return $this->db->exec($query) !== false;
		} catch (\PDOException $e) {
			throw new MigrationException("Error dropping table $this->table: " . $e->getMessage());
		}
	}

	/**
	 * Apply migration changes within a database transaction.
	 *
	 * @return bool
	 * @throws MigrationException
	 */
	protected function applyMigration(): bool
	{
		try {
			$this->db->beginTransaction();
			$result = $this->up();
			$this->db->commit();
			return $result;
		} catch (\Exception $e) {
			$this->db->rollBack();
			throw new MigrationException("Migration failed, rolled back changes: " . $e->getMessage());
		}
	}

	/**
	 * Rollback migration changes within a database transaction.
	 *
	 * @return bool
	 * @throws MigrationException
	 */
	protected function rollbackMigration(): bool
	{
		try {
			$this->db->beginTransaction();
			$result = $this->down();
			$this->db->commit();
			return $result;
		} catch (\Exception $e) {
			$this->db->rollBack();
			throw new MigrationException("Rollback failed, rolled back changes: " . $e->getMessage());
		}
	}
}
