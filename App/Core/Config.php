<?php

namespace App\Core;

use App\Utilities\Managers\SettingsManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Traits\{
    ArrayTrait,
    ErrorTrait,
    ManipulationTrait,
    TypeCheckerTrait
};
use App\Utilities\Traits\Patterns\PatternTrait;

/**
 * Runtime configuration facade.
 *
 * Configuration should be read, cached, and exposed at runtime without mutating
 * checked-in files. SettingsManager owns the loading and environment merge logic;
 * this class provides a focused application-facing API.
 */
class Config
{
    use ErrorTrait, TypeCheckerTrait;
    use ArrayTrait, ManipulationTrait, PatternTrait {
        ManipulationTrait::toLower as private toLowerString;
        PatternTrait::replaceByPattern as private patternReplace;
    }

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
            $normalizedFile = $this->toLowerString(
                (string) ($this->patternReplace('/\.php$/i', '', $this->trimString($file)) ?? $this->trimString($file))
            );
            $settings = $this->all()[$normalizedFile] ?? null;

            if (!$this->isArray($settings)) {
                return $default;
            }

            if ($key === null || $key === '') {
                return $settings;
            }

            $value = $settings;

            foreach (explode('.', $key) as $segment) {
                if (!$this->isArray($value)) {
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
        if ($this->keyExists($value, $segment)) {
            return $segment;
        }

        $normalizedSegment = $this->toLowerString($segment);

        foreach ($value as $key => $_) {
            if ($this->isString($key) && $this->toLowerString($key) === $normalizedSegment) {
                return $key;
            }
        }

        return null;
    }
}
