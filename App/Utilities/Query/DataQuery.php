<?php

declare(strict_types=1);

namespace App\Utilities\Query;

use App\Abstracts\Database\Query;
use App\Utilities\Traits\Query\DataQueryTrait;

/**
 * Parameterized SQL data query builder.
 *
 * This builder is intentionally framework-level infrastructure:
 * - fluent trait methods mutate internal state
 * - SQL is compiled lazily
 * - values are always emitted as bindings where appropriate
 * - identifier quoting is driver-aware and shared through Query
 */
class DataQuery extends Query
{
    use DataQueryTrait;

    protected string $operation = 'select';

    /**
     * @var array<int, string>
     */
    protected array $selectColumns = ['*'];

    /**
     * @var array<string, mixed>
     */
    protected array $insertData = [];

    /**
     * @var array<string, mixed>
     */
    protected array $updateData = [];

    /**
     * @var list<mixed>
     */
    protected array $whereConditions = [];

    /**
     * @var list<mixed>
     */
    protected array $havingConditions = [];

    /**
     * @var list<mixed>
     */
    protected array $pendingJoinConditions = [];

    /**
     * @var list<array{type:string,table:string,on:list<mixed>,cols:array<int, string>}>
     */
    protected array $joins = [];

    /**
     * @var list<array<string, mixed>>
     */
    protected array $orderings = [];

    /**
     * @var list<array<string, mixed>>
     */
    protected array $groupings = [];

    /**
     * @var list<string>
     */
    protected array $locking = [];

    /**
     * @var list<array{type:string,query:mixed}>
     */
    protected array $setOperations = [];

    /**
     * @var list<array{alias:string,query:mixed}>
     */
    protected array $applyClauses = [];

    /**
     * @var list<array<string, mixed>>
     */
    protected array $specialConditions = [];

    /**
     * @var list<string>
     */
    protected array $returningColumns = [];

    /**
     * @var list<string>
     */
    protected array $distinctOnColumns = [];

    protected ?int $limitValue = null;
    protected ?int $offsetValue = null;

    public function __construct(
        ?\App\Utilities\Handlers\SQLHandler $sql = null,
        ?\App\Utilities\Managers\System\ErrorManager $errorManager = null,
        string $table = '',
        array $columns = [],
        array $values = [],
        string $driver = 'mysql'
    ) {
        parent::__construct($sql, $errorManager, $table, $columns, $values, $driver);

        if ($columns !== []) {
            $this->selectColumns = $columns;
        }
    }

    /**
     * @return array{sql:string, bindings:list<mixed>}
     */
    public function toExecutable(): array
    {
        $sql = $this->toSql();

        return [
            'sql' => $sql,
            'bindings' => $this->getBindings(),
        ];
    }

    public function toSql(): string
    {
        $this->clearBindings();

        return match ($this->operation) {
            'insert' => $this->compileInsertStatement(),
            'update' => $this->compileUpdateStatement(),
            'delete' => $this->compileDeleteStatement(),
            default => $this->compileSelectStatement(),
        };
    }

    public function processInsert(array $data): array
    {
        $this->operation = 'insert';
        $this->insertData = $data;
        $this->columns = $this->getKeys($data);
        $this->values = $this->getValuesList($data);

        return [
            'columns' => $this->processColumns($this->columns),
            'values' => $this->values,
        ];
    }

    public function buildInsert(array $data): string
    {
        $this->processInsert($data);

        return $this->compileInsertStatement();
    }

    public function processSelect(array $columns): array
    {
        $this->operation = 'select';
        $this->selectColumns = $columns === [] ? ['*'] : $columns;
        $this->columns = $this->selectColumns;

        return ['columns' => $this->processColumns($this->selectColumns)];
    }

    public function buildSelect(array $columns): string
    {
        $this->processSelect($columns);

        return $this->compileSelectStatement();
    }

    public function processUpdate(array $data): array
    {
        $this->operation = 'update';
        $this->updateData = $data;
        $this->columns = $this->getKeys($data);
        $this->values = $this->getValuesList($data);

        return ['assignments' => $data];
    }

    public function buildUpdate(array $data): string
    {
        $this->processUpdate($data);

        return $this->compileUpdateStatement();
    }

    public function processDelete(): array
    {
        $this->operation = 'delete';

        return ['table' => $this->getTable()];
    }

