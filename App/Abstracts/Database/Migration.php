<?php

declare(strict_types=1);

namespace App\Abstracts\Database;

use App\Contracts\Database\MigrationInterface;

/**
 * Base migration abstraction for schema operations.
 */
abstract class Migration implements MigrationInterface
{
    public function __construct(protected object $schema)
    {
    }

    abstract public function up(): void;

    abstract public function down(): void;

    abstract public function addColumn(string $table, string $column, string $type, array $options = []): void;

    abstract public function dropColumn(string $table, string $column): void;

    abstract public function addIndex(string $table, array $columns, ?string $name = null): void;

    abstract public function dropIndex(string $table, string $name): void;

    abstract public function renameTable(string $from, string $to): void;

    abstract public function dropTable(string $table): void;

    abstract public function createTable(string $table, callable $callback): void;
}
