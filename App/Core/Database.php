<?php

declare(strict_types=1);

namespace App\Core;

use PDO; // PHP Data Objects - Core database abstraction layer.
use PDOStatement; // Represents a prepared statement and its result set.
use App\Utilities\Traits\{
	ErrorTrait,       // Provides error handling with `wrapInTry` for exception transformation.
	TypeCheckerTrait, // Offers utilities for type validation and checking.
	ArrayTrait        // Includes utility methods for array operations.
};
use App\Utilities\Managers\System\ErrorManager; // Handles error and exception management in the system.
use App\Utilities\Managers\SettingsManager;    // Manages configuration settings for the database.
use App\Exceptions\Database\DatabaseException; // Custom exception for database-related errors.

/**
 * Class Database
 *
 * A robust PDO wrapper that integrates:
 * - Error handling via `ErrorManager` and `ErrorTrait`.
 * - Configuration management via `SettingsManager` (if no config is provided).
 * - Advanced PDO functionality with constants and caching.
 *
 * Key Features:
 * - PDO constants mapped and exposed via `pdoConstants`.
 * - Failover mechanism for secondary connections.
 * - Prepared statement caching for efficient query execution.
 * - Error handling with dynamic exception resolution.
 *
 * Traits Used:
 * - **ErrorTrait**: Provides error handling with `wrapInTry` for exception transformation.
 * - **TypeCheckerTrait**: Offers utilities for type validation and checking.
 * - **ArrayTrait**: Includes utility methods for array operations.
 *
 * @package App\Core
 */
class Database
{
	use ErrorTrait,        // Provides error handling with `wrapInTry` for exceptions.
		TypeCheckerTrait,  // Validates and checks data types.
		ArrayTrait;        // Offers array manipulation and validation utilities.

