<?php

namespace App\Utilities\Traits\Query;

/**
 * DataQueryTrait
 *
 * This trait provides reusable methods for handling SQL data queries.
 * It is intended to be used within query builder classes to simplify
 * CRUD operations, filtering, sorting, pagination, and advanced SQL
 * query construction.
 */
trait DataQueryTrait
{

	// ─────────────────────────────────────────────────────────────
	// GENERAL CONDITIONS
	// ─────────────────────────────────────────────────────────────

	/**
	 * Adds a WHERE condition to the query.
	 *
	 * This method processes a condition where a specified column must satisfy
	 * a given comparison against a value.
	 *
	 * @param string $column The column name.
	 * @param string $operator The comparison operator (e.g., '=', '!=', '>', '<').
	 * @param mixed $value The value to compare against.
	 * @return self The updated query instance.
	 */
	public function where(string $column, string $operator, mixed $value): self {
		$this->processConditions([[$column, $operator, $value]]);
		return $this;
	}

	/**
	 * Adds a HAVING condition to the query.
	 *
	 * Similar to `where()`, but applies to aggregate function results.
	 *
	 * @param string $column The column name or aggregate function result.
	 * @param string $operator The comparison operator.
	 * @param mixed $value The value to compare against.
	 * @return self The updated query instance.
	 */
	public function having(string $column, string $operator, mixed $value): self {
		$this->processConditions([[$column, $operator, $value]]);
		return $this;
	}

	/**
	 * Adds an ON condition for a JOIN clause.
	 *
	 * Specifies a condition to match records between joined tables.
	 *
	 * @param string $column The column name.
	 * @param string $operator The comparison operator.
	 * @param mixed $value The value to compare against.
	 * @return self The updated query instance.
	 */
	public function on(string $column, string $operator, mixed $value): self {
		$this->processConditions([[$column, $operator, $value]]);
		return $this;
	}

	/**
	 * Adds a general filtering condition.
	 *
	 * Functions similarly to `where()`, but can be used for additional
	 * filtering in different contexts.
	 *
	 * @param string $column The column name.
	 * @param string $operator The comparison operator.
	 * @param mixed $value The value to compare against.
	 * @return self The updated query instance.
	 */
	public function filter(string $column, string $operator, mixed $value): self {
		$this->processConditions([[$column, $operator, $value]]);
		return $this;
	}

	/**
	 * Limits the number of rows to fetch in the result set.
	 *
	 * @param int $rowCount The number of rows to retrieve.
	 * @return self The updated query instance.
	 */
	public function fetch(int $rowCount): self {
		$this->processPagination($rowCount, 0);
		return $this;
	}

	/**
	 * Specifies columns to return in a query result.
	 *
	 * Used in databases that support the RETURNING clause for INSERT, UPDATE, and DELETE statements.
	 *
	 * @param array $columns The columns to return.
	 * @return self The updated query instance.
	 */
	public function returning(array $columns): self {
		$this->processColumns($columns);
		return $this;
	}

	/**
	 * Adds a WITH (Common Table Expression) clause to the query.
	 *
	 * Allows defining temporary named result sets within a query.
	 *
	 * @param string $queryAlias The alias for the subquery.
	 * @param callable $subquery A callable function that defines the subquery.
	 * @return self The updated query instance.
	 */
	public function with(string $queryAlias, callable $subquery): self {
		$this->processApplyClauses([[$queryAlias, $subquery]]);
		return $this;
	}

	// ─────────────────────────────────────────────────────────────
	// LOGICAL OPERATORS
	// ─────────────────────────────────────────────────────────────

	/**
	 * Applies a NOT logical operator to the given conditions.
	 *
	 * @param array $conditions The conditions to negate.
	 * @return self The updated query instance.
	 */
	public function not(array $conditions): self {
		$this->processConditions($conditions);
		return $this;
	}