    public function buildDelete(): string
    {
        $this->processDelete();

        return $this->compileDeleteStatement();
    }

    public function processConditions(array $conditions, string $target = 'where'): array
    {
        return $this->wrapInTry(function () use ($conditions, $target): array {
            $normalizedTarget = $this->toLowerString($this->trimString($target));

            return match ($normalizedTarget) {
                'having' => $this->havingConditions = $this->merge($this->havingConditions, $conditions),
                'on' => $this->pendingJoinConditions = $this->merge($this->pendingJoinConditions, $conditions),
                default => $this->whereConditions = $this->merge($this->whereConditions, $conditions),
            };
        }, 'database');
    }

    public function buildConditions(array $conditions): string
    {
        return $this->wrapInTry(function () use ($conditions): string {
            $compiled = $this->compileConditionList($conditions, 'where');

            return $compiled === '' ? '' : $this->sql->clause('where') . ' ' . $compiled;
        }, 'database');
    }

    public function processSpecialConditions(array $specialConditions): array
    {
        foreach ($specialConditions as $specialCondition) {
            if (($specialCondition['type'] ?? null) === 'distinctOn') {
                $this->distinctOnColumns = $this->merge(
                    $this->distinctOnColumns,
                    $this->getValuesList((array) ($specialCondition['columns'] ?? []))
                );
                continue;
            }

            $this->specialConditions[] = $specialCondition;
        }

        return $this->specialConditions;
    }

    public function buildSpecialConditions(array $specialConditions): string
    {
        $compiled = $this->compileSpecialConditions($specialConditions);

        return $compiled === [] ? '' : $this->implodeWith(' ', $compiled);
    }

    public function processLocking(array $lockingOptions): array
    {
        $this->locking = $this->merge(
            $this->locking,
            $this->map(fn(mixed $option): string => $this->trimString((string) $option), $lockingOptions)
        );

        return $this->locking;
    }

    public function buildLocking(array $lockingOptions): string
    {
        $processed = $this->processLocking($lockingOptions);

        return $processed === [] ? '' : $this->implodeWith(' ', $processed);
    }

    public function processJoins(array $joins): array
    {
        foreach ($joins as $join) {
            $effectiveConditions = $join['on'] ?? [];

            if ($this->pendingJoinConditions !== []) {
                $effectiveConditions = $this->merge($this->pendingJoinConditions, $effectiveConditions);
                $this->pendingJoinConditions = [];
            }

            $columns = $this->getValuesList((array) ($join['cols'] ?? []));

            if ($columns !== []) {
                $this->selectColumns = $this->getValuesList($this->unique($this->merge($this->selectColumns, $columns)));
            }

            $this->joins[] = [
                'type' => (string) ($join['type'] ?? 'join'),
                'table' => (string) ($join['table'] ?? ''),
                'on' => $this->getValuesList($effectiveConditions),
                'cols' => $columns,
            ];
        }

        return $this->joins;
    }

    public function buildJoins(array $joins): string
    {
        $processed = $this->processJoins($joins);

        return $processed === [] ? '' : $this->compileJoinClause($processed);
    }

    public function processOrdering(array $orderingOptions): array
    {
        foreach ($orderingOptions as $orderingOption) {
            $type = $orderingOption['type'] ?? 'order';

            if ($type === 'order') {
                $this->orderings[] = $orderingOption;
                continue;
            }

            $this->groupings[] = $orderingOption;
        }

        return $this->merge($this->groupings, $this->orderings);
    }

    public function buildOrdering(array $orderingOptions): string
    {
        $this->processOrdering($orderingOptions);

        return $this->compileOrderClause();
    }

    public function processPagination(int $limit, int $offset): array
    {
        if ($limit > 0) {
            $this->limitValue = $limit;
        }

        if ($offset > 0 || ($limit === 0 && $offset === 0 && $this->offsetValue !== null)) {
            $this->offsetValue = $offset;
        }

        return [
            'limit' => $this->limitValue,
            'offset' => $this->offsetValue,
        ];
    }

    public function buildPagination(int $limit, int $offset): string
    {
        $this->processPagination($limit, $offset);

        return $this->compilePaginationClause();
    }

