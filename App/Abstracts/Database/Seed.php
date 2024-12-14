<?php

declare(strict_types=1);

namespace App\Abstracts\Database;

/**
 * Abstract Seed Class
 *
 * Responsibilities:
 * - Provides a contract for seeding database tables with initial or test data.
 * - Relies on a repository to insert data without directly handling raw queries.
 *
 * Boundaries:
 * - Does not handle HTTP, presentation, or business logic.
 * - Focused on data insertion logic for setup or testing environments.
 */
abstract class Seed
{
	/**
	 * Constructor using property promotion for dependency injection.
	 *
	 * @param object $repository The repository instance used for inserting, truncating,
	 *                           and retrieving default data. Concrete classes should
	 *                           provide a repository that knows how to interact with the database.
	 */
	public function __construct(protected object $repository)
	{
	}

	/**
	 * Define the data and logic for running the seed.
	 * Concrete classes implement their seeding logic here,
	 * calling insert methods on the repository as needed.
	 *
	 * @return void
	 */
	abstract protected function run(): void;

	/**
	 * Insert a single record into the database.
	 * Concrete classes must define how a single record is inserted using the repository.
	 *
	 * @param array<string,mixed> $data The data to insert as a single record.
	 * @return object The created record as a model instance.
	 */
	abstract protected function insert(array $data): object;

	/**
	 * Insert multiple records into the database at once.
	 * Concrete classes must define how batch inserts are handled by the repository.
	 *
	 * @param array<int,array<string,mixed>> $data An array of records to insert.
	 * @return object[] An array of created model instances.
	 */
	abstract protected function insertMany(array $data): array;

	/**
	 * Truncate the table associated with this seed.
	 * Concrete classes must define how the repository performs a truncate operation.
	 *
	 * @return void
	 */
	abstract protected function truncate(): void;

	/**
	 * Provide a default set of seed data.
	 * Concrete classes can define reusable arrays of attributes for easy seeding.
	 *
	 * @return array<int,array<string,mixed>> A default data set for seeding.
	 */
	abstract protected function defaultData(): array;
}
