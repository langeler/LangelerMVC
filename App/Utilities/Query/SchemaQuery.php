<?php

declare(strict_types=1);

namespace App\Utilities\Query;

use App\Abstracts\Database\Query;
use App\Utilities\Traits\Query\SchemaQueryTrait;

/**
 * Driver-aware schema statement builder.
 *
 * SchemaQuery intentionally models DDL as a sequence of explicit statements
 * rather than pretending every action can be merged into one vendor-neutral
 * mega-query. That keeps the builder predictable and much easier to maintain.
 */
class SchemaQuery extends Query
{
    use SchemaQueryTrait;

    /**
     * @var list<array{kind:string,payload:array<string, mixed>}>
     */
    protected array $operations = [];

    public function __construct(
        ?\App\Utilities\Handlers\SQLHandler $sql = null,
        ?\App\Utilities\Managers\System\ErrorManager $errorManager = null,
        string $table = '',
        array $columns = [],
        array $values = [],
        string $driver = 'mysql'
    ) {
        parent::__construct($sql, $errorManager, $table, $columns, $values, $driver);
    }

    /**
     * @return list<string>
     */
    public function toStatements(): array
    {
        return $this->map(
            fn(array $operation): string => $this->buildOperation($operation['kind'], $operation['payload']),
            $this->operations
        );
    }

    public function toSql(): string
    {
        return $this->implodeWith(";\n", $this->toStatements());
    }

    public function processColumn(
        string $table,
        string $column,
        ?string $definition = null,
        ?string $operation = 'add'
    ): array {
        $payload = [
            'operation' => (string) $operation,
            'table' => $table,
            'column' => $column,
            'definition' => $definition,
        ];

        $this->pushOperation('column', $payload);

        return $payload;
    }

    public function buildColumn(array $colOp): string
    {
        $table = $this->quoteIdentifier((string) $colOp['table']);
        $column = $this->quoteIdentifier((string) $colOp['column']);
        $definition = $colOp['definition'] !== null ? $this->trimString((string) $colOp['definition']) : null;

        return match ($this->sql->normalize((string) $colOp['operation'])) {
            'add' => sprintf(
                '%s %s %s %s %s',
                $this->sql->clause('alter'),
                $this->sql->statement('table'),
                $table,
                $this->sql->statement('add') . ' ' . $this->sql->statement('column'),
                $column . ' ' . $definition
            ),
            'remove' => sprintf(
                '%s %s %s %s %s %s',
                $this->sql->clause('alter'),
                $this->sql->statement('table'),
                $table,
                $this->sql->statement('drop'),
                $this->sql->statement('column'),
                $column
            ),
            'rename' => sprintf(
                '%s %s %s %s %s %s %s',
                $this->sql->clause('alter'),
                $this->sql->statement('table'),
                $table,
                $this->sql->statement('rename'),
                $this->sql->statement('column'),
                $column,
                $this->sql->operator('to') . ' ' . $this->quoteIdentifier((string) $definition)
            ),
            'modify' => sprintf(
                '%s %s %s %s %s %s',
                $this->sql->clause('alter'),
                $this->sql->statement('table'),
                $table,
                $this->sql->statement('modify') . ' ' . $this->sql->statement('column'),
                $column,
                (string) $definition
            ),
            'default' => sprintf(
                '%s %s %s %s %s %s %s',
                $this->sql->clause('alter'),
                $this->sql->statement('table'),
                $table,
                $this->sql->clause('alter') . ' ' . $this->sql->statement('column'),
                $column,
                $this->sql->statement('setDefault'),
                (string) $definition
            ),
            'dropdefault' => sprintf(
                '%s %s %s %s %s %s',
                $this->sql->clause('alter'),
                $this->sql->statement('table'),
                $table,
                $this->sql->clause('alter') . ' ' . $this->sql->statement('column'),
                $column,
                $this->sql->statement('dropDefault')
            ),
            default => throw $this->errorManager->resolveException(
                'database',
                sprintf('Invalid schema column operation [%s].', $colOp['operation'])
            ),
        };
    }

