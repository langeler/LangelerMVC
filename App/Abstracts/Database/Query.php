<?php

declare(strict_types=1);

namespace App\Abstracts\Database;

use App\Providers\ExceptionProvider;
use App\Utilities\Handlers\SQLHandler;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Traits\{
    ArrayTrait,
    ErrorTrait,
    ManipulationTrait,
    TypeCheckerTrait
};
use App\Utilities\Traits\Patterns\PatternTrait;

/**
 * Base query infrastructure for parameterized SQL builders.
 *
 * The query layer is responsible for:
 * - validating and quoting identifiers consistently
 * - collecting parameter bindings instead of inlining values
 * - keeping SQL keyword/operator lookup centralized through SQLHandler
 * - providing a shared foundation for data and schema builders
 */
abstract class Query
{
    use ErrorTrait, TypeCheckerTrait;
    use ArrayTrait, ManipulationTrait, PatternTrait {
        ArrayTrait::replace insteadof ManipulationTrait, PatternTrait;
        ArrayTrait::pad insteadof ManipulationTrait;
        ArrayTrait::reverse insteadof ManipulationTrait;
        ArrayTrait::shuffle insteadof ManipulationTrait;
        PatternTrait::split insteadof ManipulationTrait;
        ManipulationTrait::join as protected implodeWith;
        ManipulationTrait::trim as protected trimString;
        ManipulationTrait::toLower as protected toLowerString;
        ManipulationTrait::toUpper as protected toUpperString;
        PatternTrait::match as protected matchPattern;
    }

    /**
     * @var list<mixed>
     */
    protected array $bindings = [];

    public function __construct(
        ?SQLHandler $sql = null,
        ?ErrorManager $errorManager = null,
        protected string $table = '',
        protected array $columns = [],
        protected array $values = [],
        protected string $driver = 'mysql'
    ) {
        $this->sql = $sql ?? new SQLHandler();
        $this->errorManager = $errorManager ?? new ErrorManager(new ExceptionProvider());
        $this->driver = $this->normalizeDriver($this->driver);
    }

    /**
     * SQL vocabulary lookup.
     */
    protected SQLHandler $sql;

    /**
     * Error translation surface used by ErrorTrait.
     */
    protected ErrorManager $errorManager;

    public function build(string $queryType, array $parameters, ?string $comment = null): string
    {
        return $this->wrapInTry(function () use ($queryType, $parameters, $comment): string {
            $sql = $this->buildType($queryType, $parameters);

            if ($comment === null || $this->trimString($comment) === '') {
                return $sql;
            }

            return $sql . ' /*' . $this->escapeSqlComment($comment) . '*/';
        }, 'database');
    }

    public function buildType(string $queryType, array $parameters): string
    {
        $segments = $this->filter(
            $parameters,
            fn(mixed $segment): bool => $this->isString($segment) && $this->trimString($segment) !== ''
        );

        return $this->trimString(
            $this->sql->statement($queryType)
            . ($segments === [] ? '' : ' ' . $this->implodeWith(' ', $segments))
        );
    }

