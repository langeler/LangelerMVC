<?php

declare(strict_types=1);

namespace App\Utilities\Traits\Query;

/**
 * Fluent API surface for the data query builder.
 *
 * The trait intentionally keeps the API expressive while delegating all
 * normalization and compilation to the concrete DataQuery implementation.
 */
trait DataQueryTrait
{
    private function chainCondition(array $condition, string $target = 'where'): self
    {
        $this->processConditions([$condition], $target);

        return $this;
    }

    private function chainConditionGroup(string $logic, array $conditions, string $target = 'where'): self
    {
        $this->processConditions([[strtolower($logic) => $conditions]], $target);

        return $this;
    }

    private function chainJoin(string $type, string $table, array $onConditions = [], array $columns = []): self
    {
        $this->processJoins([[
            'type' => $type,
            'table' => $table,
            'on' => $onConditions,
            'cols' => $columns,
        ]]);

        return $this;
    }

    public function from(string $table): self
    {
        $this->setTable($table);

        return $this;
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        return $this->chainCondition([$column, $operator, $value], 'where');
    }

    public function having(string $column, string $operator, mixed $value): self
    {
        return $this->chainCondition([$column, $operator, $value], 'having');
    }

    public function on(string $column, string $operator, mixed $value): self
    {
        return $this->chainCondition([$column, $operator, $value], 'on');
    }

    public function whereFilter(string $column, string $operator, mixed $value): self
    {
        return $this->chainCondition([$column, $operator, $value], 'where');
    }

    public function fetch(int $rowCount): self
    {
        $this->processPagination($rowCount, 0);

        return $this;
    }

    public function returning(array $columns): self
    {
        $this->processReturning($columns);

        return $this;
    }

    public function with(string $queryAlias, callable|string|object $subquery): self
    {
        $this->processApplyClauses([[
            'alias' => $queryAlias,
            'query' => $subquery,
        ]]);

        return $this;
    }

    public function not(array $conditions): self
    {
        return $this->chainConditionGroup('not', $conditions);
    }

    public function and(array $conditions): self
    {
        return $this->chainConditionGroup('and', $conditions);
    }

    public function or(array $conditions): self
    {
        return $this->chainConditionGroup('or', $conditions);
    }

    public function xor(array $conditions): self
    {
        return $this->chainConditionGroup('xor', $conditions);
    }

    public function andNot(array $conditions): self
    {
        return $this->chainConditionGroup('andNot', $conditions);
    }

    public function orNot(array $conditions): self
    {
        return $this->chainConditionGroup('orNot', $conditions);
    }

    public function allOf(array $conditions): self
    {
        return $this->chainConditionGroup('all', $conditions);
    }

    public function anyOf(array $conditions): self
    {
        return $this->chainConditionGroup('any', $conditions);
    }

    public function equal(string $column, mixed $value): self
    {
        return $this->chainCondition([$column, 'equal', $value]);
    }

    public function notEqual(string $column, mixed $value): self
    {
        return $this->chainCondition([$column, 'notEqual', $value]);
    }

    public function notEqualAlt(string $column, mixed $value): self
    {
        return $this->chainCondition([$column, 'notEqualAlt', $value]);
    }

    public function nullSafeEqual(string $column, mixed $value): self
    {
        return $this->chainCondition([$column, 'nullSafeEqual', $value]);
    }

    public function greaterThan(string $column, mixed $value): self
    {
        return $this->chainCondition([$column, 'greaterThan', $value]);
    }

    public function greaterThanOrEqual(string $column, mixed $value): self
    {
        return $this->chainCondition([$column, 'greaterThanOrEqual', $value]);
    }

    public function lessThan(string $column, mixed $value): self
    {
        return $this->chainCondition([$column, 'lessThan', $value]);
    }

    public function lessThanOrEqual(string $column, mixed $value): self
    {
        return $this->chainCondition([$column, 'lessThanOrEqual', $value]);
    }

    public function isDistinctFrom(string $column, mixed $value): self
    {
        return $this->chainCondition([$column, 'isDistinctFrom', $value]);
    }

    public function notDistinctFrom(string $column, mixed $value): self
    {
        return $this->chainCondition([$column, 'notDistinctFrom', $value]);
    }

    public function whereNull(string $column): self
    {
        return $this->chainCondition([$column, 'isNull']);
    }

    public function whereNotNull(string $column): self
    {
        return $this->chainCondition([$column, 'isNotNull']);
    }

    public function in(string $column, array $values): self
    {
        return $this->chainCondition([$column, 'in', $values]);
    }

    public function notIn(string $column, array $values): self
    {
        return $this->chainCondition([$column, 'notIn', $values]);
    }

    public function exists(callable|string|object $subquery): self
    {
        return $this->chainCondition(['exists', $subquery]);
    }

    public function notExists(callable|string|object $subquery): self
    {
        return $this->chainCondition(['notExists', $subquery]);
    }

    public function except(callable|string|object $query): self
    {
        $this->processSet([[
            'type' => 'except',
            'query' => $query,
        ]]);

        return $this;
    }

    public function intersectWith(callable|string|object $query): self
    {
        $this->processSet([[
            'type' => 'intersect',
            'query' => $query,
        ]]);

        return $this;
    }

    public function minus(callable|string|object $query): self
    {
        $this->processSet([[
            'type' => 'minus',
            'query' => $query,
        ]]);

        return $this;
    }

    public function union(callable|string|object $query): self
    {
        $this->processSet([[
            'type' => 'union',
            'query' => $query,
        ]]);

        return $this;
    }