    public function processTable(string $action, string $table, mixed $extra = null, array $constraints = []): array
    {
        $payload = [
            'action' => $action,
            'table' => $table,
            'extra' => $extra,
            'constraints' => $constraints,
        ];

        $this->pushOperation('table', $payload);

        return $payload;
    }

    public function buildTable(array $tableOp): string
    {
        $table = $this->quoteIdentifier((string) $tableOp['table']);
        $action = $this->sql->normalize((string) $tableOp['action']);
        $extra = $tableOp['extra'];
        $constraints = $this->getValuesList((array) ($tableOp['constraints'] ?? []));

        return match ($action) {
            'create' => sprintf(
                '%s %s %s (%s)',
                $this->sql->statement('create'),
                $this->sql->statement('table'),
                $table,
                $this->implodeWith(', ', $this->merge($this->getValuesList((array) $extra), $constraints))
            ),
            'rename' => sprintf(
                '%s %s %s %s %s',
                $this->sql->clause('alter'),
                $this->sql->statement('table'),
                $table,
                $this->sql->statement('rename'),
                $this->sql->operator('to') . ' ' . $this->quoteIdentifier((string) $extra)
            ),
            'truncate' => sprintf(
                '%s %s %s',
                $this->sql->clause('truncate'),
                $this->sql->statement('table'),
                $table
            ),
            'drop' => sprintf(
                '%s %s %s',
                $this->sql->clause('drop'),
                $this->sql->statement('table'),
                $table
            ),
            'alter' => sprintf(
                '%s %s %s %s',
                $this->sql->clause('alter'),
                $this->sql->statement('table'),
                $table,
                $this->implodeWith(' ', $this->getValuesList((array) $extra))
            ),
            default => throw $this->errorManager->resolveException(
                'database',
                sprintf('Invalid schema table action [%s].', $tableOp['action'])
            ),
        };
    }

    public function processStructure(string $structureType, string $action, string $table, ...$params): array
    {
        $payload = [
            'structure' => $structureType,
            'action' => $action,
            'table' => $table,
            'params' => $params,
        ];

        $this->pushOperation('structure', $payload);

        return $payload;
    }