    public function processSet(array $setOperations): array
    {
        foreach ($setOperations as $setOperation) {
            if ($this->isAssociativeArray($setOperation)) {
                $this->setOperations[] = [
                    'type' => (string) ($setOperation['type'] ?? 'union'),
                    'query' => $setOperation['query'] ?? '',
                ];
                continue;
            }

            if ($this->isArray($setOperation) && $this->countElements($setOperation) >= 2) {
                $this->setOperations[] = [
                    'type' => (string) $setOperation[0],
                    'query' => $setOperation[1],
                ];
            }
        }

        return $this->setOperations;
    }

    public function buildSet(array $setOperations): string
    {
        $this->processSet($setOperations);

        return $this->compileSetClause();
    }

    public function processApplyClauses(array $apply): array
    {
        foreach ($apply as $clause) {
            if ($this->isAssociativeArray($clause)) {
                $this->applyClauses[] = [
                    'alias' => (string) ($clause['alias'] ?? ''),
                    'query' => $clause['query'] ?? '',
                ];
                continue;
            }

            if ($this->isArray($clause) && $this->countElements($clause) >= 2) {
                $this->applyClauses[] = [
                    'alias' => (string) $clause[0],
                    'query' => $clause[1],
                ];
            }
        }

        return $this->applyClauses;
    }

    public function buildApply(array $applyClauses): string
    {
        $this->processApplyClauses($applyClauses);

        return $this->compileWithClause();
    }

    public function processReturning(array $columns): array
    {
        $this->returningColumns = $this->getValuesList($this->merge($this->returningColumns, $columns));

        return $this->returningColumns;
    }

    private function compileSelectStatement(): string
    {
        $segments = [];
        $with = $this->compileWithClause();

        if ($with !== '') {
            $segments[] = $with;
        }

        $selectPrefix = $this->sql->statement('select');

        if ($this->distinctOnColumns !== []) {
            $selectPrefix .= ' ' . $this->sql->clause('distinctOn')
                . ' (' . $this->compileColumnList($this->distinctOnColumns) . ')';
        }

        $segments[] = $selectPrefix . ' ' . $this->compileColumnList($this->selectColumns);

        if ($this->getTable() !== '') {
            $segments[] = $this->sql->clause('from') . ' ' . $this->quoteIdentifier($this->getTable());
        }

        $joinClause = $this->compileJoinClause($this->joins);

        if ($joinClause !== '') {
            $segments[] = $joinClause;
        }

        $whereClause = $this->compileWhereClause();

        if ($whereClause !== '') {
            $segments[] = $whereClause;
        }

        $specialClause = $this->buildSpecialConditions($this->specialConditions);

        if ($specialClause !== '') {
            $segments[] = $specialClause;
        }

        $groupClause = $this->compileGroupClause();

        if ($groupClause !== '') {
            $segments[] = $groupClause;
        }

        $havingClause = $this->compileHavingClause();

        if ($havingClause !== '') {
            $segments[] = $havingClause;
        }

        $setClause = $this->compileSetClause();

        if ($setClause !== '') {
            $segments[] = $setClause;
        }

        $orderClause = $this->compileOrderClause();

        if ($orderClause !== '') {
            $segments[] = $orderClause;
        }

        $paginationClause = $this->compilePaginationClause();

        if ($paginationClause !== '') {
            $segments[] = $paginationClause;
        }

        $lockingClause = $this->locking === [] ? '' : $this->implodeWith(' ', $this->locking);

        if ($lockingClause !== '') {
            $segments[] = $lockingClause;
        }

        return $this->trimString($this->implodeWith(' ', $segments));
    }

    private function compileInsertStatement(): string
    {
        if ($this->getTable() === '') {
            throw $this->errorManager->resolveException('database', 'Cannot build insert query without a table.');
        }

        if ($this->insertData === []) {
            throw $this->errorManager->resolveException('database', 'Cannot build insert query without values.');
        }

        $columns = $this->getKeys($this->insertData);
        $values = $this->getValuesList($this->insertData);
        $segments = [
            $this->sql->statement('insert'),
            $this->sql->statement('into'),
            $this->quoteIdentifier($this->getTable()),
            '(' . $this->compileIdentifierList($columns) . ')',
            $this->sql->clause('values'),
            '(' . $this->bindValues($values) . ')',
        ];

        $returning = $this->compileReturningClause();

        if ($returning !== '') {
            $segments[] = $returning;
        }

        return $this->trimString($this->implodeWith(' ', $segments));
    }

