<?php

namespace App\Utilities\Traits\Query;

/**
 * SchemaQueryTrait
 *
 * This trait provides reusable methods for handling SQL schema queries.
 * It is intended to be used within query builder classes to simplify
 * database schema modifications, including table, column, index,
 * constraint, sequence, and routine operations.
 */
trait SchemaQueryTrait
{

    // ─────────────────────────────────────────────────────────────
    // COLUMN OPERATIONS
    // ─────────────────────────────────────────────────────────────

    /**
     * Adds a new column to a table.
     *
     * @param string $table The name of the table.
     * @param string $column The name of the new column.
     * @param string $definition The SQL definition for the column.
     * @return self The updated query instance.
     */
    public function addColumn(string $table, string $column, string $definition): self {
        return (fn() => ($this->processColumn($table, $column, $definition), $this))[1];
    }

    /**
     * Removes a column from a table.
     *
     * @param string $table The name of the table.
     * @param string $column The name of the column to remove.
     * @return self The updated query instance.
     */
    public function removeColumn(string $table, string $column): self {
        return (fn() => ($this->processColumn($table, $column), $this))[1];
    }

    /**
     * Renames a column in a table.
     *
     * @param string $table The name of the table.
     * @param string $oldName The current name of the column.
     * @param string $newName The new name of the column.
     * @return self The updated query instance.
     */
    public function renameColumn(string $table, string $oldName, string $newName): self {
        return (fn() => ($this->processColumn($table, $oldName, $newName), $this))[1];
    }

    /**
     * Modifies an existing column in a table.
     *
     * @param string $table The name of the table.
     * @param string $column The name of the column to modify.
     * @param string $newDefinition The new SQL definition for the column.
     * @return self The updated query instance.
     */
    public function modifyColumn(string $table, string $column, string $newDefinition): self {
        return (fn() => ($this->processColumn($table, $column, $newDefinition), $this))[1];
    }

    // ─────────────────────────────────────────────────────────────
    // TABLE OPERATIONS
    // ─────────────────────────────────────────────────────────────

    /**
     * Creates a new table with the specified columns and constraints.
     *
     * @param string $table The name of the table.
     * @param array $columns An array of column definitions.
     * @param array $constraints Optional constraints for the table.
     * @return self The updated query instance.
     */
    public function createTable(string $table, array $columns, array $constraints = []): self {
        return (fn() => ($this->processTable('create', $table, $columns, $constraints), $this))[1];
    }

    /**
     * Renames a table.
     *
     * @param string $oldName The current name of the table.
     * @param string $newName The new name of the table.
     * @return self The updated query instance.
     */
    public function renameTable(string $oldName, string $newName): self {
        return (fn() => ($this->processTable('rename', $oldName, $newName), $this))[1];
    }

    /**
     * Truncates a table, removing all its data while keeping the structure.
     *
     * @param string $table The name of the table.
     * @return self The updated query instance.
     */
    public function truncateTable(string $table): self {
        return (fn() => ($this->processTable('truncate', $table), $this))[1];
    }

    /**
     * Drops a table from the database.
     *
     * @param string $table The name of the table.
     * @return self The updated query instance.
     */
    public function dropTable(string $table): self {
        return (fn() => ($this->processTable('drop', $table), $this))[1];
    }

    /**
     * Alters a table with the specified options.
     *
     * @param string $table The name of the table.
     * @param array $options An array of alterations to apply.
     * @return self The updated query instance.
     */
    public function alterTable(string $table, array $options): self {
        return (fn() => ($this->processTable('alter', $table, $options), $this))[1];
    }

    // ─────────────────────────────────────────────────────────────
    // INDEX OPERATIONS
    // ─────────────────────────────────────────────────────────────

    /**
     * Adds an index to a table.
     *
     * @param string $table The name of the table.
     * @param string $indexName The name of the index.
     * @param array $columns The columns to include in the index.
     * @param string $indexType The type of index (optional).
     * @return self The updated query instance.
     */
    public function addIndex(string $table, string $indexName, array $columns, string $indexType = ''): self {
        return (fn() => ($this->processStructure('index', 'create', $table, $indexName, $columns, $indexType), $this))[1];
    }

