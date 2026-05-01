<?php

declare(strict_types=1);

namespace App\Installer;

use App\Abstracts\Presentation\View;
use App\Core\Config;
use App\Support\Theming\ThemeManager;
use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Finders\FileFinder;
use App\Utilities\Managers\CacheManager;
use App\Utilities\Managers\FileManager;
use App\Utilities\Sanitation\PatternSanitizer;
use App\Utilities\Validation\PatternValidator;

final class InstallerView extends View
{
    public function __construct(
        FileFinder $files,
        DirectoryFinder $dirs,
        CacheManager $cache,
        FileManager $fileManager,
        PatternSanitizer $sanitizer,
        PatternValidator $validator,
        Config $config,
        ?ThemeManager $themes = null
    ) {
        parent::__construct($files, $dirs, $cache, $fileManager, $sanitizer, $validator);

        $this->setDefaultLayout('InstallerShell');
        $this->share([
            ...($themes ?? new ThemeManager($config))->layoutGlobals('installer'),
            'appName' => (string) $config->get('app', 'NAME', 'LangelerMVC'),
            'appVersion' => (string) $config->get('app', 'VERSION', '1.0.0'),
            'moduleName' => 'Installer',
        ]);
    }
}