    private function compileUpdateStatement(): string
    {
        if ($this->getTable() === '') {
            throw $this->errorManager->resolveException('database', 'Cannot build update query without a table.');
        }

        if ($this->updateData === []) {
            throw $this->errorManager->resolveException('database', 'Cannot build update query without assignments.');
        }

        $assignments = [];

        foreach ($this->updateData as $column => $value) {
            $assignments[] = $this->quoteIdentifier((string) $column) . ' = ' . $this->bindValue($value);
        }

        $segments = [
            $this->sql->statement('update'),
            $this->quoteIdentifier($this->getTable()),
            $this->sql->clause('set'),
            $this->implodeWith(', ', $assignments),
        ];

        $whereClause = $this->compileWhereClause();

        if ($whereClause !== '') {
            $segments[] = $whereClause;
        }

        $returning = $this->compileReturningClause();

        if ($returning !== '') {
            $segments[] = $returning;
        }

        return $this->trimString($this->implodeWith(' ', $segments));
    }

    private function compileDeleteStatement(): string
    {
        if ($this->getTable() === '') {
            throw $this->errorManager->resolveException('database', 'Cannot build delete query without a table.');
        }

        $segments = [
            $this->sql->statement('delete'),
            $this->sql->clause('from'),
            $this->quoteIdentifier($this->getTable()),
        ];

        $whereClause = $this->compileWhereClause();

        if ($whereClause !== '') {
            $segments[] = $whereClause;
        }

        $returning = $this->compileReturningClause();

        if ($returning !== '') {
            $segments[] = $returning;
        }

        return $this->trimString($this->implodeWith(' ', $segments));
    }

    private function compileWhereClause(): string
    {
        $compiled = $this->compileConditionList($this->whereConditions, 'where');

        return $compiled === '' ? '' : $this->sql->clause('where') . ' ' . $compiled;
    }

    private function compileHavingClause(): string
    {
        $compiled = $this->compileConditionList($this->havingConditions, 'having');

        return $compiled === '' ? '' : $this->sql->clause('having') . ' ' . $compiled;
    }

    /**
     * @param list<mixed> $conditions
     */
    private function compileConditionList(array $conditions, string $context, string $joiner = 'AND'): string
    {
        $compiled = [];

        foreach ($conditions as $condition) {
            $fragment = $this->compileCondition($condition, $context);

            if ($fragment !== '') {
                $compiled[] = $fragment;
            }
        }

        return $compiled === [] ? '' : $this->implodeWith(' ' . $joiner . ' ', $compiled);
    }

    private function compileCondition(mixed $condition, string $context): string
    {
        if ($this->isString($condition)) {
            return $this->trimString($condition);
        }

        if (!$this->isArray($condition) || $condition === []) {
            return '';
        }

        if ($this->isAssociativeArray($condition) && $this->countElements($condition) === 1) {
            $logic = (string) $this->keyFirst($condition);
            $nestedConditions = $this->getValuesList($condition)[0];

            if (!$this->isArray($nestedConditions)) {
                return '';
            }

            return $this->compileLogicalCondition($logic, $nestedConditions, $context);
        }

        return $this->compileAtomicCondition($condition, $context);
    }

    /**
     * @param list<mixed> $conditions
     */
    private function compileLogicalCondition(string $logic, array $conditions, string $context): string
    {
        $normalized = $this->sql->normalize($logic);
        $compiled = match ($normalized) {
            'not' => 'NOT (' . $this->compileConditionList($conditions, $context) . ')',
            'or', 'any' => '(' . $this->compileConditionList($conditions, $context, 'OR') . ')',
            'xor' => '(' . $this->compileConditionList($conditions, $context, 'XOR') . ')',
            'andnot' => '(' . $this->compileConditionList($conditions, $context, 'AND NOT') . ')',
            'ornot' => '(' . $this->compileConditionList($conditions, $context, 'OR NOT') . ')',
            default => '(' . $this->compileConditionList($conditions, $context, 'AND') . ')',
        };

        return $this->replaceText(['( )', '()'], '', $compiled);
    }

