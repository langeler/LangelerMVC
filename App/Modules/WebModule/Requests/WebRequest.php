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
            is_array($_FILES ?? null) ? $_FILES : [],
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
        $query = is_array($_GET ?? null) ? $_GET : [];
        $body = is_array($_POST ?? null) ? $_POST : [];

        return array_replace($query, $body);
    }

    /**
     * @return array<string, string>
     */
    private function captureHeaders(): array
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();

            if (is_array($headers)) {
                return array_map(
                    static fn(mixed $value): string => is_scalar($value) ? trim((string) $value) : '',
                    $headers
                );
            }
        }

        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (!is_string($key) || !is_scalar($value)) {
                continue;
            }

            if (str_starts_with($key, 'HTTP_')) {
                $normalized = str_replace('_', '-', substr($key, 5));
                $headers[$normalized] = trim((string) $value);
                continue;
            }

            if (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $normalized = str_replace('_', '-', $key);
                $headers[$normalized] = trim((string) $value);
            }
        }

        return $headers;
    }
}
