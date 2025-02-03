<?php

namespace App\Utilities\Query;

use App\Abstracts\Database\Query;
use App\Utilities\Traits\Query\SchemaQueryTrait;

/**
 * SchemaQuery Class
 *
 * Extends the base Query class and includes additional functionality
 * for handling schema-related SQL operations such as table and column modifications.
 */
class SchemaQuery extends Query
{
    use SchemaQueryTrait;

    // ========================================================================
    // COLUMN OPERATIONS
    // ========================================================================

    /**
     * Processes a column operation such as adding, removing, renaming, or modifying a column.
     *
     * Escapes the table and column identifiers and prepares the column definition
     * based on the requested operation.
     *
     * @param string $table The name of the table where the operation will be performed.
     * @param string $column The column name.
     * @param string|null $definition The column definition (e.g., data type, constraints).
     * @param string|null $operation The type of column operation (default: 'add').
     * @return array Processed column operation data.
     */
    public function processColumn(string $table, string $column, ?string $definition = null, ?string $operation = 'add'): array {
        return $this->wrapInTry(fn() => [
            'operation'  => $operation,
            'table'      => $this->escapeIdentifiers([$table])[0],
            'column'     => $this->escapeColumns([$column])[0],
            'definition' => $definition ? trim($definition) : null,
        ], 'Failed to process column operation');
    }

    /**
     * Builds an SQL column operation query.
     *
     * Constructs a query string for altering a table by adding, removing, renaming, modifying,
     * or changing the default value of a column.
     *
     * @param array $colOp The column operation data.
     * @return string The constructed SQL query for the column operation.
     * @throws \InvalidArgumentException If an invalid column operation is provided.
     */
    public function buildColumn(array $colOp): string {
        return $this->wrapInTry(fn() =>
            match($colOp['operation']) {
                'add'         => $this->sql->clause('alter').' '.
                                 $this->sql->statement('table').' '.$colOp['table'].' '.
                                 $this->sql->statement('add').' '.
                                 $this->sql->statement('column').' '.$colOp['column'].' '.
                                 $colOp['definition'],
                'remove'      => $this->sql->clause('alter').' '.
                                 $this->sql->statement('table').' '.$colOp['table'].' '.
                                 $this->sql->statement('drop').' '.
                                 $this->sql->statement('column').' '.$colOp['column'],
                'rename'      => $this->sql->clause('alter').' '.
                                 $this->sql->statement('table').' '.$colOp['table'].' '.
                                 $this->sql->statement('rename').' '.
                                 $this->sql->statement('column').' '.$colOp['column'].' '.
                                 $this->sql->operator('to').' '.
                                 $this->escapeColumns([$colOp['definition']])[0],
                'modify'      => $this->sql->clause('alter').' '.
                                 $this->sql->statement('table').' '.$colOp['table'].' '.
                                 $this->sql->statement('modify').' '.
                                 $this->sql->statement('column').' '.$colOp['column'].' '.
                                 $colOp['definition'],
                'default'     => $this->sql->clause('alter').' '.
                                 $this->sql->statement('table').' '.$colOp['table'].' '.
                                 $this->sql->statement('alter').' '.
                                 $this->sql->statement('column').' '.$colOp['column'].' '.
                                 $this->sql->statement('set default').' '.
                                 $colOp['definition'],
                'dropDefault' => $this->sql->clause('alter').' '.
                                 $this->sql->statement('table').' '.$colOp['table'].' '.
                                 $this->sql->statement('alter').' '.
                                 $this->sql->statement('column').' '.$colOp['column'].' '.
                                 $this->sql->statement('drop default'),
                default       => throw new \InvalidArgumentException("Invalid column operation: ".$colOp['operation']),
            }
        , 'Failed to build column operation');
    }

    // ========================================================================
    // TABLE OPERATIONS
    // ========================================================================

    /**
     * Processes a table operation such as creating, renaming, truncating, dropping, or altering a table.
     *
     * Escapes the table name and prepares additional details for the operation.
     *
     * @param string $action The table operation to perform (e.g., create, rename, drop).
     * @param string $table The table name.
     * @param mixed $extra Additional table-related data (e.g., columns for creation, new name for renaming).
     * @param array $constraints Any constraints to be applied to the table.
     * @return array Processed table operation data.
     */
    public function processTable(string $action, string $table, $extra = null, array $constraints = []): array {
        return $this->wrapInTry(fn() => [
            'action'      => $action,
            'table'       => $this->escapeIdentifiers([$table])[0],
            'extra'       => $extra,
            'constraints' => $constraints,
        ], 'Failed to process table operation');
    }

