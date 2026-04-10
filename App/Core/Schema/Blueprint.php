<?php

declare(strict_types=1);

namespace App\Core\Schema;

use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\ManipulationTrait;

/**
 * Lightweight schema blueprint used by framework migrations.
 *
 * The goal is not to mirror every SQL feature, but to provide a stable,
 * framework-native vocabulary for the first-party modules and operational
 * tooling.
 */
class Blueprint
{
    use ArrayTrait, ManipulationTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    /**
     * @var list<string>
     */
    private array $columns = [];

    /**
     * @var list<array{type:string, payload:array<string, mixed>}>
     */
    private array $operations = [];

    public function __construct(
        private readonly string $table,
        private readonly string $driver = 'mysql'
    ) {
    }

    public function id(string $name = 'id'): self
    {
        $definition = match ($this->driver) {
            'pgsql' => sprintf('"%s" BIGSERIAL PRIMARY KEY', $name),
            'sqlite' => sprintf('"%s" INTEGER PRIMARY KEY AUTOINCREMENT', $name),
            'sqlsrv' => sprintf('"%s" BIGINT IDENTITY(1,1) PRIMARY KEY', $name),
            default => sprintf('`%s` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY', $name),
        };

        $this->columns[] = $definition;

        return $this;
    }

    public function string(
        string $name,
        int $length = 255,
        bool $nullable = false,
        bool $unique = false,
        mixed $default = null
    ): self {
        return $this->addColumnDefinition($name, sprintf('VARCHAR(%d)', $length), $nullable, $default, $unique);
    }

    public function text(string $name, bool $nullable = false, mixed $default = null): self
    {
        return $this->addColumnDefinition($name, 'TEXT', $nullable, $default);
    }

    public function integer(string $name, bool $nullable = false, mixed $default = null): self
    {
        $type = $this->driver === 'pgsql' ? 'INTEGER' : 'INT';

        return $this->addColumnDefinition($name, $type, $nullable, $default);
    }

    public function boolean(string $name, bool $nullable = false, mixed $default = null): self
    {
        $type = match ($this->driver) {
            'sqlsrv' => 'BIT',
            'sqlite' => 'INTEGER',
            default => 'BOOLEAN',
        };

        return $this->addColumnDefinition($name, $type, $nullable, $default);
    }

    public function decimal(
        string $name,
        int $precision = 10,
        int $scale = 2,
        bool $nullable = false,
        mixed $default = null
    ): self {
        return $this->addColumnDefinition(
            $name,
            sprintf('DECIMAL(%d,%d)', $precision, $scale),
            $nullable,
            $default
        );
    }

    public function json(string $name, bool $nullable = false): self
    {
        $type = match ($this->driver) {
            'sqlite' => 'TEXT',
            'sqlsrv' => 'NVARCHAR(MAX)',
            default => 'JSON',
        };

        return $this->addColumnDefinition($name, $type, $nullable);
    }

    public function timestamp(string $name, bool $nullable = false, mixed $default = null): self
    {
        $type = match ($this->driver) {
            'sqlsrv' => 'DATETIME2',
            'sqlite' => 'TEXT',
            default => 'TIMESTAMP',
        };

        return $this->addColumnDefinition($name, $type, $nullable, $default);
    }

    public function timestamps(): self
    {
        return $this
            ->timestamp('created_at', true)
            ->timestamp('updated_at', true);
    }

    public function rememberToken(string $name = 'remember_token'): self
    {
        return $this->string($name, 100, true);
    }

    public function foreignId(string $name, bool $nullable = false): self
    {
        return $this->addColumnDefinition($name, 'BIGINT', $nullable);
    }

    /**
     * @param array<int, string>|string $columns
     */
    public function unique(array|string $columns, ?string $name = null): self
    {
        $columns = $this->normalizeColumns($columns);
        $name ??= $this->defaultIndexName('uniq', $columns);

        $this->operations[] = [
            'type' => 'unique',
            'payload' => ['name' => $name, 'columns' => $columns],
        ];

        return $this;
    }

    /**
     * @param array<int, string>|string $columns
     */
    public function index(array|string $columns, ?string $name = null): self
    {
        $columns = $this->normalizeColumns($columns);
        $name ??= $this->defaultIndexName('idx', $columns);

        $this->operations[] = [
            'type' => 'index',
            'payload' => ['name' => $name, 'columns' => $columns],
        ];

        return $this;
    }

    /**
     * @param array<int, string>|string $columns
     */
    public function primary(array|string $columns, ?string $name = null): self
    {
        $this->operations[] = [
            'type' => 'primary',
            'payload' => [
                'name' => $name ?? $this->defaultIndexName('pk', $this->normalizeColumns($columns)),
                'columns' => $this->normalizeColumns($columns),
            ],
        ];

        return $this;
    }

    public function foreign(
        string $column,
        string $referencedTable,
        string $referencedColumn = 'id',
        string $onDelete = 'RESTRICT',
        string $onUpdate = 'RESTRICT'
    ): self {
        $this->operations[] = [
            'type' => 'foreign',
            'payload' => [
                'column' => $column,
                'referencedTable' => $referencedTable,
                'referencedColumn' => $referencedColumn,
                'onDelete' => $onDelete,
                'onUpdate' => $onUpdate,
            ],
        ];

        return $this;
    }

    /**
     * @return list<string>
     */
    public function columns(): array
    {
        return $this->columns;
    }

    /**
     * @return list<array{type:string, payload:array<string, mixed>}>
     */
    public function operations(): array
    {
        return $this->operations;
    }

    private function addColumnDefinition(
        string $name,
        string $type,
        bool $nullable = false,
        mixed $default = null,
        bool $unique = false
    ): self {
        $definition = $this->quoteIdentifier($name) . ' ' . $type;
        $definition .= $nullable ? ' NULL' : ' NOT NULL';

        if ($default !== null) {
            $definition .= ' DEFAULT ' . $this->compileDefault($default);
        }

        $this->columns[] = $definition;

        if ($unique) {
            $this->unique($name);
        }

        return $this;
    }

    private function quoteIdentifier(string $name): string
    {
        return match ($this->driver) {
            'pgsql', 'sqlite' => '"' . $name . '"',
            'sqlsrv' => '[' . $name . ']',
            default => '`' . $name . '`',
        };
    }

    private function compileDefault(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? '1' : '0',
            is_int($value), is_float($value) => (string) $value,
            $value === 'CURRENT_TIMESTAMP' => 'CURRENT_TIMESTAMP',
            default => "'" . str_replace("'", "''", (string) $value) . "'",
        };
    }

    /**
     * @param array<int, string>|string $columns
     * @return list<string>
     */
    private function normalizeColumns(array|string $columns): array
    {
        if (is_string($columns)) {
            return [$columns];
        }

        return array_values(array_map(static fn(string $column): string => trim($column), $columns));
    }

    /**
     * @param list<string> $columns
     */
    private function defaultIndexName(string $prefix, array $columns): string
    {
        return $this->table . '_' . $this->joinStrings('_', $columns) . '_' . $prefix;
    }
}