    public function unionAll(callable|string|object $query): self
    {
        $this->processSet([[
            'type' => 'unionAll',
            'query' => $query,
        ]]);

        return $this;
    }

    public function between(string $column, mixed $start, mixed $end): self
    {
        return $this->chainCondition([$column, 'between', $start, $end]);
    }

    public function notBetween(string $column, mixed $start, mixed $end): self
    {
        return $this->chainCondition([$column, 'notBetween', $start, $end]);
    }

    public function like(string $column, string $pattern): self
    {
        return $this->chainCondition([$column, 'like', $pattern]);
    }

    public function notLike(string $column, string $pattern): self
    {
        return $this->chainCondition([$column, 'notLike', $pattern]);
    }

    public function iLike(string $column, string $pattern): self
    {
        return $this->chainCondition([$column, 'iLike', $pattern]);
    }

    public function regexp(string $column, string $pattern): self
    {
        return $this->chainCondition([$column, 'regexp', $pattern]);
    }

    public function notRegexp(string $column, string $pattern): self
    {
        return $this->chainCondition([$column, 'notRegexp', $pattern]);
    }

    public function whereSoundsLike(string $column, string $pattern): self
    {
        return $this->chainCondition([$column, 'soundsLike', $pattern]);
    }

    public function similarTo(string $column, string $pattern): self
    {
        return $this->chainCondition([$column, 'similarTo', $pattern]);
    }

    public function notSimilarTo(string $column, string $pattern): self
    {
        return $this->chainCondition([$column, 'notSimilarTo', $pattern]);
    }

    public function connectBy(string $column, mixed $value): self
    {
        $this->processSpecialConditions([['type' => 'connectBy', 'column' => $column, 'value' => $value]]);

        return $this;
    }

    public function startWith(string $column, mixed $value): self
    {
        $this->processSpecialConditions([['type' => 'startWith', 'column' => $column, 'value' => $value]]);

        return $this;
    }

    public function connectByPrior(string $column, mixed $value): self
    {
        $this->processSpecialConditions([['type' => 'connectByPrior', 'column' => $column, 'value' => $value]]);

        return $this;
    }

    public function prior(string $column, mixed $value): self
    {
        $this->processSpecialConditions([['type' => 'prior', 'column' => $column, 'value' => $value]]);

        return $this;
    }

    public function withRecursive(string $column, mixed $value): self
    {
        $this->processSpecialConditions([['type' => 'withRecursive', 'column' => $column, 'value' => $value]]);

        return $this;
    }

    public function distinctOn(array $columns): self
    {
        $this->processSpecialConditions([['type' => 'distinctOn', 'columns' => $columns]]);

        return $this;
    }

    public function overlaps(string $column, string $operator, mixed $value): self
    {
        $this->processSpecialConditions([[
            'type' => 'overlaps',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ]]);

        return $this;
    }

    public function forUpdate(): self
    {
        $this->processLocking(['FOR UPDATE']);

        return $this;
    }

    public function forShare(): self
    {
        $this->processLocking(['FOR SHARE']);

        return $this;
    }

    public function joinTable(string $table, array $onConditions, array $columns = []): self
    {
        return $this->chainJoin('join', $table, $onConditions, $columns);
    }

    public function fullOuterJoin(string $table, array $onConditions, array $columns = []): self
    {
        return $this->chainJoin('fullOuterJoin', $table, $onConditions, $columns);
    }

    public function leftJoin(string $table, array $onConditions, array $columns = []): self
    {
        return $this->chainJoin('leftJoin', $table, $onConditions, $columns);
    }

    public function rightJoin(string $table, array $onConditions, array $columns = []): self
    {
        return $this->chainJoin('rightJoin', $table, $onConditions, $columns);
    }

    public function innerJoin(string $table, array $onConditions, array $columns = []): self
    {
        return $this->chainJoin('innerJoin', $table, $onConditions, $columns);
    }

    public function crossJoin(string $table, array $onConditions = [], array $columns = []): self
    {
        return $this->chainJoin('crossJoin', $table, $onConditions, $columns);
    }

    public function naturalJoin(string $table, array $columns = []): self
    {
        return $this->chainJoin('naturalJoin', $table, [], $columns);
    }

    public function fullJoin(string $table, array $onConditions, array $columns = []): self
    {
        return $this->chainJoin('fullJoin', $table, $onConditions, $columns);
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->processOrdering([[
            'type' => 'order',
            'column' => $column,
            'direction' => $direction,
        ]]);

        return $this;
    }

    public function groupBy(array $columns): self
    {
        $this->processOrdering([[
            'type' => 'group',
            'columns' => $columns,
        ]]);

        return $this;
    }

    public function groupingSets(array $sets): self
    {
        $this->processOrdering([[
            'type' => 'groupingSets',
            'sets' => $sets,
        ]]);

        return $this;
    }

    public function cube(array $columns): self
    {
        $this->processOrdering([[
            'type' => 'cube',
            'columns' => $columns,
        ]]);

        return $this;
    }

    public function rollup(array $columns): self
    {
        $this->processOrdering([[
            'type' => 'rollup',
            'columns' => $columns,
        ]]);

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->processPagination($limit, 0);

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->processPagination(0, $offset);

        return $this;
    }

    public function insert(string $table, array $data): self
    {
        $this->setTable($table);
        $this->processInsert($data);

        return $this;
    }

    public function select(array $columns = ['*']): self
    {
        $this->processSelect($columns);

        return $this;
    }

    public function update(string $table, array $data): self
    {
        $this->setTable($table);
        $this->processUpdate($data);

        return $this;
    }

    public function delete(string $table): self
    {
        $this->setTable($table);
        $this->processDelete();

        return $this;
    }
}