	/**
	 * Database constructor.
	 *
	 * @param SettingsManager $settingsManager The settings manager for configurations.
	 * @param ErrorManager    $errorManager    The error manager for handling exceptions.
	 * @param PDO|null        $pdo             Optional PDO instance for testing/manual setup.
	 * @param array           $cache           Optional statement cache, defaults to an empty array.
	 * @param array           $config          Optional configuration, defaults to an empty array.
	 * @param bool            $connected       Initial connection status, defaults to false.
	 * @param array           $constants       The PDO constants mapping (read-only).
	 * @param int             $transactionDepth Nested transaction depth, defaults to 0.
	 *
	 * @throws DatabaseException If initialization or connection fails.
	 */
	public function __construct(
		protected SettingsManager $settingsManager,
		protected ErrorManager $errorManager,
		private ?PDO $pdo = null,
		protected array $cache = [],
		private array $config = [],
		protected bool $connected = false,
		private readonly array $constants = [
			// Data Types
			'bool' => PDO::PARAM_BOOL, // Boolean data type.
			'null' => PDO::PARAM_NULL, // SQL NULL data type.
			'int' => PDO::PARAM_INT, // SQL INTEGER data type.
			'str' => PDO::PARAM_STR, // SQL CHAR, VARCHAR, or other string data type.
			'natStr' => PDO::PARAM_STR_NATL, // National character set string.
			'charStr' => PDO::PARAM_STR_CHAR, // Regular character set string.
			'lob' => PDO::PARAM_LOB, // SQL large object LOB data type.
			'stmt' => PDO::PARAM_STMT, // Recordset type, Not supported by any drivers.
			'inOut' => PDO::PARAM_INPUT_OUTPUT, // INOUT parameter for stored procedures. Requires bitwise OR with a data type.

			// Fetch Styles
			'fetchDefault' => PDO::FETCH_DEFAULT, // Default fetch mode.
			'fetchLazy' => PDO::FETCH_LAZY, // Fetch as an object, lazy creation of properties.
			'fetchAssoc' => PDO::FETCH_ASSOC, // Fetch as an associative array.
			'fetchNamed' => PDO::FETCH_NAMED, // Fetch as an associative array, with duplicate column names returned as arrays.
			'fetchNum' => PDO::FETCH_NUM, // Fetch as a numerically indexed array.
			'fetchBoth' => PDO::FETCH_BOTH, // Fetch as both numeric and associative array, by default.
			'fetchObj' => PDO::FETCH_OBJ, // Fetch as an object with property names matching column names.
			'fetchBound' => PDO::FETCH_BOUND, // Assign result set values to PHP variables bound with bindParam.
			'fetchColumn' => PDO::FETCH_COLUMN, // Fetch a single column from the next row.
			'fetchClass' => PDO::FETCH_CLASS, // Fetch as an instance of a specified class.
			'fetchInto' => PDO::FETCH_INTO, // Populate an existing object instance with row data.
			'fetchFunc' => PDO::FETCH_FUNC, // Fetch rows into a function, which is called for each row.
			'fetchGroup' => PDO::FETCH_GROUP, // Group results by the first column.
			'fetchUnique' => PDO::FETCH_UNIQUE, // Return unique values only.
			'fetchKeyPair' => PDO::FETCH_KEY_PAIR, // Fetch rows as key-value pairs, two-column result.
			'fetchClassType' => PDO::FETCH_CLASSTYPE, // Fetch class name from the first column.
			'fetchPropsLate' => PDO::FETCH_PROPS_LATE, // Call the class constructor before setting properties.

			// Attributes
			'autoCommit' => PDO::ATTR_AUTOCOMMIT, // Auto-commit mode.
			'prefetch' => PDO::ATTR_PREFETCH, // Prefetch size for queries.
			'timeout' => PDO::ATTR_TIMEOUT, // Connection timeout, in seconds.
			'errMode' => PDO::ATTR_ERRMODE, // Error handling mode.
			'serverVersion' => PDO::ATTR_SERVER_VERSION, // Server version information.
			'clientVersion' => PDO::ATTR_CLIENT_VERSION, // Client library version information.
			'serverInfo' => PDO::ATTR_SERVER_INFO, // Meta information about the server.
			'connectionStatus' => PDO::ATTR_CONNECTION_STATUS, // Connection status.
			'case' => PDO::ATTR_CASE, // Case conversion for column names.
			'cursorName' => PDO::ATTR_CURSOR_NAME, // Cursor name for positioned updates.
			'cursor' => PDO::ATTR_CURSOR, // Cursor type, forward-only or scrollable.
			'driverName' => PDO::ATTR_DRIVER_NAME, // Name of the driver.
			'oracleNulls' => PDO::ATTR_ORACLE_NULLS, // Conversion of empty strings to NULL.
			'persistent' => PDO::ATTR_PERSISTENT, // Persistent connection.
			'statementClass' => PDO::ATTR_STATEMENT_CLASS, // Class for statements.
			'fetchCatalogNames' => PDO::ATTR_FETCH_CATALOG_NAMES, // Prepend catalog names to column names.
			'fetchTableNames' => PDO::ATTR_FETCH_TABLE_NAMES, // Prepend table names to column names.
			'stringifyFetches' => PDO::ATTR_STRINGIFY_FETCHES, // Convert all fetched values to strings.
			'maxColumnLen' => PDO::ATTR_MAX_COLUMN_LEN, // Maximum column name length.
			'defaultFetchMode' => PDO::ATTR_DEFAULT_FETCH_MODE, // Default fetch mode for results.
			'emulatePrepares' => PDO::ATTR_EMULATE_PREPARES, // Emulate prepared statements.
			'defaultStrParam' => PDO::ATTR_DEFAULT_STR_PARAM, // Default string parameter type.

			// Error Modes
			'errSilent' => PDO::ERRMODE_SILENT, // No error handling. Default mode.
			'errWarning' => PDO::ERRMODE_WARNING, // Raise PHP warnings on errors.
			'errException' => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors.

			// Case Handling
			'caseNatural' => PDO::CASE_NATURAL, // Use natural case for column names.
			'caseLower' => PDO::CASE_LOWER, // Convert column names to lowercase.
			'caseUpper' => PDO::CASE_UPPER, // Convert column names to uppercase.

			// NULL Handling
			'nullNatural' => PDO::NULL_NATURAL, // Leave NULL values unchanged.
			'nullEmptyStr' => PDO::NULL_EMPTY_STRING, // Convert empty strings to NULL.
			'nullToStr' => PDO::NULL_TO_STRING, // Convert NULL to empty strings.

			// Cursor Types
			'cursorFwdOnly' => PDO::CURSOR_FWDONLY, // Forward-only cursor.
			'cursorScroll' => PDO::CURSOR_SCROLL, // Scrollable cursor.

			// SQLite-Specific
			'sqliteDeterministic' => PDO::SQLITE_DETERMINISTIC, // Deterministic functions in SQLite.
		],
		private int $transactionDepth = 0
	) {
		$this->initializeConfig();
		$this->connect();
	}