	/**
	 * Applies an AND logical operator to the given conditions.
	 *
	 * @param array $conditions The conditions to combine with AND.
	 * @return self The updated query instance.
	 */
	public function and(array $conditions): self {
		$this->processConditions($conditions);
		return $this;
	}

	/**
	 * Applies an OR logical operator to the given conditions.
	 *
	 * @param array $conditions The conditions to combine with OR.
	 * @return self The updated query instance.
	 */
	public function or(array $conditions): self {
		$this->processConditions($conditions);
		return $this;
	}

	/**
	 * Applies an XOR logical operator to the given conditions.
	 *
	 * @param array $conditions The conditions to combine with XOR.
	 * @return self The updated query instance.
	 */
	public function xor(array $conditions): self {
		$this->processConditions($conditions);
		return $this;
	}

	/**
	 * Applies an AND NOT logical operator to the given conditions.
	 *
	 * @param array $conditions The conditions to combine with AND NOT.
	 * @return self The updated query instance.
	 */
	public function andNot(array $conditions): self {
		$this->processConditions($conditions);
		return $this;
	}

	/**
	 * Applies an OR NOT logical operator to the given conditions.
	 *
	 * @param array $conditions The conditions to combine with OR NOT.
	 * @return self The updated query instance.
	 */
	public function orNot(array $conditions): self {
		$this->processConditions($conditions);
		return $this;
	}

	/**
	 * Applies an ALL logical condition, ensuring all conditions must be met.
	 *
	 * @param array $conditions The conditions to combine with ALL.
	 * @return self The updated query instance.
	 */
	public function all(array $conditions): self {
		$this->processConditions($conditions);
		return $this;
	}

	/**
	 * Applies an ANY logical condition, ensuring at least one condition must be met.
	 *
	 * @param array $conditions The conditions to combine with ANY.
	 * @return self The updated query instance.
	 */
	public function any(array $conditions): self {
		$this->processConditions($conditions);
		return $this;
	}

	// ─────────────────────────────────────────────────────────────
	// COMPARISON OPERATORS
	// ─────────────────────────────────────────────────────────────

	/**
	 * Adds an equality condition to the query.
	 *
	 * @param string $column The column name.
	 * @param mixed $value The value to compare.
	 * @return self The updated query instance.
	 */
	public function equal(string $column, mixed $value): self {
		$this->processConditions([[$column, 'equal', $value]]);
		return $this;
	}

	/**
	 * Adds a NOT EQUAL condition to the query.
	 *
	 * @param string $column The column name.
	 * @param mixed $value The value to compare.
	 * @return self The updated query instance.
	 */
	public function notEqual(string $column, mixed $value): self {
		$this->processConditions([[$column, 'notEqual', $value]]);
		return $this;
	}

	/**
	 * Adds an alternative NOT EQUAL condition to the query.
	 *
	 * @param string $column The column name.
	 * @param mixed $value The value to compare.
	 * @return self The updated query instance.
	 */
	public function notEqualAlt(string $column, mixed $value): self {
		$this->processConditions([[$column, 'notEqualAlt', $value]]);
		return $this;
	}

	/**
	 * Adds a NULL-SAFE EQUAL condition to the query.
	 *
	 * Ensures that NULL values are compared safely.
	 *
	 * @param string $column The column name.
	 * @param mixed $value The value to compare.
	 * @return self The updated query instance.
	 */
	public function nullSafeEqual(string $column, mixed $value): self {
		$this->processConditions([[$column, 'nullSafeEqual', $value]]);
		return $this;
	}

	/**
	 * Adds a GREATER THAN condition to the query.
	 *
	 * @param string $column The column name.
	 * @param mixed $value The value to compare.
	 * @return self The updated query instance.
	 */
	public function greaterThan(string $column, mixed $value): self {
		$this->processConditions([[$column, 'greaterThan', $value]]);
		return $this;
	}

