<?php

namespace App\Utilities\Handlers;

use InvalidArgumentException;

class SQLHandler
{
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
        'as' => 'AS',
        'between' => 'BETWEEN',
        'divide' => '/',
        'equal' => '=',
        'for each row' => 'FOR EACH ROW',
        'greaterthan' => '>',
        'greaterthanorequal' => '>=',
        'in' => 'IN',
        'is' => 'IS',
        'is not' => 'IS NOT',
        'lessthan' => '<',
        'lessthanorequal' => '<=',
        'like' => 'LIKE',
        'minus' => '-',
        'modulus' => '%',
        'multiply' => '*',
        'not' => 'NOT',
        'notequal' => '!=',
        'on' => 'ON',
        'on delete' => 'ON DELETE',
        'on update' => 'ON UPDATE',
        'or' => 'OR',
        'plus' => '+',
        'to' => 'TO',
    ];

    /**
     * @var array<string, string>
     */
    private array $statements = [
        'add' => 'ADD',
        'alter' => 'ALTER',
        'column' => 'COLUMN',
        'constraint' => 'CONSTRAINT',
        'create' => 'CREATE',
        'database' => 'DATABASE',
        'delete' => 'DELETE',
        'drop' => 'DROP',
        'drop default' => 'DROP DEFAULT',
        'foreign key' => 'FOREIGN KEY',
        'function' => 'FUNCTION',
        'index' => 'INDEX',
        'insert' => 'INSERT',
        'into' => 'INTO',
        'modify' => 'MODIFY',
        'primary key' => 'PRIMARY KEY',
        'procedure' => 'PROCEDURE',
        'references' => 'REFERENCES',
        'rename' => 'RENAME',
        'select' => 'SELECT',
        'sequence' => 'SEQUENCE',
        'set default' => 'SET DEFAULT',
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

    /**
     * @param array<string, string> $map
     */
    private function lookup(array $map, ?string $type, string $kind): string
    {
        $normalized = strtolower(trim((string) $type));

        return $map[$normalized]
            ?? throw new InvalidArgumentException("Invalid SQL {$kind} type: {$type}");
    }
}
