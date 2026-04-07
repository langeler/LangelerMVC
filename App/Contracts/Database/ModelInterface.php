<?php

declare(strict_types=1);

namespace App\Contracts\Database;

use JsonSerializable;

/**
 * Public contract for a database-backed domain entity.
 */
interface ModelInterface extends JsonSerializable
{
    public function getTable(): string;

    public function getPrimaryKey(): string;

    public function getKey(): mixed;

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array;

    public function setAttribute(string $key, mixed $value): void;

    public function getAttribute(string $key): mixed;

    public function hasAttribute(string $key): bool;

    public function usesTimestamps(): bool;

    public function fill(array $attributes): void;

    public function forceFill(array $attributes): void;

    /**
     * @return array<string, mixed>
     */
    public function getOriginal(): array;

    public function syncOriginal(): void;

    /**
     * @return array<string, mixed>
     */
    public function getDirty(): array;

    public function isDirty(?string $attribute = null): bool;

    public function exists(): bool;

    public function markAsExisting(bool $exists = true): void;

    /**
     * @return string[]
     */
    public function getFillable(): array;

    /**
     * @return string[]
     */
    public function getGuarded(): array;

    public function getCreatedAtColumn(): string;

    public function getUpdatedAtColumn(): string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