	/**
	 * Adds a GREATER THAN OR EQUAL TO condition to the query.
	 *
	 * @param string $column The column name.
	 * @param mixed $value The value to compare.
	 * @return self The updated query instance.
	 */
	public function greaterThanOrEqual(string $column, mixed $value): self {
		$this->processConditions([[$column, 'greaterThanOrEqual', $value]]);
		return $this;
	}

	/**
	 * Adds a LESS THAN condition to the query.
	 *
	 * @param string $column The column name.
	 * @param mixed $value The value to compare.
	 * @return self The updated query instance.
	 */
	public function lessThan(string $column, mixed $value): self {
		$this->processConditions([[$column, 'lessThan', $value]]);
		return $this;
	}

	/**
	 * Adds a LESS THAN OR EQUAL TO condition to the query.
	 *
	 * @param string $column The column name.
	 * @param mixed $value The value to compare.
	 * @return self The updated query instance.
	 */
	public function lessThanOrEqual(string $column, mixed $value): self {
		$this->processConditions([[$column, 'lessThanOrEqual', $value]]);
		return $this;
	}

	/**
	 * Adds an IS DISTINCT FROM condition to the query.
	 *
	 * Ensures that NULL values are handled explicitly.
	 *
	 * @param string $column The column name.
	 * @param mixed $value The value to compare.
	 * @return self The updated query instance.
	 */
	public function isDistinctFrom(string $column, mixed $value): self {
		$this->processConditions([[$column, 'isDistinctFrom', $value]]);
		return $this;
	}

	/**
	 * Adds a NOT DISTINCT FROM condition to the query.
	 *
	 * Ensures that NULL values are handled explicitly.
	 *
	 * @param string $column The column name.
	 * @param mixed $value The value to compare.
	 * @return self The updated query instance.
	 */
	public function notDistinctFrom(string $column, mixed $value): self {
		$this->processConditions([[$column, 'notDistinctFrom', $value]]);
		return $this;
	}

	// ─────────────────────────────────────────────────────────────
	// NULL CHECK CONDITIONS
	// ─────────────────────────────────────────────────────────────

	/**
	 * Adds an IS NULL condition to the query.
	 *
	 * @param string $column The column to check for NULL values.
	 * @return self The updated query instance.
	 */
	public function isNull(string $column): self {
		$this->processConditions([[$column, 'isNull']]);
		return $this;
	}

	/**
	 * Adds an IS NOT NULL condition to the query.
	 *
	 * @param string $column The column to check for non-NULL values.
	 * @return self The updated query instance.
	 */
	public function isNotNull(string $column): self {
		$this->processConditions([[$column, 'isNotNull']]);
		return $this;
	}

	// ─────────────────────────────────────────────────────────────
	// SET MEMBERSHIP CONDITIONS
	// ─────────────────────────────────────────────────────────────

	/**
	 * Adds an IN condition to the query.
	 *
	 * Checks if a column's value is within a given set of values.
	 *
	 * @param string $column The column name.
	 * @param array $values The array of values to compare against.
	 * @return self The updated query instance.
	 */
	public function in(string $column, array $values): self {
		$this->processConditions([[$column, 'in', $values]]);
		return $this;
	}

	/**
	 * Adds a NOT IN condition to the query.
	 *
	 * Checks if a column's value is NOT within a given set of values.
	 *
	 * @param string $column The column name.
	 * @param array $values The array of values to compare against.
	 * @return self The updated query instance.
	 */
	public function notIn(string $column, array $values): self {
		$this->processConditions([[$column, 'notIn', $values]]);
		return $this;
	}

	/**
	 * Adds an EXISTS condition to the query.
	 *
	 * Checks if a subquery returns any results.
	 *
	 * @param callable $subquery A function that generates the subquery.
	 * @return self The updated query instance.
	 */
	public function exists(callable $subquery): self {
		$this->processConditions([['exists', $subquery]]);
		return $this;
	}

