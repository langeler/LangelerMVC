<?php

namespace App\Core;

use App\Utilities\Finders\FileFinder;
use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Managers\FileManager;
use App\Helpers\ExistenceChecker;
use App\Utilities\Sanitation\GeneralSanitizer;
use App\Utilities\Validation\GeneralValidator;
use App\Utilities\Validation\MediaValidator;
use App\Utilities\Sanitation\MediaSanitizer;
use App\Exceptions\ConfigException;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\EncodingTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ConversionTrait;
use App\Utilities\Traits\TypeCheckerTrait;
use App\Utilities\Traits\ArrayTrait;
use Throwable;

/**
 * Class Config
 *
 * Handles the configuration loading, parsing, and file management for the application.
 */
class Config
{
    use ManipulationTrait, EncodingTrait, CheckerTrait, ConversionTrait, TypeCheckerTrait, ArrayTrait;

    private string $cacheKey = 'config_data';
    private bool $cacheEnabled;
    private array $envVariables = [];

    public function __construct(
        private FileFinder $files,
        private DirectoryFinder $dirs,
        private FileManager $fileManager,
        private ExistenceChecker $exists,
        private GeneralSanitizer $sanitize,
        private MediaSanitizer $mediaSanitize,
        private GeneralValidator $validator,
        private MediaValidator $mediaValidator
    ) {
        $this->load();
    }

    /**
     * Loads all configuration files and returns the configuration data.
     *
     * @return array The configuration data.
     * @throws ConfigException If an error occurs during loading.
     */
    public function load(): array
    {
        return $this->wrapInTry(fn() => $this->loadConfigFiles());
    }

    /**
     * Loads configuration files and processes environment variables.
     *
     * @return array The processed configuration data.
     * @throws ConfigException If any file or directory is invalid.
     */
    private function loadConfigFiles(): array
    {
        return $this->wrapInTry(fn() => [
            $this->loadEnvFile($this->findEnvFilePath()),
            $this->createOrUpdateConfigFiles($this->findConfigDir()),
        ][1]);
    }

    /**
     * Finds the .env file path.
     *
     * @return string The .env file path.
     * @throws ConfigException If the .env file is not found.
     */
    private function findEnvFilePath(): string
    {
        $envFiles = $this->files->find(['extension' => 'env']);
        return $this->isArray($envFiles) && !$this->isEmpty($envFiles) && $this->fileManager->fileExists($envFiles[0])
            ? $envFiles[0]
            : throw new ConfigException(".env file not found.");
    }

    /**
     * Creates or updates configuration files from environment variables.
     *
     * @param string $configDir The configuration directory.
     * @return array The updated configuration data.
     * @throws ConfigException If an error occurs during file operations.
     */
    private function createOrUpdateConfigFiles(string $configDir): array
    {
        return $this->map(
            $this->groupEnvByPrefix(),
            fn($data, $file) => $this->createOrUpdateConfigFile($configDir, $file, $data)
        );
    }

    /**
     * Creates or updates a single configuration file.
     *
     * @param string $configDir The configuration directory.
     * @param string $file The configuration file name.
     * @param array $data The data to write to the file.
     * @return array The merged configuration data.
     * @throws ConfigException If the file cannot be created or updated.
     */
    private function createOrUpdateConfigFile(string $configDir, string $file, array $data): array
    {
        $filePath = $this->validateFilePath($configDir, $file);

        !$this->fileManager->fileExists($filePath) &&
        $this->fileManager->writeContents($filePath, "<?php\n\nreturn [];\n");

        $existingData = $this->fileManager->readContents($filePath) ? include $filePath : [];
        return $this->fileManager->writeContents(
            $filePath,
            "<?php\n\nreturn " . var_export(
                $this->merge($this->isArray($existingData) ? $existingData : [], $data),
                true
            ) . ";\n"
        );
    }

    /**
     * Validates a file path.
     *
     * @param string $dir The directory path.
     * @param string $file The file name.
     * @return string The sanitized and validated file path.
     * @throws ConfigException If the file path is invalid.
     */
    private function validateFilePath(string $dir, string $file): string
    {
        $filePath = $this->mediaSanitize->sanitizeFilePathUnix("$dir/$file.php");

        return $this->mediaValidator->validateFilePathUnix($filePath)
            ? $filePath
            : throw new ConfigException("Invalid file path: $filePath");
    }

    /**
     * Groups environment variables by their prefix.
     *
     * @return array An associative array grouped by prefixes.
     */
    private function groupEnvByPrefix(): array
    {
        return $this->reduce(
            array_keys($this->envVariables),
            fn($carry, $key) => $carry[$this->toLower($this->split('_', $key)[0])][$this->split('_', $key)[1]] = $this->envVariables[$key] ?: $carry,
            []
        );
    }

    /**
     * Finds the configuration directory.
     *
     * @return string The configuration directory path.
     * @throws ConfigException If the directory is not found.
     */
    private function findConfigDir(): string
    {
        $dirs = $this->dirs->find(['name' => 'Config']);
        return $this->isArray($dirs) && !$this->isEmpty($dirs) && $this->fileManager->fileExists($dirs[0])
            ? $dirs[0]
            : throw new ConfigException("Config directory not found.");
    }

    /**
     * Loads environment variables from a .env file.
     *
     * @param string $envFilePath The path to the .env file.
     */
    private function loadEnvFile(string $envFilePath): void
    {
        $this->parseEnvFile($this->fileManager->readContents($envFilePath) ? explode("\n", $this->fileManager->readContents($envFilePath)) : []);
    }

    /**
     * Parses and processes each line of the .env file.
     *
     * @param array $envContent The .env file content as an array of lines.
     */
    private function parseEnvFile(array $envContent): void
    {
        $this->walk($envContent, fn($line) => $this->processEnvLine($line));
    }

    /**
     * Processes a single line from the .env file.
     *
     * @param string $line The line to process.
     */
    private function processEnvLine(string $line): void
    {
        $this->isValidEnvLine($line) &&
        $this->envVariables[$this->sanitize->sanitizeString($this->trim($this->split('=', $line, 2)[0]))] =
            $this->removeInlineComments(
                $this->sanitize->sanitizeString($this->trim($this->split('=', $line, 2)[1] ?? ''))
            );
    }

    /**
     * Removes inline comments from a .env value.
     *
     * @param string $value The value to process.
     * @return string The value without comments.
     */
    private function removeInlineComments(string $value): string
    {
        return preg_replace('/\s+#.*$/', '', $value);
    }

    /**
     * Checks if a .env line is valid.
     *
     * @param string $line The line to validate.
     * @return bool True if valid, false otherwise.
     */
    private function isValidEnvLine(string $line): bool
    {
        return $this->length($this->trim($line)) > 0 && $line[0] !== '#';
    }

    /**
     * Wraps a callback in a try-catch block for consistent error handling.
     *
     * @param callable $callback The callback to execute.
     * @return mixed The result of the callback.
     * @throws ConfigException If an error occurs.
     */
    private function wrapInTry(callable $callback)
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            throw new ConfigException($e->getMessage(), 0, $e);
        }
    }
}
