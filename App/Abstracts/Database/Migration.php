<?php

declare(strict_types=1);

namespace App\Abstracts\Database;

/**
 * Abstract Migration Class
 *
 * Responsibilities:
 * - Defines a contract for handling database schema changes (migrations).
 * - Allows concrete classes to specify how tables and columns are created, altered, or dropped.
 *
 * Boundaries:
 * - Does not contain business logic or handle HTTP/presentation layers.
 * - Focused solely on defining how migrations interact with the schema builder.
 */
abstract class Migration
{
	/**
	 * The schema builder instance (e.g., a query builder or database schema manager).
	 * Concrete migrations will rely on this instance to apply changes.
	 */
	public function __construct(protected object $schema)
	{
	}

	/**
	 * Define the schema for creating or altering a table.
	 *
	 * @return void
	 */
	abstract protected function up(): void;

	/**
	 * Revert the schema changes defined in `up()`.
	 *
	 * @return void
	 */
	abstract protected function down(): void;

	/**
	 * Add a column to an existing table.
	 *
	 * @param string $table  The name of the table.
	 * @param string $column The name of the new column.
	 * @param string $type   The data type of the column.
	 * @param array<string,mixed> $options Additional options (e.g., nullable, default).
	 * @return void
	 */
	abstract protected function addColumn(string $table, string $column, string $type, array $options = []): void;

	/**
	 * Drop a column from an existing table.
	 *
	 * @param string $table  The name of the table.
	 * @param string $column The name of the column to drop.
	 * @return void
	 */
	abstract protected function dropColumn(string $table, string $column): void;

	/**
	 * Add an index to a table.
	 *
	 * @param string $table    The name of the table.
	 * @param string[] $columns The columns to include in the index.
	 * @param string|null $name Optional name for the index.
	 * @return void
	 */
	abstract protected function addIndex(string $table, array $columns, ?string $name = null): void;

	/**
	 * Drop an index from a table.
	 *
	 * @param string $table The name of the table.
	 * @param string $name  The name of the index to drop.
	 * @return void
	 */
	abstract protected function dropIndex(string $table, string $name): void;

	/**
	 * Rename a table.
	 *
	 * @param string $from The current table name.
	 * @param string $to   The new table name.
	 * @return void
	 */
	abstract protected function renameTable(string $from, string $to): void;

	/**
	 * Drop a table.
	 *
	 * @param string $table The name of the table to drop.
	 * @return void
	 */
	abstract protected function dropTable(string $table): void;

	/**
	 * Create a new table.
	 *
	 * @param string $table    The name of the table.
	 * @param callable $callback A callback defining the table schema.
	 * @return void
	 */
	abstract protected function createTable(string $table, callable $callback): void;
}