    /**
     * Builds an SQL table operation query.
     *
     * Constructs a query string for table operations such as creation, renaming, truncation, and deletion.
     *
     * @param array $tableOp The table operation data.
     * @return string The constructed SQL query for the table operation.
     * @throws \InvalidArgumentException If an invalid table operation is provided.
     */
    public function buildTable(array $tableOp): string {
        return $this->wrapInTry(fn() =>
            match($tableOp['action']) {
                'create'  => $this->sql->statement('create').' '.
                            $this->sql->statement('table').' '.$tableOp['table'].' ('.
                            $this->join(', ', $tableOp['extra']).
                            (!$this->isEmpty($tableOp['constraints']) ? ', '.$this->join(', ', $tableOp['constraints']) : '').')',
                'rename'  => $this->sql->clause('alter').' '.
                            $this->sql->statement('table').' '.$tableOp['table'].' '.
                            $this->sql->statement('rename').' '.
                            $this->sql->operator('to').' '.
                            $this->escapeIdentifiers([$tableOp['extra']])[0],
                'truncate'=> $this->sql->clause('truncate').' '.
                            $this->sql->statement('table').' '.$tableOp['table'],
                'drop'    => $this->sql->clause('drop').' '.
                            $this->sql->statement('table').' '.$tableOp['table'],
                'alter'   => $this->sql->clause('alter').' '.
                            $this->sql->statement('table').' '.$tableOp['table'].' '.
                            $this->join(' ', $tableOp['extra']),
                default   => throw new \InvalidArgumentException("Invalid table operation: ".$tableOp['action']),
            }
        , 'Failed to build table operation');
    }

    // ========================================================================
    // STRUCTURE OPERATIONS (Indexes, Constraints, Keys)
    // ========================================================================

    /**
     * Processes a structure operation such as creating, dropping, or renaming an index, constraint, or key.
     *
     * This method ensures that the provided table name is properly escaped and the structure operation details
     * are stored in an array for further processing.
     *
     * @param string $structureType The type of structure (index, constraint, unique, foreignKey, primaryKey).
     * @param string $action The operation to perform (create, drop, rename).
     * @param string $table The name of the table on which the operation is performed.
     * @param mixed ...$params Additional parameters required for the specific operation.
     * @return array Processed structure operation data.
     */
    public function processStructure(string $structureType, string $action, string $table, ...$params): array {
        return $this->wrapInTry(fn() => [
            'structure' => $structureType,
            'action'    => $action,
            'table'     => $this->escapeIdentifiers([$table])[0],
            'params'    => $params,
        ], 'Failed to process structure operation');
    }

