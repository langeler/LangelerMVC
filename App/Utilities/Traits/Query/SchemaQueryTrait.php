<?php

namespace App\Utilities\Traits\Query;

trait SchemaQueryTrait
{
    private function chainSchema(callable $callback): self
    {
        $callback();

        return $this;
    }

    public function addColumn(string $table, string $column, string $definition): self
    {
        return $this->chainSchema(fn() => $this->processColumn($table, $column, $definition));
    }

    public function removeColumn(string $table, string $column): self
    {
        return $this->chainSchema(fn() => $this->processColumn($table, $column));
    }

    public function renameColumn(string $table, string $oldName, string $newName): self
    {
        return $this->chainSchema(fn() => $this->processColumn($table, $oldName, $newName));
    }

    public function modifyColumn(string $table, string $column, string $newDefinition): self
    {
        return $this->chainSchema(fn() => $this->processColumn($table, $column, $newDefinition));
    }

    public function createTable(string $table, array $columns, array $constraints = []): self
    {
        return $this->chainSchema(fn() => $this->processTable('create', $table, $columns, $constraints));
    }

    public function renameTable(string $oldName, string $newName): self
    {
        return $this->chainSchema(fn() => $this->processTable('rename', $oldName, $newName));
    }

    public function truncateTable(string $table): self
    {
        return $this->chainSchema(fn() => $this->processTable('truncate', $table));
    }

    public function dropTable(string $table): self
    {
        return $this->chainSchema(fn() => $this->processTable('drop', $table));
    }

    public function alterTable(string $table, array $options): self
    {
        return $this->chainSchema(fn() => $this->processTable('alter', $table, $options));
    }

    public function addIndex(string $table, string $indexName, array $columns, string $indexType = ''): self
    {
        return $this->chainSchema(
            fn() => $this->processStructure('index', 'create', $table, $indexName, $columns, $indexType)
        );
    }

    public function dropIndex(string $table, string $indexName): self
    {
        return $this->chainSchema(fn() => $this->processStructure('index', 'drop', $table, $indexName));
    }

    public function renameIndex(string $table, string $oldIndexName, string $newIndexName): self
    {
        return $this->chainSchema(
            fn() => $this->processStructure('index', 'rename', $table, $oldIndexName, $newIndexName)
        );
    }

    public function setConstraint(string $table, string $constraint, array $definition): self
    {
        return $this->chainSchema(
            fn() => $this->processStructure('constraint', 'create', $table, $constraint, $definition)
        );
    }

    public function dropConstraint(string $table, string $constraint): self
    {
        return $this->chainSchema(fn() => $this->processStructure('constraint', 'drop', $table, $constraint));
    }

    public function renameConstraint(string $table, string $oldConstraint, string $newConstraint): self
    {
        return $this->chainSchema(
            fn() => $this->processStructure('constraint', 'rename', $table, $oldConstraint, $newConstraint)
        );
    }

    public function setUnique(string $table, array $columns): self
    {
        return $this->chainSchema(fn() => $this->processStructure('unique', 'create', $table, $columns));
    }

    public function dropUnique(string $table, string $constraint): self
    {
        return $this->chainSchema(fn() => $this->processStructure('unique', 'drop', $table, $constraint));
    }

    public function setCheck(string $table, string $constraint, string $condition): self
    {
        return $this->chainSchema(
            fn() => $this->processStructure('check', 'create', $table, $constraint, $condition)
        );
    }

    public function dropCheck(string $table, string $constraint): self
    {
        return $this->chainSchema(fn() => $this->processStructure('check', 'drop', $table, $constraint));
    }

    public function setForeign(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn,
        string $onDelete = 'RESTRICT',
        string $onUpdate = 'RESTRICT'
    ): self {
        return $this->chainSchema(
            fn() => $this->processStructure(
                'foreignKey',
                'create',
                $table,
                $column,
                $referencedTable,
                $referencedColumn,
                $onDelete,
                $onUpdate
            )
        );
    }

    public function dropForeign(string $table, string $foreignKeyName): self
    {
        return $this->chainSchema(fn() => $this->processStructure('foreignKey', 'drop', $table, $foreignKeyName));
    }

    public function addPrimary(string $table, array $columns): self
    {
        return $this->chainSchema(fn() => $this->processStructure('primaryKey', 'create', $table, $columns));
    }

    public function dropPrimary(string $table): self
    {
        return $this->chainSchema(fn() => $this->processStructure('primaryKey', 'drop', $table));
    }

    public function setDefault(string $table, string $column, mixed $defaultValue): self
    {
        return $this->chainSchema(fn() => $this->processColumn($table, $column, (string) $defaultValue));
    }

    public function dropDefault(string $table, string $column): self
    {
        return $this->chainSchema(fn() => $this->processColumn($table, $column));
    }

    public function createView(string $viewName, string $selectQuery): self
    {
        return $this->chainSchema(fn() => $this->processView('create', $viewName, $selectQuery));
    }

    public function dropView(string $viewName): self
    {
        return $this->chainSchema(fn() => $this->processView('drop', $viewName));
    }

    public function alterView(string $viewName, string $selectQuery): self
    {
        return $this->chainSchema(fn() => $this->processView('alter', $viewName, $selectQuery));
    }

    public function createTrigger(
        string $triggerName,
        string $table,
        string $timing,
        string $event,
        string $statement
    ): self {
        return $this->chainSchema(
            fn() => $this->processTrigger('create', $triggerName, $table, $timing, $event, $statement)
        );
    }

    public function dropTrigger(string $triggerName): self
    {
        return $this->chainSchema(fn() => $this->processTrigger('drop', $triggerName));
    }

    public function alterTrigger(
        string $triggerName,
        string $table,
        string $timing,
        string $event,
        string $statement
    ): self {
        return $this->chainSchema(
            fn() => $this->processTrigger('alter', $triggerName, $table, $timing, $event, $statement)
        );
    }

    public function createDatabase(string $database): self
    {
        return $this->chainSchema(fn() => $this->processDatabase('create', $database));
    }

    public function dropDatabase(string $database): self
    {
        return $this->chainSchema(fn() => $this->processDatabase('drop', $database));
    }

    public function alterDatabase(string $database, array $options): self
    {
        return $this->chainSchema(fn() => $this->processDatabase('alter', $database, $options));
    }

    public function createProcedure(string $procedureName, string $definition): self
    {
        return $this->chainSchema(fn() => $this->processRoutine('create', 'procedure', $procedureName, $definition));
    }

    public function dropProcedure(string $procedureName): self
    {
        return $this->chainSchema(fn() => $this->processRoutine('drop', 'procedure', $procedureName));
    }

    public function createFunction(string $functionName, string $definition): self
    {
        return $this->chainSchema(fn() => $this->processRoutine('create', 'function', $functionName, $definition));
    }

    public function dropFunction(string $functionName): self
    {
        return $this->chainSchema(fn() => $this->processRoutine('drop', 'function', $functionName));
    }

    public function createSequence(string $sequenceName, array $options = []): self
    {
        return $this->chainSchema(fn() => $this->processSequence('create', $sequenceName, $options));
    }

    public function dropSequence(string $sequenceName): self
    {
        return $this->chainSchema(fn() => $this->processSequence('drop', $sequenceName));
    }
}
