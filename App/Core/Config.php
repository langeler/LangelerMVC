<?php

namespace App\Core;

use App\Utilities\Managers\SettingsManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Traits\ErrorTrait;

/**
 * Runtime configuration facade.
 *
 * Configuration should be read, cached, and exposed at runtime without mutating
 * checked-in files. SettingsManager owns the loading and environment merge logic;
 * this class provides a focused application-facing API.
 */
class Config
{
    use ErrorTrait;

    /**
     * Cached configuration payload.
     *
     * @var array<string, array>
     */
    private array $config = [];

    public function __construct(
        private SettingsManager $settingsManager,
        private ErrorManager $errorManager
    ) {
        $this->config = $this->load();
    }

    /**
     * Loads all configuration files.
     *
     * @return array<string, array>
     */
    public function load(): array
    {
        return $this->config = $this->wrapInTry(
            fn() => $this->settingsManager->getAllConfigs(),
            'config'
        );
    }

    /**
     * Returns the full runtime configuration map.
     *
     * @return array<string, array>
     */
    public function all(): array
    {
        return $this->config ?: $this->load();
    }

    /**
     * Returns a config file or a nested key within a config file.
     *
     * @param string $file
     * @param string|null $key Dot-notated key path.
     * @param mixed $default
     * @return mixed
     */
    public function get(string $file, ?string $key = null, mixed $default = null): mixed
    {
        return $this->wrapInTry(function () use ($file, $key, $default): mixed {
            $normalizedFile = strtolower((string) preg_replace('/\.php$/i', '', trim($file)));
            $settings = $this->all()[$normalizedFile] ?? null;

            if (!is_array($settings)) {
                return $default;
            }

            if ($key === null || $key === '') {
                return $settings;
            }

            $value = $settings;

            foreach (explode('.', $key) as $segment) {
                if (!is_array($value)) {
                    return $default;
                }

                $resolvedSegment = $this->resolveSegmentKey($value, $segment);

                if ($resolvedSegment === null) {
                    return $default;
                }

                $value = $value[$resolvedSegment];
            }

            return $value;
        }, 'config');
    }

    /**
     * Determines whether a config file or nested key exists.
     *
     * @param string $file
     * @param string|null $key
     * @return bool
     */
    public function has(string $file, ?string $key = null): bool
    {
        return $this->wrapInTry(
            fn() => $this->get($file, $key, '__missing__') !== '__missing__',
            'config'
        );
    }

    /**
     * Resolve a config segment key without requiring exact-case matches.
     *
     * @param array<int|string, mixed> $value
     */
    private function resolveSegmentKey(array $value, string $segment): int|string|null
    {
        if (array_key_exists($segment, $value)) {
            return $segment;
        }

        $normalizedSegment = strtolower($segment);

        foreach ($value as $key => $_) {
            if (is_string($key) && strtolower($key) === $normalizedSegment) {
                return $key;
            }
        }

        return null;
    }
}
