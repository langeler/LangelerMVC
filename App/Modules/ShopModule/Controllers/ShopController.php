<?php

declare(strict_types=1);

namespace App\Modules\ShopModule\Controllers;

use App\Abstracts\Http\Controller;
use App\Contracts\Http\ResponseInterface;
use App\Modules\ShopModule\Presenters\ShopPresenter;
use App\Modules\ShopModule\Presenters\ShopResource;
use App\Modules\ShopModule\Requests\ShopRequest;
use App\Modules\ShopModule\Responses\ShopResponse;
use App\Modules\ShopModule\Services\CatalogService;
use App\Modules\ShopModule\Views\ShopView;

class ShopController extends Controller
{
    private string $action = 'catalog';

    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    public function __construct(
        private readonly ShopRequest $shopRequest,
        ShopResponse $response,
        private readonly CatalogService $catalogService,
        ShopPresenter $presenter,
        ShopView $view
    ) {
        parent::__construct($shopRequest, $response, $catalogService, $presenter, $view);
    }

    public function index(): ResponseInterface
    {
        $this->shopRequest->forScenario('catalog');
        $this->action = 'catalog';
        $this->context = [
            'page' => (int) ($this->request->input('page', 1) ?? 1),
            'q' => (string) ($this->request->input('q', '') ?? ''),
            'availability' => (string) ($this->request->input('availability', 'all') ?? 'all'),
            'sort' => (string) ($this->request->input('sort', 'newest') ?? 'newest'),
        ];

        return $this->run();
    }

    public function category(string $slug): ResponseInterface
    {
        $this->shopRequest->forScenario('catalog');
        $this->action = 'category';
        $this->context = [
            'category_slug' => $slug,
            'page' => (int) ($this->request->input('page', 1) ?? 1),
            'q' => (string) ($this->request->input('q', '') ?? ''),
            'availability' => (string) ($this->request->input('availability', 'all') ?? 'all'),
            'sort' => (string) ($this->request->input('sort', 'newest') ?? 'newest'),
        ];

        return $this->run();
    }

    public function show(string $slug): ResponseInterface
    {
        $this->action = 'product';
        $this->context = ['slug' => $slug];

        return $this->run();
    }

    protected function execute(): mixed
    {
        return $this->catalogService->forAction($this->action, $this->context)->execute();
    }

    protected function finalize(mixed $result): ResponseInterface
    {
        if (!is_array($result)) {
            return parent::finalize($result);
        }

        return $this->respondWithPresentation(
            $result,
            (string) ($result['template'] ?? 'ShopCatalog'),
            ShopResource::class,
            ['X-Module' => 'ShopModule']
        );
    }
}
