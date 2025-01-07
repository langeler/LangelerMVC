<?php

namespace App\Utilities\Managers;

use PDO;
use PDOStatement;
use mysqli;
use mysqli_stmt;

/**
 * Class DatabaseManager
 *
 * Provides utility methods for interacting with databases using both PDO and MySQLi.
 * This class simplifies database operations such as preparing statements, executing queries, and fetching results.
 */
class DatabaseManager
{
	// PDO Methods

	/**
	 * Create a new PDO instance.
	 *
	 * @param string $dsn The Data Source Name (DSN) for the database connection.
	 * @param string $username The username for the database connection.
	 * @param string $password The password for the database connection.
	 * @param array $options Optional. Additional PDO options.
	 * @return PDO The created PDO instance.
	 */
	public function createPDO(string $dsn, string $username, string $password, array $options = []): PDO
	{
		return new PDO($dsn, $username, $password, $options);
	}

	/**
	 * Prepare an SQL query using PDO.
	 *
	 * @param PDO $pdo The PDO instance.
	 * @param string $query The SQL query to prepare.
	 * @return PDOStatement The prepared PDOStatement.
	 */
	public function prepare(PDO $pdo, string $query): PDOStatement
	{
		return $pdo->prepare($query);
	}

	/**
	 * Execute an SQL query directly using PDO.
	 *
	 * @param PDO $pdo The PDO instance.
	 * @param string $query The SQL query to execute.
	 * @return int The number of affected rows.
	 */
	public function executeQuery(PDO $pdo, string $query): int
	{
		return $pdo->exec($query);
	}

	/**
	 * Execute a prepared PDOStatement.
	 *
	 * @param PDOStatement $statement The prepared PDO statement.
	 * @param array $parameters Optional. Parameters to bind to the query.
	 * @return bool True on success, false on failure.
	 */
	public function execute(PDOStatement $statement, array $parameters = []): bool
	{
		return $statement->execute($parameters);
	}

	/**
	 * Fetch a single row from the result set.
	 *
	 * @param PDOStatement $statement The prepared PDO statement.
	 * @param int $fetchStyle Optional. The fetch style (default: associative array).
	 * @return mixed The fetched row.
	 */
	public function fetch(PDOStatement $statement, int $fetchStyle = PDO::FETCH_ASSOC)
	{
		return $statement->fetch($fetchStyle);
	}

	/**
	 * Fetch all rows from the result set.
	 *
	 * @param PDOStatement $statement The prepared PDO statement.
	 * @param int $fetchStyle Optional. The fetch style (default: associative array).
	 * @return array The fetched rows.
	 */
	public function fetchAll(PDOStatement $statement, int $fetchStyle = PDO::FETCH_ASSOC): array
	{
		return $statement->fetchAll($fetchStyle);
	}

	// MySQLi Methods

	/**
	 * Create a new MySQLi instance.
	 *
	 * @param string $host The database host.
	 * @param string $username The database username.
	 * @param string $password The database password.
	 * @param string $dbname The name of the database.
	 * @param int $port Optional. The database port (default: 3306).
	 * @return mysqli The created MySQLi instance.
	 */
	public function createMysqli(string $host, string $username, string $password, string $dbname, int $port = 3306): mysqli
	{
		return new mysqli($host, $username, $password, $dbname, $port);
	}

	/**
	 * Prepare an SQL query using MySQLi.
	 *
	 * @param mysqli $mysqli The MySQLi instance.
	 * @param string $query The SQL query to prepare.
	 * @return mysqli_stmt The prepared MySQLi statement.
	 */
	public function prepareMysqli(mysqli $mysqli, string $query): mysqli_stmt
	{
		return $mysqli->prepare($query);
	}

	/**
	 * Execute a prepared MySQLi statement.
	 *
	 * @param mysqli_stmt $statement The prepared MySQLi statement.
	 * @return bool True on success, false on failure.
	 */
	public function executeMysqli(mysqli_stmt $statement): bool
	{
		return $statement->execute();
	}

	/**
	 * Fetch a single row from a MySQLi result set.
	 *
	 * @param mysqli_stmt $statement The prepared MySQLi statement.
	 * @return array|null The fetched row, or null if no rows are available.
	 */
	public function fetchMysqli(mysqli_stmt $statement)
	{
		return $statement->get_result()->fetch_assoc();
	}
}
