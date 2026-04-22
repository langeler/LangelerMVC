<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;
use App\Utilities\Handlers\SQLHandler;
use App\Utilities\Traits\Filters\FiltrationTrait;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Query\DataQuery;
use App\Utilities\Query\SchemaQuery;
use App\Utilities\Traits\{
	ErrorTrait,
	TypeCheckerTrait,
	ArrayTrait,
	ManipulationTrait
};
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Managers\SettingsManager;

/**
 * Class Database
 *
 * Lazy PDO wrapper that keeps configuration loading separate from connection
 * establishment, making the service safe to resolve during application boot.
 */
class Database
{
	use ErrorTrait, TypeCheckerTrait, FiltrationTrait;
	use ArrayTrait, ManipulationTrait, PatternTrait {
		ManipulationTrait::toLower as private toLowerString;
		PatternTrait::match as private matchPattern;
	}

	public function __construct(
		protected SettingsManager $settingsManager,
		protected ErrorManager $errorManager,
		private ?PDO $pdo = null,
		protected array $cache = [],
		private array $config = [],
		protected bool $connected = false,
		private readonly array $constants = [
			'bool' => PDO::PARAM_BOOL,
			'null' => PDO::PARAM_NULL,
			'int' => PDO::PARAM_INT,
			'str' => PDO::PARAM_STR,
			'natStr' => PDO::PARAM_STR_NATL,
			'charStr' => PDO::PARAM_STR_CHAR,
			'lob' => PDO::PARAM_LOB,
			'stmt' => PDO::PARAM_STMT,
			'inOut' => PDO::PARAM_INPUT_OUTPUT,

			'fetchDefault' => PDO::FETCH_DEFAULT,
			'fetchLazy' => PDO::FETCH_LAZY,
			'fetchAssoc' => PDO::FETCH_ASSOC,
			'fetchNamed' => PDO::FETCH_NAMED,
			'fetchNum' => PDO::FETCH_NUM,
			'fetchBoth' => PDO::FETCH_BOTH,
			'fetchObj' => PDO::FETCH_OBJ,
			'fetchBound' => PDO::FETCH_BOUND,
			'fetchColumn' => PDO::FETCH_COLUMN,
			'fetchClass' => PDO::FETCH_CLASS,
			'fetchInto' => PDO::FETCH_INTO,
			'fetchFunc' => PDO::FETCH_FUNC,
			'fetchGroup' => PDO::FETCH_GROUP,
			'fetchUnique' => PDO::FETCH_UNIQUE,
			'fetchKeyPair' => PDO::FETCH_KEY_PAIR,
			'fetchClassType' => PDO::FETCH_CLASSTYPE,
			'fetchPropsLate' => PDO::FETCH_PROPS_LATE,

			'autoCommit' => PDO::ATTR_AUTOCOMMIT,
			'prefetch' => PDO::ATTR_PREFETCH,
			'timeout' => PDO::ATTR_TIMEOUT,
			'errMode' => PDO::ATTR_ERRMODE,
			'serverVersion' => PDO::ATTR_SERVER_VERSION,
			'clientVersion' => PDO::ATTR_CLIENT_VERSION,
			'serverInfo' => PDO::ATTR_SERVER_INFO,
			'connectionStatus' => PDO::ATTR_CONNECTION_STATUS,
			'case' => PDO::ATTR_CASE,
			'cursorName' => PDO::ATTR_CURSOR_NAME,
			'cursor' => PDO::ATTR_CURSOR,
			'driverName' => PDO::ATTR_DRIVER_NAME,
			'oracleNulls' => PDO::ATTR_ORACLE_NULLS,
			'persistent' => PDO::ATTR_PERSISTENT,
			'statementClass' => PDO::ATTR_STATEMENT_CLASS,
			'fetchCatalogNames' => PDO::ATTR_FETCH_CATALOG_NAMES,
			'fetchTableNames' => PDO::ATTR_FETCH_TABLE_NAMES,
			'stringifyFetches' => PDO::ATTR_STRINGIFY_FETCHES,
			'maxColumnLen' => PDO::ATTR_MAX_COLUMN_LEN,
			'defaultFetchMode' => PDO::ATTR_DEFAULT_FETCH_MODE,
			'emulatePrepares' => PDO::ATTR_EMULATE_PREPARES,
			'defaultStrParam' => PDO::ATTR_DEFAULT_STR_PARAM,

			'errSilent' => PDO::ERRMODE_SILENT,
			'errWarning' => PDO::ERRMODE_WARNING,
			'errException' => PDO::ERRMODE_EXCEPTION,

			'caseNatural' => PDO::CASE_NATURAL,
			'caseLower' => PDO::CASE_LOWER,
			'caseUpper' => PDO::CASE_UPPER,

			'nullNatural' => PDO::NULL_NATURAL,
			'nullEmptyStr' => PDO::NULL_EMPTY_STRING,
			'nullToStr' => PDO::NULL_TO_STRING,

			'cursorFwdOnly' => PDO::CURSOR_FWDONLY,
			'cursorScroll' => PDO::CURSOR_SCROLL,

			'sqliteDeterministic' => PDO::SQLITE_DETERMINISTIC,
		],
		private int $transactionDepth = 0
	) {
		$this->initializeConfig();

		if ($this->pdo instanceof PDO) {
			$this->connected = true;
		}
	}