    /**
     * Builds an SQL query for a structure operation.
     *
     * Constructs a query string for creating, dropping, or renaming indexes, constraints, or keys.
     *
     * @param array $structOp The structure operation data.
     * @return string The constructed SQL query for the structure operation.
     * @throws \InvalidArgumentException If an invalid structure type or action is provided.
     */
    public function buildStructure(array $structOp): string {
        return $this->wrapInTry(fn() =>
            match($structOp['structure']) {
                'index' => match($structOp['action']) {
                    'create' => $this->sql->clause('alter').' '.
                                $this->sql->statement('table').' '.$structOp['table'].' '.
                                $this->sql->statement('add').' '.
                                $this->sql->statement('index').' '.$structOp['params'][0].' ('.
                                $this->join(', ', $structOp['params'][1]).')'
                                .(!$this->isEmpty($structOp['params'][2]) ? ' '.$structOp['params'][2] : ''),
                    'drop'   => $this->sql->clause('alter').' '.
                                $this->sql->statement('table').' '.$structOp['table'].' '.
                                $this->sql->statement('drop').' '.
                                $this->sql->statement('index').' '.$structOp['params'][0],
                    'rename' => $this->sql->clause('alter').' '.
                                $this->sql->statement('table').' '.$structOp['table'].' '.
                                $this->sql->statement('rename').' '.
                                $this->sql->statement('index').' '.$structOp['params'][0].' '.
                                $this->sql->operator('to').' '.$structOp['params'][1],
                    default  => throw new \InvalidArgumentException("Invalid index action: ".$structOp['action']),
                },
                'constraint' => match($structOp['action']) {
                    'create' => $this->sql->clause('alter').' '.
                                $this->sql->statement('table').' '.$structOp['table'].' '.
                                $this->sql->statement('add').' '.
                                $this->sql->statement('constraint').' '.$structOp['params'][0].' '.
                                $this->join(' ', $structOp['params'][1]),
                    'drop'   => $this->sql->clause('alter').' '.
                                $this->sql->statement('table').' '.$structOp['table'].' '.
                                $this->sql->statement('drop').' '.
                                $this->sql->statement('constraint').' '.$structOp['params'][0],
                    'rename' => $this->sql->clause('alter').' '.
                                $this->sql->statement('table').' '.$structOp['table'].' '.
                                $this->sql->statement('rename').' '.
                                $this->sql->statement('constraint').' '.$structOp['params'][0].' '.
                                $this->sql->operator('to').' '.$structOp['params'][1],
                    default  => throw new \InvalidArgumentException("Invalid constraint action: ".$structOp['action']),
                },
                'unique' => match($structOp['action']) {
                    'create' => $this->sql->clause('alter').' '.
                                $this->sql->statement('table').' '.$structOp['table'].' '.
                                $this->sql->statement('add').' '.
                                $this->sql->statement('unique').' ('.
                                $this->join(', ', $structOp['params'][0]).')',
                    'drop'   => $this->sql->clause('alter').' '.
                                $this->sql->statement('table').' '.$structOp['table'].' '.
                                $this->sql->statement('drop').' '.
                                $this->sql->statement('index').' '.$structOp['params'][0],
                    default  => throw new \InvalidArgumentException("Invalid unique action: ".$structOp['action']),
                },
                'foreignKey' => match($structOp['action']) {
                    'create' => $this->sql->clause('alter').' '.
                                $this->sql->statement('table').' '.$structOp['table'].' '.
                                $this->sql->statement('add').' '.
                                $this->sql->statement('foreign key').' ('.$structOp['params'][0].') '.
                                $this->sql->statement('references').' '.
                                $structOp['params'][1].'('.$structOp['params'][2].') '.
                                $this->sql->operator('on delete').' '.$structOp['params'][3].' '.
                                $this->sql->operator('on update').' '.$structOp['params'][4],
                    'drop'   => $this->sql->clause('alter').' '.
                                $this->sql->statement('table').' '.$structOp['table'].' '.
                                $this->sql->statement('drop').' '.
                                $this->sql->statement('foreign key').' '.$structOp['params'][0],
                    default  => throw new \InvalidArgumentException("Invalid foreign key action: ".$structOp['action']),
                },
                'primaryKey' => match($structOp['action']) {
                    'create' => $this->sql->clause('alter').' '.
                                $this->sql->statement('table').' '.$structOp['table'].' '.
                                $this->sql->statement('add').' '.
                                $this->sql->statement('primary key').' ('.
                                $this->join(', ', $structOp['params'][0]).')',
                    'drop'   => $this->sql->clause('alter').' '.
                                $this->sql->statement('table').' '.$structOp['table'].' '.
                                $this->sql->statement('drop').' '.
                                $this->sql->statement('primary key'),
                    default  => throw new \InvalidArgumentException("Invalid primary key action: ".$structOp['action']),
                },
                default => throw new \InvalidArgumentException("Invalid structure type: ".$structOp['structure']),
            }
        , 'Failed to build structure operation');
    }

    // ========================================================================
    // VIEW OPERATIONS
    // ========================================================================

    /**
     * Processes a view operation such as creating, dropping, or altering a view.
     *
     * Escapes the view name and prepares the SELECT query for creation or alteration.
     *
     * @param string $action The operation to perform (create, drop, alter).
     * @param string $viewName The name of the view.
     * @param string|null $selectQuery The SELECT query used to create or alter the view.
     * @return array Processed view operation data.
     */
    public function processView(string $action, string $viewName, ?string $selectQuery = null): array {
        return $this->wrapInTry(fn() => [
            'action' => $action,
            'view'   => $this->escapeIdentifiers([$viewName])[0],
            'query'  => $selectQuery,
        ], 'Failed to process view operation');
    }

