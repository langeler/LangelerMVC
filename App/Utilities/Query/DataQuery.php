<?php

namespace App\Utilities\Query;

use App\Abstracts\Database\Query;
use App\Utilities\Traits\Query\DataQueryTrait;

/**
 * DataQuery Class
 *
 * Extends the base Query class and incorporates additional functionality
 * for handling data-related SQL queries.
 */
class DataQuery extends Query
{
	use DataQueryTrait;
	// ─────────────────────────────────────────────────────────────
	// PROCESSING & BUILDING CRUD OPERATIONS
	// ─────────────────────────────────────────────────────────────

	/**
	 * Processes data for an INSERT operation.
	 *
	 * Extracts columns and values from the provided data array and applies the necessary
	 * transformations to ensure they are properly formatted for SQL insertion.
	 *
	 * @param array $data The data to be inserted (associative array: column => value).
	 * @return array Processed columns and values ready for an INSERT query.
	 */
	public function processInsert(array $data): array {
		return $this->wrapInTry(fn() => [
			'columns' => $this->processColumns($this->keys($data)),
			'values'  => $this->processValues($this->values($data))
		], 'Failed to process insert data.');
	}

	/**
	 * Builds an SQL INSERT query string from the provided data.
	 *
	 * Constructs an INSERT INTO statement with dynamically processed column names
	 * and values.
	 *
	 * @param array $data The data to be inserted.
	 * @return string The constructed SQL INSERT query string.
	 */
	public function buildInsert(array $data): string {
		return $this->wrapInTry(fn() =>
			$this->sql->statement('insert').' '.
			$this->sql->statement('into').' '.
			$this->getTable().' ('.
			$this->join(', ', $this->processColumns($this->keys($data))).') '.
			$this->sql->clause('values').' ('.
			$this->join(', ', $this->processValues($this->values($data))).')'
		, 'Failed to build insert query.');
	}

	/**
	 * Processes columns for a SELECT query.
	 *
	 * Ensures that column names are properly formatted before being used in a SELECT statement.
	 *
	 * @param array $columns The columns to select.
	 * @return array Processed column names.
	 */
	public function processSelect(array $columns): array {
		return $this->wrapInTry(fn() => ['columns' => $this->processColumns($columns)], 'Failed to process select columns.');
	}

	/**
	 * Builds an SQL SELECT query string.
	 *
	 * Constructs a SELECT statement with the provided column names and table reference.
	 *
	 * @param array $columns The columns to select.
	 * @return string The constructed SQL SELECT query.
	 */
	public function buildSelect(array $columns): string {
		return $this->wrapInTry(fn() =>
			$this->sql->statement('select').' '.
			$this->join(', ', $this->processColumns($columns)).' '.
			$this->sql->clause('from').' '.$this->getTable()
		, 'Failed to build select query.');
	}

	/**
	 * Processes data for an UPDATE operation.
	 *
	 * Converts column-value pairs into SQL assignment expressions (`column = value`)
	 * ensuring safe escaping and formatting.
	 *
	 * @param array $data The data to be updated (associative array: column => value).
	 * @return array Processed update assignments.
	 */
	public function processUpdate(array $data): array {
		return $this->wrapInTry(fn() => [
			'assign' => $this->join(', ', $this->map(
				fn($col) => $this->processColumns([$col])[0].'='.
						   $this->processValues([$data[$col]])[0],
				$this->keys($data)
			))
		], 'Failed to process update data.');
	}

	/**
	 * Builds an SQL UPDATE query string.
	 *
	 * Constructs an UPDATE statement with dynamically processed column-value assignments.
	 *
	 * @param array $data The data to be updated.
	 * @return string The constructed SQL UPDATE query.
	 */
	public function buildUpdate(array $data): string {
		return $this->wrapInTry(fn() =>
			$this->sql->statement('update').' '.
			$this->getTable().' '.
			$this->sql->clause('set').' '.
			$this->join(', ', $this->map(
				fn($col) => $this->processColumns([$col])[0].'='.
						   $this->processValues([$data[$col]])[0],
				$this->keys($data)
			))
		, 'Failed to build update query.');
	}

