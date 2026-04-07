<?php

namespace App\Utilities\Handlers;

use Throwable;
use App\Utilities\Managers\{
    FileManager,
    System\ErrorManager
};
use App\Utilities\Sanitation\{
    PatternSanitizer
};
use App\Utilities\Validation\{
    PatternValidator
};
use App\Utilities\Traits\{
    TypeCheckerTrait,
    ExistenceCheckerTrait,
    ErrorTrait
};

/**
 * Class NamespaceResolveHandler
 *
 * Resolves declared PHP classes from files by parsing the file contents rather
 * than inferring namespaces from filesystem paths.
 */
class NamespaceResolveHandler
{
    use ErrorTrait, ExistenceCheckerTrait, TypeCheckerTrait;

    public function __construct(
        protected FileManager $fileManager,
        protected ErrorManager $errorManager,
        protected PatternSanitizer $patternSanitizer,
        protected PatternValidator $patternValidator
    ) {}

    /**
     * Resolves declared classes from one or more file paths.
     *
     * @param string|array $paths
     * @return array<int, array{file: string, namespace: string, class: string, shortName: string}>
     */
    public function resolvePaths(string|array $paths): array
    {
        return $this->wrapInTry(function () use ($paths): array {
            $pathList = is_string($paths) ? [$paths] : $paths;

            if (!is_array($pathList)) {
                throw $this->errorManager->resolveException(
                    'invalidArgument',
                    'Input must be a string or an array of strings.'
                );
            }

            foreach ($pathList as $path) {
                if (!is_string($path)) {
                    throw $this->errorManager->resolveException(
                        'invalidArgument',
                        'Input must be a string or an array of strings.'
                    );
                }
            }

            $resolved = [];

            foreach ($pathList as $path) {
                $class = $this->resolvePath($path);

                if ($class !== null) {
                    $resolved[] = $class;
                }
            }

            return $resolved;
        }, 'runtime');
    }

    /**
     * Resolves a single class declaration from a file path.
     *
     * @param string $filePath
     * @return array{file: string, namespace: string, class: string, shortName: string}|null
     */
    protected function resolvePath(string $filePath): ?array
    {
        return $this->wrapInTry(function () use ($filePath): ?array {
            $sanitizedPath = $this->sanitizePath($filePath);
            $validatedPath = $this->validatePath($sanitizedPath);

            if (!$this->checkFile($validatedPath)) {
                throw $this->errorManager->resolveException(
                    'invalidArgument',
                    "File does not exist or is invalid: {$validatedPath}"
                );
            }

            $source = $this->fileManager->readContents($validatedPath);

            if (!is_string($source)) {
                throw $this->errorManager->resolveException(
                    'runtime',
                    "Failed to read file: {$validatedPath}"
                );
            }

            return $this->parseDeclaration($validatedPath, $source);
        }, 'runtime');
    }

    /**
     * Sanitizes a file path.
     *
     * @param string $filePath
     * @return string
     */
    protected function sanitizePath(string $filePath): string
    {
        return $this->wrapInTry(
            fn() => $this->patternSanitizer->sanitizePathUnix(
                (string) preg_replace('/[\x00-\x1F\x7F]/u', '', $filePath)
            ) ?? '',
            'sanitization'
        );
    }

    /**
     * Validates a file path.
     *
     * @param string $filePath
     * @return string
     */
    protected function validatePath(string $filePath): string
    {
        return $this->wrapInTry(
            fn() => $this->patternValidator->validatePathUnix($filePath)
                ? $filePath
                : throw $this->errorManager->resolveException(
                    'validation',
                    "Validation failed for path: {$filePath}"
                ),
            'validation'
        );
    }

    /**
     * Checks that a file is a readable PHP file.
     *
     * @param string $filePath
     * @return bool
     */
    protected function checkFile(string $filePath): bool
    {
        return $this->fileManager->fileExists($filePath)
            && $this->fileManager->getExtension($filePath) === 'php'
            && $this->fileManager->isReadable($filePath)
            && !$this->fileManager->isDirectory($filePath);
    }

    /**
     * Parses the first declared class-like symbol from a PHP file.
     *
     * @param string $filePath
     * @param string $source
     * @return array{file: string, namespace: string, class: string, shortName: string}|null
     */
    protected function parseDeclaration(string $filePath, string $source): ?array
    {
        $tokens = token_get_all($source);
        $namespace = '';
        $shortName = null;
        $tokenCount = count($tokens);

        for ($index = 0; $index < $tokenCount; $index++) {
            $token = $tokens[$index];

            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespace = $this->readNamespace($tokens, $index + 1);
                continue;
            }

            if (
                $token[0] === T_CLASS
                || $token[0] === T_INTERFACE
                || (defined('T_TRAIT') && $token[0] === T_TRAIT)
                || (defined('T_ENUM') && $token[0] === T_ENUM)
            ) {
                $shortName = $this->readDeclarationName($tokens, $index + 1);

                if ($shortName !== null) {
                    break;
                }
            }
        }

        if ($shortName === null) {
            return null;
        }

        return [
            'file' => $filePath,
            'namespace' => $namespace,
            'class' => $namespace !== '' ? $namespace . '\\' . $shortName : $shortName,
            'shortName' => $shortName,
        ];
    }

    /**
     * Reads a namespace token sequence.
     *
     * @param array $tokens
     * @param int $index
     * @return string
     */
    private function readNamespace(array $tokens, int $index): string
    {
        $namespace = '';
        $tokenCount = count($tokens);

        for ($cursor = $index; $cursor < $tokenCount; $cursor++) {
            $token = $tokens[$cursor];

            if (is_string($token)) {
                if ($token === ';' || $token === '{') {
                    break;
                }

                if ($token === '\\') {
                    $namespace .= '\\';
                }

                continue;
            }

            if (
                $token[0] === T_STRING
                || (defined('T_NAME_QUALIFIED') && $token[0] === T_NAME_QUALIFIED)
                || (defined('T_NAME_FULLY_QUALIFIED') && $token[0] === T_NAME_FULLY_QUALIFIED)
            ) {
                $namespace .= $token[1];
            }
        }

        return trim($namespace, '\\');
    }

    /**
     * Reads the declared class name following a class-like token.
     *
     * @param array $tokens
     * @param int $index
     * @return string|null
     */
    private function readDeclarationName(array $tokens, int $index): ?string
    {
        $tokenCount = count($tokens);

        for ($cursor = $index; $cursor < $tokenCount; $cursor++) {
            $token = $tokens[$cursor];

            if (is_array($token) && $token[0] === T_STRING) {
                return $token[1];
            }

            if (is_string($token) && $token === '(') {
                return null;
            }
        }

        return null;
    }
}