    /**
     * @param list<mixed> $condition
     */
    private function compileAtomicCondition(array $condition, string $context): string
    {
        $count = $this->countElements($condition);

        if ($count === 2 && $this->isString($condition[0])) {
            $normalizedFirst = $this->sql->normalize((string) $condition[0]);

            if ($this->isInArray($normalizedFirst, ['exists', 'notexists'], true)) {
                return $this->compileExistsCondition($normalizedFirst, $condition[1]);
            }

            return $this->compileUnaryCondition((string) $condition[0], (string) $condition[1]);
        }

        if ($count === 3 && $this->isString($condition[0]) && $this->isString($condition[1])) {
            return $this->compileBinaryCondition((string) $condition[0], (string) $condition[1], $condition[2], $context);
        }

        if ($count >= 4 && $this->isString($condition[0]) && $this->isString($condition[1])) {
            return $this->compileRangeCondition(
                (string) $condition[0],
                (string) $condition[1],
                $condition[2],
                $condition[3],
                $context
            );
        }

        return '';
    }

    private function compileUnaryCondition(string $column, string $operator): string
    {
        $left = $this->quoteExpression($column);
        $normalized = $this->normalizeConditionOperator($operator);

        return match ($normalized) {
            'isnull' => $left . ' ' . $this->sql->operator('isNull'),
            'isnotnull' => $left . ' ' . $this->sql->operator('isNotNull'),
            default => throw $this->errorManager->resolveException(
                'database',
                sprintf('Unsupported unary query operator [%s].', $operator)
            ),
        };
    }

    private function compileBinaryCondition(string $column, string $operator, mixed $value, string $context): string
    {
        $left = $this->quoteExpression($column);
        $normalized = $this->normalizeConditionOperator($operator);

        return match ($normalized) {
            'in' => $this->compileInCondition($left, $value, false),
            'notin' => $this->compileInCondition($left, $value, true),
            'like', 'notlike', 'ilike', 'regexp', 'notregexp', 'soundslike', 'similarto', 'notsimilarto',
            'equal', 'notequal', 'notequalalt', 'greaterthan', 'greaterthanorequal', 'lessthan', 'lessthanorequal'
                => $left . ' ' . $this->sql->operator($normalized) . ' ' . $this->compileOperand($value, $context),
            'isdistinctfrom' => $this->compileDistinctCondition($left, $value, false),
            'notdistinctfrom' => $this->compileDistinctCondition($left, $value, true),
            'nullsafeequal' => $this->compileNullSafeEqualCondition($left, $value),
            default => throw $this->errorManager->resolveException(
                'database',
                sprintf('Unsupported binary query operator [%s].', $operator)
            ),
        };
    }

    private function compileRangeCondition(
        string $column,
        string $operator,
        mixed $start,
        mixed $end,
        string $context
    ): string {
        $left = $this->quoteExpression($column);
        $normalized = $this->normalizeConditionOperator($operator);

        return match ($normalized) {
            'between', 'notbetween' => sprintf(
                '%s %s %s AND %s',
                $left,
                $this->sql->operator($normalized),
                $this->compileOperand($start, $context),
                $this->compileOperand($end, $context)
            ),
            default => throw $this->errorManager->resolveException(
                'database',
                sprintf('Unsupported range query operator [%s].', $operator)
            ),
        };
    }

    private function compileExistsCondition(string $operator, mixed $query): string
    {
        return $this->sql->operator($operator) . ' (' . $this->compileSubquery($query) . ')';
    }

    private function compileInCondition(string $left, mixed $value, bool $negated): string
    {
        if ($value instanceof self || $this->isCallable($value) || $this->isString($value)) {
            return sprintf(
                '%s %s (%s)',
                $left,
                $this->sql->operator($negated ? 'notIn' : 'in'),
                $this->compileSubquery($value)
            );
        }

        $values = $this->isArray($value) ? $this->getValuesList($value) : [$value];

        if ($values === []) {
            return $negated ? '1 = 1' : '1 = 0';
        }

        return sprintf(
            '%s %s (%s)',
            $left,
            $this->sql->operator($negated ? 'notIn' : 'in'),
            $this->bindValues($values)
        );
    }

    private function compileNullSafeEqualCondition(string $left, mixed $value): string
    {
        if ($this->getDriver() === 'mysql') {
            return $left . ' ' . $this->sql->operator('nullSafeEqual') . ' ' . $this->bindValue($value);
        }

        $first = $this->bindValue($value);
        $second = $this->bindValue($value);

        return sprintf(
            '(%s = %s OR (%s IS NULL AND %s IS NULL))',
            $left,
            $first,
            $left,
            $second
        );
    }