	/**
	 * Adds a NOT EXISTS condition to the query.
	 *
	 * Checks if a subquery returns no results.
	 *
	 * @param callable $subquery A function that generates the subquery.
	 * @return self The updated query instance.
	 */
	public function notExists(callable $subquery): self {
		$this->processConditions([['notExists', $subquery]]);
		return $this;
	}

	/**
	 * Adds an EXCEPT set operation to the query.
	 *
	 * Returns the difference between two query result sets.
	 *
	 * @param callable $query A function that generates the second query.
	 * @return self The updated query instance.
	 */
	public function except(callable $query): self {
		$this->processSet(['except', $query]);
		return $this;
	}

	/**
	 * Adds an INTERSECT set operation to the query.
	 *
	 * Returns only the common records between two query result sets.
	 *
	 * @param callable $query A function that generates the second query.
	 * @return self The updated query instance.
	 */
	public function intersect(callable $query): self {
		$this->processSet(['intersect', $query]);
		return $this;
	}

	/**
	 * Adds a MINUS set operation to the query.
	 *
	 * Equivalent to EXCEPT but used in specific database engines.
	 *
	 * @param callable $query A function that generates the second query.
	 * @return self The updated query instance.
	 */
	public function minus(callable $query): self {
		$this->processSet(['minus', $query]);
		return $this;
	}

	/**
	 * Adds a UNION set operation to the query.
	 *
	 * Combines the results of two queries, removing duplicates.
	 *
	 * @param callable $query A function that generates the second query.
	 * @return self The updated query instance.
	 */
	public function union(callable $query): self {
		$this->processSet(['union', $query]);
		return $this;
	}

	/**
	 * Adds a UNION ALL set operation to the query.
	 *
	 * Combines the results of two queries, keeping duplicates.
	 *
	 * @param callable $query A function that generates the second query.
	 * @return self The updated query instance.
	 */
	public function unionAll(callable $query): self {
		$this->processSet(['unionAll', $query]);
		return $this;
	}

	// ─────────────────────────────────────────────────────────────
	// RANGE CONDITIONS
	// ─────────────────────────────────────────────────────────────

	/**
	 * Adds a BETWEEN condition to the query.
	 *
	 * Checks if a column's value falls within a specified range (inclusive).
	 *
	 * @param string $column The column name.
	 * @param mixed $start The start of the range.
	 * @param mixed $end The end of the range.
	 * @return self The updated query instance.
	 */
	public function between(string $column, mixed $start, mixed $end): self {
		$this->processConditions([[$column, 'between', $start, $end]]);
		return $this;
	}

	/**
	 * Adds a NOT BETWEEN condition to the query.
	 *
	 * Checks if a column's value falls outside a specified range.
	 *
	 * @param string $column The column name.
	 * @param mixed $start The start of the range.
	 * @param mixed $end The end of the range.
	 * @return self The updated query instance.
	 */
	public function notBetween(string $column, mixed $start, mixed $end): self {
		$this->processConditions([[$column, 'notBetween', $start, $end]]);
		return $this;
	}

	// ─────────────────────────────────────────────────────────────
	// PATTERN MATCHING CONDITIONS
	// ─────────────────────────────────────────────────────────────

	/**
	 * Adds a LIKE condition to the query.
	 *
	 * Checks if a column's value matches a given pattern.
	 *
	 * @param string $column The column name.
	 * @param string $pattern The pattern to match.
	 * @return self The updated query instance.
	 */
	public function like(string $column, string $pattern): self {
		$this->processConditions([[$column, 'like', $pattern]]);
		return $this;
	}

	/**
	 * Adds a NOT LIKE condition to the query.
	 *
	 * Checks if a column's value does NOT match a given pattern.
	 *
	 * @param string $column The column name.
	 * @param string $pattern The pattern to match.
	 * @return self The updated query instance.
	 */
	public function notLike(string $column, string $pattern): self {
		$this->processConditions([[$column, 'notLike', $pattern]]);
		return $this;
	}