    public function buildStructure(array $structOp): string
    {
        $table = $this->quoteIdentifier((string) $structOp['table']);
        $structure = $this->sql->normalize((string) $structOp['structure']);
        $action = $this->sql->normalize((string) $structOp['action']);
        $params = $structOp['params'] ?? [];

        return match ($structure) {
            'index' => match ($action) {
                'create' => sprintf(
                    '%s %s %s %s %s %s (%s)%s',
                    $this->sql->clause('alter'),
                    $this->sql->statement('table'),
                    $table,
                    $this->sql->statement('add'),
                    $this->sql->statement('index'),
                    $this->quoteIdentifier((string) ($params[0] ?? '')),
                    $this->compileIdentifierArray((array) ($params[1] ?? [])),
                    $this->trimString((string) ($params[2] ?? '')) !== ''
                        ? ' ' . $this->trimString((string) $params[2])
                        : ''
                ),
                'drop' => sprintf(
                    '%s %s %s %s %s %s',
                    $this->sql->clause('alter'),
                    $this->sql->statement('table'),
                    $table,
                    $this->sql->statement('drop'),
                    $this->sql->statement('index'),
                    $this->quoteIdentifier((string) ($params[0] ?? ''))
                ),
                'rename' => sprintf(
                    '%s %s %s %s %s %s %s',
                    $this->sql->clause('alter'),
                    $this->sql->statement('table'),
                    $table,
                    $this->sql->statement('rename'),
                    $this->sql->statement('index'),
                    $this->quoteIdentifier((string) ($params[0] ?? '')),
                    $this->sql->operator('to') . ' ' . $this->quoteIdentifier((string) ($params[1] ?? ''))
                ),
                default => throw $this->errorManager->resolveException('database', 'Invalid schema index action.'),
            },
            'constraint' => match ($action) {
                'create' => sprintf(
                    '%s %s %s %s %s %s %s',
                    $this->sql->clause('alter'),
                    $this->sql->statement('table'),
                    $table,
                    $this->sql->statement('add'),
                    $this->sql->statement('constraint'),
                    $this->quoteIdentifier((string) ($params[0] ?? '')),
                    $this->implodeWith(' ', $this->getValuesList((array) ($params[1] ?? [])))
                ),
                'drop' => sprintf(
                    '%s %s %s %s %s %s',
                    $this->sql->clause('alter'),
                    $this->sql->statement('table'),
                    $table,
                    $this->sql->statement('drop'),
                    $this->sql->statement('constraint'),
                    $this->quoteIdentifier((string) ($params[0] ?? ''))
                ),
                'rename' => sprintf(
                    '%s %s %s %s %s %s %s',
                    $this->sql->clause('alter'),
                    $this->sql->statement('table'),
                    $table,
                    $this->sql->statement('rename'),
                    $this->sql->statement('constraint'),
                    $this->quoteIdentifier((string) ($params[0] ?? '')),
                    $this->sql->operator('to') . ' ' . $this->quoteIdentifier((string) ($params[1] ?? ''))
                ),
                default => throw $this->errorManager->resolveException('database', 'Invalid schema constraint action.'),
            },
            'unique' => match ($action) {
                'create' => sprintf(
                    '%s %s %s %s %s (%s)',
                    $this->sql->clause('alter'),
                    $this->sql->statement('table'),
                    $table,
                    $this->sql->statement('add'),
                    $this->sql->statement('unique'),
                    $this->compileIdentifierArray((array) ($params[0] ?? []))
                ),
                'drop' => sprintf(
                    '%s %s %s %s %s %s',
                    $this->sql->clause('alter'),
                    $this->sql->statement('table'),
                    $table,
                    $this->sql->statement('drop'),
                    $this->sql->statement('constraint'),
                    $this->quoteIdentifier((string) ($params[0] ?? ''))
                ),
                default => throw $this->errorManager->resolveException('database', 'Invalid schema unique action.'),
            },
            'check' => match ($action) {
                'create' => sprintf(
                    '%s %s %s %s %s %s CHECK (%s)',
                    $this->sql->clause('alter'),
                    $this->sql->statement('table'),
                    $table,
                    $this->sql->statement('add'),
                    $this->sql->statement('constraint'),
                    $this->quoteIdentifier((string) ($params[0] ?? '')),
                    $this->trimString((string) ($params[1] ?? ''))
                ),
                'drop' => sprintf(
                    '%s %s %s %s %s %s',
                    $this->sql->clause('alter'),
                    $this->sql->statement('table'),
                    $table,
                    $this->sql->statement('drop'),
                    $this->sql->statement('constraint'),
                    $this->quoteIdentifier((string) ($params[0] ?? ''))
                ),
                default => throw $this->errorManager->resolveException('database', 'Invalid schema check action.'),
            },
            'foreignkey' => match ($action) {
                'create' => sprintf(
                    '%s %s %s %s %s (%s) %s %s(%s) %s %s %s %s',
                    $this->sql->clause('alter'),
                    $this->sql->statement('table'),
                    $table,
                    $this->sql->statement('add'),
                    $this->sql->statement('foreignKey'),
                    $this->quoteIdentifier((string) ($params[0] ?? '')),
                    $this->sql->statement('references'),
                    $this->quoteIdentifier((string) ($params[1] ?? '')),
                    $this->quoteIdentifier((string) ($params[2] ?? '')),
                    $this->sql->operator('onDelete'),
                    $this->toUpperString((string) ($params[3] ?? 'RESTRICT')),
                    $this->sql->operator('onUpdate'),
                    $this->toUpperString((string) ($params[4] ?? 'RESTRICT'))
                ),
                'drop' => sprintf(
                    '%s %s %s %s %s %s',
                    $this->sql->clause('alter'),
                    $this->sql->statement('table'),
                    $table,
                    $this->sql->statement('drop'),
                    $this->sql->statement('foreignKey'),
                    $this->quoteIdentifier((string) ($params[0] ?? ''))
                ),
                default => throw $this->errorManager->resolveException('database', 'Invalid schema foreign key action.'),
            },
            'primarykey' => match ($action) {
                'create' => sprintf(
                    '%s %s %s %s %s (%s)',
                    $this->sql->clause('alter'),
                    $this->sql->statement('table'),
                    $table,
                    $this->sql->statement('add'),
                    $this->sql->statement('primaryKey'),
                    $this->compileIdentifierArray((array) ($params[0] ?? []))
                ),
                'drop' => sprintf(
                    '%s %s %s %s %s',
                    $this->sql->clause('alter'),
                    $this->sql->statement('table'),
                    $table,
                    $this->sql->statement('drop'),
                    $this->sql->statement('primaryKey')
                ),
                default => throw $this->errorManager->resolveException('database', 'Invalid schema primary key action.'),
            },
            default => throw $this->errorManager->resolveException(
                'database',
                sprintf('Invalid schema structure type [%s].', $structOp['structure'])
            ),
        };
    }