	/**
	 * Initializes database configuration lazily.
	 *
	 * @return void
	 */
	private function initializeConfig(): void
	{
		if ($this->config !== []) {
			$this->config = $this->normalizeConfig($this->config);
			return;
		}

		$this->config = $this->wrapInTry(
			fn() => $this->normalizeConfig($this->settingsManager->getAllSettings('db')),
			'database'
		);
	}

	/**
	 * Creates a live PDO connection when needed.
	 *
	 * @return void
	 */
	private function connect(): void
	{
		if ($this->isConnected()) {
			return;
		}

		try {
			$this->pdo = $this->createPdo($this->config);
		} catch (\Throwable $exception) {
			if ($this->hasFailoverConfig()) {
				$this->pdo = $this->handleFailover();
			} else {
				throw $this->errorManager->resolveException(
					'database',
					'Failed to establish database connection.',
					$exception->getCode(),
					$exception
				);
			}
		}

		$this->cache = [];
		$this->connected = $this->pdo instanceof PDO;
	}

	/**
	 * Public backwards-compatible wrapper for the historical method name.
	 *
	 * @return PDO
	 */
	public function handleFallover(): PDO
	{
		return $this->handleFailover();
	}

	/**
	 * Establishes a failover PDO connection using FAILOVER settings.
	 *
	 * @return PDO
	 */
	public function handleFailover(): PDO
	{
		if (!$this->hasFailoverConfig()) {
			throw $this->errorManager->resolveException(
				'database',
				'Failover configuration is not defined.'
			);
		}

		$this->errorManager->logError('Attempting failover connection.', 'userWarning');

		return $this->wrapInTry(
			fn(): PDO => $this->createPdo($this->replaceElements($this->config, $this->config['FAILOVER'])),
			'database'
		);
	}

	/**
	 * Returns true when a PDO handle is available.
	 *
	 * @return bool
	 */
	public function isConnected(): bool
	{
		return $this->pdo instanceof PDO;
	}

	/**
	 * Disconnects the current PDO connection.
	 *
	 * @return void
	 */
	public function disconnect(): void
	{
		$this->pdo = null;
		$this->cache = [];
		$this->connected = false;
		$this->errorManager->logError('Database connection closed.', 'userNotice');
	}

	/**
	 * Begins a database transaction.
	 *
	 * @return bool
	 */
	public function beginTransaction(): bool
	{
		$this->ensureConnection();

		return $this->wrapInTry(
			function (): bool {
				if ($this->transactionDepth === 0) {
					$started = $this->pdo->beginTransaction();

					if ($started) {
						$this->transactionDepth = 1;
					}

					return $started;
				}

				$depth = $this->transactionDepth + 1;
				$started = $this->supportsSavepoints()
					? $this->executeTransactionCommand('SAVEPOINT ' . $this->savepointName($depth))
					: true;

				if ($started) {
					$this->transactionDepth = $depth;
				}

				return $started;
			},
			'database'
		);
	}

	/**
	 * Commits the current transaction.
	 *
	 * @return bool
	 */
	public function commit(): bool
	{
		$this->ensureConnection();

		return $this->wrapInTry(
			function (): bool {
				if ($this->transactionDepth === 0) {
					return true;
				}

				if ($this->transactionDepth === 1) {
					$committed = $this->pdo->commit();

					if ($committed) {
						$this->transactionDepth = 0;
					}

					return $committed;
				}

				$depth = $this->transactionDepth;
				$committed = $this->supportsSavepoints()
					? $this->executeTransactionCommand('RELEASE SAVEPOINT ' . $this->savepointName($depth))
					: true;

				if ($committed) {
					$this->transactionDepth--;
				}

				return $committed;
			},
			'database'
		);
	}

