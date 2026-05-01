# Database Matrix Testing

LangelerMVC now keeps the fast day-to-day regression suite focused on `Tests/Framework`, with external database and runtime-backend verification available through a separate matrix harness.

## Purpose

The matrix harness exists to verify framework-level schema, query-builder, and repository behavior against:

- MySQL
- PostgreSQL
- SQL Server
- Redis-backed cache/session runtime
- Memcached-backed cache runtime

SQLite remains the fast default path for normal local development and `composer test`.

## Available Commands

```bash
composer test
composer test:sqlite
composer test:db-matrix
composer test:mysql
composer test:pgsql
composer test:sqlsrv
composer test:runtime-backends
composer test:redis
composer test:memcached
composer ops:health
```

## Local Backend Stack

The repository now includes `docker-compose.verify.yml` for local production-style verification of:

- MySQL
- PostgreSQL
- SQL Server
- Redis
- Memcached

Bring the stack up with:

```bash
docker compose -f docker-compose.verify.yml up -d
```

`docker-compose.verify.yml` exposes the standard local ports:

- MySQL: `3306`
- PostgreSQL: `5432`
- SQL Server: `1433`
- Redis: `6379`
- Memcached: `11211`

The GitHub Actions workflow uses hosted-service port mappings (`3307` for MySQL and `5433` for PostgreSQL) inside the CI job so service setup stays deterministic on runners.

## Environment Variables

Set the DSN and optional credentials for the drivers you want to verify:

```bash
export LANGELER_MYSQL_DSN="mysql:host=127.0.0.1;port=3306;dbname=langelermvc_test"
export LANGELER_MYSQL_USER="root"
export LANGELER_MYSQL_PASSWORD="secret"

export LANGELER_PGSQL_DSN="pgsql:host=127.0.0.1;port=5432;dbname=langelermvc_test"
export LANGELER_PGSQL_USER="postgres"
export LANGELER_PGSQL_PASSWORD="secret"

export LANGELER_SQLSRV_DSN="sqlsrv:Server=127.0.0.1,1433;Database=langelermvc_test"
export LANGELER_SQLSRV_USER="sa"
export LANGELER_SQLSRV_PASSWORD="secret"

export LANGELER_REDIS_HOST="127.0.0.1"
export LANGELER_REDIS_PORT="6379"
export LANGELER_REDIS_PASSWORD=""
export LANGELER_REDIS_DATABASE="0"

export LANGELER_MEMCACHED_HOST="127.0.0.1"
export LANGELER_MEMCACHED_PORT="11211"
```

## How It Works

- `phpunit.xml` runs the default framework suite only.
- `phpunit.db-matrix.xml` runs `Tests/DbMatrix`.
- `Tests/DbMatrix/DatabaseMatrixHarnessTest.php` only executes for drivers that have DSNs configured.
- `Tests/DbMatrix/RuntimeBackendHarnessTest.php` only executes Redis/Memcached checks when the matching PHP extension and local service are available.
- The harness creates framework-managed tables, exercises schema/query/repository round-trips, and then removes temporary state.
- The runtime harness performs namespaced cache and session round-trips without requiring project-specific credentials.

## Notes

- The harness is intentionally local and opt-in for SQL Server and other environment-specific backends. GitHub Actions now covers the default suite plus supported MySQL/PostgreSQL matrix execution.
- If a driver DSN is not configured, the related harness test is skipped rather than failing the default local workflow.
- If Redis or Memcached extensions/services are unavailable, their runtime checks are skipped instead of blocking unrelated release work.
- The GitHub Actions workflow now prints the selected matrix target, waits explicitly for MySQL/PostgreSQL readiness, and uploads DB service logs on failures to make CI diagnosis less opaque.
- The matrix harness is the intended place to extend future cross-driver verification for migrations, repositories, query builders, queue tables, notification persistence, cache persistence, and session persistence.