	/**
	 * Processes data for a DELETE operation.
	 *
	 * Simply retrieves the table name since DELETE queries do not require additional parameters.
	 *
	 * @return array Processed delete information with the table reference.
	 */
	public function processDelete(): array {
		return $this->wrapInTry(fn() => ['tbl' => $this->getTable()], 'Failed to process delete data.');
	}

	/**
	 * Builds an SQL DELETE query string.
	 *
	 * Constructs a DELETE statement targeting the specified table.
	 *
	 * @return string The constructed SQL DELETE query.
	 */
	public function buildDelete(): string {
		return $this->wrapInTry(fn() =>
			$this->sql->statement('delete').' '.
			$this->sql->clause('from').' '.$this->getTable()
		, 'Failed to build delete query.');
	}

	// ─────────────────────────────────────────────────────────────
	// PROCESSING & BUILDING GENERAL CONDITIONS
	// ─────────────────────────────────────────────────────────────

	/**
	 * Processes general SQL conditions for WHERE clauses.
	 *
	 * This method iterates through the provided conditions, determining if they are
	 * associative arrays (nested conditions), arrays containing column/operator/value triplets,
	 * or simple string conditions. The conditions are formatted accordingly.
	 *
	 * @param array $conditions The conditions to process.
	 * @return array Processed conditions formatted for SQL queries.
	 */
	public function processConditions(array $conditions): array {
		return $this->wrapInTry(fn() =>
			$this->map(fn($c) =>
				($this->isArray($c) && $this->isAssoc($c))
					? $this->join(' '.$this->sql->operator(key($c)).' ', $this->processConditions(current($c)))
					: ($this->isArray($c) && $this->count($c) >= 3 && $this->isString($c[0])
						? [$this->processColumns([$c[0]])[0],
						   $this->processOperators([$c[1]])[0],
						   $this->processValues([$c[2]])[0]]
						: (string)$c
					)
			, $conditions)
		, 'Failed to process conditions.');
	}

	/**
	 * Builds an SQL WHERE clause from the given conditions.
	 *
	 * Uses `processConditions()` to transform input conditions and constructs a
	 * WHERE clause string using the `AND` operator by default.
	 *
	 * @param array $conditions The conditions for the WHERE clause.
	 * @return string The constructed SQL WHERE clause.
	 */
	public function buildConditions(array $conditions): string {
		return $this->wrapInTry(fn() =>
			$this->isEmpty($conditions)
				? ''
				: $this->sql->clause('where').' '.
				  $this->join(' '.$this->sql->operator('and').' ', $this->processConditions($conditions))
		, 'Failed to build conditions.');
	}

	// ─────────────────────────────────────────────────────────────
	// PROCESSING & BUILDING SPECIALIZED CONDITIONS (Hierarchy, Recursive, Time-based)
	// ─────────────────────────────────────────────────────────────

	/**
	 * Processes specialized conditions such as hierarchical, recursive, or time-based filters.
	 *
	 * Ensures that the provided conditions are properly formatted as an array.
	 *
	 * @param array $specialConditions The specialized conditions to process.
	 * @return array Processed specialized conditions.
	 */
	public function processSpecialConditions(array $specialConditions): array {
		return $this->wrapInTry(fn() => $this->isArray($specialConditions)
			? $specialConditions
			: [$specialConditions]
		, 'Failed to process special conditions.');
	}

	/**
	 * Builds an SQL query segment for specialized conditions.
	 *
	 * Processes special conditions and constructs a query segment prefixed with a comment.
	 * The `AND` operator is used for combining conditions.
	 *
	 * @param array $specialConditions The specialized conditions for the query.
	 * @return string The constructed SQL query segment.
	 */
	public function buildSpecialConditions(array $specialConditions): string {
		return $this->wrapInTry(fn() =>
			$this->isEmpty($specialConditions)
				? ''
				: ' /*special*/ '.$this->join(' '.$this->sql->operator('and').' ',
					  $this->processSpecialConditions($specialConditions))
		, 'Failed to build special conditions.');
	}