    /**
     * Builds an SQL query for a view operation.
     *
     * Constructs a query string for creating, dropping, or altering views.
     *
     * @param array $viewOp The view operation data.
     * @return string The constructed SQL query for the view operation.
     * @throws \InvalidArgumentException If an invalid view action is provided.
     */
    public function buildView(array $viewOp): string {
        return $this->wrapInTry(fn() =>
            match($viewOp['action']) {
                'create' => $this->sql->statement('create').' '.$this->sql->statement('view').' '.
                            $viewOp['view'].' '.$this->sql->operator('as').' '.$viewOp['query'],
                'drop'   => $this->sql->statement('drop').' '.$this->sql->statement('view').' '.
                            $viewOp['view'],
                'alter'  => $this->sql->clause('alter').' '.$this->sql->statement('view').' '.
                            $viewOp['view'].' '.$this->sql->operator('as').' '.$viewOp['query'],
                default  => throw new \InvalidArgumentException("Invalid view action: ".$viewOp['action']),
            }
        , 'Failed to build view operation');
    }

    // ========================================================================
    // TRIGGER OPERATIONS
    // ========================================================================

    /**
     * Processes a trigger operation such as creating, dropping, or altering a trigger.
     *
     * Escapes the trigger and table names and stores additional parameters required
     * for the trigger definition.
     *
     * @param string $action The operation to perform (create, drop, alter).
     * @param string $triggerName The name of the trigger.
     * @param string|null $table The table associated with the trigger.
     * @param string|null $timing The timing of the trigger (BEFORE, AFTER, INSTEAD OF).
     * @param string|null $event The event that activates the trigger (INSERT, UPDATE, DELETE).
     * @param string|null $statement The SQL statement executed by the trigger.
     * @return array Processed trigger operation data.
     */
    public function processTrigger(string $action, string $triggerName, ?string $table = null, ?string $timing = null, ?string $event = null, ?string $statement = null): array {
        return $this->wrapInTry(fn() => [
            'action'    => $action,
            'trigger'   => $this->escapeIdentifiers([$triggerName])[0],
            'table'     => $table ? $this->escapeIdentifiers([$table])[0] : null,
            'timing'    => $timing,
            'event'     => $event,
            'statement' => $statement,
        ], 'Failed to process trigger operation');
    }

    /**
     * Builds an SQL query for a trigger operation.
     *
     * Constructs a query string for creating, dropping, or altering triggers.
     *
     * @param array $triggerOp The trigger operation data.
     * @return string The constructed SQL query for the trigger operation.
     * @throws \InvalidArgumentException If an invalid trigger action is provided.
     */
    public function buildTrigger(array $triggerOp): string {
        return $this->wrapInTry(fn() =>
            match($triggerOp['action']) {
                'create' => $this->sql->statement('create').' '.$this->sql->statement('trigger').' '.
                            $triggerOp['trigger'].' '.$triggerOp['timing'].' '.$triggerOp['event'].' '.
                            $this->sql->operator('on').' '.$triggerOp['table'].' '.
                            $this->sql->operator('for each row').' '.$triggerOp['statement'],
                'drop'   => $this->sql->statement('drop').' '.$this->sql->statement('trigger').' '.
                            $triggerOp['trigger'],
                'alter'  => $this->sql->clause('alter').' '.$this->sql->statement('trigger').' '.
                            $triggerOp['trigger'].' '.$this->sql->operator('on').' '.$triggerOp['table'].' '.
                            $triggerOp['statement'],
                default  => throw new \InvalidArgumentException("Invalid trigger action: ".$triggerOp['action']),
            }
        , 'Failed to build trigger operation');
    }

    // ========================================================================
    // DATABASE OPERATIONS
    // ========================================================================

    /**
     * Processes a database operation such as creating, dropping, or altering a database.
     *
     * Ensures that the database name is properly escaped and stores any additional options.
     *
     * @param string $action The operation to perform (create, drop, alter).
     * @param string $database The name of the database.
     * @param array $options Additional options for altering the database.
     * @return array Processed database operation data.
     */
    public function processDatabase(string $action, string $database, array $options = []): array {
        return $this->wrapInTry(fn() => [
            'action'   => $action,
            'database' => $this->escapeIdentifiers([$database])[0],
            'options'  => $options,
        ], 'Failed to process database operation');
    }

