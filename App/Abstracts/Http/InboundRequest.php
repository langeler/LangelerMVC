<?php

declare(strict_types=1);

namespace App\Abstracts\Http;

use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Managers\FileManager;
use App\Utilities\Sanitation\GeneralSanitizer;
use App\Utilities\Sanitation\PatternSanitizer;
use App\Utilities\Validation\GeneralValidator;
use App\Utilities\Validation\PatternValidator;

/**
 * Captures HTTP globals into the framework request lifecycle.
 *
 * Concrete module requests can focus on sanitation and validation concerns
 * without reimplementing superglobal/header parsing.
 */
abstract class InboundRequest extends Request
{
    public function __construct(
        GeneralSanitizer $generalSanitizer,
        PatternSanitizer $patternSanitizer,
        GeneralValidator $generalValidator,
        PatternValidator $patternValidator,
        FileManager $fileManager,
        DirectoryFinder $directoryFinder
    ) {
        parent::__construct(
            $generalSanitizer,
            $patternSanitizer,
            $generalValidator,
            $patternValidator,
            $fileManager,
            $directoryFinder,
            $this->captureInput(),
            $this->captureFiles(),
            $this->requestSettings(),
            $this->captureHeaders(),
            $this->captureServer()
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function requestSettings(): array
    {
        return [
            'ext' => ['jpg', 'jpeg', 'png', 'webp', 'svg', 'pdf'],
            'max' => 4096,
            'resize' => ['w' => 1600, 'h' => 1200],
            'strip' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function captureInput(): array
    {
        $query = $this->isArray($_GET ?? null) ? $_GET : [];
        $form = $this->isArray($_POST ?? null) ? $_POST : [];
        $json = $this->captureJsonBody();

        return $this->replaceElements($query, $this->replaceElements($json, $form));
    }

    /**
     * @return array<string, mixed>
     */
    protected function captureFiles(): array
    {
        return $this->isArray($_FILES ?? null) ? $_FILES : [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function captureServer(): array
    {
        return $this->isArray($_SERVER ?? null) ? $_SERVER : [];
    }

    /**
     * @return array<string, string>
     */
    protected function captureHeaders(): array
    {
        if ($this->functionExists('getallheaders')) {
            $headers = getallheaders();

            if ($this->isArray($headers)) {
                return $this->map(
                    fn(mixed $value): string => $this->isScalar($value) ? $this->trimString((string) $value) : '',
                    $headers
                );
            }
        }

        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (!$this->isString($key) || !$this->isScalar($value)) {
                continue;
            }

            if ($this->startsWith($key, 'HTTP_')) {
                $normalized = $this->replaceText('_', '-', $this->substring($key, 5));
                $headers[$normalized] = $this->trimString((string) $value);
                continue;
            }

            if ($this->isInArray($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $normalized = $this->replaceText('_', '-', $key);
                $headers[$normalized] = $this->trimString((string) $value);
            }
        }

        return $headers;
    }

    /**
     * @return array<string, mixed>
     */
    protected function captureJsonBody(): array
    {
        $contentType = '';

        if ($this->isString($_SERVER['CONTENT_TYPE'] ?? null)) {
            $contentType = (string) $_SERVER['CONTENT_TYPE'];
        } elseif ($this->functionExists('getallheaders')) {
            $headers = getallheaders();
            $contentType = $this->isArray($headers) && $this->isString($headers['Content-Type'] ?? null)
                ? (string) $headers['Content-Type']
                : '';
        }

        if ($contentType === '' || !$this->contains($this->toLowerString($contentType), 'application/json')) {
            return [];
        }

        $raw = file_get_contents('php://input');

        if (!$this->isString($raw) || $this->trimString($raw) === '') {
            return [];
        }

        try {
            $decoded = $this->fromJson($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return $this->isArray($decoded) ? $decoded : [];
    }
}