    /**
     * Drops an index from a table.
     *
     * @param string $table The name of the table.
     * @param string $indexName The name of the index to drop.
     * @return self The updated query instance.
     */
    public function dropIndex(string $table, string $indexName): self {
        return (fn() => ($this->processStructure('index', 'drop', $table, $indexName), $this))[1];
    }

    /**
     * Renames an existing index.
     *
     * @param string $table The name of the table.
     * @param string $oldIndexName The current name of the index.
     * @param string $newIndexName The new name for the index.
     * @return self The updated query instance.
     */
    public function renameIndex(string $table, string $oldIndexName, string $newIndexName): self {
        return (fn() => ($this->processStructure('index', 'rename', $table, $oldIndexName, $newIndexName), $this))[1];
    }

    // ─────────────────────────────────────────────────────────────
    // CONSTRAINT OPERATIONS
    // ─────────────────────────────────────────────────────────────

    /**
     * Adds a constraint to a table.
     *
     * @param string $table The name of the table.
     * @param string $constraint The name of the constraint.
     * @param array $definition The constraint definition.
     * @return self The updated query instance.
     */
    public function setConstraint(string $table, string $constraint, array $definition): self {
        return (fn() => ($this->processStructure('constraint', 'create', $table, $constraint, $definition), $this))[1];
    }

    /**
     * Drops a constraint from a table.
     *
     * @param string $table The name of the table.
     * @param string $constraint The name of the constraint to drop.
     * @return self The updated query instance.
     */
    public function dropConstraint(string $table, string $constraint): self {
        return (fn() => ($this->processStructure('constraint', 'drop', $table, $constraint), $this))[1];
    }

    /**
     * Renames a constraint on a table.
     *
     * @param string $table The name of the table.
     * @param string $oldConstraint The current name of the constraint.
     * @param string $newConstraint The new name for the constraint.
     * @return self The updated query instance.
     */
    public function renameConstraint(string $table, string $oldConstraint, string $newConstraint): self {
        return (fn() => ($this->processStructure('constraint', 'rename', $table, $oldConstraint, $newConstraint), $this))[1];
    }

    /**
     * Adds a UNIQUE constraint to a table.
     *
     * @param string $table The name of the table.
     * @param array $columns The columns to enforce uniqueness on.
     * @return self The updated query instance.
     */
    public function setUnique(string $table, array $columns): self {
        return (fn() => ($this->processStructure('unique', 'create', $table, $columns), $this))[1];
    }

    /**
     * Drops a UNIQUE constraint from a table.
     *
     * @param string $table The name of the table.
     * @param string $constraint The name of the unique constraint.
     * @return self The updated query instance.
     */
    public function dropUnique(string $table, string $constraint): self {
        return (fn() => ($this->processStructure('unique', 'drop', $table, $constraint), $this))[1];
    }

    /**
     * Adds a CHECK constraint to a table.
     *
     * @param string $table The name of the table.
     * @param string $constraint The name of the check constraint.
     * @param string $condition The condition for the constraint.
     * @return self The updated query instance.
     */
    public function setCheck(string $table, string $constraint, string $condition): self {
        return (fn() => ($this->processStructure('check', 'create', $table, $constraint, $condition), $this))[1];
    }

    /**
     * Drops a CHECK constraint from a table.
     *
     * @param string $table The name of the table.
     * @param string $constraint The name of the check constraint.
     * @return self The updated query instance.
     */
    public function dropCheck(string $table, string $constraint): self {
        return (fn() => ($this->processStructure('check', 'drop', $table, $constraint), $this))[1];
    }

    // ─────────────────────────────────────────────────────────────
    // FOREIGN KEY OPERATIONS
    // ─────────────────────────────────────────────────────────────

