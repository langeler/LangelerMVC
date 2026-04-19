<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Controllers;

use App\Abstracts\Http\Controller;
use App\Contracts\Http\ResponseInterface;
use App\Modules\OrderModule\Presenters\OrderPresenter;
use App\Modules\OrderModule\Presenters\OrderResource;
use App\Modules\OrderModule\Requests\OrderRequest;
use App\Modules\OrderModule\Responses\OrderResponse;
use App\Modules\OrderModule\Services\OrderService;
use App\Modules\OrderModule\Views\OrderView;

class OrderController extends Controller
{
    private string $action = 'checkoutForm';

    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    public function __construct(
        private readonly OrderRequest $orderRequest,
        OrderResponse $response,
        private readonly OrderService $orderService,
        OrderPresenter $presenter,
        OrderView $view
    ) {
        parent::__construct($orderRequest, $response, $orderService, $presenter, $view);
    }

    public function checkoutForm(): ResponseInterface
    {
        $this->action = 'checkoutForm';

        return $this->run();
    }

    public function checkout(): ResponseInterface
    {
        $this->orderRequest->forScenario('checkout');
        $this->action = 'checkout';

        return $this->run();
    }

    public function index(): ResponseInterface
    {
        $this->action = 'orders';

        return $this->run();
    }

    public function show(string $order): ResponseInterface
    {
        $this->action = 'showOrder';
        $this->context = ['order' => (int) $order];

        return $this->run();
    }

    public function capture(string $order): ResponseInterface
    {
        $this->action = 'capture';
        $this->context = ['order' => (int) $order];

        return $this->run();
    }

    public function cancel(string $order): ResponseInterface
    {
        $this->action = 'cancel';
        $this->context = ['order' => (int) $order];

        return $this->run();
    }

    public function refund(string $order): ResponseInterface
    {
        $this->action = 'refund';
        $this->context = ['order' => (int) $order];

        return $this->run();
    }

    public function reconcile(string $order): ResponseInterface
    {
        $this->action = 'reconcile';
        $this->context = ['order' => (int) $order];

        return $this->run();
    }

    protected function execute(): mixed
    {
        return $this->orderService->forAction($this->action, $this->request->all(), $this->context)->execute();
    }

    protected function finalize(mixed $result): ResponseInterface
    {
        if (!is_array($result)) {
            return parent::finalize($result);
        }

        return $this->respondWithPresentation(
            $result,
            (string) ($result['template'] ?? 'OrderCheckout'),
            OrderResource::class,
            ['X-Module' => 'OrderModule']
        );
    }
}