	/**
	 * Rolls back the current transaction.
	 *
	 * @return bool
	 */
	public function rollBack(): bool
	{
		$this->ensureConnection();

		return $this->wrapInTry(
			function (): bool {
				if ($this->transactionDepth === 0) {
					return true;
				}

				if ($this->transactionDepth === 1) {
					$rolledBack = $this->pdo->rollBack();

					if ($rolledBack) {
						$this->transactionDepth = 0;
					}

					return $rolledBack;
				}

				$depth = $this->transactionDepth;
				$rolledBack = $this->supportsSavepoints()
					? $this->executeTransactionCommand('ROLLBACK TO SAVEPOINT ' . $this->savepointName($depth))
					: true;

				if ($rolledBack) {
					$this->transactionDepth--;
				}

				return $rolledBack;
			},
			'database'
		);
	}

	/**
	 * Returns the current SQLSTATE code.
	 *
	 * @return string
	 */
	public function errorCode(): string
	{
		$this->ensureConnection();

		return $this->wrapInTry(
			fn(): string => (string) $this->pdo->errorCode(),
			'database'
		);
	}

	/**
	 * Returns detailed PDO error information.
	 *
	 * @return array
	 */
	public function errorInfo(): array
	{
		$this->ensureConnection();

		return $this->wrapInTry(
			fn(): array => $this->pdo->errorInfo(),
			'database'
		);
	}

	/**
	 * Executes a prepared query and returns the statement.
	 *
	 * @param string $query
	 * @param array $params
	 * @return PDOStatement
	 */
	public function query(string $query, array $params = []): PDOStatement
	{
		return $this->executeQuery($query, $params);
	}

	/**
	 * Executes a prepared query and returns the affected row count.
	 *
	 * @param string $query
	 * @param array $params
	 * @return int
	 */
	public function execute(string $query, array $params = []): int
	{
		return $this->wrapInTry(function () use ($query, $params): int {
			$statement = $this->executeQuery($query, $params);

			try {
				return $statement->rowCount();
			} finally {
				$statement->closeCursor();
			}
		}, 'database');
	}

	/**
	 * Fetches the first row of a query as an associative array.
	 *
	 * @param string $query
	 * @param array $params
	 * @return array|null
	 */
	public function fetchOne(string $query, array $params = []): ?array
	{
		return $this->wrapInTry(function () use ($query, $params): ?array {
			$statement = $this->executeQuery($query, $params);

			try {
				$result = $statement->fetch(PDO::FETCH_ASSOC);

				return $this->isArray($result) ? $result : null;
			} finally {
				$statement->closeCursor();
			}
		}, 'database');
	}

	/**
	 * Fetches all rows of a query.
	 *
	 * @param string $query
	 * @param array $params
	 * @param int $fetchMode
	 * @return array
	 */
	public function fetchAll(string $query, array $params = [], int $fetchMode = PDO::FETCH_ASSOC): array
	{
		return $this->wrapInTry(function () use ($query, $params, $fetchMode): array {
			$statement = $this->executeQuery($query, $params);

			try {
				return $statement->fetchAll($fetchMode);
			} finally {
				$statement->closeCursor();
			}
		}, 'database');
	}

	/**
	 * Fetches a single column from the first row of a query.
	 *
	 * @param string $query
	 * @param array $params
	 * @param int $column
	 * @return mixed
	 */
	public function fetchColumn(string $query, array $params = [], int $column = 0): mixed
	{
		return $this->wrapInTry(function () use ($query, $params, $column): mixed {
			$statement = $this->executeQuery($query, $params);

			try {
				return $statement->fetchColumn($column);
			} finally {
				$statement->closeCursor();
			}
		}, 'database');
	}

	/**
	 * Prepares a statement for execution, using the statement cache when possible.
	 *
	 * @param string $query
	 * @return PDOStatement
	 */
	public function prepare(string $query): PDOStatement
	{
		$this->ensureConnection();

		if (!$this->isString($query) || $this->trimString($query) === '') {
			throw $this->errorManager->resolveException('invalidArgument', 'Query must be a non-empty string.');
		}

		return $this->wrapInTry(
			fn(): PDOStatement => $this->cache[$query] ??= $this->pdo->prepare($query),
			'database'
		);
	}

