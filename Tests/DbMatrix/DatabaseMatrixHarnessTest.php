<?php

declare(strict_types=1);

namespace Tests\DbMatrix;

use App\Abstracts\Database\Model;
use App\Abstracts\Database\Repository;
use App\Core\Database;
use App\Providers\ExceptionProvider;
use App\Utilities\Managers\SettingsManager;
use App\Utilities\Managers\System\ErrorManager;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;

final class MatrixHarnessModel extends Model
{
    protected string $table = 'framework_matrix_records';

    protected array $fillable = [
        'id',
        'title',
        'created_at',
        'updated_at',
    ];
}

final class MatrixHarnessRepository extends Repository
{
    protected string $modelClass = MatrixHarnessModel::class;
}

final class DatabaseMatrixHarnessTest extends TestCase
{
    #[DataProvider('configuredConnections')]
    public function testConfiguredDriversSupportSchemaQueryRepositoryAndDataQueryRoundTrip(
        string $driver,
        string $dsn,
        ?string $user,
        ?string $password
    ): void {
        $target = getenv('LANGELER_DB_MATRIX_TARGET') ?: '';

        if ($target !== '' && strcasecmp($target, $driver) !== 0) {
            self::markTestSkipped(sprintf('Skipping [%s] because LANGELER_DB_MATRIX_TARGET=%s.', $driver, $target));
        }

        if ($dsn === '') {
            self::markTestSkipped(sprintf('Set LANGELER_%s_DSN to run the %s matrix harness.', strtoupper($driver), $driver));
        }

        try {
            $pdo = new PDO($dsn, $user ?: null, $password ?: null);
        } catch (Throwable $exception) {
            self::markTestSkipped(sprintf('Unable to connect to %s for matrix testing: %s', $driver, $exception->getMessage()));
        }

        $database = $this->makeDatabase($driver, $pdo);
        $table = 'framework_matrix_records';
        $quotedId = $this->quoteIdentifier('id', $driver);
        $quotedTitle = $this->quoteIdentifier('title', $driver);
        $quotedCreatedAt = $this->quoteIdentifier('created_at', $driver);
        $quotedUpdatedAt = $this->quoteIdentifier('updated_at', $driver);

        $this->dropTableIfExists($database, $table, $driver);

        foreach (
            $database->schemaQuery()->createTable($table, [
                $quotedId . ' VARCHAR(64) PRIMARY KEY',
                $quotedTitle . ' VARCHAR(191) NOT NULL',
                $quotedCreatedAt . ' VARCHAR(32) NULL',
                $quotedUpdatedAt . ' VARCHAR(32) NULL',
            ])->toStatements() as $statement
        ) {
            $database->query($statement);
        }

        try {
            $repository = new MatrixHarnessRepository($database);
            $created = $repository->create([
                'id' => 'matrix-' . $driver,
                'title' => 'Matrix ' . $driver,
            ]);

            $found = $repository->find('matrix-' . $driver);
            $select = $database
                ->dataQuery($table)
                ->select(['title'])
                ->where('id', '=', 'matrix-' . $driver)
                ->limit(1)
                ->toExecutable();

            self::assertSame('matrix-' . $driver, (string) $created->getKey());
            self::assertNotNull($found);
            self::assertSame('Matrix ' . $driver, (string) $found?->getAttribute('title'));
            self::assertSame('Matrix ' . $driver, $database->fetchColumn($select['sql'], $select['bindings']));
        } finally {
            $this->dropTableIfExists($database, $table, $driver);
        }
    }

    /**
     * @return array<string, array{0:string,1:string,2:?string,3:?string}>
     */
    public static function configuredConnections(): array
    {
        return [
            'mysql' => [
                'mysql',
                (string) (getenv('LANGELER_MYSQL_DSN') ?: ''),
                self::nullableEnv('LANGELER_MYSQL_USER'),
                self::nullableEnv('LANGELER_MYSQL_PASSWORD'),
            ],
            'pgsql' => [
                'pgsql',
                (string) (getenv('LANGELER_PGSQL_DSN') ?: ''),
                self::nullableEnv('LANGELER_PGSQL_USER'),
                self::nullableEnv('LANGELER_PGSQL_PASSWORD'),
            ],
            'sqlsrv' => [
                'sqlsrv',
                (string) (getenv('LANGELER_SQLSRV_DSN') ?: ''),
                self::nullableEnv('LANGELER_SQLSRV_USER'),
                self::nullableEnv('LANGELER_SQLSRV_PASSWORD'),
            ],
        ];
    }

    private function makeDatabase(string $driver, PDO $pdo): Database
    {
        $settings = new class($driver) extends SettingsManager {
            public function __construct(private readonly string $driver)
            {
            }

            public function getAllSettings(string $fileName): array
            {
                return [
                    'DRIVER' => $this->driver,
                    'CONNECTION' => $this->driver,
                    'DATABASE' => 'matrix',
                ];
            }
        };

        return new Database(
            $settings,
            new ErrorManager(new ExceptionProvider()),
            $pdo
        );
    }

    private function dropTableIfExists(Database $database, string $table, string $driver): void
    {
        try {
            $database->query('DROP TABLE ' . $this->quoteIdentifier($table, $driver));
        } catch (Throwable) {
        }
    }

    private function quoteIdentifier(string $identifier, string $driver): string
    {
        return match (strtolower($driver)) {
            'pgsql' => '"' . $identifier . '"',
            'sqlsrv' => '[' . $identifier . ']',
            default => '`' . $identifier . '`',
        };
    }

    private static function nullableEnv(string $name): ?string
    {
        $value = getenv($name);

        return $value === false || $value === '' ? null : $value;
    }
}
