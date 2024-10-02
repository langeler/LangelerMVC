<?php

namespace App\Core;

use App\Utilities\Finders\FileFinder;
use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Managers\FileManager;
use App\Helpers\ExistenceChecker;
use App\Helpers\ArrayHelper;
use App\Helpers\TypeChecker;
use App\Utilities\Sanitation\GeneralSanitizer;
use App\Utilities\Validation\GeneralValidator;
use App\Utilities\Validation\MediaValidator;
use App\Utilities\Sanitation\MediaSanitizer;
use App\Exceptions\ConfigException;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\EncodingTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ConversionTrait;
use SplFileObject;
use Exception;

/**
 * Class Config
 *
 * Handles loading, caching, and managing configuration files and .env files.
 * Automatically generates config files from ENV variables and supports conditional caching.
 */
class Config
{
    use ManipulationTrait, EncodingTrait, CheckerTrait, ConversionTrait;

    private string $cacheKey = 'config_data';
    private bool $cacheEnabled;

    /**
     * Store the loaded .env variables separately
     *
     * @var array
     */
    private array $envVariables = [];

    public function __construct(
        private FileFinder $files,
        private DirectoryFinder $dirs,
        private FileManager $fileManager,
        private ExistenceChecker $exists,
        private ArrayHelper $arrays,
        private TypeChecker $types,
        private GeneralSanitizer $sanitize,
        private MediaSanitizer $mediaSanitize,
        private GeneralValidator $validator,
        private MediaValidator $mediaValidator
    ) {
        $this->load();
    }

    /**
     * Load configuration files and .env data based on ENV configuration.
     *
     * @return array The loaded configuration data.
     * @throws ConfigException If loading fails.
     */
    public function load(): array
    {
        return $this->wrapInTry(fn() => $this->loadConfigFiles());
    }

    /**
     * Load configuration files and handle .env processing.
     *
     * @return array The loaded configuration data.
     * @throws ConfigException If loading fails.
     */
    private function loadConfigFiles(): array
    {
        return $this->wrapInTry(function () {
            $envPath = $this->findEnvFilePath();
            $this->loadEnvFile($envPath);

            return $this->createOrUpdateConfigFiles($this->findConfigDir());
        });
    }

    /**
     * Find the path to the .env file.
     *
     * @return string Path to the .env file.
     * @throws ConfigException If .env file is not found.
     */
    private function findEnvFilePath(): string
    {
        $envFiles = $this->files->find(['extension' => 'env']);
        return $this->types->isArray($envFiles) && !$this->types->isEmpty($envFiles)
            ? $envFiles[0]->getPathname()
            : throw new ConfigException(".env file not found.");
    }

    /**
     * Create or update configuration files based on ENV variables.
     *
     * @param string $configDir The configuration directory path.
     * @return array The updated configuration data.
     * @throws ConfigException If creating or updating fails.
     */
    private function createOrUpdateConfigFiles(string $configDir): array
    {
        return $this->wrapInTry(function () use ($configDir) {
            $envGroups = $this->groupEnvByPrefix();
            $configData = [];

            foreach ($envGroups as $file => $data) {
                $configData[$file] = $this->createOrUpdateConfigFile($configDir, $file, $data);
            }

            return $configData;
        });
    }

    /**
     * Process and update an individual configuration file.
     *
     * @param string $configDir The configuration directory path.
     * @param string $file The configuration file name.
     * @param array $data The configuration data to merge.
     * @return array The merged configuration data.
     * @throws ConfigException If processing fails.
     */
    private function createOrUpdateConfigFile(string $configDir, string $file, array $data): array
    {
        return $this->wrapInTry(function () use ($configDir, $file, $data) {
            $filePath = $this->validateFilePath($configDir, $file);

            // Open the file using SplFileObject
            $fileObj = $this->fileManager->openFile($filePath, 'a+');
            $this->fileManager->lock($fileObj, LOCK_EX); // Lock the file for exclusive access

            $existingData = $fileObj->getSize() > 0 ? include $fileObj->getRealPath() : [];

            if (!$this->types->isArray($existingData)) {
                $existingData = [];
            }

            $mergedData = $this->arrays->merge($existingData, $data);

            // Rewind to the start of the file and write the updated configuration data
            $fileObj->rewind();
            $fileObj->ftruncate(0); // Clear the file contents
            $this->fileManager->writeLine($fileObj, "<?php\n\nreturn " . var_export($mergedData, true) . ";\n");

            // Unlock and close the file
            $this->fileManager->lock($fileObj, LOCK_UN);

            return $mergedData;
        });
    }