	/**
	 * Adds an ILIKE condition to the query (case-insensitive LIKE).
	 *
	 * Typically used in PostgreSQL to perform case-insensitive pattern matching.
	 *
	 * @param string $column The column name.
	 * @param string $pattern The pattern to match.
	 * @return self The updated query instance.
	 */
	public function iLike(string $column, string $pattern): self {
		$this->processConditions([[$column, 'iLike', $pattern]]);
		return $this;
	}

	/**
	 * Adds a REGEXP condition to the query.
	 *
	 * Checks if a column's value matches a given regular expression pattern.
	 *
	 * @param string $column The column name.
	 * @param string $pattern The regex pattern to match.
	 * @return self The updated query instance.
	 */
	public function regexp(string $column, string $pattern): self {
		$this->processConditions([[$column, 'regexp', $pattern]]);
		return $this;
	}

	/**
	 * Adds a NOT REGEXP condition to the query.
	 *
	 * Checks if a column's value does NOT match a given regular expression pattern.
	 *
	 * @param string $column The column name.
	 * @param string $pattern The regex pattern to match.
	 * @return self The updated query instance.
	 */
	public function notRegexp(string $column, string $pattern): self {
		$this->processConditions([[$column, 'notRegexp', $pattern]]);
		return $this;
	}

	/**
	 * Adds a SOUNDS LIKE condition to the query.
	 *
	 * Compares phonetic similarity between column values and a given pattern.
	 * Typically supported in MySQL using the SOUNDEX function.
	 *
	 * @param string $column The column name.
	 * @param string $pattern The phonetic pattern to match.
	 * @return self The updated query instance.
	 */
	public function soundsLike(string $column, string $pattern): self {
		$this->processConditions([[$column, 'soundsLike', $pattern]]);
		return $this;
	}

	/**
	 * Adds a SIMILAR TO condition to the query.
	 *
	 * Similar to LIKE but supports more complex pattern matching (SQL standard).
	 *
	 * @param string $column The column name.
	 * @param string $pattern The pattern to match.
	 * @return self The updated query instance.
	 */
	public function similarTo(string $column, string $pattern): self {
		$this->processConditions([[$column, 'similarTo', $pattern]]);
		return $this;
	}

	/**
	 * Adds a NOT SIMILAR TO condition to the query.
	 *
	 * Checks if a column's value does NOT match a given pattern using SIMILAR TO.
	 *
	 * @param string $column The column name.
	 * @param string $pattern The pattern to match.
	 * @return self The updated query instance.
	 */
	public function notSimilarTo(string $column, string $pattern): self {
		$this->processConditions([[$column, 'notSimilarTo', $pattern]]);
		return $this;
	}

	// ─────────────────────────────────────────────────────────────
	// HIERARCHY & RECURSIVE QUERIES
	// ─────────────────────────────────────────────────────────────

	/**
	 * Adds a CONNECT BY condition for hierarchical queries.
	 *
	 * Used in databases that support hierarchical queries to establish parent-child relationships.
	 *
	 * @param string $column The column name.
	 * @param mixed $value The value to compare.
	 * @return self The updated query instance.
	 */
	public function connectBy(string $column, mixed $value): self {
		$this->processSpecialConditions([['connectBy', $column, $value]]);
		return $this;
	}

	/**
	 * Adds a START WITH condition for hierarchical queries.
	 *
	 * Specifies the starting point for a hierarchical query.
	 *
	 * @param string $column The column name.
	 * @param mixed $value The value to compare.
	 * @return self The updated query instance.
	 */
	public function startWith(string $column, mixed $value): self {
		$this->processSpecialConditions([['startWith', $column, $value]]);
		return $this;
	}

	/**
	 * Adds a CONNECT BY PRIOR condition for hierarchical queries.
	 *
	 * Used to traverse hierarchical data by linking parent-child relationships.
	 *
	 * @param string $column The column name.
	 * @param mixed $value The value to compare.
	 * @return self The updated query instance.
	 */
	public function connectByPrior(string $column, mixed $value): self {
		$this->processSpecialConditions([['connectByPrior', $column, $value]]);
		return $this;
	}

