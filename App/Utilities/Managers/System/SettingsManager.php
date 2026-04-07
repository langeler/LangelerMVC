<?php

namespace App\Utilities\Managers\System;

use Throwable;
use App\Utilities\Finders\{
    DirectoryFinder,
    FileFinder
};
use App\Utilities\Traits\{
    ArrayTrait,
    TypeCheckerTrait,
    CheckerTrait,
    ErrorTrait,
    ManipulationTrait
};
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Sanitation\{
    PatternSanitizer
};
use App\Utilities\Validation\{
    PatternValidator
};

/**
 * Class SettingsManager
 *
 * Reads configuration from the Config directory, merges runtime environment
 * overrides without mutating files, and exposes settings through a
 * case-insensitive lookup API.
 */
class SettingsManager
{
    use ArrayTrait, ErrorTrait, ManipulationTrait, PatternTrait {
        ArrayTrait::replace insteadof ManipulationTrait, PatternTrait;
        ArrayTrait::pad insteadof ManipulationTrait;
        ArrayTrait::reverse insteadof ManipulationTrait;
        ArrayTrait::shuffle insteadof ManipulationTrait;
        PatternTrait::split insteadof ManipulationTrait;
        ManipulationTrait::trim as private trimString;
        ManipulationTrait::toLower as private toLowerString;
        ManipulationTrait::toUpper as private toUpperString;
        ManipulationTrait::substring as private substringString;
        PatternTrait::replace as private patternReplace;
    }
    use CheckerTrait, TypeCheckerTrait {
        TypeCheckerTrait::isNumeric insteadof CheckerTrait;
        CheckerTrait::isNumeric as isStringNumeric;
    }

    /**
     * Validated path to the Config directory.
     */
    private string $folder;

    /**
     * Index of config files keyed by lower-case basename.
     *
     * @var array<string, string>
     */
    private array $files = [];

    /**
     * Cached config payloads keyed by lower-case basename.
     *
     * @var array<string, array>
     */
    private array $data = [];

    /**
     * Invalid config files captured during discovery.
     *
     * @var array<string, string>
     */
    private array $invalidFiles = [];

    /**
     * Environment-derived overrides grouped by config file name.
     *
     * @var array<string, array>
     */
    private array $environment = [];

    public function __construct(
        private readonly DirectoryFinder $dirFinder,
        private readonly FileFinder $fileFinder,
        private readonly FileManager $fileManager,
        private readonly PatternSanitizer $patternSanitizer,
        private readonly PatternValidator $patternValidator,
        private readonly ErrorManager $errorManager
    ) {
        $this->folder = $this->wrapInTry(
            fn() => $this->validateFolderPath($this->locateConfigFolder()),
            'settings'
        );

        $this->files = $this->wrapInTry(
            fn() => $this->retrieveConfigFiles(),
            'settings'
        );

        $this->environment = $this->wrapInTry(
            fn() => $this->loadEnvironmentOverrides(),
            'settings'
        );
    }

    /**
     * Retrieves all settings from a config file.
     *
     * @param string $fileName
     * @return array
     */
    public function getAllSettings(string $fileName): array
    {
        return $this->wrapInTry(
            fn() => $this->getConfigData($fileName),
            'settings'
        );
    }

    /**
     * Retrieves a single setting from a config file.
     *
     * @param string $fileName
     * @param string $key
     * @return mixed
     */
    public function getSetting(string $fileName, string $key): mixed
    {
        return $this->wrapInTry(function () use ($fileName, $key) {
            $value = $this->get($this->getConfigData($fileName), $key);

            if ($value !== null) {
                return $value;
            }

            $this->errorManager->logErrorMessage(
                "Key '$key' not found in file '$fileName'.",
                __FILE__,
                __LINE__,
                'userError',
                'settings'
            );

            throw $this->errorManager->resolveException(
                'settings',
                "Key '$key' not found in file '$fileName'."
            );
        }, 'settings');
    }

    /**
     * Retrieves all valid configuration files.
     *
     * Invalid files are skipped and logged instead of crashing config boot.
     *
     * @return array<string, array>
     */
    public function getAllConfigs(): array
    {
        return $this->wrapInTry(function (): array {
            $configs = [];

            foreach ($this->getKeys($this->files) as $fileName) {
                try {
                    $configs[$fileName] = $this->getConfigData($fileName);
                } catch (Throwable $exception) {
                    $this->invalidFiles[$fileName] = $exception->getMessage();
                }
            }

            return $configs;
        }, 'settings');
    }

    /**
     * Returns invalid config files encountered during discovery.
     *
     * @return array<string, string>
     */
    public function getInvalidFiles(): array
    {
        return $this->invalidFiles;
    }

