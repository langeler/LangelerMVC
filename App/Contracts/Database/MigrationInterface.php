<?php

declare(strict_types=1);

namespace App\Contracts\Database;

/**
 * MigrationInterface
 *
 * Defines the contract for handling database schema changes.
 * Aligns with the abstract Migration class, providing a public API
 * that migration runner tools or the application can rely on.
 */
interface MigrationInterface
{
	/**
	 * Define the schema for creating or altering a table.
	 *
	 * @return void
	 */
	public function up(): void;

	/**
	 * Revert the schema changes defined in `up()`.
	 *
	 * @return void
	 */
	public function down(): void;

	/**
	 * Add a column to an existing table.
	 *
	 * @param string $table
	 * @param string $column
	 * @param string $type
	 * @param array<string,mixed> $options
	 * @return void
	 */
	public function addColumn(string $table, string $column, string $type, array $options = []): void;

	/**
	 * Drop a column from an existing table.
	 *
	 * @param string $table
	 * @param string $column
	 * @return void
	 */
	public function dropColumn(string $table, string $column): void;

	/**
	 * Add an index to a table.
	 *
	 * @param string $table
	 * @param string[] $columns
	 * @param string|null $name
	 * @return void
	 */
	public function addIndex(string $table, array $columns, ?string $name = null): void;

	/**
	 * Drop an index from a table.
	 *
	 * @param string $table
	 * @param string $name
	 * @return void
	 */
	public function dropIndex(string $table, string $name): void;

	/**
	 * Rename a table.
	 *
	 * @param string $from
	 * @param string $to
	 * @return void
	 */
	public function renameTable(string $from, string $to): void;

	/**
	 * Drop a table.
	 *
	 * @param string $table
	 * @return void
	 */
	public function dropTable(string $table): void;

	/**
	 * Create a new table.
	 *
	 * @param string $table
	 * @param callable $callback
	 * @return void
	 */
	public function createTable(string $table, callable $callback): void;
}