    /**
     * Validate file path.
     *
     * @param string $dir The directory path.
     * @param string $file The file name.
     * @return string The validated file path.
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
     * Group ENV variables by their prefix.
     *
     * @return array Grouped ENV variables by prefix.
     */
    private function groupEnvByPrefix(): array
    {
        return array_reduce(array_keys($this->envVariables), function ($carry, $key) {
            [$prefix, $suffix] = $this->split('_', $key);  // Using split from ManipulationTrait
            $carry[$this->toLower($prefix)][$suffix] = $this->envVariables[$key];
            return $carry;
        }, []);
    }

    /**
     * Find the 'Config' directory.
     *
     * @return string Path to the 'Config' directory.
     * @throws ConfigException If directory is not found.
     */
    private function findConfigDir(): string
    {
        return $this->wrapInTry(function () {
            $dirs = $this->dirs->find(['name' => 'Config']);
            return $this->types->isArray($dirs) && !$this->types->isEmpty($dirs)
                ? $dirs[0]->getPathname()
                : throw new ConfigException("Config directory not found.");
        });
    }

    /**
     * Load the .env file and store variables in $envVariables.
     *
     * @param string $envFilePath Path to the .env file.
     * @throws ConfigException If loading fails.
     */
    private function loadEnvFile(string $envFilePath): void
    {
        $this->wrapInTry(fn() => $this->parseEnvFile($this->fileManager->readFile($envFilePath) ?? []));
    }

    /**
     * Parse the content of the .env file and store variables in $envVariables.
     *
     * @param array $envContent Content of the .env file.
     */
    private function parseEnvFile(array $envContent): void
    {
        array_walk($envContent, fn($line) => $this->processEnvLine($line));
    }

    /**
     * Process a single line of the .env file and store variable in $envVariables.
     *
     * @param string $line Line to process.
     * @throws ConfigException If processing fails.
     */
/**
      * Process a single line of the .env file and store variable in $envVariables.
      *
      * @param string $line Line to process.
      * @throws ConfigException If processing fails.
      */
     private function processEnvLine(string $line): void
     {
         $this->wrapInTry(function () use ($line) {
             if ($this->isValidEnvLine($line)) {
                 $lineParts = $this->split('=', $this->trim($line), 2);  // Using split and trim from ManipulationTrait

                 // Ensure that both key and value exist
                 if ($this->types->isArray($lineParts) && count($lineParts) === 2 && !$this->isEmpty($lineParts[0])) {
                     $key = $this->sanitize->sanitizeString($this->trim($lineParts[0]));
                     $value = isset($lineParts[1]) ? $this->removeInlineComments($this->sanitize->sanitizeString($this->trim($lineParts[1]))) : '';

                     if (!empty($key)) {
                         // Store the variable in $envVariables, not $_ENV
                         $this->envVariables[$key] = $value;
                     }
                 }
             }
         });
     }

    /**
     * Remove inline comments from a string.
     *
     * @param string $value The string to process.
     * @return string The string without inline comments.
     */
/**
      * Remove inline comments from a string.
      *
      * @param string $value The string to process.
      * @return string The string without inline comments.
      */
     private function removeInlineComments(string $value): string
     {
         // Remove anything after a '#' character (including the # itself) unless the # is within quotes
         return preg_replace('/\s+#.*$/', '', $value); // This will remove everything after the '#' symbol
     }

    /**
     * Check if a line is a valid ENV line (not a comment or empty).
     *
     * @param string $line Line to check.
     * @return bool Whether the line is valid.
     */
    private function isValidEnvLine(string $line): bool
    {
        $trimmedLine = $this->trim($line);  // Using trim from ManipulationTrait

        // Check if the line is not empty and the first character is not a comment ('#')
        return $this->length($trimmedLine) > 0 && $trimmedLine[0] !== '#';
    }

    /**
     * Wrap method calls in a try-catch block for consistent error handling.
     *
     * @param callable $callback The function to wrap.
     * @return mixed Result of the callback.
     * @throws ConfigException If an exception is thrown.
     */
    private function wrapInTry(callable $callback)
    {
        try {
            return $callback();
        } catch (Exception $e) {
            throw new ConfigException($e->getMessage(), 0, $e);
        }
    }
}