	/**
	 * Initializes the database configuration only if $this->config is empty.
	 *
	 * @return void
	 * @throws DatabaseException If configuration cannot be loaded.
	 */
	private function initializeConfig(): void
	{
		!$this->isEmpty($this->config)
			? null
			: $this->config = $this->wrapInTry(
				fn() => $this->settingsManager->getAllSettings('db'),
				$this->errorManager->resolveException('settings', 'Failed to load database configuration.')
			);
	}

	/**
	 * Establishes a PDO connection.
	 *
	 * @return void
	 * @throws DatabaseException If the connection fails.
	 */
	private function connect(): void
	{
		$this->isConnected()
			? null
			: $this->wrapInTry(
				fn() => $this->pdo = new PDO(
					sprintf(
						'%s:host=%s;dbname=%s;port=%s;charset=%s',
						$this->config['CONNECTION'],
						$this->config['HOST'],
						$this->config['DATABASE'],
						$this->config['PORT'] ?? '3306',
						$this->config['CHARSET'] ?? 'utf8mb4'
					),
					$this->config['USERNAME'],
					$this->config['PASSWORD'],
					[
						$this->constants['errMode']          => $this->constants['errException'],
						$this->constants['defaultFetchMode'] => $this->constants['fetchAssoc'],
						$this->constants['persistent']       => filter_var($this->config['POOLING'] ?? false, FILTER_VALIDATE_BOOLEAN),
						$this->constants['emulatePrepares']  => false,
					]
				),
				$this->errorManager->resolveException('database', 'Failed to establish database connection.')
			);

		$this->connected = true;
		$this->errorManager->logError('Database connection established.', 'info', __FILE__);
	}

	/**
	 * Handles failover by attempting to establish a secondary connection using failover settings.
	 *
	 * @return PDO The failover PDO connection instance.
	 * @throws DatabaseException If the failover connection fails.
	 */
	public function handleFallover(): PDO
	{
		$this->errorManager->logError('Attempting failover connection.', 'warning', __FILE__);

		return $this->wrapInTry(
			fn(): PDO => $this->isConnected()
				? $this->pdo
				: new PDO(
					sprintf(
						'%s:host=%s;dbname=%s;port=%s;charset=%s',
						$this->config['FALLOVER']['CONNECTION'] ?? $this->config['CONNECTION'],
						$this->config['FALLOVER']['HOST']       ?? $this->config['HOST'],
						$this->config['FALLOVER']['DATABASE']   ?? $this->config['DATABASE'],
						$this->config['FALLOVER']['PORT']       ?? $this->config['PORT'] ?? '3306',
						$this->config['FALLOVER']['CHARSET']    ?? $this->config['CHARSET'] ?? 'utf8mb4'
					),
					$this->config['FALLOVER']['USERNAME'] ?? $this->config['USERNAME'],
					$this->config['FALLOVER']['PASSWORD'] ?? $this->config['PASSWORD'],
					[
						$this->constants['errMode']          => $this->constants['errException'],
						$this->constants['defaultFetchMode'] => $this->constants['fetchAssoc'],
						$this->constants['persistent']       => filter_var(
							$this->config['FALLOVER']['POOLING'] ?? $this->config['POOLING'] ?? false,
							FILTER_VALIDATE_BOOLEAN
						),
						$this->constants['emulatePrepares']  => false,
					]
				),
			$this->errorManager->resolveException('database', 'Failover connection attempt failed.')
		);
	}

	/**
	 * Checks if the database connection is active.
	 *
	 * @return bool True if connected, false otherwise.
	 */
	public function isConnected(): bool
	{
		return $this->wrapInTry(
			fn(): bool => $this->getAttribute($this->constants['connectionStatus']) !== false,
			false
		);
	}

	/**
	 * Gracefully disconnects the database connection.
	 *
	 * @return void
	 */
	public function disconnect(): void
	{
		$this->ensureConnectionClosed();
	}

	/**
	 * Begins a database transaction.
	 *
	 * @return bool True if the transaction was successfully started.
	 * @throws DatabaseException If the transaction cannot be started.
	 */
	public function beginTransaction(): bool
	{
		return $this->wrapInTry(
			fn(): bool => ++$this->transactionDepth === 1
				? $this->pdo->beginTransaction()
				: true,
			$this->errorManager->resolveException('database', 'Failed to begin transaction.')
		);
	}

