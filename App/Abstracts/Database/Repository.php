<?php

declare(strict_types=1);

namespace App\Abstracts\Database;

use App\Contracts\Database\ModelInterface;
use App\Contracts\Database\RepositoryInterface;
use App\Core\Database;
use App\Exceptions\Database\RepositoryException;
use App\Utilities\Traits\{
    ArrayTrait,
    ManipulationTrait,
    TypeCheckerTrait
};
use App\Utilities\Traits\Patterns\PatternTrait;

/**
 * Generic repository with safe CRUD, criteria handling, and model hydration.
 */
abstract class Repository implements RepositoryInterface
{
    use TypeCheckerTrait;
    use ArrayTrait, ManipulationTrait, PatternTrait {
        ManipulationTrait::toLower as private toLowerString;
        ManipulationTrait::toUpper as private toUpperString;
        PatternTrait::match as private matchPattern;
    }

    /**
     * Fully-qualified model class handled by the repository.
     *
     * @var class-string<ModelInterface>
     */
    protected string $modelClass;

    public function __construct(protected Database $db)
    {
        $this->assertModelClass();
    }

    public function getTable(): string
    {
        return $this->newEmptyModel()->getTable();
    }

    public function mapRowToModel(array $row): ModelInterface
    {
        $model = $this->newEmptyModel();
        $model->forceFill($row);
        $model->markAsExisting();
        $model->syncOriginal();

        return $model;
    }

    public function find(mixed $id): ?ModelInterface
    {
        $query = $this->db
            ->dataQuery($this->getTable())
            ->select(['*'])
            ->where($this->getPrimaryKey(), '=', $id)
            ->limit(1)
            ->toExecutable();

        $row = $this->db->fetchOne($query['sql'], $query['bindings']);

        return $row !== null ? $this->mapRowToModel($row) : null;
    }

    public function all(): array
    {
        $query = $this->db
            ->dataQuery($this->getTable())
            ->select(['*'])
            ->orderBy($this->getPrimaryKey())
            ->toExecutable();

        return $this->hydrateMany($this->db->fetchAll($query['sql'], $query['bindings']));
    }

