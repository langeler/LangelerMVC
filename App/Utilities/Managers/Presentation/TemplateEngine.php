<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Presentation;

use App\Contracts\Presentation\TemplateEngineInterface;
use App\Exceptions\Presentation\ViewException;
use App\Utilities\Managers\FileManager;
use App\Utilities\Traits\ApplicationPathTrait;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\HashingTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Traits\TypeCheckerTrait;

final class TemplateEngine implements TemplateEngineInterface
{
    use ApplicationPathTrait;
    use ArrayTrait;
    use HashingTrait;
    use ManipulationTrait;
    use PatternTrait;
    use TypeCheckerTrait;

    private const NATIVE_EXTENSIONS = ['vide', 'lmv'];
    private const COMPILER_VERSION = '5';

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
        $extension = $this->toLower((string) $this->fileManager->getExtension($templatePath));

        return $this->any(self::NATIVE_EXTENSIONS, static fn(string $native): bool => $native === $extension);
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
        $sourceMTime = $this->fileManager->getModifiedTime($templatePath) ?? time();
        $compiledMTime = $this->fileManager->fileExists($compiledPath)
            ? ($this->fileManager->getModifiedTime($compiledPath) ?? 0)
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

        $compiled = $this->replaceByPattern('/\{\{\-\-.*?\-\-\}\}/s', '', $compiled) ?? $compiled;
        $compiled = $this->replaceExpressionDirective($compiled, 'include', static fn(string $expression): string => "<?= \$view->renderPartial(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'component', static fn(string $expression): string => "<?= \$view->renderComponent(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'section', static fn(string $expression): string => "<?php \$view->startSection(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceByPattern('/\@endsection\b/', '<?php $view->stopSection(); ?>', $compiled) ?? $compiled;
        $compiled = $this->replaceExpressionDirective($compiled, 'yield', static fn(string $expression): string => "<?= \$view->yieldContent(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'push', static fn(string $expression): string => "<?php \$view->push(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceByPattern('/\@endpush\b/', '<?php $view->stopPush(); ?>', $compiled) ?? $compiled;
        $compiled = $this->replaceExpressionDirective($compiled, 'stack', static fn(string $expression): string => "<?= \$view->stack(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'hasSection', static fn(string $expression): string => "<?php if (\$view->hasSection(...(array) [{$expression}])): ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'asset', static fn(string $expression): string => "<?= \$view->renderAsset(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'assetUrl', static fn(string $expression): string => "<?= \$view->assetUrl(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'assetVersion', static fn(string $expression): string => "<?= \$view->assetVersion(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'assetBundle', static fn(string $expression): string => "<?= \$view->assetBundle(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'preload', static fn(string $expression): string => "<?= \$view->preloadTag(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'style', static fn(string $expression): string => "<?= \$view->styleTag(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'script', static fn(string $expression): string => "<?= \$view->scriptTag(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'image', static fn(string $expression): string => "<?= \$view->imageTag(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'method', static fn(string $expression): string => "<?= \$view->formMethod(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'class', static fn(string $expression): string => "<?= \$view->classList(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'attr', static fn(string $expression): string => "<?= \$view->attributes(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'attrs', static fn(string $expression): string => "<?= \$view->attributes(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'json', static fn(string $expression): string => "<?= \$view->jsonForScript(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceByPattern('/\@csrf\b/', '<?= $view->csrfField(); ?>', $compiled) ?? $compiled;
        $compiled = $this->replaceExpressionDirective($compiled, 'isset', static fn(string $expression): string => "<?php if (isset({$expression})): ?>");
        $compiled = $this->replaceByPattern('/\@endisset\b/', '<?php endif; ?>', $compiled) ?? $compiled;
        $compiled = $this->replaceExpressionDirective($compiled, 'empty', static fn(string $expression): string => "<?php if (empty({$expression})): ?>");
        $compiled = $this->replaceByPattern('/\@endempty\b/', '<?php endif; ?>', $compiled) ?? $compiled;
        $compiled = $this->replaceExpressionDirective($compiled, 'if', static fn(string $expression): string => "<?php if ({$expression}): ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'elseif', static fn(string $expression): string => "<?php elseif ({$expression}): ?>");
        $compiled = $this->replaceByPattern('/\@else\b/', '<?php else: ?>', $compiled) ?? $compiled;
        $compiled = $this->replaceByPattern('/\@endif\b/', '<?php endif; ?>', $compiled) ?? $compiled;
        $compiled = $this->replaceExpressionDirective($compiled, 'foreach', static fn(string $expression): string => "<?php foreach ({$expression}): ?>");
        $compiled = $this->replaceByPattern('/\@endforeach\b/', '<?php endforeach; ?>', $compiled) ?? $compiled;
        $compiled = $this->replaceExpressionDirective($compiled, 'for', static fn(string $expression): string => "<?php for ({$expression}): ?>");
        $compiled = $this->replaceByPattern('/\@endfor\b/', '<?php endfor; ?>', $compiled) ?? $compiled;
        $compiled = $this->replaceExpressionDirective($compiled, 'while', static fn(string $expression): string => "<?php while ({$expression}): ?>");
        $compiled = $this->replaceByPattern('/\@endwhile\b/', '<?php endwhile; ?>', $compiled) ?? $compiled;
        $compiled = $this->replaceExpressionDirective($compiled, 'unless', static fn(string $expression): string => "<?php if (!({$expression})): ?>");
        $compiled = $this->replaceByPattern('/\@endunless\b/', '<?php endif; ?>', $compiled) ?? $compiled;
        $compiled = $this->replaceExpressionDirective($compiled, 'checked', static fn(string $expression): string => "<?= ({$expression}) ? ' checked' : '' ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'selected', static fn(string $expression): string => "<?= ({$expression}) ? ' selected' : '' ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'disabled', static fn(string $expression): string => "<?= ({$expression}) ? ' disabled' : '' ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'readonly', static fn(string $expression): string => "<?= ({$expression}) ? ' readonly' : '' ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'required', static fn(string $expression): string => "<?= ({$expression}) ? ' required' : '' ?>");
        $compiled = $this->replaceByPattern('/\@php\b/', '<?php ', $compiled) ?? $compiled;
        $compiled = $this->replaceByPattern('/\@endphp\b/', ' ?>', $compiled) ?? $compiled;
        $compiled = $this->replaceByPattern('/\{!!\s*(.+?)\s*!!\}/s', '<?= (string) (${1}); ?>', $compiled) ?? $compiled;
        $compiled = $this->replaceByPattern('/\{\{\s*(.+?)\s*\}\}/s', '<?= $view->escape(${1}); ?>', $compiled) ?? $compiled;

        $header = "<?php\n";
        $header .= "declare(strict_types=1);\n";
        $header .= $sourcePath !== null ? "/* Source: {$sourcePath} */\n" : '';
        $header .= "?>\n";

        return $header . $compiled;
    }

    private function compiledPath(string $templatePath): string
    {
        $hash = $this->hashString(self::COMPILER_VERSION . ':' . $templatePath, 'sha1');
        $file = $this->fileNameWithoutExtension($templatePath) . '-' . $hash . '.php';

        return $this->cachePath . DIRECTORY_SEPARATOR . $file;
    }

    /**
     * @param callable(string): string $callback
     */
    private function replaceExpressionDirective(string $template, string $directive, callable $callback): string
    {
        $pattern = sprintf('/@%s\s*(\((?:[^()]++|(?1))*\))/', $this->quote($directive, '/'));

        return $this->replaceCallback(
            $pattern,
            function (array $matches) use ($callback): string {
                $wrappedExpression = $matches[1] ?? '()';
                $expression = $this->substring($wrappedExpression, 1, -1);

                return $callback($expression);
            },
            $template
        ) ?? $template;
    }

    private function fileNameWithoutExtension(string $templatePath): string
    {
        $filename = (string) ($this->fileManager->getFilename($templatePath) ?? 'template');

        return $this->replaceByPattern('/\.[^.]+$/', '', $filename) ?? $filename;
    }
}
