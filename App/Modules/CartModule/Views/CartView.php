<?php

declare(strict_types=1);

namespace App\Modules\CartModule\Views;

use App\Abstracts\Presentation\View;
use App\Core\Config;
use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Finders\FileFinder;
use App\Utilities\Managers\CacheManager;
use App\Utilities\Managers\FileManager;
use App\Utilities\Sanitation\PatternSanitizer;
use App\Utilities\Validation\PatternValidator;

class CartView extends View
{
    public function __construct(
        FileFinder $files,
        DirectoryFinder $dirs,
        CacheManager $cache,
        FileManager $fileManager,
        PatternSanitizer $sanitizer,
        PatternValidator $validator,
        Config $config
    ) {
        parent::__construct($files, $dirs, $cache, $fileManager, $sanitizer, $validator);

        $this->setDefaultLayout('WebShell');
        $this->share([
            'appName' => (string) $config->get('app', 'NAME', 'LangelerMVC'),
            'moduleName' => 'CartModule',
        ]);
    }
}
