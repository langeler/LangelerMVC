<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Views;

use App\Abstracts\Presentation\View;
use App\Core\Config;
use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Finders\FileFinder;
use App\Utilities\Managers\CacheManager;
use App\Utilities\Managers\FileManager;
use App\Utilities\Sanitation\PatternSanitizer;
use App\Utilities\Validation\PatternValidator;

class UserView extends View
{
    private string $defaultLayout = 'UserShell';

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

        $this->setGlobals([
            'appName' => (string) $config->get('app', 'NAME', 'LangelerMVC'),
            'appVersion' => (string) $config->get('app', 'VERSION', '1.0.0'),
        ]);
    }

    public function renderPage(string $page, array $data = []): string
    {
        $pageContent = parent::renderPage($page, $data);

        return parent::renderLayout($this->defaultLayout, $this->replaceElements($data, [
            'content' => $pageContent,
        ]));
    }
}