	/**
	 * Adds a PRIOR condition for hierarchical queries.
	 *
	 * Specifies a recursive relationship between parent and child rows.
	 *
	 * @param string $column The column name.
	 * @param mixed $value The value to compare.
	 * @return self The updated query instance.
	 */
	public function prior(string $column, mixed $value): self {
		$this->processSpecialConditions([['prior', $column, $value]]);
		return $this;
	}

	/**
	 * Adds a WITH RECURSIVE condition for recursive common table expressions (CTEs).
	 *
	 * Used in databases that support recursive queries to retrieve hierarchical data.
	 *
	 * @param string $column The column name.
	 * @param mixed $value The value to compare.
	 * @return self The updated query instance.
	 */
	public function withRecursive(string $column, mixed $value): self {
		$this->processSpecialConditions([['withRecursive', $column, $value]]);
		return $this;
	}

	/**
	 * Specifies DISTINCT ON columns in a query.
	 *
	 * Used to select distinct rows based on specific columns.
	 *
	 * @param array $columns The columns to apply DISTINCT ON.
	 * @return self The updated query instance.
	 */
	public function distinctOn(array $columns): self {
		$this->processSpecialConditions([['distinctOn', $columns]]);
		return $this;
	}

	// ─────────────────────────────────────────────────────────────
	// TIME-BASED CONDITIONS
	// ─────────────────────────────────────────────────────────────

	/**
	 * Adds an OVERLAPS condition for time-based comparisons.
	 *
	 * Used to check if two time periods overlap.
	 *
	 * @param string $column The column name.
	 * @param string $operator The comparison operator.
	 * @param mixed $value The value to compare.
	 * @return self The updated query instance.
	 */
	public function overlaps(string $column, string $operator, mixed $value): self {
		$this->processSpecialConditions([[$column, 'overlaps', $operator, $value]]);
		return $this;
	}

	// ─────────────────────────────────────────────────────────────
	// LOCKING & CONCURRENCY
	// ─────────────────────────────────────────────────────────────

	/**
	 * Adds a FOR UPDATE locking condition to the query.
	 *
	 * Prevents other transactions from modifying selected rows until the transaction is complete.
	 *
	 * @return self The updated query instance.
	 */
	public function forUpdate(): self {
		$this->processLocking(['FOR UPDATE']);
		return $this;
	}

	/**
	 * Adds a FOR SHARE locking condition to the query.
	 *
	 * Allows other transactions to read selected rows but prevents modifications.
	 *
	 * @return self The updated query instance.
	 */
	public function forShare(): self {
		$this->processLocking(['FOR SHARE']);
		return $this;
	}

	// ─────────────────────────────────────────────────────────────
	// JOIN OPERATIONS
	// ─────────────────────────────────────────────────────────────

	/**
	 * Adds a generic JOIN clause to the query.
	 *
	 * @param string $table The name of the table to join.
	 * @param array $onConditions The conditions for the JOIN clause.
	 * @param array $columns The columns to select from the joined table.
	 * @return self The updated query instance.
	 */
	public function join(string $table, array $onConditions, array $columns = []): self {
		$this->processJoins([['type' => 'join', 'table' => $table, 'on' => $onConditions, 'cols' => $columns]]);
		return $this;
	}

	/**
	 * Adds a FULL OUTER JOIN clause to the query.
	 *
	 * Returns all records when there is a match in either table.
	 *
	 * @param string $table The name of the table to join.
	 * @param array $onConditions The conditions for the JOIN clause.
	 * @param array $columns The columns to select from the joined table.
	 * @return self The updated query instance.
	 */
	public function fullOuterJoin(string $table, array $onConditions, array $columns = []): self {
		$this->processJoins([['type' => 'fullouterjoin', 'table' => $table, 'on' => $onConditions, 'cols' => $columns]]);
		return $this;
	}

