<?php

declare(strict_types=1);

namespace App\Contracts\Database;

/**
 * SeedInterface
 *
 * Defines the contract for seeding database tables with initial or test data.
 * Aligns with the abstract Seed class, providing a public API for inserting and truncating data,
 * as well as retrieving default seed data sets.
 */
interface SeedInterface
{
	/**
	 * Define the data and logic for running the seed.
	 *
	 * @return void
	 */
	public function run(): void;

	/**
	 * Insert a single record into the database.
	 *
	 * @param array<string,mixed> $data
	 * @return object The created record as a model instance.
	 */
	public function insert(array $data): object;

	/**
	 * Insert multiple records into the database at once.
	 *
	 * @param array<int,array<string,mixed>> $data
	 * @return object[] An array of created model instances.
	 */
	public function insertMany(array $data): array;

	/**
	 * Truncate the table associated with this seed.
	 *
	 * @return void
	 */
	public function truncate(): void;

	/**
	 * Provide a default set of seed data.
	 *
	 * @return array<int,array<string,mixed>> A default data set for seeding.
	 */
	public function defaultData(): array;
}
