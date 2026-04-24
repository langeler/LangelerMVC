<?php

declare(strict_types=1);

namespace App\Modules\WebModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\WebModule\Models\Page;
use Throwable;

/**
 * Default content repository used to validate the module persistence pattern.
 */
class PageRepository extends Repository
{
    protected string $modelClass = Page::class;

    public function findBySlug(string $slug): ?Page
    {
        $slug = strtolower(trim($slug));

        if ($slug === '') {
            return null;
        }

        $page = $this->findOneBy(['slug' => $slug]);

        return $page instanceof Page ? $page : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function adminSummaries(): array
    {
        return array_map(
            fn(Page $page): array => $this->mapSummary($page),
            array_values(array_filter($this->all(), static fn(mixed $page): bool => $page instanceof Page))
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function savePage(array $attributes, int $pageId = 0): Page
    {
        $attributes = $this->normalizeAttributes($attributes);

        if ($pageId > 0) {
            $this->update($pageId, $attributes);
            $fresh = $this->find($pageId);

            if ($fresh instanceof Page) {
                return $fresh;
            }
        }

        /** @var Page $page */
        $page = $this->create($attributes);

        return $page;
    }

    public function setPublished(int $pageId, bool $published): ?Page
    {
        $this->update($pageId, ['is_published' => $published ? 1 : 0]);
        $fresh = $this->find($pageId);

        return $fresh instanceof Page ? $fresh : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function mapSummary(Page $page): array
    {
        $id = (int) $page->getKey();
        $slug = (string) ($page->getAttribute('slug') ?? '');
        $published = (bool) ($page->getAttribute('is_published') ?? false);

        return [
            'id' => $id,
            'slug' => $slug,
            'title' => (string) ($page->getAttribute('title') ?? ''),
            'content' => (string) ($page->getAttribute('content') ?? ''),
            'excerpt' => $this->excerpt((string) ($page->getAttribute('content') ?? '')),
            'is_published' => $published,
            'status' => $published ? 'published' : 'draft',
            'view_path' => $slug === 'home' ? '/' : '/pages/' . $slug,
            'update_path' => '/admin/pages/' . $id . '/update',
            'publish_path' => '/admin/pages/' . $id . '/publish',
            'unpublish_path' => '/admin/pages/' . $id . '/unpublish',
            'delete_path' => '/admin/pages/' . $id . '/delete',
            'created_at' => (string) ($page->getAttribute('created_at') ?? ''),
            'updated_at' => (string) ($page->getAttribute('updated_at') ?? ''),
        ];
    }

    public function tableExists(): bool
    {
        try {
            $driver = strtolower((string) $this->db->getAttribute('driverName'));
            $result = match ($driver) {
                'sqlite' => $this->db->fetchColumn(
                    "SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?",
                    [$this->getTable()]
                ),
                'pgsql', 'sqlsrv' => $this->db->fetchColumn(
                    'SELECT 1 FROM information_schema.tables WHERE table_name = ?',
                    [$this->getTable()]
                ),
                'mysql' => $this->db->fetchColumn(
                    'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
                    [$this->getTable()]
                ),
                default => 1,
            };

            return $result !== false && $result !== null;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function normalizeAttributes(array $attributes): array
    {
        return [
            'slug' => strtolower(trim((string) ($attributes['slug'] ?? ''))),
            'title' => trim((string) ($attributes['title'] ?? '')),
            'content' => trim((string) ($attributes['content'] ?? '')),
            'is_published' => !empty($attributes['is_published']) ? 1 : 0,
        ];
    }

    private function excerpt(string $content): string
    {
        $content = preg_replace('/\s+/', ' ', trim($content)) ?? '';

        if (strlen($content) <= 140) {
            return $content;
        }

        return rtrim(substr($content, 0, 137)) . '...';
    }
}
