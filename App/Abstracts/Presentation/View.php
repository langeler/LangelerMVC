<?php

declare(strict_types=1);

namespace App\Abstracts\Presentation;

use App\Contracts\Presentation\AssetManagerInterface;
use App\Contracts\Presentation\HtmlManagerInterface;
use App\Contracts\Presentation\TemplateEngineInterface;
use App\Contracts\Presentation\ViewInterface;
use App\Exceptions\Data\FinderException;
use App\Exceptions\Presentation\ViewException;
use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Finders\FileFinder;
use App\Utilities\Managers\CacheManager;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\Presentation\AssetManager;
use App\Utilities\Managers\Presentation\HtmlManager;
use App\Utilities\Managers\Presentation\TemplateEngine;
use App\Utilities\Sanitation\PatternSanitizer;
use App\Utilities\Traits\ApplicationPathTrait;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\ConversionTrait;
use App\Utilities\Traits\EncodingTrait;
use App\Utilities\Traits\ErrorTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;
use App\Utilities\Validation\PatternValidator;

/**
 * Base presentation view.
 *
 * Centralizes path resolution, page-to-layout composition, shared globals,
 * partial/component rendering, and safe template lookup so modules can focus
 * on presentation intent instead of filesystem orchestration.
 */
abstract class View implements ViewInterface
{
	use ApplicationPathTrait;
	use ErrorTrait;
	use ArrayTrait;
	use ConversionTrait;
	use ManipulationTrait;
	use EncodingTrait;
	use TypeCheckerTrait;

	protected array $globals = [];
	protected string $templateExt = 'vide';
	protected string $resourceExt = 'php';
	protected string $theme = 'default';
	protected ?string $defaultLayout = null;
	protected array $templateExtensions = ['vide', 'lmv', 'php'];

	private string $resourcesPath;
	private string $templatesPath;
    private TemplateEngineInterface $templateEngine;
    private AssetManagerInterface $assetManager;
    private HtmlManagerInterface $htmlManager;

	/**
	 * @var array<string, string>
	 */
	private array $resolvedPaths = [];

	public function __construct(
		private FileFinder $files,
		private DirectoryFinder $dirs,
		private CacheManager $cache,
		private FileManager $fileManager,
        private PatternSanitizer $sanitizer,
        private PatternValidator $validator,
        ?TemplateEngineInterface $templateEngine = null,
        ?AssetManagerInterface $assetManager = null,
        ?HtmlManagerInterface $htmlManager = null
	) {
		$this->resourcesPath = $this->resolveBasePath('Resources');
		$this->templatesPath = $this->resolveBasePath('Templates');
        $this->htmlManager = $htmlManager ?? new HtmlManager();
        $this->templateEngine = $templateEngine ?? new TemplateEngine($this->fileManager);
        $this->assetManager = $assetManager ?? new AssetManager($this->fileManager, $this->sanitizer, $this->validator, $this->htmlManager);
	}

	public function setDefaultLayout(string $layout): static
	{
		$this->defaultLayout = $this->normalizeTemplateName($layout, 'layout');

		return $this;
	}

	public function getDefaultLayout(): ?string
	{
		return $this->defaultLayout;
	}

	public function clearDefaultLayout(): static
	{
		$this->defaultLayout = null;

		return $this;
	}

	public function renderLayout(string $layout, array $data = []): string
	{
		return $this->renderTemplate($this->getLayoutPath($layout), $data);
	}

	public function renderPage(string $page, array $data = []): string
	{
		if ($this->defaultLayout === null || $this->defaultLayout === '') {
			return $this->renderPageContent($page, $data);
		}

		return $this->renderPageWithLayout($this->defaultLayout, $page, $data);
	}

	public function renderPageContent(string $page, array $data = []): string
	{
		return $this->renderTemplate($this->getPagePath($page), $data);
	}

	public function renderPageWithLayout(string $layout, string $page, array $data = []): string
	{
		$pageContent = $this->renderPageContent($page, $data);

		return $this->renderLayout($layout, $this->replaceElements($data, [
			'content' => $pageContent,
		]));
	}

	public function renderPartial(string $partial, array $data = []): string
	{
		return $this->renderTemplate($this->getPartialPath($partial), $data);
	}

	public function renderComponent(string $component, array $data = []): string
	{
		return $this->renderTemplate($this->getComponentPath($component), $data);
	}

	public function templateExists(string $type, string $template): bool
	{
		try {
			$this->resolveTemplatePath($type, $template);

			return true;
		} catch (\Throwable) {
			return false;
		}
	}

	public function renderAsset(string $type, string $asset): string
	{
		return $this->wrapInTry(function () use ($type, $asset): string {
			return $this->assetManager->sourcePath($type, $asset);
		}, ViewException::class);
	}

    public function assetUrl(string $type, string $asset): string
    {
        return $this->wrapInTry(
            fn(): string => $this->assetManager->publicUrl($type, $asset),
            ViewException::class
        );
    }

    public function assetVersion(string $type, string $asset): string
    {
        return $this->wrapInTry(
            fn(): string => $this->assetManager->versionedUrl($type, $asset),
            ViewException::class
        );
    }

