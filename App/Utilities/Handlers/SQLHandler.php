<?php

declare(strict_types=1);

namespace App\Utilities\Handlers;

use InvalidArgumentException;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\Patterns\PatternTrait;

/**
 * Central SQL vocabulary registry for the framework query layer.
 *
 * The handler deliberately normalizes camelCase, snake_case, kebab-case, and
 * spaced aliases into the same lookup key so the builders can keep a readable
 * API without duplicating keyword tables.
 */
class SQLHandler
{
    use ManipulationTrait, PatternTrait;

    /**
     * @var array<string, string>
     */
    private array $clauses = [
        'alter' => 'ALTER',
        'connectby' => 'CONNECT BY',
        'crossjoin' => 'CROSS JOIN',
        'distincton' => 'DISTINCT ON',
        'drop' => 'DROP',
        'except' => 'EXCEPT',
        'fetch' => 'FETCH FIRST',
        'filter' => 'FILTER',
        'from' => 'FROM',
        'fulljoin' => 'FULL JOIN',
        'fullouterjoin' => 'FULL OUTER JOIN',
        'groupby' => 'GROUP BY',
        'having' => 'HAVING',
        'innerjoin' => 'INNER JOIN',
        'into' => 'INTO',
        'intersect' => 'INTERSECT',
        'join' => 'JOIN',
        'leftjoin' => 'LEFT JOIN',
        'limit' => 'LIMIT',
        'minus' => 'MINUS',
        'naturaljoin' => 'NATURAL JOIN',
        'offset' => 'OFFSET',
        'on' => 'ON',
        'orderby' => 'ORDER BY',
        'outerapply' => 'OUTER APPLY',
        'overlaps' => 'OVERLAPS',
        'returning' => 'RETURNING',
        'rightjoin' => 'RIGHT JOIN',
        'set' => 'SET',
        'startwith' => 'START WITH',
        'tablesample' => 'TABLESAMPLE',
        'truncate' => 'TRUNCATE',
        'union' => 'UNION',
        'unionall' => 'UNION ALL',
        'using' => 'USING',
        'values' => 'VALUES',
        'where' => 'WHERE',
        'window' => 'WINDOW',
        'with' => 'WITH',
    ];

    /**
     * @var array<string, string>
     */
    private array $expressions = [
        'age' => 'AGE()',
        'arrayagg' => 'ARRAY_AGG()',
        'avg' => 'AVG()',
        'case' => 'CASE',
        'cast' => 'CAST()',
        'coalesce' => 'COALESCE()',
        'concat' => 'CONCAT()',
        'count' => 'COUNT()',
        'currentdate' => 'CURRENT_DATE',
        'currenttime' => 'CURRENT_TIME',
        'currenttimestamp' => 'CURRENT_TIMESTAMP',
        'datetrunc' => 'DATE_TRUNC()',
        'distinct' => 'DISTINCT',
        'extract' => 'EXTRACT()',
        'jsonarrayagg' => 'JSON_ARRAYAGG()',
        'jsonextract' => 'JSON_EXTRACT()',
        'jsonobjectagg' => 'JSON_OBJECTAGG()',
        'lag' => 'LAG()',
        'lead' => 'LEAD()',
        'length' => 'LENGTH()',
        'lower' => 'LOWER()',
        'max' => 'MAX()',
        'min' => 'MIN()',
        'now' => 'NOW()',
        'nullif' => 'NULLIF()',
        'over' => 'OVER',
        'partitionby' => 'PARTITION BY',
        'position' => 'POSITION()',
        'rank' => 'RANK()',
        'replace' => 'REPLACE()',
        'reverse' => 'REVERSE()',
        'rownumber' => 'ROW_NUMBER()',
        'substring' => 'SUBSTRING()',
        'sum' => 'SUM()',
        'trim' => 'TRIM()',
        'upper' => 'UPPER()',
        'when' => 'WHEN',
    ];

