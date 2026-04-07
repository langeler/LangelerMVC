<?php

declare(strict_types=1);

namespace App\Abstracts\Database;

use App\Contracts\Database\ModelInterface;
use App\Exceptions\Database\ModelException;

/**
 * Base database entity with mass-assignment protection and dirty tracking.
 */
abstract class Model implements ModelInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * @var array<string, mixed>
     */
    protected array $original = [];

    protected string $table = '';

    protected string $primaryKey = 'id';

    protected bool $timestamps = true;

    /**
     * @var string[]
     */
    protected array $fillable = [];

    /**
     * @var string[]
     */
    protected array $guarded = [];

    protected string $createdAtColumn = 'created_at';

    protected string $updatedAtColumn = 'updated_at';

    protected bool $exists = false;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        if ($attributes !== []) {
            $this->fill($attributes);
        }
    }

    public function getTable(): string
    {
        return $this->table !== '' ? $this->table : $this->resolveDefaultTableName();
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function getKey(): mixed
    {
        return $this->getAttribute($this->getPrimaryKey());
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $key = trim($key);

        if ($key === '') {
            throw new ModelException('Attribute names must be non-empty strings.');
        }

        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    public function usesTimestamps(): bool
    {
        return $this->timestamps;
    }

    public function fill(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $attribute = (string) $key;

            if (!$this->isFillable($attribute)) {
                throw new ModelException(
                    sprintf(
                        'Attribute [%s] is not mass assignable on model [%s].',
                        $attribute,
                        static::class
                    )
                );
            }

            $this->setAttribute($attribute, $value);
        }
    }

    public function forceFill(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute((string) $key, $value);
        }
    }

    public function getOriginal(): array
    {
        return $this->original;
    }

    public function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }

    public function getDirty(): array
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    public function isDirty(?string $attribute = null): bool
    {
        if ($attribute === null) {
            return $this->getDirty() !== [];
        }

        return array_key_exists($attribute, $this->getDirty());
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function markAsExisting(bool $exists = true): void
    {
        $this->exists = $exists;
    }

    public function getFillable(): array
    {
        return $this->fillable;
    }

    public function getGuarded(): array
    {
        return $this->guarded;
    }

    public function getCreatedAtColumn(): string
    {
        return $this->createdAtColumn;
    }

    public function getUpdatedAtColumn(): string
    {
        return $this->updatedAtColumn;
    }

    public function toArray(): array
    {
        return $this->getAttributes();
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        return $this->hasAttribute($key);
    }

    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    protected function isFillable(string $attribute): bool
    {
        if ($attribute === '') {
            return false;
        }

        if ($this->fillable !== []) {
            return in_array($attribute, $this->fillable, true);
        }

        if ($this->guarded === ['*']) {
            return false;
        }

        return !in_array($attribute, $this->guarded, true);
    }

    protected function resolveDefaultTableName(): string
    {
        $segments = explode('\\', static::class);
        $baseName = end($segments) ?: 'model';
        $snakeCase = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $baseName));

        return $snakeCase . 's';
    }
}