    public function styleTag(string $asset, array $attributes = []): string
    {
        return $this->wrapInTry(
            fn(): string => $this->assetManager->tag('css', $asset, $attributes),
            ViewException::class
        );
    }

    public function scriptTag(string $asset, array $attributes = []): string
    {
        return $this->wrapInTry(
            fn(): string => $this->assetManager->tag('js', $asset, $attributes),
            ViewException::class
        );
    }

    public function imageTag(string $asset, string $alt = '', array $attributes = []): string
    {
        return $this->wrapInTry(
            fn(): string => $this->assetManager->tag('images', $asset, $this->replaceElements(['alt' => $alt], $attributes)),
            ViewException::class
        );
    }

    public function preloadTag(string $type, string $asset, array $attributes = []): string
    {
        return $this->wrapInTry(
            fn(): string => $this->assetManager->preloadTag($type, $asset, $attributes),
            ViewException::class
        );
    }

    public function assetBundle(string $name, array $attributes = []): string
    {
        return $this->wrapInTry(
            fn(): string => $this->assetManager->bundleTags($name, $attributes),
            ViewException::class
        );
    }

    public function csrfField(): string
    {
        $token = (string) ($this->globals['csrfToken'] ?? $this->globals['csrf_token'] ?? '');
        $field = (string) ($this->globals['csrfField'] ?? $this->globals['csrf_field'] ?? '_token');

        return $this->htmlManager->csrfField($token, $field);
    }

    public function formMethod(string $method): string
    {
        return $this->htmlManager->methodField($method);
    }

    public function classList(array|string $classes): string
    {
        return $this->htmlManager->classList($classes);
    }

    public function attributes(array $attributes): string
    {
        return $this->htmlManager->attributes($attributes);
    }

    public function jsonForScript(mixed $value, int $flags = 0, int $depth = 512): string
    {
        return $this->htmlManager->json($value, $flags, $depth);
    }

	public function setGlobals(array $variables): void
	{
		$this->globals = $this->replaceElements($this->globals, $variables);
	}

	public function share(string|array $key, mixed $value = null): static
	{
		$this->setGlobals($this->isArray($key) ? $key : [$key => $value]);

		return $this;
	}

	public function getGlobals(): array
	{
		return $this->globals;
	}

	public function cacheTemplate(string $key, string $content, ?int $ttl = null): void
	{
		$this->wrapInTry(function () use ($key, $content, $ttl): void {
			if (!$this->cache->set($key, $content, $ttl)) {
				throw new ViewException("Failed to cache template '{$key}'.");
			}
		}, ViewException::class);
	}

	public function fetchCachedTemplate(string $key): ?string
	{
		return $this->wrapInTry(function () use ($key): ?string {
			$cached = $this->cache->get($key);

			return $this->isString($cached) ? $cached : null;
		}, ViewException::class);
	}

	public function escape(mixed $value): string
	{
		return $this->htmlManager->escape($value);
	}

	public function escapeUrl(mixed $value): string
	{
		return $this->htmlManager->escapeUrl($value);
	}

	protected function getCssPath(string $file): string
	{
		return $this->resolveAssetPath('css', $file);
	}

	protected function getJsPath(string $file): string
	{
		return $this->resolveAssetPath('js', $file);
	}

	protected function getImagePath(string $file): string
	{
		return $this->resolveAssetPath('images', $file);
	}

	protected function getLayoutPath(string $file): string
	{
		return $this->resolveTemplatePath('layout', $file);
	}

	protected function getPagePath(string $file): string
	{
		return $this->resolveTemplatePath('page', $file);
	}

	protected function getPartialPath(string $file): string
	{
		return $this->resolveTemplatePath('partial', $file);
	}

	protected function getComponentPath(string $file): string
	{
		return $this->resolveTemplatePath('component', $file);
	}

	/**
	 * Render a resolved template file with merged globals and local data.
	 *
	 * @param array<string,mixed> $data
	 * @return string
	 */
	protected function renderTemplate(string $path, array $data = []): string
	{
		return $this->wrapInTry(function () use ($path, $data): string {
			$variables = $this->replaceElements($this->globals, $data);
			$view = $this;
            $renderPath = $this->templateEngine->resolveRenderablePath($path);

			ob_start();

			try {
				extract($variables, EXTR_SKIP);
				$result = include $renderPath;
			} catch (\Throwable $exception) {
				ob_end_clean();
				throw $exception;
			}

			$output = ob_get_clean();

			if ($output !== false && $output !== '') {
				return $output;
			}

			return $this->isString($result ?? null) ? $result : '';
		}, ViewException::class);
	}

	private function resolveBasePath(string $dirName): string
	{
		return $this->wrapInTry(function () use ($dirName): string {
			$expected = $this->frameworkBasePath()
				. DIRECTORY_SEPARATOR
				. 'App'
				. DIRECTORY_SEPARATOR
				. $dirName;

			return $this->getValidPath($expected, "Base directory '{$dirName}' not found.");
		}, ViewException::class);
	}