    public function processView(string $action, string $viewName, ?string $selectQuery = null): array
    {
        $payload = [
            'action' => $action,
            'view' => $viewName,
            'query' => $selectQuery,
        ];

        $this->pushOperation('view', $payload);

        return $payload;
    }

    public function buildView(array $viewOp): string
    {
        $view = $this->quoteIdentifier((string) $viewOp['view']);
        $query = $this->trimString((string) ($viewOp['query'] ?? ''));

        return match ($this->sql->normalize((string) $viewOp['action'])) {
            'create' => sprintf('%s %s %s %s %s', $this->sql->statement('create'), $this->sql->statement('view'), $view, $this->sql->operator('as'), $query),
            'drop' => sprintf('%s %s %s', $this->sql->statement('drop'), $this->sql->statement('view'), $view),
            'alter' => sprintf('%s %s %s %s %s', $this->sql->clause('alter'), $this->sql->statement('view'), $view, $this->sql->operator('as'), $query),
            default => throw $this->errorManager->resolveException('database', 'Invalid schema view action.'),
        };
    }

    public function processTrigger(
        string $action,
        string $triggerName,
        ?string $table = null,
        ?string $timing = null,
        ?string $event = null,
        ?string $statement = null
    ): array {
        $payload = [
            'action' => $action,
            'trigger' => $triggerName,
            'table' => $table,
            'timing' => $timing,
            'event' => $event,
            'statement' => $statement,
        ];

        $this->pushOperation('trigger', $payload);

        return $payload;
    }

    public function buildTrigger(array $triggerOp): string
    {
        $trigger = $this->quoteIdentifier((string) $triggerOp['trigger']);

        return match ($this->sql->normalize((string) $triggerOp['action'])) {
            'create' => sprintf(
                '%s %s %s %s %s %s %s %s %s',
                $this->sql->statement('create'),
                $this->sql->statement('trigger'),
                $trigger,
                $this->toUpperString((string) ($triggerOp['timing'] ?? '')),
                $this->toUpperString((string) ($triggerOp['event'] ?? '')),
                $this->sql->operator('on'),
                $this->quoteIdentifier((string) ($triggerOp['table'] ?? '')),
                $this->sql->operator('forEachRow'),
                $this->trimString((string) ($triggerOp['statement'] ?? ''))
            ),
            'drop' => sprintf('%s %s %s', $this->sql->statement('drop'), $this->sql->statement('trigger'), $trigger),
            'alter' => sprintf(
                '%s %s %s %s',
                $this->sql->clause('alter'),
                $this->sql->statement('trigger'),
                $trigger,
                $this->trimString((string) ($triggerOp['statement'] ?? ''))
            ),
            default => throw $this->errorManager->resolveException('database', 'Invalid schema trigger action.'),
        };
    }

    public function processDatabase(string $action, string $database, array $options = []): array
    {
        $payload = [
            'action' => $action,
            'database' => $database,
            'options' => $options,
        ];

        $this->pushOperation('database', $payload);

        return $payload;
    }