	/**
	 * Adds a LEFT JOIN clause to the query.
	 *
	 * Returns all records from the left table and matching records from the right table.
	 *
	 * @param string $table The name of the table to join.
	 * @param array $onConditions The conditions for the JOIN clause.
	 * @param array $columns The columns to select from the joined table.
	 * @return self The updated query instance.
	 */
	public function leftJoin(string $table, array $onConditions, array $columns = []): self {
		$this->processJoins([['type' => 'leftjoin', 'table' => $table, 'on' => $onConditions, 'cols' => $columns]]);
		return $this;
	}

	/**
	 * Adds a RIGHT JOIN clause to the query.
	 *
	 * Returns all records from the right table and matching records from the left table.
	 *
	 * @param string $table The name of the table to join.
	 * @param array $onConditions The conditions for the JOIN clause.
	 * @param array $columns The columns to select from the joined table.
	 * @return self The updated query instance.
	 */
	public function rightJoin(string $table, array $onConditions, array $columns = []): self {
		$this->processJoins([['type' => 'rightjoin', 'table' => $table, 'on' => $onConditions, 'cols' => $columns]]);
		return $this;
	}

	/**
	 * Adds an INNER JOIN clause to the query.
	 *
	 * Returns only records that have matching values in both tables.
	 *
	 * @param string $table The name of the table to join.
	 * @param array $onConditions The conditions for the JOIN clause.
	 * @param array $columns The columns to select from the joined table.
	 * @return self The updated query instance.
	 */
	public function innerJoin(string $table, array $onConditions, array $columns = []): self {
		$this->processJoins([['type' => 'innerjoin', 'table' => $table, 'on' => $onConditions, 'cols' => $columns]]);
		return $this;
	}

	/**
	 * Adds a CROSS JOIN clause to the query.
	 *
	 * Returns the Cartesian product of both tables.
	 *
	 * @param string $table The name of the table to join.
	 * @param array $onConditions The conditions for the JOIN clause.
	 * @param array $columns The columns to select from the joined table.
	 * @return self The updated query instance.
	 */
	public function crossJoin(string $table, array $onConditions, array $columns = []): self {
		$this->processJoins([['type' => 'crossjoin', 'table' => $table, 'on' => $onConditions, 'cols' => $columns]]);
		return $this;
	}

	/**
	 * Adds a NATURAL JOIN clause to the query.
	 *
	 * Performs a join based on columns with the same name in both tables.
	 *
	 * @param string $table The name of the table to join.
	 * @param array $columns The columns to select from the joined table.
	 * @return self The updated query instance.
	 */
	public function naturalJoin(string $table, array $columns = []): self {
		$this->processJoins([['type' => 'naturaljoin', 'table' => $table, 'on' => [], 'cols' => $columns]]);
		return $this;
	}

	/**
	 * Adds a FULL JOIN clause to the query.
	 *
	 * Returns all records from both tables when there is a match.
	 *
	 * @param string $table The name of the table to join.
	 * @param array $onConditions The conditions for the JOIN clause.
	 * @param array $columns The columns to select from the joined table.
	 * @return self The updated query instance.
	 */
	public function fullJoin(string $table, array $onConditions, array $columns = []): self {
		$this->processJoins([['type' => 'fulljoin', 'table' => $table, 'on' => $onConditions, 'cols' => $columns]]);
		return $this;
	}

	// ─────────────────────────────────────────────────────────────
	// SORTING & GROUPING
	// ─────────────────────────────────────────────────────────────

	/**
	 * Adds an ORDER BY clause to the query.
	 *
	 * Specifies the column and direction for sorting the results.
	 *
	 * @param string $column The column to sort by.
	 * @param string $direction The sorting direction (ASC or DESC).
	 * @return self The updated query instance.
	 */
	public function orderBy(string $column, string $direction = 'ASC'): self {
		$this->processOrdering([$column.' '.$this->toUpper($direction)]);
		return $this;
	}

