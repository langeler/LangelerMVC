<?php

namespace App\Utilities\Managers;

use App\Exceptions\ConfigException;
use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Finders\FileFinder;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\TypeCheckerTrait;
use App\Utilities\Traits\CheckerTrait;
use Throwable;

/**
 * Class SettingsManager
 *
 * Manages application configuration files and their contents,
 * including validation, sanitization, and retrieval of settings.
 */
class SettingsManager
{
    use ArrayTrait;
    use TypeCheckerTrait;
    use CheckerTrait;

    private string $folder;
    private array $files = [];
    private array $data = [];

    public function __construct(
        private readonly DirectoryFinder $dirFinder,
        private readonly FileFinder $fileFinder,
        private readonly FileManager $fileManager
    ) {
        $this->folder = $this->wrapInTry(
            fn() => $this->validateFolder(
                $this->sanitizeFilePathUnix(
                    $this->locateConfigFolder()
                )
            ),
            "Failed to initialize configuration folder."
        );

        $this->files = $this->wrapInTry(
            fn() => $this->retrieveConfigFiles(),
            "Failed to retrieve configuration files."
        );
    }

    /**
     * Wraps a callable in a try-catch block to handle exceptions consistently.
     *
     * @param callable $callback The callable to execute.
     * @param string $errorMessage The error message to use in the exception.
     * @return mixed The result of the callable.
     * @throws ConfigException
     */
    private function wrapInTry(callable $callback, string $errorMessage): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            throw new ConfigException("$errorMessage: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Locates the configuration folder.
     *
     * @return string The path to the configuration folder.
     * @throws ConfigException
     */
    private function locateConfigFolder(): string
    {
        return $this->wrapInTry(
            fn() => $this->isArray($dirs = $this->dirFinder->find(['name' => 'Config'])) &&
            !$this->isEmpty($dirs) &&
            $this->fileManager->isDirectory($dirs[0])
                ? $dirs[0]
                : throw new ConfigException("Configuration directory not found."),
            "Failed to locate configuration folder."
        );
    }

    /**
     * Validates the configuration folder path.
     *
     * @param string $folderPath The folder path to validate.
     * @return string The validated folder path.
     * @throws ConfigException
     */
    private function validateFolder(string $folderPath): string
    {
        return $this->isValidFilePath($folderPath)
            ? $folderPath
            : throw new ConfigException("Invalid configuration directory path: $folderPath");
    }

    /**
     * Retrieves all configuration files in the folder.
     *
     * @return array The list of configuration files.
     * @throws ConfigException
     */
    private function retrieveConfigFiles(): array
    {
        return $this->wrapInTry(
            fn() => $this->isArray($files = $this->fileFinder->find(['extension' => 'php'], $this->folder)) && !$this->isEmpty($files)
                ? $files
                : [],
            "Error retrieving configuration files."
        );
    }

    /**
     * Parses a configuration file and returns its data.
     *
     * @param string $filePath The path to the configuration file.
     * @return array The parsed configuration data.
     * @throws ConfigException
     */
    private function parseFile(string $filePath): array
    {
        return $this->wrapInTry(
            fn() => $this->isArray($parsedData = include $filePath)
                ? $parsedData
                : throw new ConfigException("Invalid configuration format in '$filePath'. Expected an array."),
            "Failed to parse configuration file '$filePath'."
        );
    }

    /**
     * Retrieves all settings from a specified configuration file.
     *
     * @param string $fileName The name of the configuration file.
     * @return array The settings from the file.
     * @throws ConfigException
     */
    public function getAllSettings(string $fileName): array
    {
        return $this->getConfigData($fileName);
    }

    /**
     * Retrieves a specific setting by key from a configuration file.
     *
     * @param string $fileName The name of the configuration file.
     * @param string $key The key to retrieve.
     * @return mixed The setting value.
     * @throws ConfigException
     */
    public function getSetting(string $fileName, string $key): mixed
    {
        return $this->get($this->getConfigData($fileName), $key)
            ?? throw new ConfigException("Key '$key' not found in file '$fileName'.");
    }

    /**
     * Retrieves all parsed configuration data.
     *
     * @return array The parsed configuration data.
     */
    public function getAllConfigs(): array
    {
        return !$this->isEmpty($this->data)
            ? $this->data
            : $this->reduce(
                $this->files,
                fn($carry, $file) => $this->merge(
                    $carry,
                    [
                        $this->fileManager->getBaseName($file, '.php') => $this->getConfigData(
                            $this->fileManager->getBaseName($file, '.php')
                        )
                    ]
                ),
                []
            );
    }

    /**
     * Retrieves and caches configuration data for a specified file.
     *
     * @param string $fileName The name of the configuration file.
     * @return array The configuration data.
     * @throws ConfigException
     */
    private function getConfigData(string $fileName): array
    {
        $filePath = $this->folder . DIRECTORY_SEPARATOR . ($this->endsWith($fileName, '.php') ? $fileName : "$fileName.php");

        return $this->data[$fileName] ??= $this->parseFile(
            $this->fileManager->fileExists($filePath)
                ? $filePath
                : throw new ConfigException("Configuration file '$fileName' not found.")
        );
    }

    /**
     * Validates a file path.
     *
     * @param string $filePath The file path to validate.
     * @return bool True if the file path is valid, otherwise false.
     */
    private function isValidFilePath(string $filePath): bool
    {
        return $this->fileManager->isReadable($filePath) && $this->fileManager->isDirectory($filePath);
    }

    /**
     * Sanitizes a file path to use a UNIX format.
     *
     * @param string $filePath The file path to sanitize.
     * @return string The sanitized file path.
     */
    private function sanitizeFilePathUnix(string $filePath): string
    {
        return str_replace('\\', '/', $filePath);
    }
}
