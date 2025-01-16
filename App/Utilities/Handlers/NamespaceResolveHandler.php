<?php

namespace App\Utilities\Handlers;

use App\Utilities\Managers\{
    FileManager,
    System\ErrorManager
};
use App\Utilities\Sanitation\{
    GeneralSanitizer,
    PatternSanitizer
};
use App\Utilities\Validation\{
    PatternValidator
};
use App\Utilities\Traits\{
    ManipulationTrait,
    ArrayTrait,
    TypeCheckerTrait,
    ExistenceCheckerTrait,
    ErrorTrait
};

/**
 * Class NamespaceResolveHandler
 *
 * Handles the extraction and resolution of namespaces and class names from given file paths.
 * Provides functionality for sanitizing, validating, and processing file paths to ensure correctness and security.
 */
class NamespaceResolveHandler
{
    use ManipulationTrait, ArrayTrait, TypeCheckerTrait, ExistenceCheckerTrait, ErrorTrait;

    public function __construct(
        protected FileManager $fileManager,
        protected ErrorManager $errorManager,
        protected GeneralSanitizer $generalSanitizer,
        protected PatternSanitizer $patternSanitizer,
        protected PatternValidator $patternValidator
    ) {}

    /**
     * Resolves namespaces and class names from file path(s).
     *
     * @param string|array $paths
     * @return array
     * @throws \Throwable
     */
    protected function resolvePaths(string|array $paths): array
{
    return $this->wrapInTry(
        fn() => $this->isString($paths)
            ? [$this->resolvePair(
                $this->sanitizePath($this->validatePath($paths))
            )]
            : ($this->isArray($paths) && $this->all($paths, fn($path) => $this->isString($path))
                ? $this->map(
                    fn($path) => $this->resolvePair(
                        $this->sanitizePath($this->validatePath($path))
                    ),
                    $paths
                )
                : throw $this->errorManager->resolveException(
                    'invalidArgument',
                    'Input must be a string or an array of strings.'
                )),
        fn($exception) => $this->errorManager->logThrowable(
            $exception,
            'NamespaceResolveHandler::resolvePaths',
            'userError'
        )
    );
}

    /**
     * Sanitizes the file path using GeneralSanitizer and PatternSanitizer.
     *
     * @param string $filePath
     * @return string
     * @throws \Throwable
     */
    protected function sanitizePath(string $filePath): string
    {
        return $this->wrapInTry(
            fn() => $this->patternSanitizer->clean(
                $this->generalSanitizer->clean([
                    'path' => [
                        ['string', 'fullSpecialChars'], // GeneralSanitizer types
                        ['noEncodeQuotes', 'stripLow', 'stripHigh', 'stripBacktick'], // GeneralSanitizer flags
                        ['require', 'notEmpty'] // GeneralSanitizer rules
                    ]
                ])['path'] ?? ''
            )['path'] ?? '',
            $this->errorManager->resolveException('sanitization', "Sanitation failed for path: {$filePath}")
        );
    }

    /**
     * Validates the provided file path using PatternValidator.
     *
     * @param string $filePath
     * @return string
     * @throws \Throwable
     */
    protected function validatePath(string $filePath): string
    {
        return $this->wrapInTry(
            fn() => $this->patternValidator->verify([
                'path' => [
                    ['pathUnix', 'fileName', 'directory'], // Validation types
                    ['require', 'notEmpty', 'minLength' => 3, 'maxLength' => 255] // Validation rules
                ]
            ])['path'] ?? '',
            $this->errorManager->resolveException('validation', "Validation failed for path: {$filePath}")
        );
    }

    /**
     * Checks if the file is valid and accessible for namespace resolution.
     *
     * @param string $filePath
     * @return bool
     * @throws \Throwable
     */
protected function checkFile(string $filePath): bool
{
    return $this->wrapInTry(
        fn() => $this->fileManager->fileExists($filePath) &&
            $this->fileManager->getExtension($filePath) === 'php' &&
            $this->fileManager->isReadable($filePath) &&
            !$this->fileManager->isDirectory($filePath),
        fn($exception) => $this->errorManager->logThrowable(
            $exception,
            'NamespaceResolveHandler::checkFile',
            'userNotice'
        )
    );
}

    /**
     * Resolves the namespace and class name pair from a single file path.
     *
     * @param string $filePath
     * @return array
     * @throws \Throwable
     */
    protected function resolvePair(string $filePath): array
    {
        return $this->wrapInTry(
            fn() => !$this->checkFile($filePath)
                ? throw $this->errorManager->resolveException(
                    'invalidArgument',
                    "File does not exist or is invalid: {$filePath}"
                )
                : [
                    $this->resolveNamespace($filePath),
                    $this->resolveClass($filePath)
                ],
            $this->errorManager->resolveException('runtime', "Failed to resolve namespace and class for: {$filePath}")
        );
    }

    /**
     * Resolves the namespace string from the file path.
     *
     * @param string $filePath
     * @return string
     * @throws \Throwable
     */
    protected function resolveNamespace(string $filePath): string
    {
        return $this->wrapInTry(
            fn() => $this->join(
                '\\',
                $this->filterNonEmpty(
                    $this->map(
                        $this->tokenizeString(
                            $this->replace(
                                ['/', '\\'],
                                '\\',
                                $this->fileManager->getPath($this->fileManager->getRealPath($filePath))
                            ),
                            '\\'
                        ),
                        fn($segment) => $this->trim($segment)
                    )
                )
            ),
            $this->errorManager->resolveException('logic', "Failed to resolve namespace from: {$filePath}")
        );
    }

    /**
     * Resolves the class name from the file path.
     *
     * @param string $filePath
     * @return string
     * @throws \Throwable
     */
    protected function resolveClass(string $filePath): string
    {
        return $this->wrapInTry(
            fn() => $this->fileManager->getBaseName($filePath, '.php'),
            $this->errorManager->resolveException('logic', "Failed to resolve class from: {$filePath}")
        );
    }
}