<?php

declare(strict_types=1);

namespace App\Abstracts\Database;

use App\Core\Database;
use App\Core\Schema\Blueprint;
use App\Contracts\Database\MigrationInterface;

/**
 * Base migration abstraction for schema operations.
 */
abstract class Migration implements MigrationInterface
{
    public function __construct(protected Database $database)
    {
    }

    abstract public function up(): void;

    abstract public function down(): void;

    public function addColumn(string $table, string $column, string $type, array $options = []): void
    {
        $definition = $type;

        if (($options['nullable'] ?? false) !== true) {
            $definition .= ' NOT NULL';
        }

        if (array_key_exists('default', $options)) {
            $default = $options['default'];
            $definition .= ' DEFAULT ' . (is_numeric($default) ? (string) $default : "'" . str_replace("'", "''", (string) $default) . "'");
        }

        $this->executeSchema(fn($schema) => $schema->addColumn($table, $column, $definition));
    }

    public function dropColumn(string $table, string $column): void
    {
        $this->executeSchema(fn($schema) => $schema->removeColumn($table, $column));
    }

    public function addIndex(string $table, array $columns, ?string $name = null): void
    {
        $name ??= $table . '_' . implode('_', $columns) . '_idx';
        $this->executeSchema(fn($schema) => $schema->addIndex($table, $name, $columns));
    }

    public function dropIndex(string $table, string $name): void
    {
        $this->executeSchema(fn($schema) => $schema->dropIndex($table, $name));
    }

    public function renameTable(string $from, string $to): void
    {
        $this->executeSchema(fn($schema) => $schema->renameTable($from, $to));
    }

    public function dropTable(string $table): void
    {
        $this->executeSchema(fn($schema) => $schema->dropTable($table));
    }

    public function createTable(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table, $this->configuredDriver());
        $callback($blueprint);

        $this->executeSchema(function ($schema) use ($table, $blueprint): void {
            $schema->createTable($table, $blueprint->columns());

            foreach ($blueprint->operations() as $operation) {
                $payload = $operation['payload'];

                match ($operation['type']) {
                    'unique' => $schema->setUnique($table, $payload['columns']),
                    'index' => $schema->addIndex($table, (string) $payload['name'], $payload['columns']),
                    'primary' => $schema->addPrimary($table, $payload['columns']),
                    'foreign' => $schema->setForeign(
                        $table,
                        (string) $payload['column'],
                        (string) $payload['referencedTable'],
                        (string) $payload['referencedColumn'],
                        (string) $payload['onDelete'],
                        (string) $payload['onUpdate']
                    ),
                    default => null,
                };
            }
        });
    }

    protected function executeSchema(callable $callback): void
    {
        $schema = $this->database->schemaQuery();
        $callback($schema);

        foreach ($schema->toStatements() as $statement) {
            $this->database->query($statement);
        }
    }

    protected function configuredDriver(): string
    {
        return strtolower((string) $this->database->getAttribute('driverName'));
    }
}
