<?php

declare(strict_types=1);

namespace App\Modules\CartModule\Controllers;

use App\Abstracts\Http\Controller;
use App\Contracts\Http\ResponseInterface;
use App\Modules\CartModule\Presenters\CartPresenter;
use App\Modules\CartModule\Presenters\CartResource;
use App\Modules\CartModule\Requests\CartRequest;
use App\Modules\CartModule\Responses\CartResponse;
use App\Modules\CartModule\Services\CartService;
use App\Modules\CartModule\Views\CartView;

class CartController extends Controller
{
    private string $action = 'show';

    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    public function __construct(
        private readonly CartRequest $cartRequest,
        CartResponse $response,
        private readonly CartService $cartService,
        CartPresenter $presenter,
        CartView $view
    ) {
        parent::__construct($cartRequest, $response, $cartService, $presenter, $view);
    }

    public function show(): ResponseInterface
    {
        $this->action = 'show';

        return $this->run();
    }

    public function addItem(): ResponseInterface
    {
        $this->cartRequest->forScenario('addItem');
        $this->action = 'addItem';

        return $this->run();
    }

    public function updateItem(string $item): ResponseInterface
    {
        $this->cartRequest->forScenario('updateItem');
        $this->action = 'updateItem';
        $this->context = ['item' => (int) $item];

        return $this->run();
    }

    public function removeItem(string $item): ResponseInterface
    {
        $this->action = 'removeItem';
        $this->context = ['item' => (int) $item];

        return $this->run();
    }

    public function applyDiscount(): ResponseInterface
    {
        $this->cartRequest->forScenario('applyDiscount');
        $this->action = 'applyDiscount';

        return $this->run();
    }

    public function removeDiscount(): ResponseInterface
    {
        $this->action = 'removeDiscount';

        return $this->run();
    }

    protected function execute(): mixed
    {
        return $this->cartService->forAction($this->action, $this->request->all(), $this->context)->execute();
    }

    protected function finalize(mixed $result): ResponseInterface
    {
        if (!is_array($result)) {
            return parent::finalize($result);
        }

        return $this->respondWithPresentation(
            $result,
            'CartPage',
            CartResource::class,
            ['X-Module' => 'CartModule']
        );
    }
}
