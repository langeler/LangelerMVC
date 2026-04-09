<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Data;

use App\Contracts\Data\CacheDriverInterface;
use App\Exceptions\Data\CacheException;
use App\Providers\CacheProvider;
use App\Utilities\Managers\SettingsManager;
use App\Utilities\Traits\{
    ArrayTrait,
    ConversionTrait,
    ErrorTrait,
    ManipulationTrait,
    TypeCheckerTrait
};
use App\Utilities\Traits\Patterns\PatternTrait;

class CacheManager
{
    use ArrayTrait, ConversionTrait, ErrorTrait, ManipulationTrait, PatternTrait, TypeCheckerTrait;

    public array $cacheSettings;
    public CacheDriverInterface $cacheDriver;

    public function __construct(
        protected CacheProvider $cacheProvider,
        protected SettingsManager $settingsManager,
        ?CacheDriverInterface $cacheDriver = null
    ) {
        $this->cacheProvider->registerServices();
        $this->cacheSettings = $this->normalizeCacheSettings(
            $this->settingsManager->getAllSettings('CACHE')
        );
        $this->cacheDriver = $cacheDriver ?? $this->resolveCacheDriver();
    }

    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->cacheDriver->set($this->normalizeKey($key), $value, $ttl);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->put($key, $value, $ttl);
    }

    public function forever(string $key, mixed $value): bool
    {
        return $this->put($key, $value, 0);
    }

    public function add(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->has($key)
            ? false
            : $this->put($key, $value, $ttl);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->cacheDriver->get($this->normalizeKey($key));

        if ($value !== null) {
            return $value;
        }

        return $this->isCallable($default)
            ? $default()
            : $default;
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $normalizedKey = $this->normalizeKey($key);
        $value = $this->cacheDriver->get($normalizedKey);

        if ($value === null) {
            return $this->isCallable($default)
                ? $default()
                : $default;
        }

        $this->cacheDriver->delete($normalizedKey);

        return $value;
    }

    public function remember(string $key, callable $resolver, ?int $ttl = null): mixed
    {
        $normalizedKey = $this->normalizeKey($key);
        $cached = $this->cacheDriver->get($normalizedKey);

        if ($cached !== null) {
            return $cached;
        }

        $value = $resolver();
        $this->cacheDriver->set($normalizedKey, $value, $ttl);

        return $value;
    }

    public function rememberForever(string $key, callable $resolver): mixed
    {
        return $this->remember($key, $resolver, 0);
    }

    public function has(string $key): bool
    {
        return $this->cacheDriver->has($this->normalizeKey($key));
    }

    public function missing(string $key): bool
    {
        return !$this->has($key);
    }

    public function forget(string $key): bool
    {
        return $this->cacheDriver->delete($this->normalizeKey($key));
    }

    public function delete(string $key): bool
    {
        return $this->forget($key);
    }

    public function clear(): bool
    {
        return $this->cacheDriver->clear();
    }

    public function flush(): bool
    {
        return $this->clear();
    }

    public function putMultiple(array $items, ?int $ttl = null): bool
    {
        return $this->all(
            $items,
            fn(mixed $value, mixed $key): bool => $this->put((string) $key, $value, $ttl)
        );
    }

    public function setMultiple(array $items, ?int $ttl = null): bool
    {
        return $this->putMultiple($items, $ttl);
    }

    public function getMultiple(array $keys, mixed $default = null): array
    {
        $values = [];

        foreach ($keys as $key) {
            $values[(string) $key] = $this->get((string) $key, $default);
        }

        return $values;
    }

    public function deleteMultiple(array $keys): bool
    {
        return $this->all(
            $keys,
            fn(mixed $key): bool => $this->forget((string) $key)
        );
    }

    public function many(array $keys, mixed $default = null): array
    {
        return $this->getMultiple($keys, $default);
    }

    public function updateCacheDriver(array $newSettings = []): self
    {
        $this->cacheSettings = $this->normalizeCacheSettings($this->merge($this->cacheSettings, $newSettings));
        $this->cacheDriver = $this->cacheProvider->getCacheDriver($this->cacheSettings);

        return $this;
    }

    public function getDriver(): CacheDriverInterface
    {
        return $this->cacheDriver;
    }

    public function getDriverName(): string
    {
        return $this->cacheDriver->driverName();
    }

    /**
     * @return array<string, mixed>
     */
    public function capabilities(): array
    {
        return $this->cacheDriver->capabilities();
    }

    public function supports(string $feature): bool
    {
        return $this->cacheDriver->supports($feature);
    }

    public function isEnabled(): bool
    {
        return $this->normalizeBoolean($this->cacheSettings['ENABLED'] ?? true, true);
    }

    public function defaultTtl(): int
    {
        $ttl = $this->toInt($this->cacheSettings['TTL'] ?? 3600);

        return $ttl > 0 ? $ttl : 0;
    }

    public function prefix(): string
    {
        $prefix = $this->normalizeStringSetting($this->cacheSettings['PREFIX'] ?? 'langelermvc_cache');

        return $prefix !== '' ? $prefix : 'langelermvc_cache';
    }

    private function resolveCacheDriver(): CacheDriverInterface
    {
        return $this->cacheProvider->getCacheDriver($this->cacheSettings);
    }

    private function normalizeCacheSettings(array $settings): array
    {
        $driver = $this->normalizeStringSetting((string) ($settings['DRIVER'] ?? 'file'));
        $driver = match ($this->toLower($driver)) {
            'memcached' => 'memcache',
            default => $this->toLower($driver),
        };

        $settings['DRIVER'] = $driver;
        $settings['TTL'] = $this->toInt($settings['TTL'] ?? 3600);
        $settings['PREFIX'] = $this->normalizeStringSetting($settings['PREFIX'] ?? 'langelermvc_cache');
        $settings['ENABLED'] = $this->normalizeBoolean($settings['ENABLED'] ?? true, true);
        $settings['COMPRESSION'] = $this->normalizeBoolean($settings['COMPRESSION'] ?? true, true);
        $settings['ENCRYPT'] = $this->normalizeBoolean($settings['ENCRYPT'] ?? false, false);

        return $settings;
    }

    private function normalizeKey(string $key): string
    {
        $normalized = $this->trimString($key);

        if ($normalized === '') {
            throw new CacheException('Cache key must be a non-empty string.');
        }

        if ($this->match('/[\x00-\x1F\x7F]/', $normalized) === 1) {
            throw new CacheException("Cache key contains invalid control characters: {$key}");
        }

        return $normalized;
    }

    private function normalizeBoolean(mixed $value, bool $default): bool
    {
        if ($this->isBool($value)) {
            return $value;
        }

        if ($this->isInt($value) || $this->isFloat($value)) {
            return (int) $value !== 0;
        }

        if (!$this->isString($value)) {
            return $default;
        }

        return match ($this->toLower($this->normalizeStringSetting($value))) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off', '' => false,
            default => $default,
        };
    }

    private function normalizeStringSetting(mixed $value): string
    {
        if (!$this->isString($value) && !$this->isInt($value) && !$this->isFloat($value) && !$this->isBool($value)) {
            return '';
        }

        $stringValue = (string) $value;
        $withoutComment = (string) ($this->replaceByPattern('/\s+#.*$/', '', $stringValue) ?? $stringValue);

        return $this->trimString($withoutComment);
    }
}