	private function resolveDirectory(string $basePath, string $subDir, string $label): string
	{
		$cacheKey = 'dir:' . $basePath . ':' . $subDir;

		if (isset($this->resolvedPaths[$cacheKey])) {
			return $this->resolvedPaths[$cacheKey];
		}

		$path = $basePath . DIRECTORY_SEPARATOR . $subDir;
		$this->resolvedPaths[$cacheKey] = $this->getValidPath(
			$path,
			"{$label} directory '{$subDir}' not found in '{$basePath}'."
		);

		return $this->resolvedPaths[$cacheKey];
	}

	private function resolveAssetPath(string $type, string $file): string
	{
		$directory = $this->resolveDirectory($this->resourcesPath, $type, 'Resource');

		return $this->resolveFilePath($directory, $file, null, 'asset');
	}

	private function resolveTemplatePath(string $type, string $template): string
	{
		$normalizedType = $this->normalizeTemplateType($type);
		$directory = match ($normalizedType) {
			'layout' => $this->resolveDirectory($this->templatesPath, 'Layouts', 'Template'),
			'page' => $this->resolveDirectory($this->templatesPath, 'Pages', 'Template'),
			'partial' => $this->resolveDirectory($this->templatesPath, 'Partials', 'Template'),
			'component' => $this->resolveDirectory($this->templatesPath, 'Components', 'Template'),
		};

		return $this->resolveTemplateFilePath($directory, $template, $normalizedType);
	}

	private function resolveFilePath(string $basePath, string $fileName, ?string $ext = null, string $label = 'file'): string
	{
		$normalizedName = $this->normalizeTemplateName($fileName, $label);
		$cacheKey = 'file:' . $basePath . ':' . $normalizedName . ':' . ($ext ?? '');

		if (isset($this->resolvedPaths[$cacheKey])) {
			return $this->resolvedPaths[$cacheKey];
		}

		$path = $basePath
			. DIRECTORY_SEPARATOR
			. $normalizedName
			. ($ext !== null && $ext !== '' ? ".{$ext}" : '');

		$this->resolvedPaths[$cacheKey] = $this->getValidPath(
			$path,
			ucfirst($label) . " '{$normalizedName}' not found in '{$basePath}'.",
			isFileCheck: true
		);

		return $this->resolvedPaths[$cacheKey];
	}

    private function resolveTemplateFilePath(string $basePath, string $fileName, string $label): string
    {
        $normalizedName = $this->normalizeTemplateName($fileName, $label);
        $extensions = array_values(array_unique(array_filter(
            $this->templateExtensions(),
            static fn(string $extension): bool => $extension !== ''
        )));

        foreach ($extensions as $extension) {
            $path = $basePath . DIRECTORY_SEPARATOR . $normalizedName . '.' . $extension;

            if ($this->fileManager->fileExists($path)) {
                return $this->normalizePath($path);
            }
        }

        throw new FinderException(
            ucfirst($label) . " '{$normalizedName}' not found in '{$basePath}'."
        );
    }

    /**
     * @return array<int, string>
     */
    private function templateExtensions(): array
    {
        $configured = $this->templateExtensions;

        if ($this->templateExt !== '' && !$this->any($configured, fn(string $extension): bool => $extension === $this->templateExt)) {
            $configured[] = $this->templateExt;
        }

        return $configured;
    }

    private function normalizeTemplateType(string $type): string
    {
        $normalized = $this->toLower($this->trimString($type));

		return match ($normalized) {
			'layout', 'layouts' => 'layout',
			'page', 'pages' => 'page',
			'partial', 'partials' => 'partial',
			'component', 'components' => 'component',
			default => throw new ViewException("Unsupported template type '{$type}'."),
		};
	}

	private function normalizeTemplateName(string $name, string $label): string
	{
		$normalized = $this->replaceText('\\', '/', $this->trimString($name));
		$normalized = $this->sanitizer->sanitizePathUnix((string) $normalized);

		if ($normalized === '') {
			throw new ViewException("Invalid {$label} identifier '{$name}'.");
		}

		$segments = $this->splitString('/', (string) $normalized);

		if ($this->any($segments, fn(string $segment): bool => $segment === '' || $segment === '.' || $segment === '..')) {
			throw new ViewException("Unsafe {$label} identifier '{$name}'.");
		}

		if ($this->any($segments, fn(string $segment): bool => !$this->validator->validateDirectory($segment))) {
			throw new ViewException("Invalid {$label} identifier '{$name}'.");
		}

		return $this->joinStrings(DIRECTORY_SEPARATOR, $segments);
	}

	private function getValidPath(?string $path, string $errorMessage, bool $isFileCheck = false): string
	{
		if (
			!$this->isString($path)
			|| $path === ''
			|| ($isFileCheck && !$this->fileManager->fileExists($path))
			|| (!$isFileCheck && !$this->fileManager->isDirectory($path))
		) {
			throw new FinderException($errorMessage);
		}

		return $this->normalizePath($path);
	}

	private function normalizePath(string $path): string
	{
		return $this->fileManager->normalizePath($this->fileManager->getRealPath($path) ?? $path);
	}

}