	/**
	 * Quotes one or more string values for inclusion in a query.
	 *
	 * @param string|array $value
	 * @param int|null $parameterType
	 * @return string|array
	 */
	public function quote(string|array $value, ?int $parameterType = null): string|array
	{
		$this->ensureConnection();
		$type = $parameterType ?? $this->constants['str'];

		return $this->wrapInTry(
			fn(): string|array => $this->isArray($value)
				? $this->map(fn($item) => $this->pdo->quote($item, $type), $value)
				: $this->pdo->quote($value, $type),
			'database'
		);
	}

	/**
	 * Creates a data query builder aligned with the current connection driver.
	 */
	public function dataQuery(string $table = ''): DataQuery
	{
		return new DataQuery(
			new SQLHandler(),
			$this->errorManager,
			$table,
			[],
			[],
			$this->configuredDriver()
		);
	}

	/**
	 * Creates a schema query builder aligned with the current connection driver.
	 */
	public function schemaQuery(): SchemaQuery
	{
		return new SchemaQuery(
			new SQLHandler(),
			$this->errorManager,
			'',
			[],
			[],
			$this->configuredDriver()
		);
	}

	/**
	 * Truncates a database table after validating the identifier.
	 *
	 * @param string $table
	 * @return void
	 */
	public function truncate(string $table): void
	{
		if ($this->matchPattern('/^[A-Za-z_][A-Za-z0-9_]*$/', $table) !== 1) {
			throw $this->errorManager->resolveException('invalidArgument', 'Invalid or empty table name provided.');
		}

		$this->ensureConnection();

		$driver = $this->toLowerString((string) $this->getAttribute('driverName'));
		$identifier = $this->quoteIdentifier($table, $driver);

		if ($driver === 'sqlite') {
			$this->execute('DELETE FROM ' . $identifier);
			return;
		}

		$this->execute('TRUNCATE TABLE ' . $identifier);
	}

	/**
	 * Returns the last inserted ID.
	 *
	 * @param string|null $name
	 * @return string
	 */
	public function lastInsertId(?string $name = null): string
	{
		$this->ensureConnection();

		return $this->wrapInTry(
			fn(): string => $name !== null ? $this->pdo->lastInsertId($name) : $this->pdo->lastInsertId(),
			'database'
		);
	}

	/**
	 * Retrieves a connection attribute by key or PDO constant.
	 *
	 * @param int|string $attribute
	 * @return mixed
	 */
	public function getAttribute(int|string $attribute): mixed
	{
		$this->ensureConnection();

		return $this->wrapInTry(
			fn(): mixed => $this->pdo->getAttribute($this->constants[$attribute] ?? $attribute),
			'database'
		);
	}

	/**
	 * Sets a connection attribute by key or PDO constant.
	 *
	 * @param int|string $attribute
	 * @param mixed $value
	 * @return bool
	 */
	public function setAttribute(int|string $attribute, mixed $value): bool
	{
		$this->ensureConnection();

		return $this->wrapInTry(
			fn(): bool => $this->pdo->setAttribute($this->constants[$attribute] ?? $attribute, $value),
			'database'
		);
	}

	/**
	 * Determines whether a transaction is active.
	 *
	 * @return bool
	 */
	public function inTransaction(): bool
	{
		$this->ensureConnection();

		return $this->wrapInTry(
			fn(): bool => $this->pdo->inTransaction(),
			'database'
		);
	}

	private function supportsSavepoints(): bool
	{
		return $this->isInArray($this->configuredDriver(), ['mysql', 'pgsql', 'sqlite', 'sqlsrv'], true);
	}

	private function savepointName(int $depth): string
	{
		return 'langeler_sp_' . max(1, $depth);
	}

	private function executeTransactionCommand(string $statement): bool
	{
		$this->pdo->exec($statement);

		return true;
	}

	/**
	 * Binds parameters to a prepared statement.
	 *
	 * @param PDOStatement $stmt
	 * @param array $params
	 * @return void
	 */
	public function bindParams(PDOStatement $stmt, array $params): void
	{
		foreach ($params as $key => $value) {
			$stmt->bindValue(
				$this->isInt($key) ? $key + 1 : $key,
				$value,
				$this->constants[match (true) {
					$this->isInt($value) => 'int',
					$this->isBool($value) => 'bool',
					$value === null => 'null',
					default => 'str',
				}]
			);
		}
	}