    /**
     * Builds an SQL query for a database operation.
     *
     * Constructs a query string for creating, dropping, or altering a database.
     *
     * @param array $dbOp The database operation data.
     * @return string The constructed SQL query for the database operation.
     * @throws \InvalidArgumentException If an invalid database action is provided.
     */
    public function buildDatabase(array $dbOp): string {
        return $this->wrapInTry(fn() =>
            match($dbOp['action']) {
                'create' => $this->sql->statement('create').' '.$this->sql->statement('database').' '.
                            $dbOp['database'],
                'drop'   => $this->sql->statement('drop').' '.$this->sql->statement('database').' '.
                            $dbOp['database'],
                'alter'  => $this->sql->clause('alter').' '.$this->sql->statement('database').' '.
                            $dbOp['database'].' '.$this->join(' ', $dbOp['options']),
                default  => throw new \InvalidArgumentException("Invalid database action: ".$dbOp['action']),
            }
        , 'Failed to build database operation');
    }

    // ========================================================================
    // ROUTINE OPERATIONS (Procedures & Functions)
    // ========================================================================

    /**
     * Processes a routine operation such as creating or dropping a stored procedure or function.
     *
     * Escapes the routine name and stores the definition if provided.
     *
     * @param string $action The operation to perform (create, drop).
     * @param string $routineType The type of routine (procedure or function).
     * @param string $routineName The name of the routine.
     * @param string|null $definition The SQL definition of the routine.
     * @return array Processed routine operation data.
     */
    public function processRoutine(string $action, string $routineType, string $routineName, ?string $definition = null): array {
        return $this->wrapInTry(fn() => [
            'action'      => $action,
            'routineType' => $routineType,
            'name'        => $this->escapeIdentifiers([$routineName])[0],
            'definition'  => $definition,
        ], 'Failed to process routine operation');
    }

    /**
     * Builds an SQL query for a routine operation.
     *
     * Constructs a query string for creating or dropping a stored procedure or function.
     *
     * @param array $routineOp The routine operation data.
     * @return string The constructed SQL query for the routine operation.
     * @throws \InvalidArgumentException If an invalid routine action is provided.
     */
    public function buildRoutine(array $routineOp): string {
        return $this->wrapInTry(fn() =>
            match($routineOp['action']) {
                'create' => $this->sql->statement('create').' '.$routineOp['routineType'].' '.
                            $routineOp['name'].' '.$this->sql->operator('as').' '.$routineOp['definition'],
                'drop'   => $this->sql->statement('drop').' '.$routineOp['routineType'].' '.
                            $routineOp['name'],
                default  => throw new \InvalidArgumentException("Invalid routine action: ".$routineOp['action']),
            }
        , 'Failed to build routine operation');
    }

    // ========================================================================
    // SEQUENCE OPERATIONS
    // ========================================================================

    /**
     * Processes a sequence operation such as creating or dropping a sequence.
     *
     * Escapes the sequence name and stores additional options if provided.
     *
     * @param string $action The operation to perform (create, drop).
     * @param string $sequenceName The name of the sequence.
     * @param array $options Additional options for creating or modifying the sequence.
     * @return array Processed sequence operation data.
     */
    public function processSequence(string $action, string $sequenceName, array $options = []): array {
        return $this->wrapInTry(fn() => [
            'action'   => $action,
            'sequence' => $this->escapeIdentifiers([$sequenceName])[0],
            'options'  => $options,
        ], 'Failed to process sequence operation');
    }

    /**
     * Builds an SQL query for a sequence operation.
     *
     * Constructs a query string for creating or dropping a sequence.
     *
     * @param array $seqOp The sequence operation data.
     * @return string The constructed SQL query for the sequence operation.
     * @throws \InvalidArgumentException If an invalid sequence action is provided.
     */
    public function buildSequence(array $seqOp): string {
        return $this->wrapInTry(fn() =>
            match($seqOp['action']) {
                'create' => $this->sql->statement('create').' '.$this->sql->statement('sequence').' '.
                            $seqOp['sequence'].
                            ($this->isEmpty($seqOp['options']) ? '' : ' '.$this->join(' ', $seqOp['options'])),
                'drop'   => $this->sql->statement('drop').' '.$this->sql->statement('sequence').' '.
                            $seqOp['sequence'],
                default  => throw new \InvalidArgumentException("Invalid sequence action: ".$seqOp['action']),
            }
        , 'Failed to build sequence operation');
    }
}