    /**
     * Adds a FOREIGN KEY constraint to a table.
     *
     * Defines a foreign key relationship between two tables.
     *
     * @param string $table The name of the table.
     * @param string $column The column in the current table that references another table.
     * @param string $referencedTable The referenced table.
     * @param string $referencedColumn The column in the referenced table.
     * @param string $onDelete The ON DELETE action (default: RESTRICT).
     * @param string $onUpdate The ON UPDATE action (default: RESTRICT).
     * @return self The updated query instance.
     */
    public function setForeign(string $table, string $column, string $referencedTable, string $referencedColumn, string $onDelete = 'RESTRICT', string $onUpdate = 'RESTRICT'): self {
        return (fn() => ($this->processStructure('foreignKey', 'create', $table, $column, $referencedTable, $referencedColumn, $onDelete, $onUpdate), $this))[1];
    }

    /**
     * Drops a FOREIGN KEY constraint from a table.
     *
     * @param string $table The name of the table.
     * @param string $foreignKeyName The name of the foreign key constraint.
     * @return self The updated query instance.
     */
    public function dropForeign(string $table, string $foreignKeyName): self {
        return (fn() => ($this->processStructure('foreignKey', 'drop', $table, $foreignKeyName), $this))[1];
    }

    // ─────────────────────────────────────────────────────────────
    // PRIMARY KEY & DEFAULT VALUE OPERATIONS
    // ─────────────────────────────────────────────────────────────

    /**
     * Adds a PRIMARY KEY constraint to a table.
     *
     * @param string $table The name of the table.
     * @param array $columns The columns to be used as the primary key.
     * @return self The updated query instance.
     */
    public function addPrimary(string $table, array $columns): self {
        return (fn() => ($this->processStructure('primaryKey', 'create', $table, $columns), $this))[1];
    }

    /**
     * Drops the PRIMARY KEY constraint from a table.
     *
     * @param string $table The name of the table.
     * @return self The updated query instance.
     */
    public function dropPrimary(string $table): self {
        return (fn() => ($this->processStructure('primaryKey', 'drop', $table), $this))[1];
    }

    /**
     * Sets a DEFAULT value for a column in a table.
     *
     * @param string $table The name of the table.
     * @param string $column The column name.
     * @param mixed $defaultValue The default value to be assigned.
     * @return self The updated query instance.
     */
    public function setDefault(string $table, string $column, mixed $defaultValue): self {
        return (fn() => ($this->processColumn($table, $column, (string)$defaultValue), $this))[1];
    }

    /**
     * Drops the DEFAULT value from a column in a table.
     *
     * @param string $table The name of the table.
     * @param string $column The column name.
     * @return self The updated query instance.
     */
    public function dropDefault(string $table, string $column): self {
        return (fn() => ($this->processColumn($table, $column), $this))[1];
    }

    // ─────────────────────────────────────────────────────────────
    // VIEW OPERATIONS
    // ─────────────────────────────────────────────────────────────

    /**
     * Creates a database view.
     *
     * @param string $viewName The name of the view.
     * @param string $selectQuery The SELECT query defining the view.
     * @return self The updated query instance.
     */
    public function createView(string $viewName, string $selectQuery): self {
        return (fn() => ($this->processView('create', $viewName, $selectQuery), $this))[1];
    }

    /**
     * Drops a database view.
     *
     * @param string $viewName The name of the view to be dropped.
     * @return self The updated query instance.
     */
    public function dropView(string $viewName): self {
        return (fn() => ($this->processView('drop', $viewName), $this))[1];
    }

    /**
     * Alters an existing database view.
     *
     * @param string $viewName The name of the view.
     * @param string $selectQuery The new SELECT query for the view.
     * @return self The updated query instance.
     */
    public function alterView(string $viewName, string $selectQuery): self {
        return (fn() => ($this->processView('alter', $viewName, $selectQuery), $this))[1];
    }

    // ─────────────────────────────────────────────────────────────
    // TRIGGER OPERATIONS
    // ─────────────────────────────────────────────────────────────

    /**
     * Creates a database trigger.
     *
     * @param string $triggerName The name of the trigger.
     * @param string $table The table the trigger is associated with.
     * @param string $timing The trigger timing (BEFORE or AFTER).
     * @param string $event The event that fires the trigger (INSERT, UPDATE, DELETE).
     * @param string $statement The SQL statement executed when the trigger is fired.
     * @return self The updated query instance.
     */
    public function createTrigger(string $triggerName, string $table, string $timing, string $event, string $statement): self {
        return (fn() => ($this->processTrigger('create', $triggerName, $table, $timing, $event, $statement), $this))[1];
    }

