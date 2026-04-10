<?php

declare(strict_types=1);

namespace App\Modules\WebModule\Requests;

use App\Abstracts\Http\InboundRequest;
use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Managers\FileManager;
use App\Utilities\Sanitation\GeneralSanitizer;
use App\Utilities\Sanitation\PatternSanitizer;
use App\Utilities\Validation\GeneralValidator;
use App\Utilities\Validation\PatternValidator;

/**
 * Framework-facing HTTP request for the default web module.
 */
class WebRequest extends InboundRequest
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
            $directoryFinder
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
}
