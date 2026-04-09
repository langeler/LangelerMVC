<?php

declare(strict_types=1);

namespace App\Modules\WebModule\Services;

use App\Abstracts\Http\Service;
use App\Core\Config;
use App\Modules\WebModule\Models\Page;
use App\Modules\WebModule\Repositories\PageRepository;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Traits\ManipulationTrait;
use Throwable;

/**
 * Resolves starter web pages from the configured content source.
 */
class PageService extends Service
{
    use ManipulationTrait;

    private string $slug = 'home';
    private int $status = 200;

    public function __construct(
        private PageRepository $pages,
        private Config $config,
        private ErrorManager $errorManager
    ) {
    }

    public function forSlug(string $slug, int $status = 200): static
    {
        $this->slug = $slug;
        $this->status = $status;

        return $this;
    }

    protected function handle(): array
    {
        $page = $this->loadPage($this->slug);

        return [
            'status' => $this->status,
            'page' => $page,
            'template' => $this->resolveTemplate($page['slug'] ?? $this->slug),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadPage(string $slug): array
    {
        if ($this->toLower((string) $this->config->get('webmodule', 'CONTENT_SOURCE', 'memory')) !== 'database') {
            return $this->defaultPage($slug);
        }

        try {
            $page = $this->pages->findOneBy([
                'slug' => $slug,
                'is_published' => ['>' => 0],
            ]);

            if ($page instanceof Page) {
                return $this->mapPageModel($page);
            }
        } catch (Throwable $exception) {
            $this->errorManager->logThrowable($exception, 'web.module', 'userNotice');
        }

        return $this->defaultPage($slug);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapPageModel(Page $page): array
    {
        return [
            'slug' => (string) $page->getAttribute('slug'),
            'title' => (string) $page->getAttribute('title'),
            'headline' => (string) ($page->getAttribute('title') ?? 'LangelerMVC'),
            'summary' => 'Loaded from the configured database-backed page repository.',
            'body' => (string) ($page->getAttribute('content') ?? ''),
            'source' => 'database',
            'callToAction' => [
                'label' => 'Back to Home',
                'href' => '/',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultPage(string $slug): array
    {
        return match ($slug) {
            'not-found' => [
                'slug' => 'not-found',
                'title' => 'Page Not Found',
                'headline' => 'Route not found.',
                'summary' => 'The requested route does not map to a module page yet.',
                'body' => 'LangelerMVC handled the miss through the WebModule fallback controller, which keeps routing concerns inside the framework instead of leaking them into the public entrypoint.',
                'source' => 'memory',
                'callToAction' => [
                    'label' => 'Return Home',
                    'href' => '/',
                ],
            ],
            default => [
                'slug' => 'home',
                'title' => 'LangelerMVC',
                'headline' => 'LangelerMVC is running.',
                'summary' => 'A custom-built PHP MVC framework focused on structure, modularity, and backend clarity.',
                'body' => 'The WebModule now runs through the framework lifecycle with concrete request, service, presenter, view, and response classes. It is ready to grow into richer modules without bypassing the backend architecture.',
                'source' => 'memory',
                'callToAction' => [
                    'label' => 'View Architecture',
                    'href' => '#architecture',
                ],
            ],
        };
    }

    private function resolveTemplate(string $slug): string
    {
        return $slug === 'not-found' ? 'NotFound' : 'Home';
    }
}
