<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Presentation;

use App\Contracts\Presentation\TemplateEngineInterface;
use App\Exceptions\Presentation\ViewException;
use App\Utilities\Managers\FileManager;
use App\Utilities\Traits\ApplicationPathTrait;

final class TemplateEngine implements TemplateEngineInterface
{
    use ApplicationPathTrait;

    private const NATIVE_EXTENSIONS = ['vide', 'lmv'];

    private string $cachePath;

    public function __construct(
        private readonly FileManager $fileManager,
        ?string $cachePath = null
    ) {
        $this->cachePath = $cachePath
            ?? $this->frameworkStoragePath('Cache/Templates');
    }

    public function supports(string $templatePath): bool
    {
        return in_array(strtolower(pathinfo($templatePath, PATHINFO_EXTENSION)), self::NATIVE_EXTENSIONS, true);
    }

    public function resolveRenderablePath(string $templatePath): string
    {
        if (!$this->supports($templatePath)) {
            return $templatePath;
        }

        $source = $this->fileManager->readContents($templatePath);

        if (!is_string($source)) {
            throw new ViewException(sprintf('Unable to read template [%s].', $templatePath));
        }

        $compiledPath = $this->compiledPath($templatePath);
        $sourceMTime = filemtime($templatePath) ?: time();
        $compiledMTime = $this->fileManager->fileExists($compiledPath)
            ? (filemtime($compiledPath) ?: 0)
            : 0;

        if ($compiledMTime < $sourceMTime) {
            $compiled = $this->compileString($source, $templatePath);

            if ($this->fileManager->writeContents($compiledPath, $compiled) === false) {
                throw new ViewException(sprintf('Unable to compile template [%s].', $templatePath));
            }
        }

        return $compiledPath;
    }

    public function compileString(string $template, ?string $sourcePath = null): string
    {
        $compiled = $template;

        $compiled = preg_replace('/\{\{\-\-.*?\-\-\}\}/s', '', $compiled) ?? $compiled;
        $compiled = preg_replace('/\@include\s*\(\s*(.+?)\s*\)/', '<?= $view->renderPartial(...(array) [${1}]); ?>', $compiled) ?? $compiled;
        $compiled = preg_replace('/\@component\s*\(\s*(.+?)\s*\)/', '<?= $view->renderComponent(...(array) [${1}]); ?>', $compiled) ?? $compiled;
        $compiled = preg_replace('/\@asset\s*\(\s*(.+?)\s*\)/', '<?= $view->renderAsset(...(array) [${1}]); ?>', $compiled) ?? $compiled;
        $compiled = preg_replace('/\@if\s*\((.+?)\)/', '<?php if (${1}): ?>', $compiled) ?? $compiled;
        $compiled = preg_replace('/\@elseif\s*\((.+?)\)/', '<?php elseif (${1}): ?>', $compiled) ?? $compiled;
        $compiled = preg_replace('/\@else\b/', '<?php else: ?>', $compiled) ?? $compiled;
        $compiled = preg_replace('/\@endif\b/', '<?php endif; ?>', $compiled) ?? $compiled;
        $compiled = preg_replace('/\@foreach\s*\((.+?)\)/', '<?php foreach (${1}): ?>', $compiled) ?? $compiled;
        $compiled = preg_replace('/\@endforeach\b/', '<?php endforeach; ?>', $compiled) ?? $compiled;
        $compiled = preg_replace('/\@for\s*\((.+?)\)/', '<?php for (${1}): ?>', $compiled) ?? $compiled;
        $compiled = preg_replace('/\@endfor\b/', '<?php endfor; ?>', $compiled) ?? $compiled;
        $compiled = preg_replace('/\@while\s*\((.+?)\)/', '<?php while (${1}): ?>', $compiled) ?? $compiled;
        $compiled = preg_replace('/\@endwhile\b/', '<?php endwhile; ?>', $compiled) ?? $compiled;
        $compiled = preg_replace('/\@unless\s*\((.+?)\)/', '<?php if (!(${1})): ?>', $compiled) ?? $compiled;
        $compiled = preg_replace('/\@endunless\b/', '<?php endif; ?>', $compiled) ?? $compiled;
        $compiled = preg_replace('/\@php\b/', '<?php ', $compiled) ?? $compiled;
        $compiled = preg_replace('/\@endphp\b/', ' ?>', $compiled) ?? $compiled;
        $compiled = preg_replace('/\{!!\s*(.+?)\s*!!\}/s', '<?= (string) (${1}); ?>', $compiled) ?? $compiled;
        $compiled = preg_replace('/\{\{\s*(.+?)\s*\}\}/s', '<?= $view->escape(${1}); ?>', $compiled) ?? $compiled;

        $header = "<?php\n";
        $header .= "declare(strict_types=1);\n";
        $header .= $sourcePath !== null ? "/* Source: {$sourcePath} */\n" : '';
        $header .= "?>\n";

        return $header . $compiled;
    }

    private function compiledPath(string $templatePath): string
    {
        $hash = sha1($templatePath);
        $file = pathinfo($templatePath, PATHINFO_FILENAME) . '-' . $hash . '.php';

        return $this->cachePath . DIRECTORY_SEPARATOR . $file;
    }
}
