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
    private const COMPILER_VERSION = '3';

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
        $compiled = $this->replaceExpressionDirective($compiled, 'include', static fn(string $expression): string => "<?= \$view->renderPartial(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'component', static fn(string $expression): string => "<?= \$view->renderComponent(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'asset', static fn(string $expression): string => "<?= \$view->renderAsset(...(array) [{$expression}]); ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'isset', static fn(string $expression): string => "<?php if (isset({$expression})): ?>");
        $compiled = preg_replace('/\@endisset\b/', '<?php endif; ?>', $compiled) ?? $compiled;
        $compiled = $this->replaceExpressionDirective($compiled, 'empty', static fn(string $expression): string => "<?php if (empty({$expression})): ?>");
        $compiled = preg_replace('/\@endempty\b/', '<?php endif; ?>', $compiled) ?? $compiled;
        $compiled = $this->replaceExpressionDirective($compiled, 'if', static fn(string $expression): string => "<?php if ({$expression}): ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'elseif', static fn(string $expression): string => "<?php elseif ({$expression}): ?>");
        $compiled = preg_replace('/\@else\b/', '<?php else: ?>', $compiled) ?? $compiled;
        $compiled = preg_replace('/\@endif\b/', '<?php endif; ?>', $compiled) ?? $compiled;
        $compiled = $this->replaceExpressionDirective($compiled, 'foreach', static fn(string $expression): string => "<?php foreach ({$expression}): ?>");
        $compiled = preg_replace('/\@endforeach\b/', '<?php endforeach; ?>', $compiled) ?? $compiled;
        $compiled = $this->replaceExpressionDirective($compiled, 'for', static fn(string $expression): string => "<?php for ({$expression}): ?>");
        $compiled = preg_replace('/\@endfor\b/', '<?php endfor; ?>', $compiled) ?? $compiled;
        $compiled = $this->replaceExpressionDirective($compiled, 'while', static fn(string $expression): string => "<?php while ({$expression}): ?>");
        $compiled = preg_replace('/\@endwhile\b/', '<?php endwhile; ?>', $compiled) ?? $compiled;
        $compiled = $this->replaceExpressionDirective($compiled, 'unless', static fn(string $expression): string => "<?php if (!({$expression})): ?>");
        $compiled = preg_replace('/\@endunless\b/', '<?php endif; ?>', $compiled) ?? $compiled;
        $compiled = $this->replaceExpressionDirective($compiled, 'checked', static fn(string $expression): string => "<?= ({$expression}) ? ' checked' : '' ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'selected', static fn(string $expression): string => "<?= ({$expression}) ? ' selected' : '' ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'disabled', static fn(string $expression): string => "<?= ({$expression}) ? ' disabled' : '' ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'readonly', static fn(string $expression): string => "<?= ({$expression}) ? ' readonly' : '' ?>");
        $compiled = $this->replaceExpressionDirective($compiled, 'required', static fn(string $expression): string => "<?= ({$expression}) ? ' required' : '' ?>");
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
        $hash = sha1(self::COMPILER_VERSION . ':' . $templatePath);
        $file = pathinfo($templatePath, PATHINFO_FILENAME) . '-' . $hash . '.php';

        return $this->cachePath . DIRECTORY_SEPARATOR . $file;
    }

    /**
     * @param callable(string): string $callback
     */
    private function replaceExpressionDirective(string $template, string $directive, callable $callback): string
    {
        $pattern = sprintf('/@%s\s*(\((?:[^()]++|(?1))*\))/', preg_quote($directive, '/'));

        return preg_replace_callback(
            $pattern,
            static function (array $matches) use ($callback): string {
                $wrappedExpression = $matches[1] ?? '()';
                $expression = substr($wrappedExpression, 1, -1);

                return $callback($expression);
            },
            $template
        ) ?? $template;
    }
}