	/**
	 * Commits the current transaction.
	 *
	 * @return bool True if the transaction was successfully committed.
	 * @throws DatabaseException If the commit fails.
	 */
	public function commit(): bool
	{
		return $this->wrapInTry(
			fn(): bool => $this->transactionDepth > 0 && --$this->transactionDepth === 0
				? $this->pdo->commit()
				: true,
			$this->errorManager->resolveException('database', 'Failed to commit transaction.')
		);
	}

	/**
	 * Rolls back the current transaction.
	 *
	 * @return bool True if the transaction was successfully rolled back.
	 * @throws DatabaseException If the rollback fails.
	 */
	public function rollBack(): bool
	{
		return $this->wrapInTry(
			fn(): bool => $this->transactionDepth > 0 && --$this->transactionDepth === 0
				? $this->pdo->rollBack()
				: true,
			$this->errorManager->resolveException('database', 'Failed to roll back transaction.')
		);
	}

	/**
	 * Fetches the SQLSTATE associated with the last operation on the DB handle.
	 *
	 * @return string The SQLSTATE error code.
	 * @throws DatabaseException If retrieval fails.
	 */
	public function errorCode(): string
	{
		return $this->wrapInTry(
			fn(): string => $this->pdo->errorCode(),
			$this->errorManager->resolveException('database', 'Failed to retrieve SQLSTATE error code.')
		);
	}

	/**
	 * Fetches extended error information associated with the last operation on the DB handle.
	 *
	 * @return array The error information array.
	 * @throws DatabaseException If retrieval fails.
	 */
	public function errorInfo(): array
	{
		return $this->wrapInTry(
			fn(): array => $this->pdo->errorInfo(),
			$this->errorManager->resolveException('database', 'Failed to retrieve extended error information.')
		);
	}

	/**
	 * Executes a query with parameters, returning PDOStatement or false.
	 *
	 * @param string $query  The SQL query.
	 * @param array  $params Parameters to bind.
	 *
	 * @return PDOStatement|false
	 *
	 * @throws DatabaseException
	 */
	private function executeQuery(string $query, array $params = []): PDOStatement|false
	{
		(!$this->isString($query) || $this->isEmpty($query))
			? throw $this->errorManager->resolveException('invalidArgument', 'Query must be a non-empty string.')
			: null;

		return $this->wrapInTry(
			function () use ($query, $params): PDOStatement|false {
				$stmt = $this->prepare($query);
				$this->bindParams($stmt, $params);
				return $stmt->execute() ? $stmt : false;
			},
			$this->errorManager->resolveException('database', sprintf('Failed to execute query: %s', $query))
		);
	}

	/**
	 * Prepares, binds, and executes a query, returning PDOStatement on success.
	 *
	 * @param string $query
	 * @param array  $params
	 *
	 * @return PDOStatement
	 *
	 * @throws DatabaseException
	 */
	public function query(string $query, array $params = []): PDOStatement
	{
		return $this->wrapInTry(
			fn(): PDOStatement => $this->executeQuery($query, $params),
			$this->errorManager->resolveException('database', sprintf('Failed to execute query: %s', $query))
		);
	}

	/**
	 * Executes a query and returns the affected row count.
	 *
	 * @param string $query
	 * @param array  $params
	 *
	 * @return int
	 *
	 * @throws DatabaseException
	 */
	public function execute(string $query, array $params = []): int
	{
		return $this->wrapInTry(
			fn(): int => $this->executeQuery($query, $params)?->rowCount() ?? 0,
			$this->errorManager->resolveException('database', sprintf('Failed to execute query: %s', $query))
		);
	}

	/**
	 * Prepares a statement for execution, using statement cache if available.
	 *
	 * @param string $query
	 *
	 * @return PDOStatement
	 *
	 * @throws DatabaseException
	 */
	public function prepare(string $query): PDOStatement
	{
		$this->ensureConnection();

		return $this->wrapInTry(
			fn(): PDOStatement => $this->cache[$query] ??= $this->pdo->prepare($query),
			$this->errorManager->resolveException('database', sprintf('Failed to prepare query: %s', $query))
		);
	}

