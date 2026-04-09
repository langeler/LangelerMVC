<?php

declare(strict_types=1);

namespace App\Modules\WebModule\Presenters;

use App\Abstracts\Presentation\Presenter;

/**
 * Shapes starter web page data for templates.
 */
class PagePresenter extends Presenter
{
    protected function transformData(array $data): array
    {
        $page = isset($data['page']) && $this->isArray($data['page']) ? $data['page'] : [];
        $status = isset($data['status']) && $this->isInt($data['status']) ? $data['status'] : 200;

        return [
            'status' => $status,
            'slug' => (string) ($page['slug'] ?? 'home'),
            'title' => (string) ($page['title'] ?? 'LangelerMVC'),
            'headline' => (string) ($page['headline'] ?? $page['title'] ?? 'LangelerMVC'),
            'summary' => (string) ($page['summary'] ?? ''),
            'body' => (string) ($page['body'] ?? ''),
            'source' => (string) ($page['source'] ?? 'memory'),
            'callToAction' => $this->isArray($page['callToAction'] ?? null)
                ? $page['callToAction']
                : ['label' => 'Home', 'href' => '/'],
        ];
    }

    protected function computeProperties(array $data): array
    {
        $status = isset($data['status']) && $this->isInt($data['status']) ? $data['status'] : 200;
        $slug = (string) ($data['slug'] ?? 'home');

        return [
            'pageClass' => 'page-' . $this->replaceText('_', '-', $this->toLower($slug)),
            'isErrorPage' => $status >= 400,
            'metaDescription' => (string) ($data['summary'] ?? ''),
        ];
    }

    protected function buildMetadata(array $data): array
    {
        return [
            'status' => $data['status'] ?? 200,
            'source' => $data['source'] ?? 'memory',
            'generatedAt' => $this->dateTimeManager->formatDateTime(
                $this->dateTimeManager->createDateTime('now'),
                \DateTime::RFC3339
            ),
        ];
    }
}