    /**
     * Locates the Config directory.
     *
     * @return string
     */
    private function locateConfigFolder(): string
    {
        return $this->wrapInTry(function (): string {
            $directories = $this->dirFinder->find(['name' => 'Config']);
            $directoryPath = $this->isArray($directories) && !$this->isEmpty($directories)
                ? $this->keyFirst($directories)
                : null;

            if ($this->isString($directoryPath) && $this->fileManager->isDirectory($directoryPath)) {
                return $directoryPath;
            }

            $this->errorManager->logErrorMessage(
                'Configuration directory not found.',
                __FILE__,
                __LINE__,
                'userError',
                'settings'
            );

            throw $this->errorManager->resolveException('settings', 'Configuration directory not found.');
        }, 'settings');
    }

    /**
     * Sanitizes and validates the Config directory path.
     *
     * @param string $folderPath
     * @return string
     */
    private function validateFolderPath(string $folderPath): string
    {
        return $this->wrapInTry(function () use ($folderPath): string {
            $sanitizedPath = $this->patternSanitizer->sanitizePathUnix($folderPath) ?? '';
            $validatedPath = $this->patternValidator->validatePathUnix($sanitizedPath)
                ? $sanitizedPath
                : null;

            if ($this->isString($validatedPath) && $this->isValidFilePath($validatedPath)) {
                return $validatedPath;
            }

            $this->errorManager->logErrorMessage(
                "Invalid configuration directory path: {$folderPath}",
                __FILE__,
                __LINE__,
                'userError',
                'settings'
            );

            throw $this->errorManager->resolveException(
                'settings',
                "Invalid configuration directory path: {$folderPath}"
            );
        }, 'settings');
    }

    /**
     * Retrieves all PHP config files in the Config directory.
     *
     * @return array<string, string>
     */
    private function retrieveConfigFiles(): array
    {
        return $this->wrapInTry(function (): array {
            $files = $this->fileFinder->find(['extension' => 'php'], $this->folder);

            if (!$this->isArray($files) || $this->isEmpty($files)) {
                return [];
            }

            return $this->reduce(
                $this->getKeys($files),
                function (array $carry, string $filePath): array {
                    $name = $this->toLowerString((string) $this->fileManager->getBaseName($filePath, '.php'));

                    if ($name !== '') {
                        $carry[$name] = $filePath;
                    }

                    return $carry;
                },
                []
            );
        }, 'settings');
    }

    /**
     * Retrieves and caches a config payload.
     *
     * @param string $fileName
     * @return array
     */
    private function getConfigData(string $fileName): array
    {
        return $this->wrapInTry(function () use ($fileName): array {
            $normalizedName = $this->normalizeFileName($fileName);

            if (isset($this->data[$normalizedName])) {
                return $this->data[$normalizedName];
            }

            $filePath = $this->buildFilePath($normalizedName);

            if (!$this->fileManager->fileExists($filePath)) {
                $this->errorManager->logErrorMessage(
                    "Configuration file '$fileName' not found.",
                    __FILE__,
                    __LINE__,
                    'userError',
                    'settings'
                );

                throw $this->errorManager->resolveException(
                    'settings',
                    "Configuration file '$fileName' not found."
                );
            }

            return $this->data[$normalizedName] = $this->mergeEnvironmentOverrides(
                $normalizedName,
                $this->parseFile($filePath)
            );
        }, 'settings');
    }

    /**
     * Parses a config file and normalizes its values.
     *
     * @param string $filePath
     * @return array
     */
    private function parseFile(string $filePath): array
    {
        return $this->wrapInTry(function () use ($filePath): array {
            $parsed = include $filePath;

            if ($this->isArray($parsed)) {
                return $this->normalizeConfigValues($parsed);
            }

            $this->errorManager->logErrorMessage(
                "Invalid config format in '$filePath'. Expected an array.",
                __FILE__,
                __LINE__,
                'userError',
                'settings'
            );

            throw $this->errorManager->resolveException(
                'settings',
                "Invalid config format in '$filePath'."
            );
        }, 'settings');
    }

    /**
     * Builds a case-insensitive file path for a config file.
     *
     * @param string $fileName
     * @return string
     */
    private function buildFilePath(string $fileName): string
    {
        return $this->wrapInTry(function () use ($fileName): string {
            $normalizedName = $this->normalizeFileName($fileName);
            $filePath = $this->files[$normalizedName]
                ?? ($this->folder . '/' . $normalizedName . '.php');

            return $this->patternSanitizer->sanitizePathUnix($filePath) ?? $filePath;
        }, 'settings');
    }