    public function buildDatabase(array $dbOp): string
    {
        $database = $this->quoteIdentifier((string) $dbOp['database']);

        return match ($this->sql->normalize((string) $dbOp['action'])) {
            'create' => sprintf('%s %s %s', $this->sql->statement('create'), $this->sql->statement('database'), $database),
            'drop' => sprintf('%s %s %s', $this->sql->statement('drop'), $this->sql->statement('database'), $database),
            'alter' => sprintf(
                '%s %s %s %s',
                $this->sql->clause('alter'),
                $this->sql->statement('database'),
                $database,
                $this->implodeWith(' ', $this->getValuesList((array) ($dbOp['options'] ?? [])))
            ),
            default => throw $this->errorManager->resolveException('database', 'Invalid schema database action.'),
        };
    }

    public function processRoutine(string $action, string $routineType, string $routineName, ?string $definition = null): array
    {
        $payload = [
            'action' => $action,
            'routineType' => $routineType,
            'name' => $routineName,
            'definition' => $definition,
        ];

        $this->pushOperation('routine', $payload);

        return $payload;
    }

    public function buildRoutine(array $routineOp): string
    {
        $name = $this->quoteIdentifier((string) $routineOp['name']);
        $type = $this->toUpperString((string) $routineOp['routineType']);

        return match ($this->sql->normalize((string) $routineOp['action'])) {
            'create' => sprintf(
                '%s %s %s %s %s',
                $this->sql->statement('create'),
                $type,
                $name,
                $this->sql->operator('as'),
                $this->trimString((string) ($routineOp['definition'] ?? ''))
            ),
            'drop' => sprintf('%s %s %s', $this->sql->statement('drop'), $type, $name),
            default => throw $this->errorManager->resolveException('database', 'Invalid schema routine action.'),
        };
    }

    public function processSequence(string $action, string $sequenceName, array $options = []): array
    {
        $payload = [
            'action' => $action,
            'sequence' => $sequenceName,
            'options' => $options,
        ];

        $this->pushOperation('sequence', $payload);

        return $payload;
    }

    public function buildSequence(array $seqOp): string
    {
        $sequence = $this->quoteIdentifier((string) $seqOp['sequence']);

        return match ($this->sql->normalize((string) $seqOp['action'])) {
            'create' => sprintf(
                '%s %s %s%s',
                $this->sql->statement('create'),
                $this->sql->statement('sequence'),
                $sequence,
                ($seqOp['options'] ?? []) === [] ? '' : ' ' . $this->implodeWith(' ', $this->getValuesList((array) $seqOp['options']))
            ),
            'drop' => sprintf('%s %s %s', $this->sql->statement('drop'), $this->sql->statement('sequence'), $sequence),
            default => throw $this->errorManager->resolveException('database', 'Invalid schema sequence action.'),
        };
    }

    protected function compileDefaultValue(mixed $value): string
    {
        return $this->quoteLiteral($value);
    }

    private function pushOperation(string $kind, array $payload): void
    {
        $this->operations[] = [
            'kind' => $kind,
            'payload' => $payload,
        ];
    }

    private function buildOperation(string $kind, array $payload): string
    {
        return match ($kind) {
            'column' => $this->buildColumn($payload),
            'table' => $this->buildTable($payload),
            'structure' => $this->buildStructure($payload),
            'view' => $this->buildView($payload),
            'trigger' => $this->buildTrigger($payload),
            'database' => $this->buildDatabase($payload),
            'routine' => $this->buildRoutine($payload),
            'sequence' => $this->buildSequence($payload),
            default => throw $this->errorManager->resolveException(
                'database',
                sprintf('Invalid schema operation kind [%s].', $kind)
            ),
        };
    }

    private function compileIdentifierArray(array $identifiers): string
    {
        return $this->implodeWith(
            ', ',
            $this->map(fn(string $identifier): string => $this->quoteIdentifier($identifier), $this->getValuesList($identifiers))
        );
    }
}
