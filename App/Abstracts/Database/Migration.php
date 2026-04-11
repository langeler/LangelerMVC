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

    /**
     * @return list<class-string|non-empty-string>
     */
    public static function dependencies(): array
    {
        return [];
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
        $schema = $this->database->schemaQuery();
        $constraints = [];
        $indexes = [];

        foreach ($blueprint->operations() as $operation) {
            $payload = $operation['payload'];

            match ($operation['type']) {
                'unique' => $constraints[] = $this->compileUniqueConstraint((array) $payload['columns']),
                'primary' => $constraints[] = $this->compilePrimaryConstraint((array) $payload['columns']),
                'foreign' => $constraints[] = $this->compileForeignConstraint(
                    (string) $payload['column'],
                    (string) $payload['referencedTable'],
                    (string) $payload['referencedColumn'],
                    (string) $payload['onDelete'],
                    (string) $payload['onUpdate']
                ),
                'index' => $indexes[] = [
                    'name' => (string) $payload['name'],
                    'columns' => (array) $payload['columns'],
                ],
                default => null,
            };
        }

        $schema->createTable($table, $blueprint->columns(), $constraints);

        foreach ($schema->toStatements() as $statement) {
            $this->database->query($statement);
        }

        foreach ($indexes as $index) {
            $this->database->query($this->compileCreateIndexStatement(
                $table,
                (string) $index['name'],
                (array) $index['columns']
            ));
        }
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

    /**
     * @param array<int, string> $columns
     */
    private function compileUniqueConstraint(array $columns): string
    {
        return 'UNIQUE (' . $this->compileIdentifierArray($columns) . ')';
    }

    /**
     * @param array<int, string> $columns
     */
    private function compilePrimaryConstraint(array $columns): string
    {
        return 'PRIMARY KEY (' . $this->compileIdentifierArray($columns) . ')';
    }

    private function compileForeignConstraint(
        string $column,
        string $referencedTable,
        string $referencedColumn,
        string $onDelete,
        string $onUpdate
    ): string {
        return sprintf(
            'FOREIGN KEY (%s) REFERENCES %s(%s) ON DELETE %s ON UPDATE %s',
            $this->quoteIdentifier($column),
            $this->quoteIdentifier($referencedTable),
            $this->quoteIdentifier($referencedColumn),
            strtoupper($onDelete),
            strtoupper($onUpdate)
        );
    }

    /**
     * @param array<int, string> $columns
     */
    private function compileCreateIndexStatement(string $table, string $name, array $columns): string
    {
        return sprintf(
            'CREATE INDEX %s ON %s (%s)',
            $this->quoteIdentifier($name),
            $this->quoteIdentifier($table),
            $this->compileIdentifierArray($columns)
        );
    }

    /**
     * @param array<int, string> $columns
     */
    private function compileIdentifierArray(array $columns): string
    {
        return implode(', ', array_map(
            fn(string $column): string => $this->quoteIdentifier($column),
            array_values($columns)
        ));
    }

    private function quoteIdentifier(string $identifier): string
    {
        return match ($this->configuredDriver()) {
            'pgsql', 'sqlite' => '"' . $identifier . '"',
            'sqlsrv' => '[' . $identifier . ']',
            default => '`' . $identifier . '`',
        };
    }
}