    /**
     * Loads environment-derived overrides without mutating config files on disk.
     *
     * @return array<string, array>
     */
    private function loadEnvironmentOverrides(): array
    {
        $variables = [];
        $envFile = dirname($this->folder) . '/.env';

        if ($this->fileManager->fileExists($envFile)) {
            $variables = $this->replace($variables, $this->parseEnvFile($envFile));
        }

        foreach ([getenv(), $_ENV, $_SERVER] as $source) {
            if (!$this->isArray($source)) {
                continue;
            }

            foreach ($source as $key => $value) {
                if ($this->isString($key) && ($this->isString($value) || $this->isNumeric($value) || $this->isBool($value))) {
                    $variables[$key] = (string) $value;
                }
            }
        }

        return $this->groupEnvironmentByConfig($variables);
    }

    /**
     * Parses a .env file into a flat key/value map.
     *
     * @param string $envFile
     * @return array<string, string>
     */
    private function parseEnvFile(string $envFile): array
    {
        $contents = $this->fileManager->readContents($envFile);

        if (!$this->isString($contents) || $contents === '') {
            return [];
        }

        $variables = [];

        foreach (($this->split('/\R/', $contents) ?: []) as $line) {
            $line = $this->trimString($line);

            if ($line === '' || $this->substringString($line, 0, 1) === '#' || !$this->contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $variables[$this->trimString($key)] = $this->normalizeScalarValue($value);
        }

        return $variables;
    }

    /**
     * Groups raw environment variables by config file prefix and nested key path.
     *
     * @param array<string, string> $variables
     * @return array<string, array>
     */
    private function groupEnvironmentByConfig(array $variables): array
    {
        $grouped = [];

        foreach ($variables as $key => $value) {
            $segments = $this->getValues($this->filter(explode('_', $key), fn($segment) => $segment !== ''));

            if (count($segments) < 2) {
                continue;
            }

            $file = $this->toLowerString((string) array_shift($segments));

            if (!isset($this->files[$file])) {
                continue;
            }

            $path = $this->map(fn(string $segment): string => $this->toUpperString($segment), $segments);
            $this->setNestedValue($grouped[$file], $path, $this->normalizeScalarValue($value));
        }

        return $grouped;
    }

    /**
     * Sets a nested value inside an array using a list of path segments.
     *
     * @param array|null $target
     * @param array $path
     * @param mixed $value
     * @return void
     */
    private function setNestedValue(?array &$target, array $path, mixed $value): void
    {
        $target ??= [];
        $cursor = &$target;

        foreach ($path as $index => $segment) {
            if ($index === count($path) - 1) {
                $cursor[$segment] = $value;
                return;
            }

            if (!isset($cursor[$segment]) || !$this->isArray($cursor[$segment])) {
                $cursor[$segment] = [];
            }

            $cursor = &$cursor[$segment];
        }
    }

    /**
     * Merges environment overrides into a parsed config array.
     *
     * @param string $fileName
     * @param array $config
     * @return array
     */
    private function mergeEnvironmentOverrides(string $fileName, array $config): array
    {
        return isset($this->environment[$fileName])
            ? $this->replaceRecursive($config, $this->environment[$fileName])
            : $config;
    }

    /**
     * Normalizes nested config values.
     *
     * @param mixed $value
     * @return mixed
     */
    private function normalizeConfigValues(mixed $value): mixed
    {
        if ($this->isArray($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeConfigValues($item);
            }

            return $normalized;
        }

        if ($this->isString($value)) {
            return $this->normalizeScalarValue($value);
        }

        return $value;
    }

    /**
     * Normalizes scalar config values by trimming whitespace, comments, and quotes.
     *
     * @param string $value
     * @return string
     */
    private function normalizeScalarValue(string $value): string
    {
        $trimmed = $this->trimString($value);

        if ($trimmed === '') {
            return '';
        }

        $firstChar = $this->substringString($trimmed, 0, 1);
        $lastChar = $this->substringString($trimmed, -1);

        if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
            return $this->substringString($trimmed, 1, -1);
        }

        return $this->trimString((string) ($this->patternReplace('/\s+#.*$/', '', $trimmed) ?? $trimmed));
    }

    /**
     * Normalizes a config file name for case-insensitive lookup.
     *
     * @param string $fileName
     * @return string
     */
    private function normalizeFileName(string $fileName): string
    {
        return $this->toLowerString(
            (string) ($this->patternReplace('/\.php$/i', '', $this->trimString($fileName)) ?? $this->trimString($fileName))
        );
    }

    /**
     * Checks if a path is a readable file or directory.
     *
     * @param string $filePath
     * @return bool
     */
    private function isValidFilePath(string $filePath): bool
    {
        return $this->fileManager->isReadable($filePath)
            && (
                $this->fileManager->isDirectory($filePath)
                || $this->fileManager->fileExists($filePath)
            );
    }
}
