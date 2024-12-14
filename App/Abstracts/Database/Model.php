<?php

declare(strict_types=1);

namespace App\Abstracts\Database;

/**
 * Abstract Model Class
 *
 * Responsibilities:
 * - Represents a single database record as an object.
 * - Defines contracts for attribute handling, table configuration, and timestamps.
 * - Does not directly interact with the database (that’s the repository’s responsibility).
 *
 * Boundaries:
 * - No HTTP or presentation logic.
 * - No complex business logic: that should be handled by services.
 * - Focused on entity representation, not persistence.
 */
abstract class Model
{
	/**
	 * Construct a new model instance with optional initial attributes.
	 *
	 * @param array<string,mixed> $attributes Key-value pairs of model properties.
	 */
	public function __construct(protected array $attributes = [])
	{
	}

	/**
	 * The table name associated with the model.
	 * Defined by concrete classes via getTable().
	 */
	protected string $table;

	/**
	 * The primary key for the model.
	 * Can be overridden by concrete models via getPrimaryKey().
	 */
	protected string $primaryKey = 'id';

	/**
	 * Indicates if the model uses timestamp fields (created_at, updated_at).
	 */
	protected bool $timestamps = true;

	/**
	 * The attributes that are mass assignable.
	 * Concrete models can define their fillable attributes.
	 */
	protected array $fillable = [];

	/**
	 * The attributes that are guarded from mass assignment.
	 * Concrete models can define attributes that should not be mass assigned.
	 */
	protected array $guarded = [];

	/**
	 * The original attributes as retrieved from the database.
	 * Used for change detection or reverting to original state.
	 */
	protected array $original = [];

	/**
	 * Get the table name associated with the model.
	 *
	 * @return string The table name.
	 */
	abstract protected function getTable(): string;

	/**
	 * Get the primary key for the model.
	 *
	 * @return string The primary key column name.
	 */
	abstract protected function getPrimaryKey(): string;

	/**
	 * Retrieve all attributes of the model.
	 *
	 * @return array<string,mixed> An associative array of all attributes.
	 */
	abstract protected function getAttributes(): array;

	/**
	 * Set a specific attribute on the model.
	 *
	 * @param string $key   The attribute name.
	 * @param mixed  $value The attribute value.
	 * @return void
	 */
	abstract protected function setAttribute(string $key, mixed $value): void;

	/**
	 * Get a specific attribute from the model.
	 *
	 * @param string $key The attribute name.
	 * @return mixed The attribute value.
	 */
	abstract protected function getAttribute(string $key): mixed;

	/**
	 * Determine if a specific attribute exists on the model.
	 *
	 * @param string $key The attribute name.
	 * @return bool True if the attribute exists, false otherwise.
	 */
	abstract protected function hasAttribute(string $key): bool;

	/**
	 * Check if timestamps are enabled for the model.
	 *
	 * @return bool True if timestamps are used, false otherwise.
	 */
	abstract protected function usesTimestamps(): bool;

	/**
	 * Fill the model with an array of attributes, respecting fillable and guarded rules.
	 *
	 * @param array<string,mixed> $attributes Attributes to mass assign.
	 * @return void
	 */
	abstract protected function fill(array $attributes): void;

	/**
	 * Retrieve the original attributes as retrieved from the database.
	 *
	 * @return array<string,mixed> An associative array of the original attributes.
	 */
	abstract protected function getOriginal(): array;
}