	private function quoteIdentifier(string $identifier, string $driver): string
	{
		return match ($driver) {
			'pgsql', 'sqlite' => '"' . $identifier . '"',
			'sqlsrv' => '[' . $identifier . ']',
			default => '`' . $identifier . '`',
		};
	}

	private function configuredDriver(): string
	{
		return $this->toLowerString((string) ($this->config['CONNECTION'] ?? $this->config['DRIVER'] ?? 'mysql'));
	}

	/**
	 * Ensures that a PDO connection exists.
	 *
	 * @return void
	 */
	private function ensureConnection(): void
	{
		if (!$this->isConnected()) {
			$this->connect();
		}
	}

	/**
	 * Executes a prepared query and returns the resulting statement.
	 *
	 * @param string $query
	 * @param array $params
	 * @return PDOStatement
	 */
	private function executeQuery(string $query, array $params = []): PDOStatement
	{
		if (!$this->isString($query) || $this->trimString($query) === '') {
			throw $this->errorManager->resolveException('invalidArgument', 'Query must be a non-empty string.');
		}

		return $this->wrapInTry(function () use ($query, $params): PDOStatement {
			$stmt = $this->prepare($query);
			$this->bindParams($stmt, $params);

			if (!$stmt->execute()) {
				throw $this->errorManager->resolveException(
					'database',
					sprintf('Failed to execute query: %s', $query)
				);
			}

			return $stmt;
		}, 'database');
	}

	/**
	 * Creates a PDO instance from a config array.
	 *
	 * @param array $config
	 * @return PDO
	 */
	private function createPdo(array $config): PDO
	{
		return new PDO(
			$this->buildDsn($config),
			(string) ($config['USERNAME'] ?? ''),
			(string) ($config['PASSWORD'] ?? ''),
			[
				$this->constants['errMode'] => $this->constants['errException'],
				$this->constants['defaultFetchMode'] => $this->constants['fetchAssoc'],
				$this->constants['persistent'] => $this->var($config['POOLING'] ?? false, FILTER_VALIDATE_BOOLEAN),
				$this->constants['emulatePrepares'] => false,
			]
		);
	}

	/**
	 * Builds a DSN string for the configured driver.
	 *
	 * @param array $config
	 * @return string
	 */
	private function buildDsn(array $config): string
	{
		$driver = $this->toLowerString((string) ($config['CONNECTION'] ?? 'mysql'));

		return match ($driver) {
			'sqlite' => 'sqlite:' . (string) ($config['DATABASE'] ?? ':memory:'),
			'pgsql' => sprintf(
				'pgsql:host=%s;port=%s;dbname=%s',
				$config['HOST'] ?? 'localhost',
				$config['PORT'] ?? '5432',
				$config['DATABASE'] ?? ''
			),
			'sqlsrv' => sprintf(
				'sqlsrv:Server=%s,%s;Database=%s',
				$config['HOST'] ?? 'localhost',
				$config['PORT'] ?? '1433',
				$config['DATABASE'] ?? ''
			),
			default => sprintf(
				'%s:host=%s;dbname=%s;port=%s;charset=%s',
				$driver,
				$config['HOST'] ?? 'localhost',
				$config['DATABASE'] ?? '',
				$config['PORT'] ?? '3306',
				$config['CHARSET'] ?? 'utf8mb4'
			),
		};
	}

	/**
	 * Normalizes database configuration and failover structure.
	 *
	 * @param array $config
	 * @return array
	 */
	private function normalizeConfig(array $config): array
	{
		$normalized = $config;
		$failover = $normalized['FAILOVER'] ?? $normalized['FALLOVER'] ?? [];
		$normalized['FAILOVER'] = $this->normalizeFailoverConfig($failover);
		unset($normalized['FALLOVER']);

		return $normalized;
	}

	/**
	 * Normalizes FAILOVER config into an array shape.
	 *
	 * @param mixed $failover
	 * @return array
	 */
	private function normalizeFailoverConfig(mixed $failover): array
	{
		if ($this->isArray($failover)) {
			return $failover;
		}

		if ($this->isString($failover) && $this->trimString($failover) !== '') {
			return ['PORT' => $this->trimString($failover)];
		}

		return [];
	}

	/**
	 * Determines whether failover config exists.
	 *
	 * @return bool
	 */
	private function hasFailoverConfig(): bool
	{
		return $this->isArray($this->config['FAILOVER'] ?? null) && $this->config['FAILOVER'] !== [];
	}
}
