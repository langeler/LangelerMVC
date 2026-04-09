<?php

declare(strict_types=1);

namespace App\Modules\WebModule\Requests;

use App\Abstracts\Http\Request;
use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Managers\FileManager;
use App\Utilities\Sanitation\GeneralSanitizer;
use App\Utilities\Sanitation\PatternSanitizer;
use App\Utilities\Validation\GeneralValidator;
use App\Utilities\Validation\PatternValidator;

/**
 * Framework-facing HTTP request for the default web module.
 */
class WebRequest extends Request
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
            $this->isArray($_FILES ?? null) ? $_FILES : [],
            [
                'ext' => ['jpg', 'jpeg', 'png', 'webp', 'svg', 'pdf'],
                'max' => 4096,
                'resize' => ['w' => 1600, 'h' => 1200],
                'strip' => true,
            ],
            $this->captureHeaders()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function captureInput(): array
    {
        $query = $this->isArray($_GET ?? null) ? $_GET : [];
        $body = $this->isArray($_POST ?? null) ? $_POST : [];

        return $this->replaceElements($query, $body);
    }

    /**
     * @return array<string, string>
     */
    private function captureHeaders(): array
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
}
