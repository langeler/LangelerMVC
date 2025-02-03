<?php

namespace App\Abstracts\Database;

use App\Utilities\Handlers\SQLHandler;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Traits\{
	EncodingTrait,
	CheckerTrait,
	ManipulationTrait,
	ArrayTrait,
	ErrorTrait,
	TypeCheckerTrait
};

/**
 * Abstract Query Class
 *
 * Provides a foundation for SQL query construction and execution.
 * Includes methods for processing identifiers, operators, columns, and values
 * while ensuring proper escaping and validation.
 */
abstract class Query
{
	use EncodingTrait,
		CheckerTrait,
		ManipulationTrait,
		ArrayTrait,
		ErrorTrait,
		TypeCheckerTrait;

	/**
	 * Constructor for initializing the Query object.
	 *
	 * @param SQLHandler $sql Handler for SQL-related operations.
	 * @param ErrorManager $errorManager Error manager for handling query-related errors.
	 * @param string $table The database table associated with the query.
	 * @param array $columns The columns involved in the query.
	 * @param array $values The values used within the query.
	 */
	protected function __construct(
		protected SQLHandler $sql,
		protected ErrorManager $errorManager,
		protected string $table = '',
		protected array $columns = [],
		protected array $values = []
	) {}

	// -------------------------------------------------------------------------
	// CORE QUERY METHODS
	// -------------------------------------------------------------------------

	/**
	 * Builds an SQL query string with optional comments.
	 *
	 * This method constructs an SQL query string by combining the query type with
	 * the provided parameters. If a comment is provided, it is appended to the query.
	 * Any errors encountered during query construction are handled via `wrapInTry`.
	 *
	 * @param string $queryType The type of SQL query (e.g., SELECT, INSERT, UPDATE).
	 * @param array $parameters Query parameters such as columns, values, and conditions.
	 * @param string|null $comment Optional SQL comment for clarity.
	 * @return string The fully constructed SQL query string.
	 */
	public function build(string $queryType, array $parameters, ?string $comment = null): string
	{
		return $this->wrapInTry(fn() =>
			$this->buildType($queryType, $parameters)
			. ($this->isString($comment) && !$this->isEmpty($comment)
				? ' /*' . $this->escapeHtml($comment) . '*/'
				: '')
		, 'Failed to build query string');
	}

	/**
	 * Constructs an SQL query string based on the given type and parameters.
	 *
	 * This method generates an SQL statement by retrieving the corresponding
	 * SQL keyword from `SQLHandler` and appending the provided parameters.
	 *
	 * @param string $queryType The SQL statement type (e.g., SELECT, INSERT).
	 * @param array $parameters Query parameters to be appended to the statement.
	 * @return string The generated SQL query string.
	 */
	public function buildType(string $queryType, array $parameters): string
	{
		return $this->sql->statement($queryType) . ' ' .
			($this->count($parameters) > 0 ? $this->join(' ', $parameters) : '');
	}

	// -------------------------------------------------------------------------
	// UTILITY METHODS (ESCAPING)
	// -------------------------------------------------------------------------

	/**
	 * Escapes and wraps SQL identifiers such as table names and aliases.
	 *
	 * This method ensures that SQL identifiers are properly escaped to prevent
	 * syntax errors or security vulnerabilities.
	 *
	 * @param array $parameters Identifiers to escape.
	 * @return array Escaped identifiers, enclosed in double quotes.
	 */
	public function escapeIdentifiers(array $parameters): array
	{
		return $this->map(fn($p) => '"' . $this->escapeHtml($p) . '"', $parameters);
	}

	/**
	 * Escapes SQL operators to ensure valid syntax and prevent injection risks.
	 *
	 * This method retrieves the correct SQL operator from `SQLHandler` for safe use.
	 *
	 * @param array $parameters Operators to escape.
	 * @return array Escaped operators.
	 */
	public function escapeOperators(array $parameters): array
	{
		return $this->map(fn($op) => $this->sql->operator($op), $parameters);
	}

	/**
	 * Escapes SQL column names to ensure proper query execution.
	 *
	 * Column names are enclosed in backticks (`) to comply with SQL syntax rules.
	 *
	 * @param array $parameters Column names to escape.
	 * @return array Escaped column names.
	 */
	public function escapeColumns(array $parameters): array
	{
		return $this->map(fn($col) => '`' . $this->escapeHtml($col) . '`', $parameters);
	}

	/**
	 * Escapes SQL values while preserving data integrity.
	 *
	 * Strings are enclosed in single quotes ('), and null values are converted to `NULL`.
	 * This method ensures safe query execution without unintended side effects.
	 *
	 * @param array $parameters Values to escape.
	 * @return array Escaped values.
	 */
	public function escapeValues(array $parameters): array
	{
		return $this->map(fn($val) =>
			$this->isNull($val)
				? 'NULL'
				: ($this->isString($val)
					? "'" . $this->escapeHtml($val) . "'"
					: (string)$val
				),
			$parameters
		);
	}

	// -------------------------------------------------------------------------
	// PARSING METHODS – using CheckerTrait, ManipulationTrait, and ArrayTrait
	// -------------------------------------------------------------------------

	/**
	 * Parses SQL identifiers by ensuring they are alphabetic and converting them to uppercase.
	 *
	 * This method filters out empty or whitespace-only values and ensures identifiers
	 * are properly formatted.
	 *
	 * @param array $parameters List of identifiers to parse.
	 * @return array Processed identifiers in uppercase.
	 */
	public function parseIdentifiers(array $parameters): array
	{
		return $this->map(
			fn($id) => ($this->isString($id) && $this->isAlphabetic($id))
				? $this->toUpper($id)
				: $id,
			$this->filterNonEmpty(
				$this->filter($parameters, fn($x) => $this->isString($x) && !$this->isWhitespace($x))
			)
		);
	}

