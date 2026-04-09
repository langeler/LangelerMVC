<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Core\Database;
use App\Providers\ExceptionProvider;
use App\Utilities\Managers\SettingsManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Query\DataQuery;
use App\Utilities\Query\SchemaQuery;
use PDO;
use PHPUnit\Framework\TestCase;

class QueryLayerTest extends TestCase
{
    public function testDataQueryCompilesParameterizedSelectsAndJoinClauses(): void
    {
        $query = (new DataQuery())
            ->from('pages')
            ->select(['pages.id', 'users.name'])
            ->leftJoin('users', [['pages.user_id', '=', ['column' => 'users.id']]], ['users.name'])
            ->where('pages.status', '=', 'published')
            ->orderBy('pages.id', 'DESC')
            ->limit(10)
            ->offset(5)
            ->toExecutable();

        self::assertSame(
            'SELECT `pages`.`id`, `users`.`name` FROM `pages` LEFT JOIN `users` ON `pages`.`user_id` = `users`.`id` WHERE `pages`.`status` = ? ORDER BY `pages`.`id` DESC LIMIT 10 OFFSET 5',
            $query['sql']
        );
        self::assertSame(['published'], $query['bindings']);
    }

    public function testDataQuerySupportsSubqueriesAndSqlServerPagination(): void
    {
        $subquery = (new DataQuery(null, null, 'pages', [], [], 'sqlsrv'))
            ->select(['id'])
            ->where('status', '=', 'published');

        $query = (new DataQuery(null, null, 'pages', [], [], 'sqlsrv'))
            ->select(['id'])
            ->where('id', 'in', $subquery)
            ->orderBy('id')
            ->limit(10)
            ->offset(20)
            ->toExecutable();

        self::assertSame(
            'SELECT [id] FROM [pages] WHERE [id] IN (SELECT [id] FROM [pages] WHERE [status] = ?) ORDER BY [id] ASC OFFSET 20 ROWS FETCH NEXT 10 ROWS ONLY',
            $query['sql']
        );
        self::assertSame(['published'], $query['bindings']);
    }

    public function testSchemaQueryBuildsOrderedSchemaStatements(): void
    {
        $schema = new SchemaQuery(null, null, '', [], [], 'pgsql');
        $statements = $schema
            ->createTable('pages', ['id INTEGER PRIMARY KEY', 'title TEXT NOT NULL'], ['UNIQUE ("title")'])
            ->addColumn('pages', 'slug', 'TEXT')
            ->setDefault('pages', 'title', 'Home')
            ->toStatements();

        self::assertSame(
            'CREATE TABLE "pages" (id INTEGER PRIMARY KEY, title TEXT NOT NULL, UNIQUE ("title"))',
            $statements[0]
        );
        self::assertSame(
            'ALTER TABLE "pages" ADD COLUMN "slug" TEXT',
            $statements[1]
        );
        self::assertSame(
            'ALTER TABLE "pages" ALTER COLUMN "title" SET DEFAULT \'Home\'',
            $statements[2]
        );
    }

    public function testDatabaseFactoriesExecuteCompiledQueriesAgainstSqlite(): void
    {
        $database = $this->makeSqliteDatabase();
        $database->query('CREATE TABLE pages (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, status TEXT)');

        $insert = $database
            ->dataQuery('pages')
            ->insert('pages', ['title' => 'Home', 'status' => 'published'])
            ->toExecutable();

        $database->execute($insert['sql'], $insert['bindings']);

        $select = $database
            ->dataQuery('pages')
            ->select(['title'])
            ->where('status', '=', 'published')
            ->limit(1)
            ->toExecutable();

        self::assertSame('Home', $database->fetchColumn($select['sql'], $select['bindings']));
        self::assertSame(
            'CREATE TABLE "audit" ("id" INTEGER PRIMARY KEY)',
            $database->schemaQuery()->createTable('audit', ['"id" INTEGER PRIMARY KEY'])->toStatements()[0]
        );
    }

    private function makeSqliteDatabase(): Database
    {
        $settings = new class extends SettingsManager {
            public function __construct() {}

            public function getAllSettings(string $fileName): array
            {
                return [
                    'DRIVER' => 'sqlite',
                    'CONNECTION' => 'sqlite',
                    'DATABASE' => ':memory:',
                ];
            }
        };

        return new Database(
            $settings,
            new ErrorManager(new ExceptionProvider()),
            new PDO('sqlite::memory:')
        );
    }
}