	/**
	 * Quotes a string or array for use in a query.
	 *
	 * @param string|array $value
	 * @param int|null     $parameterType
	 *
	 * @return string|array
	 *
	 * @throws DatabaseException
	 */
	public function quote(string|array $value, ?int $parameterType = null): string|array
	{
		return $this->wrapInTry(
			function () use ($value, $parameterType): string|array {
				$type = $parameterType ?? $this->constants['str'];
				return $this->isArray($value)
					? $this->map(fn($v) => $this->pdo->quote($v, $type), $value)
					: $this->pdo->quote($value, $type);
			},
			$this->errorManager->resolveException('database', 'Failed to quote value(s) for query.')
		);
	}

	/**
	 * Closes the connection if active and logs out.
	 */
	private function ensureConnectionClosed(): void
	{
		$this->isConnected()
			? $this->wrapInTry(
				fn() => $this->getAttribute($this->constants['driverName']) === 'mysql'
					? $this->query('KILL CONNECTION_ID()')
					: null,
				$this->errorManager->resolveException('database', 'Failed to disconnect the connection.')
			)
			: null;

		$this->pdo       = null;
		$this->connected = false;
		$this->errorManager->logError('Database connection closed.', 'info', __FILE__);
	}

	/**
	 * Truncates a table.
	 *
	 * @param string $table
	 *
	 * @return void
	 *
	 * @throws DatabaseException
	 */
	public function truncate(string $table): void
	{
		(!$this->isString($table) || $this->isEmpty($table))
			? throw $this->errorManager->resolveException('invalidArgument', 'Invalid or empty table name provided.')
			: $this->execute(sprintf('TRUNCATE TABLE %s', $this->quote($table)));
	}

	/**
	 * Retrieves the ID of the last inserted row or sequence value.
	 *
	 * @param string|null $name
	 *
	 * @return string
	 *
	 * @throws DatabaseException
	 */
	public function lastInsertId(?string $name = null): string
	{
		return $this->wrapInTry(
			fn(): string => $name ? $this->pdo->lastInsertId($name) : $this->pdo->lastInsertId(),
			$this->errorManager->resolveException('database', 'Failed to retrieve last inserted ID.')
		);
	}

	/**
	 * Retrieves a database connection attribute.
	 *
	 * @param int|string $attribute One of the PDO::ATTR_* constants or a key from $this->constants.
	 *
	 * @return mixed
	 *
	 * @throws DatabaseException
	 */
	public function getAttribute(int|string $attribute): mixed
	{
		return $this->wrapInTry(
			fn(): mixed => $this->pdo->getAttribute(
				$this->isSet($this->constants[$attribute]) ? $this->constants[$attribute] : $attribute
			),
			$this->errorManager->resolveException('database', 'Failed to retrieve database connection attribute.')
		);
	}

	/**
	 * Sets an attribute on the database handle.
	 *
	 * @param int|string $attribute One of the PDO::ATTR_* constants or a key from $this->constants.
	 * @param mixed      $value
	 *
	 * @return bool
	 *
	 * @throws DatabaseException
	 */
	public function setAttribute(int|string $attribute, mixed $value): bool
	{
		return $this->wrapInTry(
			fn(): bool => $this->pdo->setAttribute(
				$this->isSet($this->constants[$attribute]) ? $this->constants[$attribute] : $attribute,
				$value
			),
			$this->errorManager->resolveException('database', 'Failed to set attribute on the database handle.')
		);
	}

	/**
	 * Checks if a transaction is currently active.
	 *
	 * @return bool True if a transaction is active; false otherwise.
	 * @throws DatabaseException If checking the transaction state fails.
	 */
	public function inTransaction(): bool
	{
		return $this->wrapInTry(
			fn(): bool => $this->pdo->inTransaction(),
			$this->errorManager->resolveException('database', 'Failed to check transaction state.')
		);
	}

	/**
	 * Binds parameters to a statement, inferring PDO param type from value.
	 *
	 * @param PDOStatement $stmt
	 * @param array        $params
	 *
	 * @return void
	 *
	 * @throws DatabaseException
	 */
	public function bindParams(PDOStatement $stmt, array $params): void
	{
		$this->walk(
			$params,
			fn($value, $key) => $this->wrapInTry(
				fn(): bool => $stmt->bindValue(
					$this->isInt($key) ? $key + 1 : $key,
					$value,
					$this->constants[match (true) {
						$this->isInt($value)  => 'int',
						$this->isBool($value) => 'bool',
						$this->isNull($value) => 'null',
						default               => 'str'
					}]
				),
				$this->errorManager->resolveException('database', "Failed to bind parameter: $key")
			)
		);
	}
}