    /**
     * Drops a database trigger.
     *
     * @param string $triggerName The name of the trigger to be dropped.
     * @return self The updated query instance.
     */
    public function dropTrigger(string $triggerName): self {
        return (fn() => ($this->processTrigger('drop', $triggerName), $this))[1];
    }

    /**
     * Alters an existing database trigger.
     *
     * @param string $triggerName The name of the trigger.
     * @param string $table The table the trigger is associated with.
     * @param string $timing The new trigger timing (BEFORE or AFTER).
     * @param string $event The event that fires the trigger (INSERT, UPDATE, DELETE).
     * @param string $statement The new SQL statement executed when the trigger is fired.
     * @return self The updated query instance.
     */
    public function alterTrigger(string $triggerName, string $table, string $timing, string $event, string $statement): self {
        return (fn() => ($this->processTrigger('alter', $triggerName, $table, $timing, $event, $statement), $this))[1];
    }

    // ─────────────────────────────────────────────────────────────
    // DATABASE OPERATIONS
    // ─────────────────────────────────────────────────────────────

    /**
     * Creates a new database.
     *
     * @param string $database The name of the database.
     * @return self The updated query instance.
     */
    public function createDatabase(string $database): self {
        return (fn() => ($this->processDatabase('create', $database), $this))[1];
    }

    /**
     * Drops a database.
     *
     * @param string $database The name of the database to be dropped.
     * @return self The updated query instance.
     */
    public function dropDatabase(string $database): self {
        return (fn() => ($this->processDatabase('drop', $database), $this))[1];
    }

    /**
     * Alters an existing database with specified options.
     *
     * @param string $database The name of the database.
     * @param array $options The options to apply during the alteration.
     * @return self The updated query instance.
     */
    public function alterDatabase(string $database, array $options): self {
        return (fn() => ($this->processDatabase('alter', $database, $options), $this))[1];
    }

    // ─────────────────────────────────────────────────────────────
    // ROUTINE OPERATIONS (Procedures & Functions)
    // ─────────────────────────────────────────────────────────────

    /**
     * Creates a stored procedure.
     *
     * @param string $procedureName The name of the procedure.
     * @param string $definition The SQL definition of the procedure.
     * @return self The updated query instance.
     */
    public function createProcedure(string $procedureName, string $definition): self {
        return (fn() => ($this->processRoutine('create', 'procedure', $procedureName, $definition), $this))[1];
    }

    /**
     * Drops a stored procedure.
     *
     * @param string $procedureName The name of the procedure to drop.
     * @return self The updated query instance.
     */
    public function dropProcedure(string $procedureName): self {
        return (fn() => ($this->processRoutine('drop', 'procedure', $procedureName), $this))[1];
    }

    /**
     * Creates a stored function.
     *
     * @param string $functionName The name of the function.
     * @param string $definition The SQL definition of the function.
     * @return self The updated query instance.
     */
    public function createFunction(string $functionName, string $definition): self {
        return (fn() => ($this->processRoutine('create', 'function', $functionName, $definition), $this))[1];
    }

    /**
     * Drops a stored function.
     *
     * @param string $functionName The name of the function to drop.
     * @return self The updated query instance.
     */
    public function dropFunction(string $functionName): self {
        return (fn() => ($this->processRoutine('drop', 'function', $functionName), $this))[1];
    }

    // ─────────────────────────────────────────────────────────────
    // SEQUENCE OPERATIONS
    // ─────────────────────────────────────────────────────────────

    /**
     * Creates a database sequence.
     *
     * @param string $sequenceName The name of the sequence.
     * @param array $options Optional sequence configuration options.
     * @return self The updated query instance.
     */
    public function createSequence(string $sequenceName, array $options = []): self {
        return (fn() => ($this->processSequence('create', $sequenceName, $options), $this))[1];
    }

    /**
     * Drops a database sequence.
     *
     * @param string $sequenceName The name of the sequence to drop.
     * @return self The updated query instance.
     */
    public function dropSequence(string $sequenceName): self {
        return (fn() => ($this->processSequence('drop', $sequenceName), $this))[1];
    }
}