    /**
     * @var array<string, string>
     */
    private array $operators = [
        'and' => 'AND',
        'andnot' => 'AND NOT',
        'any' => 'ANY',
        'as' => 'AS',
        'between' => 'BETWEEN',
        'divide' => '/',
        'equal' => '=',
        'exists' => 'EXISTS',
        'foreachrow' => 'FOR EACH ROW',
        'greaterthan' => '>',
        'greaterthanorequal' => '>=',
        'ilike' => 'ILIKE',
        'in' => 'IN',
        'is' => 'IS',
        'isdistinctfrom' => 'IS DISTINCT FROM',
        'isnot' => 'IS NOT',
        'isnotnull' => 'IS NOT NULL',
        'isnull' => 'IS NULL',
        'lessthan' => '<',
        'lessthanorequal' => '<=',
        'like' => 'LIKE',
        'minus' => '-',
        'modulus' => '%',
        'multiply' => '*',
        'not' => 'NOT',
        'notbetween' => 'NOT BETWEEN',
        'notdistinctfrom' => 'IS NOT DISTINCT FROM',
        'notequal' => '!=',
        'notequalalt' => '<>',
        'notexists' => 'NOT EXISTS',
        'notin' => 'NOT IN',
        'notlike' => 'NOT LIKE',
        'notregexp' => 'NOT REGEXP',
        'notsimilarto' => 'NOT SIMILAR TO',
        'nullsafeequal' => '<=>',
        'on' => 'ON',
        'ondelete' => 'ON DELETE',
        'onupdate' => 'ON UPDATE',
        'or' => 'OR',
        'ornot' => 'OR NOT',
        'overlaps' => 'OVERLAPS',
        'plus' => '+',
        'regexp' => 'REGEXP',
        'similarto' => 'SIMILAR TO',
        'soundslike' => 'SOUNDS LIKE',
        'to' => 'TO',
        'xor' => 'XOR',
    ];

    /**
     * @var array<string, string>
     */
    private array $statements = [
        'add' => 'ADD',
        'alter' => 'ALTER',
        'check' => 'CHECK',
        'column' => 'COLUMN',
        'constraint' => 'CONSTRAINT',
        'create' => 'CREATE',
        'database' => 'DATABASE',
        'delete' => 'DELETE',
        'drop' => 'DROP',
        'dropdefault' => 'DROP DEFAULT',
        'foreignkey' => 'FOREIGN KEY',
        'function' => 'FUNCTION',
        'index' => 'INDEX',
        'insert' => 'INSERT',
        'into' => 'INTO',
        'modify' => 'MODIFY',
        'primarykey' => 'PRIMARY KEY',
        'procedure' => 'PROCEDURE',
        'references' => 'REFERENCES',
        'rename' => 'RENAME',
        'select' => 'SELECT',
        'sequence' => 'SEQUENCE',
        'setdefault' => 'SET DEFAULT',
        'table' => 'TABLE',
        'trigger' => 'TRIGGER',
        'truncate' => 'TRUNCATE',
        'unique' => 'UNIQUE',
        'update' => 'UPDATE',
        'values' => 'VALUES',
        'view' => 'VIEW',
    ];

    public function clause(?string $type = null): string
    {
        return $this->lookup($this->clauses, $type, 'clause');
    }

    public function expression(?string $type = null): string
    {
        return $this->lookup($this->expressions, $type, 'expression');
    }

    public function operator(?string $type = null): string
    {
        return $this->lookup($this->operators, $type, 'operator');
    }

    public function statement(?string $type = null): string
    {
        return $this->lookup($this->statements, $type, 'statement');
    }

    public function normalize(?string $type): string
    {
        $value = $this->trimString((string) $type);
        $value = $this->replaceByPattern('/(?<!^)[A-Z]/', '_$0', $value) ?? $value;
        $value = $this->toLower($value);

        return $this->replaceByPattern('/[^a-z0-9]+/', '', $value) ?? '';
    }

    /**
     * @param array<string, string> $map
     */
    private function lookup(array $map, ?string $type, string $kind): string
    {
        $normalized = $this->normalize($type);

        return $map[$normalized]
            ?? throw new InvalidArgumentException("Invalid SQL {$kind} type: {$type}");
    }
}