    private function compileDistinctCondition(string $left, mixed $value, bool $negated): string
    {
        $first = $this->bindValue($value);
        $second = $this->bindValue($value);
        $third = $this->bindValue($value);

        if ($negated) {
            return sprintf(
                '(%s = %s OR (%s IS NULL AND %s IS NULL))',
                $left,
                $first,
                $left,
                $second
            );
        }

        return sprintf(
            '(%s <> %s OR (%s IS NULL AND %s IS NOT NULL) OR (%s IS NOT NULL AND %s IS NULL))',
            $left,
            $first,
            $left,
            $second,
            $left,
            $third
        );
    }

    private function compileOperand(mixed $value, string $context): string
    {
        if ($this->isColumnOperand($value, $context)) {
            return $this->quoteIdentifier($this->extractColumnOperand($value));
        }

        return $this->bindValue($value);
    }

    private function isColumnOperand(mixed $value, string $context): bool
    {
        if ($this->isArray($value) && isset($value['column']) && $this->isString($value['column'])) {
            return true;
        }

        if ($this->isArray($value) && isset($value['identifier']) && $this->isString($value['identifier'])) {
            return true;
        }

        return $context === 'join'
            && $this->isString($value)
            && ($this->isSimpleIdentifier($value) || $this->isQualifiedIdentifier($value));
    }

    private function extractColumnOperand(mixed $value): string
    {
        if ($this->isArray($value)) {
            return (string) ($value['column'] ?? $value['identifier'] ?? '');
        }

        return (string) $value;
    }

    private function compileJoinClause(array $joins): string
    {
        $compiled = [];

        foreach ($joins as $join) {
            $segment = $this->sql->clause($join['type'])
                . ' ' . $this->quoteIdentifier($join['table']);

            $onClause = $this->compileConditionList($join['on'], 'join');

            if ($onClause !== '') {
                $segment .= ' ' . $this->sql->clause('on') . ' ' . $onClause;
            }

            $compiled[] = $segment;
        }

        return $compiled === [] ? '' : $this->implodeWith(' ', $compiled);
    }

    /**
     * @param list<array<string, mixed>> $specialConditions
     * @return list<string>
     */
    private function compileSpecialConditions(array $specialConditions): array
    {
        $compiled = [];

        foreach ($specialConditions as $specialCondition) {
            $type = $this->sql->normalize((string) ($specialCondition['type'] ?? ''));
            $column = $specialCondition['column'] ?? null;
            $value = $specialCondition['value'] ?? null;

            if ($type === '' || !$this->isString($column)) {
                continue;
            }

            $left = $this->quoteExpression($column);

            $compiled[] = match ($type) {
                'connectby' => $this->sql->clause('connectBy') . ' ' . $left . ' = ' . $this->bindValue($value),
                'startwith' => $this->sql->clause('startWith') . ' ' . $left . ' = ' . $this->bindValue($value),
                'connectbyprior' => $this->sql->clause('connectBy') . ' PRIOR ' . $left . ' = ' . $this->bindValue($value),
                'prior' => 'PRIOR ' . $left . ' = ' . $this->bindValue($value),
                'withrecursive' => 'WITH RECURSIVE ' . $left . ' = ' . $this->bindValue($value),
                'overlaps' => $left . ' ' . $this->sql->clause('overlaps') . ' ' . $this->bindValue($value),
                default => '',
            };
        }

        return $this->getValuesList($this->filter($compiled, fn(string $fragment): bool => $fragment !== ''));
    }

    private function compileGroupClause(): string
    {
        if ($this->groupings === []) {
            return '';
        }

        $segments = [];

        foreach ($this->groupings as $grouping) {
            $type = $grouping['type'] ?? 'group';

            $segments[] = match ($type) {
                'group' => $this->compileColumnList($grouping['columns'] ?? []),
                'groupingSets' => 'GROUPING SETS (' . $this->implodeWith(', ', $this->getValuesList((array) ($grouping['sets'] ?? []))) . ')',
                'cube' => 'CUBE (' . $this->compileColumnList($grouping['columns'] ?? []) . ')',
                'rollup' => 'ROLLUP (' . $this->compileColumnList($grouping['columns'] ?? []) . ')',
                default => '',
            };
        }

        $segments = $this->getValuesList($this->filter($segments, fn(string $segment): bool => $segment !== ''));

        return $segments === [] ? '' : $this->sql->clause('groupBy') . ' ' . $this->implodeWith(', ', $segments);
    }

