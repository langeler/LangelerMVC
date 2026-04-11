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
        private readonly CatalogService $service,
        ShopPresenter $presenter,
        ShopView $view
    ) {
        parent::__construct($shopRequest, $response, $service, $presenter, $view);
    }

    public function index(): ResponseInterface
    {
        $this->action = 'catalog';
        $this->context = [
            'page' => (int) ($this->request->input('page', 1) ?? 1),
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
        return $this->service->forAction($this->action, $this->context)->execute();
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