	// ─────────────────────────────────────────────────────────────
	// PROCESSING & BUILDING LOCKING AND CONCURRENCY
	// ─────────────────────────────────────────────────────────────

	/**
	 * Processes SQL locking options.
	 *
	 * This method ensures that the provided locking options are properly formatted.
	 *
	 * @param array $lockingOptions The locking options to process.
	 * @return array The processed locking options.
	 */
	public function processLocking(array $lockingOptions): array {
		return $this->wrapInTry(fn() => $lockingOptions, 'Failed to process locking options.');
	}

	/**
	 * Builds an SQL locking clause.
	 *
	 * Constructs a query segment based on the provided locking options.
	 *
	 * @param array $lockingOptions The locking options to apply.
	 * @return string The constructed locking clause.
	 */
	public function buildLocking(array $lockingOptions): string {
		return $this->wrapInTry(fn() =>
			$this->isEmpty($lockingOptions)
				? ''
				: $this->join(' ', $lockingOptions)
		, 'Failed to build locking options.');
	}

	// ─────────────────────────────────────────────────────────────
	// PROCESSING & BUILDING JOIN OPERATIONS
	// ─────────────────────────────────────────────────────────────

	/**
	 * Processes SQL JOIN operations.
	 *
	 * Formats JOIN clauses by processing table names, join conditions, and selected columns.
	 *
	 * @param array $joins The join definitions (associative array: type, table, on, cols).
	 * @return array Processed JOIN definitions.
	 */
	public function processJoins(array $joins): array {
		return $this->wrapInTry(fn() =>
			$this->map(fn($j) => [
				'type'  => $j['type'],
				'table' => $this->processIdentifiers([$j['table']])[0],
				'on'    => $j['on'] ?? [],
				'cols'  => $j['cols'] ?? []
			], $joins)
		, 'Failed to process joins.');
	}

	/**
	 * Builds SQL JOIN clauses.
	 *
	 * Constructs JOIN statements by processing table names, conditions, and columns.
	 *
	 * @param array $joins The join definitions to build into SQL clauses.
	 * @return string The constructed JOIN SQL clauses.
	 */
	public function buildJoins(array $joins): string {
		return $this->wrapInTry(fn() =>
			$this->join(' ', $this->map(fn($j) =>
				$this->sql->clause($j['type']).' '.$j['table'].
				(!$this->isEmpty($j['on'])
					? ' '.$this->buildConditions($j['on'])
					: ''
				).
				(!$this->isEmpty($j['cols'])
					? ' ('.$this->join(', ', $this->processColumns($j['cols'])).')'
					: ''
				)
			, $this->processJoins($joins)))
		, 'Failed to build joins.');
	}

	// ─────────────────────────────────────────────────────────────
	// PROCESSING & BUILDING ORDERING & GROUPING
	// ─────────────────────────────────────────────────────────────

	/**
	 * Processes ordering options for SQL queries.
	 *
	 * This method ensures that the ordering options are properly formatted.
	 *
	 * @param array $orderingOptions The ordering options to process.
	 * @return array The processed ordering options.
	 */
	public function processOrdering(array $orderingOptions): array {
		return $this->wrapInTry(fn() => $orderingOptions, 'Failed to process ordering options.');
	}

	/**
	 * Builds an SQL ORDER BY clause.
	 *
	 * Constructs an ORDER BY clause using the provided ordering options.
	 *
	 * @param array $orderingOptions The ordering options to apply.
	 * @return string The constructed ORDER BY clause.
	 */
	public function buildOrdering(array $orderingOptions): string {
		return $this->wrapInTry(fn() =>
			$this->isEmpty($orderingOptions)
				? ''
				: $this->sql->clause('orderby').' '.
				  $this->join(', ', $this->processOrdering($orderingOptions))
		, 'Failed to build ordering.');
	}

