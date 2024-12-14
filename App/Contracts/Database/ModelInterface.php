<?php

declare(strict_types=1);

namespace App\Contracts\Database;

/**
 * ModelInterface
 *
 * Defines the contract for representing a single database entity (row).
 * Aligns with the abstract Model class, providing a public API for
 * getting/setting attributes, table and primary key info, timestamps,
 * and handling mass assignment.
 */
interface ModelInterface
{
	/**
	 * Get the database table name associated with the model.
	 *
	 * @return string
	 */
	public function getTable(): string;

	/**
	 * Get the primary key for the model.
	 *
	 * @return string
	 */
	public function getPrimaryKey(): string;

	/**
	 * Retrieve all attributes of the model.
	 *
	 * @return array<string,mixed>
	 */
	public function getAttributes(): array;

	/**
	 * Set a specific attribute on the model.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function setAttribute(string $key, mixed $value): void;

	/**
	 * Get a specific attribute from the model.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getAttribute(string $key): mixed;

	/**
	 * Determine if a specific attribute exists on the model.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function hasAttribute(string $key): bool;

	/**
	 * Check if timestamps are enabled for the model.
	 *
	 * @return bool
	 */
	public function usesTimestamps(): bool;

	/**
	 * Fill the model with an array of attributes,
	 * respecting fillable and guarded rules.
	 *
	 * @param array<string,mixed> $attributes
	 * @return void
	 */
	public function fill(array $attributes): void;

	/**
	 * Retrieve the original attributes as retrieved from the database.
	 *
	 * @return array<string,mixed>
	 */
	public function getOriginal(): array;
}
