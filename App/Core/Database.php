<?php

namespace App\Core;

use PDO;
use PDOException;
use App\Exceptions\Database\DatabaseException;
use App\Utilities\Managers\SettingsManager;

class Database
{
	/**
	 * @var PDO Database connection instance.
	 */
	private PDO $conn;

	/**
	 * @var bool Connection status.
	 */
	private bool $connected = false;

	/**
	 * @var SettingsManager Handles configurations for the database.
	 */
	private SettingsManager $settings;

	/**
	 * @var array Database configuration settings.
	 */
	private array $config;

	/**
	 * @var string Data Source Name (DSN) for the connection.
	 */
	private string $dsn;

	/**
	 * @var array PDO connection options.
	 */
	private array $options;

	/**
	 * @var array Prepared statement cache.
	 */
	private array $cache = [];

	/**
	 * Constructor to initialize settings and connect to the database.
	 *
	 * @param SettingsManager $settings The settings manager for configurations.
	 */
	public function __construct(SettingsManager $settings)
	{
		$this->settings = $settings;
		$this->initializeConfig();
		$this->connect();
	}

	/**
	 * Load the database configuration, DSN, and PDO options.
	 *
	 * @return void
	 */
	private function initializeConfig(): void
	{
		// Set config as a property
		$this->config = $this->settings->getAllSettings('db');

		// Set DSN as a property
		$this->dsn = sprintf(
			'%s:host=%s;dbname=%s;port=%s;charset=%s',
			$this->config['CONNECTION'] ?? 'mysql', // Updated to 'CONNECTION'
			$this->config['HOST'] ?? '127.0.0.1',   // Updated to 'HOST'
			$this->config['DATABASE'] ?? '',        // Updated to 'DATABASE'
			$this->config['PORT'] ?? '3306',        // Updated to 'PORT'
			$this->config['CHARSET'] ?? 'utf8mb4'   // Updated to 'CHARSET'
		);

		// Set PDO options as a property
		$this->options = [
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_PERSISTENT         => filter_var($this->config['POOLING'], FILTER_VALIDATE_BOOLEAN),  // Updated to 'POOLING'
			PDO::ATTR_EMULATE_PREPARES   => false,
		];
	}

	/**
	 * Connect to the database, handling failover if necessary.
	 *
	 * @return void
	 * @throws DatabaseException If connection fails.
	 */
	private function connect(): void
	{
		try {
			// Use properties directly
			$this->conn = new PDO($this->dsn, $this->config['USERNAME'], $this->config['PASSWORD'], $this->options);  // Updated to 'USERNAME' and 'PASSWORD'
			$this->connected = true;
		} catch (PDOException $e) {
			$this->handleFailover($e);
		}
	}

	/**
	 * Handle failover by attempting a secondary connection.
	 *
	 * @param PDOException $primaryException Exception from the primary connection attempt.
	 * @throws DatabaseException If failover also fails.
	 */
	private function handleFailover(PDOException $primaryException): void
	{
		if (isset($this->config['FAILOVER'])) {  // Updated to 'FAILOVER'
			// Modify DSN property for failover
			$this->dsn = sprintf(
				'%s:host=%s;dbname=%s;port=%s;charset=%s',
				$this->config['CONNECTION'] ?? 'mysql',   // Updated to 'CONNECTION'
				$this->config['FAILOVER'],                // Updated to 'FAILOVER'
				$this->config['DATABASE'],                // Updated to 'DATABASE'
				$this->config['PORT'],                    // Updated to 'PORT'
				$this->config['CHARSET'] ?? 'utf8mb4'     // Updated to 'CHARSET'
			);

			try {
				$this->conn = new PDO($this->dsn, $this->config['USERNAME'], $this->config['PASSWORD'], $this->options);  // Updated to 'USERNAME' and 'PASSWORD'
				$this->connected = true;
			} catch (PDOException $failoverException) {
				throw new DatabaseException('Both primary and failover connections failed: ' . $failoverException->getMessage(), 0, $primaryException);
			}
		} else {
			throw new DatabaseException('Connection failed: ' . $primaryException->getMessage());
		}
	}

	/**
	 * Prepare and execute a query.
	 *
	 * @param string $sql SQL query.
	 * @param array $params Query parameters.
	 * @return \PDOStatement Prepared statement after execution.
	 * @throws DatabaseException If the query fails.
	 */
	public function query(string $sql, array $params = []): \PDOStatement
	{
		try {
			// Reuse or create new cached prepared statements
			if (!isset($this->cache[$sql])) {
				$this->cache[$sql] = $this->conn->prepare($sql);
			}
			$stmt = $this->cache[$sql];
			$stmt->execute($params);
			return $stmt;
		} catch (PDOException $e) {
			throw new DatabaseException('Query failed: ' . $e->getMessage());
		}
	}

	/**
	 * Fetch one row.
	 *
	 * @param string $sql SQL query.
	 * @param array $params Query parameters.
	 * @return array|null Fetched row or null.
	 */
	public function fetchOne(string $sql, array $params = []): ?array
	{
		return $this->query($sql, $params)->fetch();
	}

	/**
	 * Fetch all rows.
	 *
	 * @param string $sql SQL query.
	 * @param array $params Query parameters.
	 * @return array All fetched rows.
	 */
	public function fetchAll(string $sql, array $params = []): array
	{
		return $this->query($sql, $params)->fetchAll();
	}

	/**
	 * Begin a database transaction.
	 *
	 * @return void
	 * @throws DatabaseException If transaction initiation fails.
	 */
	public function begin(): void
	{
		try {
			$this->conn->beginTransaction();
		} catch (PDOException $e) {
			throw new DatabaseException('Failed to begin transaction: ' . $e->getMessage());
		}
	}

	/**
	 * Commit the database transaction.
	 *
	 * @return void
	 * @throws DatabaseException If the commit fails.
	 */
	public function commit(): void
	{
		try {
			$this->conn->commit();
		} catch (PDOException $e) {
			throw new DatabaseException('Failed to commit transaction: ' . $e->getMessage());
		}
	}

	/**
	 * Rollback the database transaction.
	 *
	 * @return void
	 * @throws DatabaseException If the rollback fails.
	 */
	public function rollback(): void
	{
		try {
			$this->conn->rollBack();
		} catch (PDOException $e) {
			throw new DatabaseException('Failed to rollback transaction: ' . $e->getMessage());
		}
	}

	/**
	 * Disconnect the database.
	 *
	 * @return void
	 */
	public function disconnect(): void
	{
		$this->conn = null;
		$this->connected = false;
	}

	/**
	 * Check if the connection is active.
	 *
	 * @return bool True if connected, otherwise false.
	 */
	public function isConnected(): bool
	{
		return $this->connected;
	}
}
