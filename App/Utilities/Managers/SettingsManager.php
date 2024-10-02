<?php

namespace App\Utilities\Managers;

use App\Utilities\Finders\FileFinder;
use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Managers\FileManager;
use App\Utilities\Validation\MediaValidator;
use App\Utilities\Validation\GeneralValidator;
use App\Utilities\Sanitation\MediaSanitizer;
use App\Utilities\Sanitation\GeneralSanitizer;
use App\Helpers\TypeChecker;
use App\Helpers\ArrayHelper;
use App\Exceptions\ConfigException;
use App\Utilities\Traits\CheckerTrait;
use SplFileObject;

/**
 * Class SettingsManager
 *
 * Manages retrieving, validating, and sanitizing configuration settings from files.
 */
class SettingsManager
{
    use CheckerTrait;

    /**
     * @var string The path to the configuration directory.
     */
    private string $folder;

    /**
     * @var array The list of configuration files in the directory.
     */
    private array $files;

    /**
     * @var array Cache of loaded configuration data to avoid redundant file reads.
     */
    private array $data = [];

    /**
     * SettingsManager constructor.
     * Initializes the configuration directory and configuration files list.
     *
     * @param DirectoryFinder $dirFinder Utility for locating directories.
     * @param FileFinder $fileFinder Utility for locating files.
     * @param FileManager $fileManager Utility for file operations.
     * @param TypeChecker $typeChecker Utility for type checking.
     * @param ArrayHelper $arrayHelper Utility for array operations.
     * @param MediaValidator $mediaValidator Utility for validating media-related data.
     * @param GeneralValidator $generalValidator Utility for general validation.
     * @param MediaSanitizer $mediaSanitizer Utility for sanitizing media-related data.
     * @param GeneralSanitizer $generalSanitizer Utility for general sanitization.
     * @throws ConfigException If the configuration directory or files cannot be validated or loaded.
     */
    public function __construct(
        private DirectoryFinder $dirFinder,
        private FileFinder $fileFinder,
        private FileManager $fileManager,
        private TypeChecker $typeChecker,
        private ArrayHelper $arrayHelper,
        private MediaValidator $mediaValidator,
        private GeneralValidator $generalValidator,
        private MediaSanitizer $mediaSanitizer,
        private GeneralSanitizer $generalSanitizer
    ) {
        $this->folder = $this->initializeFolder();
        $this->files = $this->initializeFiles();
    }

    /**
     * Initialize and validate the configuration directory.
     *
     * @return string The validated and sanitized directory path.
     * @throws ConfigException If the directory is not found or is invalid.
     */
    private function initializeFolder(): string
    {
        return $this->validateFolder(
            $this->sanitizeFolder(
                $this->locateConfigFolder()
            )
        );
    }

    /**
     * Locate the configuration directory path.
     *
     * @return string The configuration directory path.
     * @throws ConfigException If the directory cannot be located.
     */
    private function locateConfigFolder(): string
    {
        try {
            return $this->dirFinder->find(['name' => 'Config'])[0]->getPathname();
        } catch (\Exception $e) {
            throw new ConfigException("Configuration directory not found.", 0, $e);
        }
    }

    /**
     * Sanitize the folder path.
     *
     * @param string $folderPath The directory path to sanitize.
     * @return string The sanitized directory path.
     */
    private function sanitizeFolder(string $folderPath): string
    {
        return $this->mediaSanitizer->sanitizeFilePathUnix($folderPath);
    }

    /**
     * Validate the sanitized folder path.
     *
     * @param string $folderPath The folder path to validate.
     * @return string The validated folder path.
     * @throws ConfigException If the folder path is invalid.
     */
    private function validateFolder(string $folderPath): string
    {
        if (!$this->mediaValidator->validateFilePathUnix($folderPath)) {
            throw new ConfigException("Invalid configuration directory path: $folderPath");
        }
        return $folderPath;
    }