    private function compileOrderClause(): string
    {
        if ($this->orderings === []) {
            return '';
        }

        $segments = [];

        foreach ($this->orderings as $ordering) {
            $segments[] = $this->quoteExpression((string) $ordering['column']) . ' '
                . $this->normalizeDirection((string) ($ordering['direction'] ?? 'ASC'));
        }

        return $segments === [] ? '' : $this->sql->clause('orderBy') . ' ' . $this->implodeWith(', ', $segments);
    }

    private function compilePaginationClause(): string
    {
        $limit = $this->limitValue;
        $offset = $this->offsetValue ?? 0;

        if ($limit === null && $offset === 0) {
            return '';
        }

        if ($this->getDriver() === 'sqlsrv') {
            $order = $this->compileOrderClause();
            $segments = [];

            if ($order === '') {
                $segments[] = $this->sql->clause('orderBy') . ' (SELECT 1)';
            }

            $segments[] = 'OFFSET ' . max(0, $offset) . ' ROWS';

            if ($limit !== null) {
                $segments[] = 'FETCH NEXT ' . $limit . ' ROWS ONLY';
            }

            return $this->implodeWith(' ', $segments);
        }

        $segments = [];

        if ($limit !== null) {
            $segments[] = $this->sql->clause('limit') . ' ' . $limit;
        }

        if ($offset > 0) {
            $segments[] = $this->sql->clause('offset') . ' ' . $offset;
        }

        return $this->implodeWith(' ', $segments);
    }

    private function compileSetClause(): string
    {
        if ($this->setOperations === []) {
            return '';
        }

        $segments = [];

        foreach ($this->setOperations as $setOperation) {
            $segments[] = $this->sql->clause($setOperation['type'])
                . ' (' . $this->compileSubquery($setOperation['query']) . ')';
        }

        return $this->implodeWith(' ', $segments);
    }

    private function compileWithClause(): string
    {
        if ($this->applyClauses === []) {
            return '';
        }

        $segments = [];

        foreach ($this->applyClauses as $clause) {
            $segments[] = $this->quoteIdentifier($clause['alias'])
                . ' AS (' . $this->compileSubquery($clause['query']) . ')';
        }

        return $segments === [] ? '' : $this->sql->clause('with') . ' ' . $this->implodeWith(', ', $segments);
    }

    private function compileReturningClause(): string
    {
        return $this->returningColumns === []
            ? ''
            : $this->sql->clause('returning') . ' ' . $this->compileColumnList($this->returningColumns);
    }

    private function compileColumnList(array $columns): string
    {
        $columns = $columns === [] ? ['*'] : $this->getValuesList($columns);

        return $this->implodeWith(
            ', ',
            $this->map(fn(string $column): string => $this->quoteColumnExpression($column), $columns)
        );
    }

    private function compileIdentifierList(array $identifiers): string
    {
        return $this->implodeWith(
            ', ',
            $this->map(fn(string $identifier): string => $this->quoteIdentifier($identifier), $this->getValuesList($identifiers))
        );
    }

    private function compileSubquery(mixed $query): string
    {
        if ($query instanceof self) {
            $compiled = $query->toExecutable();
            $this->mergeBindings($compiled['bindings']);

            return $compiled['sql'];
        }

        if ($this->isCallable($query)) {
            $builder = new self($this->sql, $this->errorManager, '', [], [], $this->getDriver());
            $result = $query($builder);

            if ($result instanceof self) {
                $builder = $result;
            }

            $compiled = $builder->toExecutable();
            $this->mergeBindings($compiled['bindings']);

            return $compiled['sql'];
        }

        if ($this->isString($query) && $this->trimString($query) !== '') {
            return $this->trimString($query);
        }

        throw $this->errorManager->resolveException('database', 'Invalid subquery provided to query builder.');
    }

    private function normalizeConditionOperator(string $operator): string
    {
        $trimmed = $this->trimString($operator);

        return match ($trimmed) {
            '=' => 'equal',
            '!=' => 'notequal',
            '<>' => 'notequalalt',
            '>' => 'greaterthan',
            '>=' => 'greaterthanorequal',
            '<' => 'lessthan',
            '<=' => 'lessthanorequal',
            default => $this->sql->normalize($trimmed),
        };
    }
}