	/**
	 * Parses SQL operators by ensuring they are alphanumeric and converting them to lowercase.
	 *
	 * This method removes spaces from operators and ensures they are correctly formatted.
	 *
	 * @param array $parameters List of operators to parse.
	 * @return array Processed operators in lowercase.
	 */
	public function parseOperators(array $parameters): array
	{
		return $this->map(
			fn($op) => $this->isString($op)
				? $this->toLower($op)
				: $op,
			$this->filter($parameters, fn($o) => $this->isString($o) && $this->isAlphanumeric($this->replace([' '], [''], $o)))
		);
	}

	/**
	 * Parses SQL column names by replacing underscores with spaces and capitalizing words.
	 *
	 * Numeric column names are ignored.
	 *
	 * @param array $parameters List of column names to parse.
	 * @return array Processed column names.
	 */
	public function parseColumns(array $parameters): array
	{
		return $this->map(
			fn($c) => $this->isString($c)
				? $this->capitalizeWords($this->replace(['_'], [' '], trim($c)))
				: $c,
			$this->filter($parameters, fn($col) => !($this->isString($col) && $this->isNumeric($col)))
		);
	}

	/**
	 * Parses SQL values by handling JSON decoding, base64 decoding, and stripping slashes.
	 *
	 * This method ensures values are properly formatted and decoded if necessary.
	 *
	 * @param array $parameters List of values to parse.
	 * @return array Processed values.
	 */
	public function parseValues(array $parameters): array
	{
		return $this->map(
			fn($v) => $this->isString($v)
				? ($this->isJson($v)
					? $this->fromJson($v)
					: ($this->isAlphanumeric($this->replace(['=', '/'], ['', ''], $v))
						? $this->base64DecodeString($v)
						: $this->stripSlashesFromString($v)
					)
				)
				: $v,
			$parameters
		);
	}

	// -------------------------------------------------------------------------
	// PROCESSING METHODS – chaining parsing then escaping inline
	// -------------------------------------------------------------------------

	/**
	 * Processes SQL identifiers by parsing them and then escaping them.
	 *
	 * This method ensures identifiers are unique and formatted correctly before escaping.
	 *
	 * @param array $parameters List of identifiers to process.
	 * @return array Escaped identifiers.
	 */
	public function processIdentifiers(array $parameters): array
	{
		return $this->escapeIdentifiers(
			$this->differenceByKeys(
				$this->parseIdentifiers($parameters),
				$this->parseIdentifiers($this->keys($parameters)),
				fn($k1, $k2) => strcasecmp((string)$k1, (string)$k2)
			)
		);
	}

	/**
	 * Processes SQL operators by parsing and then escaping them.
	 *
	 * This method ensures operators are formatted correctly before escaping.
	 *
	 * @param array $parameters List of operators to process.
	 * @return array Escaped operators.
	 */
	public function processOperators(array $parameters): array
	{
		return $this->escapeOperators(
			$this->mergeUnique($this->parseOperators($parameters))
		);
	}

	/**
	 * Processes SQL column names by parsing and then escaping them.
	 *
	 * Column names are converted to uppercase before escaping.
	 *
	 * @param array $parameters List of column names to process.
	 * @return array Escaped column names.
	 */
	public function processColumns(array $parameters): array
	{
		return $this->escapeColumns(
			$this->walk(
				$this->parseColumns($parameters),
				fn(&$val) => $this->isString($val) ? $val = $this->toUpper($val) : $val
			) ?: []
		);
	}

	/**
	 * Processes SQL values by parsing and then escaping them.
	 *
	 * Values are split into an array format and reduced before escaping.
	 *
	 * @param array $parameters List of values to process.
	 * @return array Escaped values.
	 */
	public function processValues(array $parameters): array
	{
		return $this->escapeValues(
			$this->splitToArray(
				$this->reduce(
					$this->parseValues($parameters),
					fn($carry, $item) => $carry.($this->isArray($item)
						? $this->toJson($item)
						: (string)$item
					).'|',
					''
				),
				1
			)
		);
	}

	// -------------------------------------------------------------------------
	// PROPERTY SETTER METHODS
	// -------------------------------------------------------------------------

	/**
	 * Sets the table name for the query.
	 *
	 * @param string $table The name of the database table.
	 * @return void
	 */
	public function setTable(string $table): void
	{
		$this->table = $table;
	}

	/**
	 * Sets the values for the query.
	 *
	 * @param array $values The values to be used in the query.
	 * @return void
	 */
	public function setValues(array $values): void
	{
		$this->values = $values;
	}

	/**
	 * Sets the columns for the query.
	 *
	 * @param array $columns The column names to be used in the query.
	 * @return void
	 */
	public function setColumns(array $columns): void
	{
		$this->columns = $columns;
	}

	// -------------------------------------------------------------------------
	// PROPERTY GETTER METHODS
	// -------------------------------------------------------------------------

	/**
	 * Retrieves the table name associated with the query.
	 *
	 * @return string The table name.
	 */
	public function getTable(): string
	{
		return $this->table;
	}

	/**
	 * Retrieves the values associated with the query.
	 *
	 * @return array The query values.
	 */
	public function getValues(): array
	{
		return $this->values;
	}

	/**
	 * Retrieves the column names associated with the query.
	 *
	 * @return array The column names.
	 */
	public function getColumns(): array
	{
		return $this->columns;
	}
}