    /**
     * Initialize and retrieve all PHP configuration files from the folder.
     *
     * @return array The list of configuration files.
     * @throws ConfigException If no files are found.
     */
    private function initializeFiles(): array
    {
        return $this->retrieveConfigFiles();
    }

    /**
     * Retrieve the list of PHP configuration files.
     *
     * @return array The list of configuration files.
     * @throws ConfigException If files cannot be found or loaded.
     */
    private function retrieveConfigFiles(): array
    {
        try {
            return $this->fileFinder->find(['extension' => 'php'], $this->folder);
        } catch (\Exception $e) {
            throw new ConfigException("Error retrieving configuration files.", 0, $e);
        }
    }

    /**
     * Retrieve configuration data for a specific file from cache or parse it.
     *
     * @param string $fileName The name of the configuration file.
     * @return array The parsed configuration data.
     * @throws ConfigException If the configuration file cannot be loaded or parsed.
     */
    private function getConfigData(string $fileName): array
    {
        return $this->data[$fileName] ??= $this->parseFile(
            $this->getFilePath($fileName)
        );
    }

    /**
     * Retrieve the full path to a configuration file.
     *
     * @param string $fileName The name of the configuration file.
     * @return string The full path to the file.
     * @throws ConfigException If the file cannot be found.
     */
    private function getFilePath(string $fileName): string
    {
        // Ensure that the file name has a ".php" extension
        $fileName = $this->endsWith($fileName, '.php') ? $fileName : $fileName . '.php';

        if (!$this->fileManager->fileExists($this->folder . DIRECTORY_SEPARATOR . $fileName)) {
            throw new ConfigException("Configuration file '$fileName' not found.");
        }

        return $this->folder . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * Parse and validate the contents of a configuration file using SplFileObject.
     *
     * @param string $filePath The path to the configuration file.
     * @return array The parsed configuration data.
     * @throws ConfigException If the configuration data is invalid or cannot be parsed.
     */
    private function parseFile(string $filePath): array
    {
        try {
            // Use SplFileObject to read file contents instead of risky eval
            $file = $this->fileManager->openFile($filePath);
            $configData = '';

            while (!$file->eof()) {
                $configData .= $file->fgets();
            }

            // Interpret the file content as a PHP array using "include"
            $parsedData = include $filePath;

            if (!$this->typeChecker->isArray($parsedData)) {
                throw new ConfigException("Invalid configuration format in '$filePath'.");
            }

            return $parsedData;
        } catch (\Exception $e) {
            throw new ConfigException("Failed to parse configuration file '$filePath'.", 0, $e);
        }
    }

    // --- Public Methods ---

    /**
     * Retrieve all settings from a specific configuration file.
     *
     * @param string $fileName The name of the configuration file.
     * @return array The configuration settings.
     * @throws ConfigException If the file cannot be loaded or parsed.
     */
    public function getAllSettings(string $fileName): array
    {
        return $this->getConfigData($fileName);
    }

    /**
     * Retrieve a specific setting from a configuration file by its key.
     *
     * @param string $fileName The name of the configuration file.
     * @param string $key The key of the configuration setting.
     * @return mixed The setting value.
     * @throws ConfigException If the setting cannot be found.
     */
    public function getSetting(string $fileName, string $key)
    {
        return $this->arrayHelper->get($this->getConfigData($fileName), $key)
            ?? throw new ConfigException("Key '$key' not found in file '$fileName'.");
    }

    /**
     * Retrieve all settings from all configuration files.
     *
     * @return array The configuration settings from all files.
     * @throws ConfigException If there is an issue loading configuration files.
     */
    public function getAllConfigs(): array
    {
        return !empty($this->data)
            ? $this->data
            : array_reduce(
                $this->files,
                fn($carry, $file) => array_merge($carry, [
                    $this->fileManager->getBaseName($file->getPathname(), '.php') => $this->getConfigData($file->getBaseName('.php'))
                ]),
                []
            );
    }
}
