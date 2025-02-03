<?php

namespace App\Utilities\Handlers;

class SQLHandler
{

	public function __construct()

	{

	}

	/**
	 * Retrieves the SQL clause string based on the given type.
	 *
	 * This method supports all major SQL clauses, sorted alphabetically by key name.
	 * Each clause is documented to briefly explain its purpose.
	 *
	 * @param string|null $type The type of SQL clause (case-insensitive).
	 * @return string The corresponding SQL clause string.
	 *
	 * @throws \InvalidArgumentException If the provided clause type is invalid.
	 */
	protected function clause(?string $type = null): string
	{
		return match (strtolower($type ?? '')) {
			// General Clauses
			'fetch' => 'FETCH FIRST',            // Limits the number of rows to fetch (SQL standard).
			'to' => 'TO',                   // Specifies the
			'from' => 'FROM',                   // Specifies the source table(s) for the query.
			'groupby' => 'GROUP BY',            // Groups rows sharing a property for aggregation.
			'having' => 'HAVING',               // Filters groups based on aggregate conditions.
			'limit' => 'LIMIT',                 // Limits the number of rows returned (non-SQL standard).
			'offset' => 'OFFSET',               // Skips a number of rows before returning the results.
			'orderby' => 'ORDER BY',            // Specifies sorting order for the results.
			'returning' => 'RETURNING',         // Returns rows affected by DML operations (PostgreSQL).
			'where' => 'WHERE',                 // Filters rows based on a condition.
			'with' => 'WITH',                   // Defines Common Table Expressions (CTEs).

			// JOIN Clauses
			'crossjoin' => 'CROSS JOIN',        // Produces a Cartesian product of the joined tables.
			'fullouterjoin' => 'FULL OUTER JOIN', // Returns rows matching on both tables or unmatched rows from either.
			'innerjoin' => 'INNER JOIN',        // Returns rows with matching values in both tables.
			'join' => 'JOIN',                   // Generic JOIN clause (defaults to INNER JOIN in many systems).
			'leftjoin' => 'LEFT JOIN',          // Returns all rows from the left table and matching rows from the right table.
			'naturaljoin' => 'NATURAL JOIN',    // Performs an automatic join based on columns with the same name.
			'rightjoin' => 'RIGHT JOIN',        // Returns all rows from the right table and matching rows from the left table.

			// Set Operation Clauses
			'except' => 'EXCEPT',               // Returns rows from the first query not in the second query (PostgreSQL).
			'intersect' => 'INTERSECT',         // Returns rows common to both queries.
			'minus' => 'MINUS',                 // Oracle-specific alternative to EXCEPT.
			'union' => 'UNION',                 // Combines the results of two queries, removing duplicates.
			'unionall' => 'UNION ALL',          // Combines the results of two queries, including duplicates.

			// Miscellaneous Clauses
			'connectby' => 'CONNECT BY',        // Used for hierarchical queries (Oracle-specific).
			'filter' => 'FILTER',               // Filters aggregate functions (SQL standard).
			'on' => 'ON',                       // Specifies join conditions.
			'overlaps' => 'OVERLAPS',           // Tests if two time periods overlap.
			'startwith' => 'START WITH',        // Specifies the root row for hierarchical queries (Oracle-specific).
			'tablesample' => 'TABLESAMPLE',     // Samples rows from a table (PostgreSQL, SQL Server).
			'using' => 'USING',                 // Specifies columns for joins or deletes.
			'window' => 'WINDOW',               // Defines named windows for window functions.

			// General Clauses
			'into' => 'INTO',                   // Specifies the target table for INSERT operations.
			'values' => 'VALUES',               // Defines values for INSERT operations.
			'grouping sets' => 'GROUPING SETS', // Allows grouping rows based on multiple grouping sets (advanced SQL feature).
			'cube' => 'CUBE',                   // Groups data hierarchically (for OLAP operations).
			'rollup' => 'ROLLUP',               // Groups data cumulatively (for OLAP operations).
			'distinctOn' => 'DISTINCT ON',     // Returns distinct rows based on the first column(s) (PostgreSQL-specific).
			'crossApply' => 'CROSS APPLY',     // Applies a table-valued function to each row (SQL Server, PostgreSQL).
			'outerApply' => 'OUTER APPLY',     // Similar to CROSS APPLY, but includes NULL results (SQL Server).
			'forUpdate' => 'FOR UPDATE',       // Locks selected rows for update (common in transactions).
			'forShare' => 'FOR SHARE',         // Locks selected rows for share mode (PostgreSQL-spes

	/**
	 * Retrieves the SQL expression string based on the given type.
	 *
	 * This method supports all major SQL expressions, sorted alphabetically by key name.
	 * Each expression is documented to explain its purpose.
	 *
	 * @param string|null $type The type of SQL expression (case-insensitive).
	 * @return string The corresponding SQL expression string.
	 *
	 * @throws \InvalidArgumentException If the provided expression type is invalid.
	 */
	protected function expression(?string $type = null): string
	{
		return match (strtolower($type ?? '')) {
			// Aggregate Functions
			'arrayagg' => 'ARRAY_AGG()',           // Aggregates elements into an array (PostgreSQL).
			'avg' => 'AVG()',                      // Calculates the average of a numeric column.
			'count' => 'COUNT()',                  // Counts rows or non-NULL values.
			'max' => 'MAX()',                      // Finds the maximum value.
			'median' => 'MEDIAN()',                // Calculates the median value (PostgreSQL, Oracle).
			'min' => 'MIN()',                      // Finds the minimum value.
			'mode' => 'MODE()',                    // Finds the most frequent value (PostgreSQL).
			'sum' => 'SUM()',                      // Calculates the sum of a numeric column.

			// Date/Time Functions
			'age' => 'AGE()',                      // Calculates the age between two dates (PostgreSQL).
			'curdate' => 'CURDATE()',              // Returns the current date (MySQL).
			'currentdate' => 'CURRENT_DATE',       // Returns the current date (SQL standard).
			'currenttime' => 'CURRENT_TIME',       // Returns the current time (SQL standard).
			'currenttimestamp' => 'CURRENT_TIMESTAMP', // Returns the current date and time (SQL standard).
			'dateadd' => 'DATEADD()',              // Adds an interval to a date (SQL Server, MySQL).
			'datediff' => 'DATEDIFF()',            // Calculates the difference between two dates.
			'datetrunc' => 'DATE_TRUNC()',         // Truncates a date to a specific granularity (PostgreSQL).
			'extract' => 'EXTRACT()',              // Extracts a part of a date (e.g., year, month, day).
			'interval' => 'INTERVAL',              // Represents a time interval (PostgreSQL-specific).
			'now' => 'NOW()',                      // Returns the current date and time.

			// JSON Functions
			'jsonarrayagg' => 'JSON_ARRAYAGG()',   // Aggregates values into a JSON array.
			'jsonbpretty' => 'JSONB_PRETTY()',     // Pretty-prints a JSONB value (PostgreSQL-specific).
			'jsonbset' => 'JSONB_SET()',           // Updates a JSONB value (PostgreSQL-specific).
			'jsonextract' => 'JSON_EXTRACT()',     // Extracts data from a JSON document.
			'jsonobjectagg' => 'JSON_OBJECTAGG()', // Aggregates key-value pairs into a JSON object.

			// Logical Expressions
			'case' => 'CASE',                      // Defines a conditional expression.
			'coalesce' => 'COALESCE()',            // Returns the first non-NULL value.
			'distinct' => 'DISTINCT',              // Ensures unique values in a column or query.
			'else' => 'ELSE',                      // Specifies the result if no CASE condition is met.
			'end' => 'END',                        // Ends a CASE expression.
			'nullif' => 'NULLIF()',                // Returns NULL if two values are equal.
			'then' => 'THEN',                      // Specifies the result of a CASE condition.
			'when' => 'WHEN',                      // Specifies a condition for a CASE expression.

			// String Functions
			'concat' => 'CONCAT()',                // Concatenates strings together.
			'length' => 'LENGTH()',                // Returns the length of a string.
			'lower' => 'LOWER()',                  // Converts a string to lowercase.
			'position' => 'POSITION()',            // Finds the position of a substring within a string.
			'replace' => 'REPLACE()',              // Replaces a substring with another string.
			'reverse' => 'REVERSE()',              // Reverses a string (SQL Server, PostgreSQL).
			'substring' => 'SUBSTRING()',          // Extracts a portion of a string.
			'trim' => 'TRIM()',                    // Removes leading and trailing whitespace.
			'upper' => 'UPPER()',                  // Converts a string to uppercase.

			// Type Conversion Functions
			'cast' => 'CAST()',                    // Converts a value to a specified type.
			'convert' => 'CONVERT()',              // Converts a value to a specified type (dialect-specific).

			// Window/Analytical Functions
			'denserank' => 'DENSE_RANK()',         // Assigns a rank to rows, with no gaps.
			'firstvalue' => 'FIRST_VALUE()',       // Returns the first value in a window.
			'lag' => 'LAG()',                      // Returns the value of a column from a previous row.
			'lastvalue' => 'LAST_VALUE()',         // Returns the last value in a window.
			'lead' => 'LEAD()',                    // Returns the value of a column from a following row.
			'ntile' => 'NTILE()',                  // Distributes rows into a specified number of groups.
			'over' => 'OVER',                      // Specifies a window for window functions.
			'partitionby' => 'PARTITION BY',       // Divides a result set into partitions.
			'rank' => 'RANK()',                    // Assigns a rank to rows, with gaps.
			'rownumber' => 'ROW_NUMBER()',         // Assigns a unique number to each row within a partition.

			// Aggregate Functions
			'bitAnd' => 'BIT_AND()',           // Computes bitwise AND across rows (PostgreSQL).
			'bitOr' => 'BIT_OR()',             // Computes bitwise OR across rows (PostgreSQL).
			'stringAgg' => 'STRING_AGG()',     // Concatenates strings with a delimiter (PostgreSQL).
			'listAgg' => 'LISTAGG()',           // Concatenates strings in SQL standard (Oracle).
			'corr' => 'CORR()',                 // Calculates the correlation coefficient between two columns.
			'covarPop' => 'COVAR_POP()',       // Calculates population covariance between two columns.
			'covarSamp' => 'COVAR_SAMP()',     // Calculates sample covariance between two columns.
			'percentileCont' => 'PERCENTILE_CONT()', // Continuous percentile function (SQL standard).
			'percentileDisc' => 'PERCENTILE_DISC()', // Discrete percentile function (SQL standard).

			// JSON/Array Functions
			'jsonbStripNulls' => 'JSONB_STRIP_NULLS()', // Removes null keys from a JSONB object (PostgreSQL).
			'arrayLength' => 'ARRAY_LENGTH()',           // Returns the length of an array (PostgreSQL).
			'unnest' => 'UNNEST()',                       // Expands an array into a set of rows (PostgreSQL).
			'arrayAppend' => 'ARRAY_APPEND()',           // Appends an element to an array (PostgreSQL).
			'arrayPrepend' => 'ARRAY_PREPEND()',         // Prepends an element to an array (PostgreSQL).

			// Logical Expressions
			'greatest' => 'GREATEST()',                   // Returns the largest value in a list.
			'least' => 'LEAST()',                         // Returns the smallest value in a list.

			// Type Conversion Functions
			'collate' => 'COLLATE',             // Specifies the collation for a string or column.

			// Additional Keywords for `expression`
			'rownumber' => 'ROW_NUMBER()',             // Assigns a sequential integer to rows in a result set.
			'partitionby' => 'PARTITION BY',           // Defines how rows are divided in analytical functions.
			'collate' => 'COLLATE',                    // Specifies collation for sorting data.

			'stringSplit' => 'STRING_SPLIT()',         // Splits a string into rows based on a delimiter (SQL Server-specific).
			'concatWithSeparator' => 'CONCAT_WS()',    // Concatenates strings with a specified separator (MySQL-specific).
			'jsonMergePatch' => 'JSON_MERGE_PATCH()',  // Merges JSON documents (MySQL-specific).
			'jsonInsert' => 'JSON_INSERT()',           // Inserts data into a JSON document (MySQL-specific).
			'jsonRemove' => 'JSON_REMOVE()',           // Removes data from a JSON document (MySQL-specific).
			'jsonReplace' => 'JSON_REPLACE()',         // Replaces data in a JSON document (MySQL-specific).
			'jsonSet' => 'JSON_SET()',                 // Updates data in a JSON document (MySQL-specific).
			'md5Hash' => 'MD5()',                      // Returns the MD5 hash of a value (MySQL, PostgreSQL).
			'sha1Hash' => 'SHA1()',                    // Returns the SHA-1 hash of a value (MySQL).
			'bitLength' => 'BIT_LENGTH()',             // Returns the length of a binary string (PostgreSQL).
			'charLength' => 'CHAR_LENGTH()',           // Returns the length of a string in characters (SQL standard).
			'currentRole' => 'CURRENT_ROLE',           // Returns the current role (SQL standard).
			'currentUser' => 'CURRENT_USER',           // Returns the current user (SQL standard).
			'sessionUser' => 'SESSION_USER',           // Returns the session user (SQL standard).
			'systemUser' => 'SYSTEM_USER',             // Returns the system user (SQL Server-specific).

			// Aggregate Functions
			'groupconcat' => 'GROUP_CONCAT()',    // Concatenates values into a string (MySQL-specific).
			'stddev' => 'STDDEV()',               // Calculates standard deviation (SQL standard).
			'variance' => 'VARIANCE()',           // Calculates variance (SQL standard).

			// Window Functions
			'cume_dist' => 'CUME_DIST()',         // Calculates cumulative distribution (SQL standard).
			'percent_rank' => 'PERCENT_RANK()',   // Calculates percentile rank (SQL standard).

			default => throw new \InvalidArgumentException("Invalid SQL expression type: {$type}")
		};
	}

	/**
	 * Retrieves the SQL operator string based on the given type.
	 *
	 * This method supports all major SQL operators, sorted alphabetically by key name.
	 * Each operator is documented to briefly explain its purpose or usage.
	 *
	 * @param string|null $type The type of SQL operator (case-insensitive).
	 * @return string The corresponding SQL operator string.
	 *
	 * @throws \InvalidArgumentException If the provided operator type is invalid.
	 */
	protected function operator(?string $type = null): string
	{
		return match (strtolower($type ?? '')) {
			// Arithmetic Operators
			'divide' => '/',                    // Divides one value by another.
			'minus' => '-',                     // Subtracts one value from another.
			'modulus' => '%',                   // Returns the remainder of a division.
			'multiply' => '*',                  // Multiplies two values.
			'plus' => '+',                      // Adds two values.

			// Bitwise Operators
			'bitand' => '&',                    // Performs a bitwise AND operation.
			'bitnot' => '~',                    // Performs a bitwise NOT operation.
			'bitor' => '|',                     // Performs a bitwise OR operation.
			'bitxor' => '^',                    // Performs a bitwise XOR operation.
			'leftshift' => '<<',                // Performs a bitwise left shift.
			'rightshift' => '>>',               // Performs a bitwise right shift.

			// Comparison Operators
			'equal' => '=',                     // Compares two values for equality.
			'greaterthan' => '>',               // Checks if a value is greater than another.
			'greaterthanorequal' => '>=',       // Checks if a value is greater than or equal to another.
			'lessthan' => '<',                  // Checks if a value is less than another.
			'lessthanorequal' => '<=',          // Checks if a value is less than or equal to another.
			'notequal' => '<>',                 // Checks if two values are not equal.
			'notequalalt' => '!=',              // Alternate syntax for not equal.
			'nullsafeequal' => '<=>',           // Compares two values, treating NULL as a value (MySQL-specific).

			// Logical Operators
			'all' => 'ALL',                     // Compares a value with all values in a list.
			'and' => 'AND',                     // Performs a logical AND operation.
			'any' => 'ANY',                     // Compares a value with any value in a list.
			'not' => 'NOT',                     // Negates a condition.
			'or' => 'OR',                       // Performs a logical OR operation.
			'xor' => 'XOR',                     // Performs an exclusive OR operation.

			// NULL Handling Operators
			'isnotnull' => 'IS NOT NULL',       // Checks if a value is not NULL.
			'isnull' => 'IS NULL',              // Checks if a value is NULL.

			// Pattern Matching Operators
			'ilike' => 'ILIKE',                 // Performs a case-insensitive LIKE match (PostgreSQL-specific).
			'like' => 'LIKE',                   // Matches a pattern in a string.
			'notlike' => 'NOT LIKE',            // Negated version of LIKE.
			'notregexp' => 'NOT REGEXP',        // Negated regular expression match (MySQL-specific).
			'regexp' => 'REGEXP',               // Regular expression match (MySQL/PostgreSQL).
			'soundslike' => 'SOUNDS LIKE',      // Matches strings phonetically (MySQL-specific).
			'similarto' => 'SIMILAR TO',        // Matches patterns using SQL-standard syntax (PostgreSQL-specific).
			'notsimilarto' => 'NOT SIMILAR TO', // Negated version of SIMILAR TO.

			// Set Operators
			'between' => 'BETWEEN',             // Checks if a value is within a range.
			'exists' => 'EXISTS',               // Checks if a subquery returns any rows.
			'as' => 'AS',                       //
			'in' => 'IN',                       // Checks if a value is in a list of values.
			'notbetween' => 'NOT BETWEEN',      // Negated version of BETWEEN.
			'notexists' => 'NOT EXISTS',        // Negated version of EXISTS.
			'notin' => 'NOT IN',                // Negated version of IN.

			// Assignment Operators (Procedural SQL)
			'assign' => '=',                    // General assignment operator.
			'assignadd' => '+=',                // Adds a value and assigns the result (SQL Server-specific).
			'assigndivide' => '/=',             // Divides a value and assigns the result (SQL Server-specific).
			'assignmultiply' => '*=',           // Multiplies a value and assigns the result (SQL Server-specific).
			'assignsubtract' => '-=',           // Subtracts a value and assigns the result (SQL Server-specific).

			// Miscellaneous Operators
			'concatoperator' => '||',           // Concatenates strings (SQL standard).
			'cuberoot' => '||/',                // Returns the cube root of a number (PostgreSQL-specific).
			'nullsafe' => '??',                 // Null-safe equality comparison (SQL standard).
			'sqrt' => '|/',                     // Returns the square root of a number (PostgreSQL-specific).

			// Miscellaneous Operators
			'overlap' => '&&',                  // Checks if two ranges overlap (PostgreSQL-specific).
			'contains' => '@>',                 // Checks if one range contains another (PostgreSQL-specific).
			'isContainedBy' => '<@',            // Checks if a range is contained by another (PostgreSQL-specific).
			'concatenation' => '||',            // Concatenates two strings (SQL standard).
			'power' => '^',                     // Exponentiation operator (PostgreSQL-specific).

			// JSON Operators
			'jsonContains' => 'JSON_CONTAINS()', // Checks if a JSON document contains a specified value.
			'jsonLength' => 'JSON_LENGTH()',     // Returns the number of elements in a JSON array or object.

			// Additional Keywords for `operator`
			'isdistinctfrom' => 'IS DISTINCT FROM',    // Compares two values for distinctness (PostgreSQL).
			'notdistinctfrom' => 'IS NOT DISTINCT FROM', // Negated version of `IS DISTINCT FROM`.
			'overlaps' => '&&',                        // Checks if two ranges overlap (PostgreSQL-specific).
			'power' => '^',                            // Exponentiation operator (PostgreSQL-specific).

			'globalVariable' => '@@',                  // Returns a global variable (SQL Server-specific).
			'jsonObjectAccess' => '->',               // Accesses JSON keys as objects (PostgreSQL, MySQL).
			'jsonTextAccess' => '->>',                // Accesses JSON keys as text (PostgreSQL, MySQL).
			'rangeIntersection' => '##',              // Returns the intersection of two ranges (PostgreSQL-specific).
			'negatedValue' => '!!',                   // Returns a negated value (PostgreSQL-specific).
			'cubeRoot' => '||/',                      // Cube root operator (PostgreSQL-specific).
			'distanceBetweenPoints' => '<->',         // Calculates the distance between points (PostGIS-specific).
			'jsonPathExists' => '@?',                 // Checks JSON path existence (PostgreSQL-specific).
			'jsonPathMatch' => '@@?',                 // Checks JSON path match (PostgreSQL-specific).
			'jsonKeyExists' => '?',                   // Checks if a key exists in a JSON object (PostgreSQL-specific).
			'jsonAnyKeyExists' => '?|',               // Checks if any key exists in a JSON object (PostgreSQL-specific).
			'jsonAllKeysExist' => '?&',               // Checks if all keys exist in a JSON object (PostgreSQL-specific).
			// Bitwise Operators (Extended)
			'bitandnot' => '&~',                // Performs a bitwise AND NOT operation (PostgreSQL-specific).

			// Logical Operators (General)
			'andnot' => 'AND NOT',              // Combines AND with NOT.
			'ornot' => 'OR NOT',                // Combines OR with NOT.

			default => throw new \InvalidArgumentException("Invalid SQL operator type: {$type}")
		};
	}

	/**
	 * Retrieves the SQL statement string based on the given type.
	 *
	 * This method supports all major SQL statements, sorted alphabetically by key name.
	 * Each statement is documented to explain its purpose or usage.
	 *
	 * @param string|null $type The type of SQL statement (case-insensitive).
	 * @return string The corresponding SQL statement string.
	 *
	 * @throws \InvalidArgumentException If the provided statement type is invalid.
	 */
	protected function statement(?string $type = null): string
	{
		return match (strtolower($type ?? '')) {
			// DDL (Data Definition Language)
			'table' => 'TABLE',
			'column' => 'COLUMN',
			'alter' => 'ALTER',                  // Modifies the structure of a database object.
			'alterindex' => 'ALTER INDEX',       // Alters an index.
			'create' => 'CREATE',                // Creates a new database object.
			'createindex' => 'CREATE INDEX',     // Creates a new index.
			'drop' => 'DROP',                    // Removes a database object.
			'dropindex' => 'DROP INDEX',         // Removes an index.
			'rename' => 'RENAME',                // Renames a database object.
			'truncate' => 'TRUNCATE',            // Removes all rows from a table.

			// DML (Data Manipulation Language)
			'delete' => 'DELETE',                // Deletes rows from a table.
			'insert' => 'INSERT',                // Inserts new rows into a table.
			'merge' => 'MERGE',                  // Merges data into a table (UPSERT behavior).
			'update' => 'UPDATE',                // Updates existing rows in a table.

			// DQL (Data Query Language)
			'select' => 'SELECT',                // Retrieves rows from a table or view.
			'with' => 'WITH',                    // Defines Common Table Expressions (CTEs).

			// DCL (Data Control Language)
			'grant' => 'GRANT',                  // Grants permissions on database objects.
			'revoke' => 'REVOKE',                // Revokes permissions on database objects.
			'deny' => 'DENY',                    // Denies permissions (SQL Server-specific).

			// TCL (Transaction Control Language)
			'begin' => 'BEGIN',                  // Starts a new transaction.
			'checkpoint' => 'CHECKPOINT',        // Creates a database checkpoint (PostgreSQL).
			'commit' => 'COMMIT',                // Commits the current transaction.
			'end' => 'END',                      // Ends a transaction (equivalent to COMMIT).
			'rollback' => 'ROLLBACK',            // Rolls back the current transaction.
			'savepoint' => 'SAVEPOINT',          // Sets a savepoint within the transaction.
			'set transaction' => 'SET TRANSACTION', // Sets the properties of a transaction.

			// Miscellaneous Statements
			'analyze' => 'ANALYZE',              // Collects statistics about tables for query planning.
			'comment' => 'COMMENT',              // Adds or modifies comments on database objects.
			'copy' => 'COPY',                    // Copies data between a file and a table (PostgreSQL).
			'describe' => 'DESCRIBE',            // Describes the structure of a table (MySQL-specific).
			'explain' => 'EXPLAIN',              // Displays the execution plan of a query.
			'locktable' => 'LOCK TABLE',         // Locks a table for exclusive access (PostgreSQL, Oracle).
			'load data' => 'LOAD DATA',          // Loads data into a table (MySQL-specific).

			// DDL (Data Definition Language)
			'createSchema' => 'CREATE SCHEMA', // Creates a new schema in the database.
			'dropSchema' => 'DROP SCHEMA',     // Drops an existing schema.

			// DML (Data Manipulation Language)
			'insertInto' => 'INSERT INTO',     // Full syntax for inserting rows into a table.
			'replace' => 'REPLACE',             // Inserts or updates rows (MySQL-specific).
			'upsert' => 'UPSERT',               // Inserts or updates rows (PostgreSQL with ON CONFLICT).

			// TCL (Transaction Control Language)
			'beginTransaction' => 'BEGIN TRANSACTION', // Starts a named transaction.
			'endTransaction' => 'END TRANSACTION',     // Ends a named transaction (equivalent to COMMIT).

			// Miscellaneous Statements
			'vacuum' => 'VACUUM',               // Reclaims storage occupied by dead tuples (PostgreSQL).
			'reIndex' => 'REINDEX',             // Rebuilds indexes (PostgreSQL).
			'analyzeTable' => 'ANALYZE TABLE', // Collects statistics for query planning (MySQL).
			'flush' => 'FLUSH',                 // Clears internal caches (MySQL-specific).

			'alterTable' => 'ALTER TABLE',               // Modifies the structure of an existing table.
			'createTempTable' => 'CREATE TEMP TABLE',    // Creates a temporary table (PostgreSQL, MySQL).
			'createDatabase' => 'CREATE DATABASE',       // Creates a new database.
			'dropDatabase' => 'DROP DATABASE',           // Deletes an existing database.
			'alterDatabase' => 'ALTER DATABASE',         // Modifies the properties of an existing database.

			'insertIgnore' => 'INSERT IGNORE',           // Ignores errors during insert (MySQL-specific).
			'updateReturning' => 'UPDATE RETURNING',     // Returns updated rows (PostgreSQL-specific).
			'deleteReturning' => 'DELETE RETURNING',     // Returns deleted rows (PostgreSQL-specific).

			'deallocate' => 'DEALLOCATE',                // Frees prepared statements or resources (PostgreSQL-specific).
			'setSavepoint' => 'SET SAVEPOINT',           // Sets a savepoint in the transaction (alternate syntax).

			'alterUser' => 'ALTER USER',                 // Alters properties of a database user (PostgreSQL-specific).
			'createUser' => 'CREATE USER',               // Creates a new database user.
			'dropUser' => 'DROP USER',                   // Removes a database user.
			'setRole' => 'SET ROLE',                     // Sets the current user role for the session.
			'resetRole' => 'RESET ROLE',                 // Resets the session user role to default.
			'execute' => 'EXECUTE',                      // Executes a prepared statement.
			'prepare' => 'PREPARE',                      // Prepares an SQL statement for execution.
			'discardAll' => 'DISCARD ALL',               // Resets all session parameters (PostgreSQL-specific).

			// DDL (Data Definition Language)
			'addPrimaryKey' => 'ADD PRIMARY KEY',
			'dropPrimaryKey' => 'DROP PRIMARY KEY',
			'addForeignKey' => 'ADD FOREIGN KEY',
			'dropForeignKey' => 'DROP FOREIGN KEY',
			'createindex' => 'CREATE INDEX',    // Creates an index on a table.
			'addIndex' => 'ADD INDEX',
			'renameIndex' => 'RENAME INDEX',
			'dropindex' => 'DROP INDEX',        // Drops an index from a table.
			'createuniqueindex' => 'CREATE UNIQUE INDEX', // Creates a unique index on a table.
			'addConstraint' => 'ADD CONSTRAINT', // Adds a constraint to an existing table.
			'addConstraint' => 'DROP CONSTRAINT', // Drops a constraint to an existing table.
			'renameConstraint' => 'DROP CONSTRAINT', // Drops a constraint to an existing table.

			// DML (Data Manipulation Language)
			'insertonduplicatekeyupdate' => 'INSERT ON DUPLICATE KEY UPDATE', // Inserts a row or updates it if the key exists (MySQL-specific).

			// Miscellaneous
			'grantoption' => 'GRANT OPTION',    // Grants permission with an option to delegate permissions.

			// Additional Keywords for `statement`
			'altercolumn' => 'ALTER COLUMN',          // Alters a column in a table.
			'altersequence' => 'ALTER SEQUENCE',      // Alters a sequence object (PostgreSQL-specific).
			'alterview' => 'ALTER VIEW',              // Alters an existing database view.
			'alterprocedure' => 'ALTER PROCEDURE',    // Alters a stored procedure (SQL Server-specific).
			'alterfunction' => 'ALTER FUNCTION',      // Alters a user-defined function (PostgreSQL, SQL Server).
			'altertrigger' => 'ALTER TRIGGER',        // Alters an existing trigger.

			'createrole' => 'CREATE ROLE',            // Creates a new database role.
			'droprole' => 'DROP ROLE',                // Drops an existing database role.
			'grantrole' => 'GRANT ROLE',              // Grants a role to a user or another role.
			'revokerole' => 'REVOKE ROLE',            // Revokes a role from a user or another role.

			'truncate' => 'TRUNCATE',                 // Removes all rows from a table.
			'dropifexists' => 'DROP TABLE IF EXISTS', // Drops a table if it exists (MySQL-specific).
			'showdatabases' => 'SHOW DATABASES',      // Lists all databases (MySQL-specific).
			'usedatabase' => 'USE DATABASE',          // Switches to a specific database for the session (MySQL-specific).

			// Additional General Clauses
			'add' => 'ADD',                        // Adds a column, constraint, or index to a table.
			'modify' => 'MODIFY',                  // Modifies an existing column or constraint (MySQL, Oracle).
			'rename' => 'RENAME',                  // Renames a table, column, or index.
			'drop' => 'DROP',                      // Drops a column, table, or constraint.

			'default' => 'DEFAULT',                // Defines a default value for a column.
			'unique' => 'UNIQUE',                  // Specifies a unique constraint on a column.
			'check' => 'CHECK',                    // Defines a check constraint on a column or table.
			'index' => 'INDEX',                    // Specifies an index on a table.
			'using' => 'USING',                    // Specifies an index type or storage method.
			'constraint' => 'CONSTRAINT',          // Adds or defines a table constraint.
			'exclude' => 'EXCLUDE',                // Adds exclusion constraints (PostgreSQL-specific).

			'alterTableAddColumn' => 'ALTER TABLE ADD COLUMN',         // Adds a new column to a table.
			'alterTableDropColumn' => 'ALTER TABLE DROP COLUMN',       // Drops a column from a table.
			'alterTableSetDefault' => 'ALTER TABLE SET DEFAULT',       // Sets a default value for a column.
			'alterTableDropDefault' => 'ALTER TABLE DROP DEFAULT',     // Drops the default value of a column.
			'alterTableAddConstraint' => 'ALTER TABLE ADD CONSTRAINT', // Adds a constraint to a table.
			'alterTableDropConstraint' => 'ALTER TABLE DROP CONSTRAINT', // Drops a constraint from a table.
			'alterTableAddPartition' => 'ALTER TABLE ADD PARTITION',   // Adds a partition to a table.
			'alterTableDropPartition' => 'ALTER TABLE DROP PARTITION', // Drops a partition from a table.
			'alterTableEnableTrigger' => 'ALTER TABLE ENABLE TRIGGER', // Enables triggers on a table.
			'alterTableDisableTrigger' => 'ALTER TABLE DISABLE TRIGGER', // Disables triggers on a table.
			'alterTablespaces' => 'ALTER TABLESPACES',                // Alters the tablespace for a table (PostgreSQL-specific).

			'createView' => 'CREATE VIEW',                            // Creates a view in the database.
			'dropView' => 'DROP VIEW',                                // Drops a view from the database.

			'createProcedure' => 'CREATE PROCEDURE',                  // Creates a stored procedure.
			'dropProcedure' => 'DROP PROCEDURE',                      // Drops a stored procedure.

			'createFunction' => 'CREATE FUNCTION',                    // Creates a user-defined function.
			'dropFunction' => 'DROP FUNCTION',                        // Drops a user-defined function.
			'alterFunction' => 'ALTER FUNCTION',                      // Alters a user-defined function.

			'createTrigger' => 'CREATE TRIGGER',                      // Creates a database trigger.
			'dropTrigger' => 'DROP TRIGGER',                          // Drops a database trigger.

			'createSequence' => 'CREATE SEQUENCE',                    // Creates a sequence object.
			'dropSequence' => 'DROP SEQUENCE',                        // Drops a sequence object.

			'createTablespace' => 'CREATE TABLESPACE',                // Creates a tablespace.
			'dropTablespace' => 'DROP TABLESPACE',                    // Drops a tablespace.

			'grantPrivileges' => 'GRANT PRIVILEGES',                  // Grants specific privileges to a user or role.
			'revokePrivileges' => 'REVOKE PRIVILEGES',                // Revokes specific privileges from a user or role.
			// Miscellaneous Statements
			'rollbackTo' => 'ROLLBACK TO SAVEPOINT',   // Rolls back a transaction to a savepoint.
			'refreshmaterializedview' => 'REFRESH MATERIALIZED VIEW', // Refreshes a materialized view (PostgreSQL-specific).

			// DCL (Data Control Language)
			'createRole' => 'CREATE ROLE',            // Creates a new database role.
			'dropRole' => 'DROP ROLE',                // Drops an existing database role.

			default => throw new \InvalidArgumentException("Invalid SQL statement type: {$type}")
		};
	}
}