	// ─────────────────────────────────────────────────────────────
	// PROCESSING & BUILDING PAGINATION
	// ─────────────────────────────────────────────────────────────

	/**
	 * Processes pagination settings for SQL queries.
	 *
	 * Stores the provided limit and offset values in an array for later use.
	 *
	 * @param int $limit The maximum number of records to retrieve.
	 * @param int $offset The number of records to skip before retrieving results.
	 * @return array The processed pagination parameters.
	 */
	public function processPagination(int $limit, int $offset): array {
		return $this->wrapInTry(fn() => ['limit' => $limit, 'offset' => $offset], 'Failed to process pagination.');
	}

	/**
	 * Builds an SQL LIMIT and OFFSET clause for pagination.
	 *
	 * Constructs pagination clauses based on the given limit and offset values.
	 *
	 * @param int $limit The maximum number of records to retrieve.
	 * @param int $offset The number of records to skip.
	 * @return string The constructed SQL pagination clause.
	 */
	public function buildPagination(int $limit, int $offset): string {
		return $this->wrapInTry(fn() =>
			($limit > 0 ? ' '.$this->sql->clause('limit').' '.$limit : '')
			.($offset > 0 ? ' '.$this->sql->clause('offset').' '.$offset : '')
		, 'Failed to build pagination.');
	}

	// ─────────────────────────────────────────────────────────────
	// PROCESSING & BUILDING SET OPERATIONS
	// ─────────────────────────────────────────────────────────────

	/**
	 * Processes set operations such as UNION, INTERSECT, and EXCEPT.
	 *
	 * Ensures that the provided set operations are stored as an array.
	 *
	 * @param array $setOperations The set operations to process.
	 * @return array The processed set operations.
	 */
	public function processSet(array $setOperations): array {
		return $this->wrapInTry(fn() => $this->isArray($setOperations) ? $setOperations : [$setOperations], 'Failed to process set operations.');
	}

	/**
	 * Builds an SQL set operation clause.
	 *
	 * Constructs a query segment for set operations by processing each operation
	 * and ensuring valid SQL syntax.
	 *
	 * @param array $setOperations The set operations to build (e.g., UNION, INTERSECT).
	 * @return string The constructed set operation clause.
	 */
	public function buildSet(array $setOperations): string {
		return $this->wrapInTry(fn() =>
			$this->isEmpty($setOperations)
				? ''
				: $this->join(' ', $this->map(fn($op) =>
					$this->sql->clause($this->isString($op) ? $op : 'union'),
					$this->processSet($setOperations)
				))
		, 'Failed to build set operations.');
	}

	// ─────────────────────────────────────────────────────────────
	// PROCESSING & BUILDING APPLY CLAUSES
	// ─────────────────────────────────────────────────────────────

	/**
	 * Processes APPLY clauses used in SQL queries.
	 *
	 * Ensures that the provided APPLY clauses are properly formatted.
	 *
	 * @param array $apply The APPLY clauses to process.
	 * @return array The processed APPLY clauses.
	 */
	public function processApplyClauses(array $apply): array {
		return $this->wrapInTry(fn() => $apply, 'Failed to process apply clauses.');
	}

	/**
	 * Builds an SQL APPLY clause.
	 *
	 * Constructs an APPLY clause by processing and joining the provided clauses.
	 *
	 * @param array $applyClauses The APPLY clauses to build.
	 * @return string The constructed APPLY clause.
	 */
	public function buildApply(array $applyClauses): string {
		return $this->wrapInTry(fn() =>
			$this->isEmpty($applyClauses)
				? ''
				: $this->join(' ', $this->processApplyClauses($applyClauses))
		, 'Failed to build apply clauses.');
	}
}