    public function paginate(int $perPage = 15, int $page = 1): array
    {
        if ($perPage < 1 || $page < 1) {
            throw new RepositoryException('Pagination values must be positive integers.');
        }

        $offset = ($page - 1) * $perPage;
        $countQuery = $this->db
            ->dataQuery($this->getTable())
            ->select(['COUNT(*) AS aggregate'])
            ->toExecutable();

        $dataQuery = $this->db
            ->dataQuery($this->getTable())
            ->select(['*'])
            ->orderBy($this->getPrimaryKey())
            ->limit($perPage)
            ->offset($offset)
            ->toExecutable();

        $total = (int) $this->db->fetchColumn($countQuery['sql'], $countQuery['bindings']);
        $rows = $this->db->fetchAll($dataQuery['sql'], $dataQuery['bindings']);

        return [
            'data' => $this->hydrateMany($rows),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function create(array $data): ModelInterface
    {
        $model = $this->newModel($data);

        return $this->persistNewModel($model);
    }

    public function update(mixed $id, array $data): bool
    {
        $existing = $this->find($id);

        if ($existing === null) {
            return false;
        }

        $existing->fill($data);
        $this->persistExistingModel($existing);

        return true;
    }

    public function delete(mixed $id): bool
    {
        $query = $this->db
            ->dataQuery($this->getTable())
            ->delete($this->getTable())
            ->where($this->getPrimaryKey(), '=', $id)
            ->toExecutable();

        return $this->db->execute($query['sql'], $query['bindings']) > 0;
    }

    public function findBy(array $criteria): array
    {
        $query = $this->applyCriteriaToQuery(
            $this->db->dataQuery($this->getTable())->select(['*']),
            $criteria
        )->orderBy($this->getPrimaryKey())->toExecutable();

        return $this->hydrateMany($this->db->fetchAll($query['sql'], $query['bindings']));
    }

    public function findOneBy(array $criteria): ?ModelInterface
    {
        $query = $this->applyCriteriaToQuery(
            $this->db->dataQuery($this->getTable())->select(['*']),
            $criteria
        )->orderBy($this->getPrimaryKey())->limit(1)->toExecutable();

        $row = $this->db->fetchOne($query['sql'], $query['bindings']);

        return $row !== null ? $this->mapRowToModel($row) : null;
    }

    public function count(array $criteria): int
    {
        $query = $this->applyCriteriaToQuery(
            $this->db->dataQuery($this->getTable())->select(['COUNT(*) AS aggregate']),
            $criteria
        )->toExecutable();

        return (int) $this->db->fetchColumn($query['sql'], $query['bindings']);
    }

    public function save(ModelInterface $model): ModelInterface
    {
        $this->assertRepositoryModel($model);

        return $model->exists()
            ? $this->persistExistingModel($model)
            : $this->persistNewModel($model);
    }

    public function deleteModel(ModelInterface $model): bool
    {
        $this->assertRepositoryModel($model);
        $key = $model->getKey();

        if ($key === null) {
            throw new RepositoryException('Cannot delete a model without a primary key value.');
        }

        $deleted = $this->delete($key);

        if ($deleted) {
            $model->markAsExisting(false);
        }

        return $deleted;
    }

    protected function newModel(array $attributes = []): ModelInterface
    {
        $model = $this->newEmptyModel();
        $model->fill($attributes);

        return $model;
    }

    protected function persistNewModel(ModelInterface $model): ModelInterface
    {
        $attributes = $this->prepareAttributesForCreate($model);

        if ($attributes === []) {
            throw new RepositoryException('Cannot create a model without any persistable attributes.');
        }

        $query = $this->db
            ->dataQuery($this->getTable())
            ->insert($this->getTable(), $attributes)
            ->toExecutable();

        $this->db->execute($query['sql'], $query['bindings']);

        $primaryKey = $this->getPrimaryKey();

        if ($model->getAttribute($primaryKey) === null) {
            $insertedId = $this->db->lastInsertId();

            if ($insertedId !== '') {
                $model->setAttribute($primaryKey, $this->isNumeric($insertedId) ? (int) $insertedId : $insertedId);
            }
        }

        $fresh = $model->getAttribute($primaryKey) !== null
            ? $this->find($model->getAttribute($primaryKey))
            : null;

        if ($fresh !== null) {
            return $fresh;
        }

        $model->markAsExisting();
        $model->syncOriginal();

        return $model;
    }

    protected function persistExistingModel(ModelInterface $model): ModelInterface
    {
        $dirty = $model->getDirty();
        $primaryKey = $this->getPrimaryKey();

        if ($this->keyExists($dirty, $primaryKey)) {
            throw new RepositoryException('Updating the primary key of an existing model is not supported.');
        }

        if ($dirty === []) {
            return $model;
        }

        $key = $model->getAttribute($primaryKey);

        if ($key === null) {
            throw new RepositoryException('Cannot update a model without a primary key value.');
        }

        if ($model->usesTimestamps()) {
            $updatedAtColumn = $model->getUpdatedAtColumn();
            $timestamp = $this->freshTimestamp();
            $model->setAttribute($updatedAtColumn, $timestamp);
            $dirty[$updatedAtColumn] = $timestamp;
        }

        $query = $this->db
            ->dataQuery($this->getTable())
            ->update($this->getTable(), $dirty)
            ->where($primaryKey, '=', $key)
            ->toExecutable();

        $updated = $this->db->execute($query['sql'], $query['bindings']);

        if ($updated === 0 && $this->find($key) === null) {
            throw new RepositoryException(
                sprintf('Unable to update [%s] because the record no longer exists.', static::class)
            );
        }

        $fresh = $this->find($key);

        if ($fresh !== null) {
            return $fresh;
        }

        $model->syncOriginal();

        return $model;
    }

    protected function applyCriteriaToQuery(\App\Utilities\Query\DataQuery $query, array $criteria): \App\Utilities\Query\DataQuery
    {
        foreach ($criteria as $column => $value) {
            $name = (string) $column;

            if ($value === null) {
                $query->whereNull($name);
                continue;
            }

            if ($this->isArray($value) && $this->isAssociative($value)) {
                foreach ($value as $operator => $operand) {
                    $normalized = $this->toLowerString($this->trimString((string) $operator));

                    match ($normalized) {
                        'is' => $operand === null
                            ? $query->whereNull($name)
                            : $query->where($name, '=', $operand),
                        'is not' => $operand === null
                            ? $query->whereNotNull($name)
                            : $query->where($name, '!=', $operand),
                        'in' => $query->in($name, (array) $operand),
                        'not in' => $query->notIn($name, (array) $operand),
                        default => $query->where($name, (string) $operator, $operand),
                    };
                }

                continue;
            }

            if ($this->isArray($value)) {
                $query->in($name, $value);
                continue;
            }

            $query->where($name, '=', $value);
        }

        return $query;
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    protected function compileCriteria(array $criteria): array
    {
        if ($criteria === []) {
            return ['', []];
        }

        $clauses = [];
        $params = [];

        foreach ($criteria as $column => $value) {
            $qualifiedColumn = $this->qualifyIdentifier((string) $column);

            if ($value === null) {
                $clauses[] = $qualifiedColumn . ' IS NULL';
                continue;
            }

            if ($this->isArray($value) && $this->isAssociative($value)) {
                foreach ($value as $operator => $operand) {
                    [$clause, $clauseParams] = $this->compileOperatorCriterion(
                        $qualifiedColumn,
                        (string) $operator,
                        $operand
                    );
                    $clauses[] = $clause;
                    array_push($params, ...$clauseParams);
                }

                continue;
            }

            if ($this->isArray($value)) {
                [$clause, $clauseParams] = $this->compileInCriterion($qualifiedColumn, $value, false);
                $clauses[] = $clause;
                array_push($params, ...$clauseParams);
                continue;
            }

            $clauses[] = $qualifiedColumn . ' = ?';
            $params[] = $value;
        }

        return [' WHERE ' . implode(' AND ', $clauses), $params];
    }

    protected function getPrimaryKey(): string
    {
        return $this->newEmptyModel()->getPrimaryKey();
    }

    /**
     * @return array<string, mixed>
     */
    protected function prepareAttributesForCreate(ModelInterface $model): array
    {
        $attributes = $model->getAttributes();

        if ($model->usesTimestamps()) {
            $timestamp = $this->freshTimestamp();
            $createdAtColumn = $model->getCreatedAtColumn();
            $updatedAtColumn = $model->getUpdatedAtColumn();

            if (!$this->keyExists($attributes, $createdAtColumn)) {
                $model->setAttribute($createdAtColumn, $timestamp);
                $attributes[$createdAtColumn] = $timestamp;
            }

            if (!$this->keyExists($attributes, $updatedAtColumn)) {
                $model->setAttribute($updatedAtColumn, $timestamp);
                $attributes[$updatedAtColumn] = $timestamp;
            }
        }

        return $attributes;
    }

    protected function freshTimestamp(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    /**
     * @return array{0: list<string>, 1: list<mixed>}
     */
    protected function extractColumnsAndParams(array $attributes): array
    {
        $columns = [];
        $params = [];

        foreach ($attributes as $column => $value) {
            $columns[] = (string) $column;
            $params[] = $value;
        }

        return [$columns, $params];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return ModelInterface[]
     */
    protected function hydrateMany(array $rows): array
    {
        return $this->map(fn(array $row): ModelInterface => $this->mapRowToModel($row), $rows);
    }

    /**
     * @return ModelInterface
     */
    protected function newEmptyModel(): ModelInterface
    {
        $class = $this->modelClass;

        return new $class();
    }

    protected function qualifyIdentifier(string $identifier): string
    {
        if ($this->matchPattern('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier) !== 1) {
            throw new RepositoryException(sprintf('Invalid SQL identifier [%s].', $identifier));
        }

        $driver = $this->toLowerString((string) $this->db->getAttribute('driverName'));

        return match ($driver) {
            'pgsql', 'sqlite' => '"' . $identifier . '"',
            'sqlsrv' => '[' . $identifier . ']',
            default => '`' . $identifier . '`',
        };
    }

    protected function assertModelClass(): void
    {
        if (!isset($this->modelClass) || $this->modelClass === '') {
            throw new RepositoryException(
                sprintf('Repository [%s] must define a model class.', static::class)
            );
        }

        if (!$this->isSubclassOf($this->modelClass, ModelInterface::class)) {
            throw new RepositoryException(
                sprintf(
                    'Repository [%s] model class [%s] must implement [%s].',
                    static::class,
                    $this->modelClass,
                    ModelInterface::class
                )
            );
        }
    }

    protected function assertRepositoryModel(ModelInterface $model): void
    {
        if (!$model instanceof $this->modelClass) {
            throw new RepositoryException(
                sprintf(
                    'Repository [%s] cannot persist model [%s]. Expected [%s].',
                    static::class,
                    $model::class,
                    $this->modelClass
                )
            );
        }
    }

    /**
     * @param array<int|string, mixed> $value
     */
    private function isAssociative(array $value): bool
    {
        if ($value === []) {
            return false;
        }

        return !$this->isList($value);
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function compileOperatorCriterion(string $column, string $operator, mixed $operand): array
    {
        $normalized = $this->toLowerString($this->trimString($operator));

        return match ($normalized) {
            '=', '!=', '<>', '>', '>=', '<', '<=', 'like', 'not like' => [
                $column . ' ' . $this->toUpperString($normalized) . ' ?',
                [$operand],
            ],
            'in' => $this->compileInCriterion($column, (array) $operand, false),
            'not in' => $this->compileInCriterion($column, (array) $operand, true),
            'is' => [$column . ' IS ' . $this->normalizeIsOperand($operand), []],
            'is not' => [$column . ' IS NOT ' . $this->normalizeIsOperand($operand), []],
            default => throw new RepositoryException(sprintf('Unsupported criteria operator [%s].', $operator)),
        };
    }

    /**
     * @param array<int, mixed> $values
     * @return array{0: string, 1: list<mixed>}
     */
    private function compileInCriterion(string $column, array $values, bool $negated): array
    {
        if ($values === []) {
            return [$negated ? '1 = 1' : '1 = 0', []];
        }

        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        return [
            sprintf('%s %s (%s)', $column, $negated ? 'NOT IN' : 'IN', $placeholders),
            $this->getValues($values),
        ];
    }

    private function normalizeIsOperand(mixed $operand): string
    {
        return match (true) {
            $operand === null => 'NULL',
            $operand === true => 'TRUE',
            $operand === false => 'FALSE',
            $this->isString($operand) && $this->isInArray($this->toUpperString($operand), ['NULL', 'TRUE', 'FALSE'], true) => $this->toUpperString($operand),
            default => throw new RepositoryException('The IS operator only supports NULL and boolean operands.'),
        };
    }
}