    public function setTable(string $table): void
    {
        $this->table = $table;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function setValues(array $values): void
    {
        $this->values = $values;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function setDriver(string $driver): void
    {
        $this->driver = $this->normalizeDriver($driver);
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * @return list<mixed>
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * @param list<mixed> $bindings
     */
    public function setBindings(array $bindings): void
    {
        $this->bindings = array_values($bindings);
    }

    public function clearBindings(): void
    {
        $this->bindings = [];
    }

    /**
     * @return array<int, string>
     */
    public function escapeIdentifiers(array $parameters): array
    {
        return $this->map(fn(string $identifier): string => $this->quoteIdentifier($identifier), $parameters);
    }

    /**
     * @return array<int, string>
     */
    public function escapeOperators(array $parameters): array
    {
        return $this->map(fn(string $operator): string => $this->sql->operator($operator), $parameters);
    }

    /**
     * @return array<int, string>
     */
    public function escapeColumns(array $parameters): array
    {
        return $this->map(fn(string $column): string => $this->quoteColumnExpression($column), $parameters);
    }

    /**
     * @return array<int, string>
     */
    public function escapeValues(array $parameters): array
    {
        return $this->map(fn(mixed $value): string => $this->quoteLiteral($value), $parameters);
    }

    /**
     * @return array<int, string>
     */
    public function parseIdentifiers(array $parameters): array
    {
        return array_values(
            $this->filter(
                $this->map(
                    fn(mixed $identifier): string => $this->trimString((string) $identifier),
                    $parameters
                ),
                fn(string $identifier): bool => $identifier !== ''
            )
        );
    }

    /**
     * @return array<int, string>
     */
    public function parseOperators(array $parameters): array
    {
        return array_values(
            $this->filter(
                $this->map(
                    fn(mixed $operator): string => $this->trimString((string) $operator),
                    $parameters
                ),
                fn(string $operator): bool => $operator !== ''
            )
        );
    }

    /**
     * @return array<int, string>
     */
    public function parseColumns(array $parameters): array
    {
        return array_values(
            $this->filter(
                $this->map(
                    fn(mixed $column): string => $this->trimString((string) $column),
                    $parameters
                ),
                fn(string $column): bool => $column !== ''
            )
        );
    }

    /**
     * @return list<mixed>
     */
    public function parseValues(array $parameters): array
    {
        return array_values($parameters);
    }

    /**
     * @return array<int, string>
     */
    public function processIdentifiers(array $parameters): array
    {
        return $this->escapeIdentifiers($this->parseIdentifiers($parameters));
    }

    /**
     * @return array<int, string>
     */
    public function processOperators(array $parameters): array
    {
        return $this->escapeOperators($this->parseOperators($parameters));
    }

    /**
     * @return array<int, string>
     */
    public function processColumns(array $parameters): array
    {
        return $this->escapeColumns($this->parseColumns($parameters));
    }

    /**
     * @return array<int, string>
     */
    public function processValues(array $parameters): array
    {
        return $this->escapeValues($this->parseValues($parameters));
    }

    protected function normalizeDriver(string $driver): string
    {
        $normalized = $this->toLowerString($this->trimString($driver));

        return match ($normalized) {
            'mariadb' => 'mysql',
            '' => 'mysql',
            default => $normalized,
        };
    }

    protected function quoteIdentifier(string $identifier): string
    {
        $identifier = $this->trimString($identifier);

        if ($identifier === '*') {
            return '*';
        }

        $segments = explode('.', $identifier);

        if ($segments === []) {
            throw $this->errorManager->resolveException('database', 'SQL identifier cannot be empty.');
        }

        return $this->implodeWith('.', $this->map(
            function (string $segment): string {
                if ($segment === '*') {
                    return '*';
                }

                if ($this->matchPattern('/^[A-Za-z_][A-Za-z0-9_]*$/', $segment) !== 1) {
                    throw $this->errorManager->resolveException(
                        'database',
                        sprintf('Invalid SQL identifier segment [%s].', $segment)
                    );
                }

                return $this->wrapIdentifierSegment($segment);
            },
            $segments
        ));
    }

    protected function quoteColumnExpression(string $column): string
    {
        $column = $this->trimString($column);

        if ($column === '') {
            throw $this->errorManager->resolveException('database', 'Column expression cannot be empty.');
        }

        if ($column === '*' || $this->isSimpleIdentifier($column) || $this->isQualifiedIdentifier($column)) {
            return $this->quoteIdentifier($column);
        }

        if (preg_match('/^(.+)\s+as\s+([A-Za-z_][A-Za-z0-9_]*)$/i', $column, $matches) === 1) {
            return $this->quoteExpression($matches[1]) . ' AS ' . $this->quoteIdentifier($matches[2]);
        }

        return $this->quoteExpression($column);
    }

    protected function quoteExpression(string $expression): string
    {
        $expression = $this->trimString($expression);

        if ($expression === '') {
            throw $this->errorManager->resolveException('database', 'SQL expression cannot be empty.');
        }

        if ($this->isSimpleIdentifier($expression) || $this->isQualifiedIdentifier($expression) || $expression === '*') {
            return $this->quoteIdentifier($expression);
        }

        return $expression;
    }

    protected function wrapIdentifierSegment(string $segment): string
    {
        return match ($this->driver) {
            'pgsql', 'sqlite' => '"' . $segment . '"',
            'sqlsrv' => '[' . $segment . ']',
            default => '`' . $segment . '`',
        };
    }

    protected function isSimpleIdentifier(string $value): bool
    {
        return $this->matchPattern('/^[A-Za-z_][A-Za-z0-9_]*$/', $value) === 1;
    }

    protected function isQualifiedIdentifier(string $value): bool
    {
        return $this->matchPattern('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*|\.\*)+$/', $value) === 1;
    }

    /**
     * @param list<mixed> $bindings
     */
    protected function mergeBindings(array $bindings): void
    {
        array_push($this->bindings, ...$bindings);
    }

    protected function bindValue(mixed $value): string
    {
        $this->bindings[] = $value;

        return '?';
    }

    protected function bindValues(array $values): string
    {
        if ($values === []) {
            return '';
        }

        return $this->implodeWith(
            ', ',
            $this->map(fn(mixed $value): string => $this->bindValue($value), array_values($values))
        );
    }

    protected function normalizeDirection(string $direction): string
    {
        $normalized = $this->toUpperString($this->trimString($direction));

        return match ($normalized) {
            'DESC' => 'DESC',
            default => 'ASC',
        };
    }

    protected function quoteLiteral(mixed $value): string
    {
        return match (true) {
            $value === null => 'NULL',
            $this->isBool($value) => $value ? '1' : '0',
            $this->isInt($value), $this->isFloat($value) => (string) $value,
            $this->isArray($value) => "'" . str_replace("'", "''", json_encode($value, JSON_THROW_ON_ERROR)) . "'",
            default => "'" . str_replace("'", "''", (string) $value) . "'",
        };
    }

    protected function getKeys(array $data): array
    {
        return array_keys($data);
    }

    protected function getValuesList(array $data): array
    {
        return array_values($data);
    }

    protected function isAssociativeArray(array $value): bool
    {
        if ($value === []) {
            return false;
        }

        return array_keys($value) !== range(0, count($value) - 1);
    }

    private function escapeSqlComment(string $comment): string
    {
        return str_replace(['/*', '*/'], '', $comment);
    }
}
