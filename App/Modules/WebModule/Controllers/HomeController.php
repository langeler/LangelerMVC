<?php

declare(strict_types=1);

namespace App\Modules\WebModule\Controllers;

use App\Abstracts\Http\Controller;
use App\Contracts\Http\ResponseInterface;
use App\Modules\WebModule\Presenters\PagePresenter;
use App\Modules\WebModule\Requests\WebRequest;
use App\Modules\WebModule\Responses\WebResponse;
use App\Modules\WebModule\Services\PageService;
use App\Modules\WebModule\Views\WebView;

/**
 * Primary web controller for the framework landing pages.
 */
class HomeController extends Controller
{
    private string $slug = 'home';
    private string $template = 'Home';
    private int $status = 200;

    public function __construct(
        WebRequest $request,
        WebResponse $response,
        private PageService $pageService,
        PagePresenter $presenter,
        WebView $view
    ) {
        parent::__construct($request, $response, $pageService, $presenter, $view);
    }

    public function index(): ResponseInterface
    {
        $this->slug = 'home';
        $this->template = 'Home';
        $this->status = 200;

        return $this->run();
    }

    public function notFound(): ResponseInterface
    {
        $this->slug = 'not-found';
        $this->template = 'NotFound';
        $this->status = 404;

        return $this->run();
    }

    protected function execute(): mixed
    {
        return $this->pageService->forSlug($this->slug, $this->status)->execute();
    }

    protected function finalize(mixed $result): ResponseInterface
    {
        if (!$this->isArray($result)) {
            return parent::finalize($result);
        }

        $payload = $this->preparePresenterData('prepare', $result);
        $status = isset($payload['status']) && $this->isInt($payload['status'])
            ? $payload['status']
            : $this->status;

        return $this->respondWithView(
            'renderPage',
            $this->template,
            $payload,
            $status,
            ['X-Module' => 'WebModule']
        );
    }
}