	/**
	 * Adds a GROUP BY clause to the query.
	 *
	 * Groups the results by the specified columns.
	 *
	 * @param array $columns The columns to group by.
	 * @return self The updated query instance.
	 */
	public function groupBy(array $columns): self {
		$this->processOrdering(['GROUP BY '.$this->join(',', $columns)]);
		return $this;
	}

	/**
	 * Adds a GROUPING SETS clause to the query.
	 *
	 * Allows defining multiple grouping sets within a GROUP BY clause.
	 *
	 * @param array $sets The grouping sets to apply.
	 * @return self The updated query instance.
	 */
	public function groupingSets(array $sets): self {
		$this->processOrdering(['GROUPING SETS('.$this->join(',', $sets).')']);
		return $this;
	}

	/**
	 * Adds a CUBE clause to the query.
	 *
	 * Used for multi-dimensional aggregation in GROUP BY queries.
	 *
	 * @param array $columns The columns to apply CUBE to.
	 * @return self The updated query instance.
	 */
	public function cube(array $columns): self {
		$this->processOrdering(['CUBE('.$this->join(',', $columns).')']);
		return $this;
	}

	/**
	 * Adds a ROLLUP clause to the query.
	 *
	 * Used for hierarchical aggregation in GROUP BY queries.
	 *
	 * @param array $columns The columns to apply ROLLUP to.
	 * @return self The updated query instance.
	 */
	public function rollup(array $columns): self {
		$this->processOrdering(['ROLLUP('.$this->join(',', $columns).')']);
		return $this;
	}

	// ─────────────────────────────────────────────────────────────
	// PAGINATION
	// ─────────────────────────────────────────────────────────────

	/**
	 * Adds a LIMIT clause to the query.
	 *
	 * Specifies the maximum number of rows to retrieve.
	 *
	 * @param int $limit The number of rows to limit the result to.
	 * @return self The updated query instance.
	 */
	public function limit(int $limit): self {
		$this->processPagination($limit, 0);
		return $this;
	}

	/**
	 * Adds an OFFSET clause to the query.
	 *
	 * Specifies the number of rows to skip before starting to return rows.
	 *
	 * @param int $offset The number of rows to skip.
	 * @return self The updated query instance.
	 */
	public function offset(int $offset): self {
		$this->processPagination(0, $offset);
		return $this;
	}

	// ─────────────────────────────────────────────────────────────
	// CRUD OPERATIONS
	// ─────────────────────────────────────────────────────────────

	/**
	 * Adds an INSERT operation to the query.
	 *
	 * Prepares an insert statement for the given table with the provided data.
	 *
	 * @param string $table The table name where data will be inserted.
	 * @param array $data The key-value pairs representing column names and values.
	 * @return self The updated query instance.
	 */
	public function insert(string $table, array $data): self {
		$this->processInsert($data);
		return $this;
	}

	/**
	 * Adds a SELECT operation to the query.
	 *
	 * Specifies the columns to retrieve in the result set.
	 *
	 * @param array $columns The columns to select (default is all columns `*`).
	 * @return self The updated query instance.
	 */
	public function select(array $columns = ['*']): self {
		$this->processSelect($columns);
		return $this;
	}

	/**
	 * Adds an UPDATE operation to the query.
	 *
	 * Prepares an update statement for the given table with the provided data.
	 *
	 * @param string $table The table name where data will be updated.
	 * @param array $data The key-value pairs representing column names and new values.
	 * @return self The updated query instance.
	 */
	public function update(string $table, array $data): self {
		$this->processUpdate($data);
		return $this;
	}

	/**
	 * Adds a DELETE operation to the query.
	 *
	 * Prepares a delete statement for the given table.
	 *
	 * @param string $table The table name where data will be deleted.
	 * @return self The updated query instance.
	 */
	public function delete(string $table): self {
		$this->processDelete();
		return $this;
	}
}
