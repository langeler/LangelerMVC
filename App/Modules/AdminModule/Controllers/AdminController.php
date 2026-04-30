<?php

declare(strict_types=1);

namespace App\Modules\AdminModule\Controllers;

use App\Abstracts\Http\Controller;
use App\Contracts\Http\ResponseInterface;
use App\Modules\AdminModule\Presenters\AdminPresenter;
use App\Modules\AdminModule\Presenters\AdminResource;
use App\Modules\AdminModule\Requests\AdminRequest;
use App\Modules\AdminModule\Responses\AdminResponse;
use App\Modules\AdminModule\Services\AdminAccessService;
use App\Modules\AdminModule\Views\AdminView;

class AdminController extends Controller
{
    private string $action = 'dashboard';

    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    public function __construct(
        private readonly AdminRequest $adminRequest,
        AdminResponse $response,
        private readonly AdminAccessService $adminService,
        AdminPresenter $presenter,
        AdminView $view
    ) {
        parent::__construct($adminRequest, $response, $adminService, $presenter, $view);
    }

    public function dashboard(): ResponseInterface
    {
        $this->action = 'dashboard';

        return $this->run();
    }

    public function users(): ResponseInterface
    {
        $this->action = 'users';

        return $this->run();
    }

    public function assignRoles(string $user): ResponseInterface
    {
        $this->adminRequest->forScenario('assignRoles');
        $this->action = 'assignRoles';
        $this->context = ['user' => (int) $user];

        return $this->run();
    }

    public function roles(): ResponseInterface
    {
        $this->action = 'roles';

        return $this->run();
    }

    public function syncPermissions(string $role): ResponseInterface
    {
        $this->adminRequest->forScenario('syncPermissions');
        $this->action = 'syncPermissions';
        $this->context = ['role' => (int) $role];

        return $this->run();
    }

    public function pages(): ResponseInterface
    {
        $this->action = 'pages';

        return $this->run();
    }

    public function savePage(): ResponseInterface
    {
        $this->adminRequest->forScenario('savePage');
        $this->action = 'savePage';

        return $this->run();
    }

    public function updatePage(string $page): ResponseInterface
    {
        $this->adminRequest->forScenario('savePage');
        $this->action = 'updatePage';
        $this->context = ['page' => (int) $page];

        return $this->run();
    }

    public function publishPage(string $page): ResponseInterface
    {
        $this->action = 'publishPage';
        $this->context = ['page' => (int) $page];

        return $this->run();
    }

    public function unpublishPage(string $page): ResponseInterface
    {
        $this->action = 'unpublishPage';
        $this->context = ['page' => (int) $page];

        return $this->run();
    }

    public function deletePage(string $page): ResponseInterface
    {
        $this->action = 'deletePage';
        $this->context = ['page' => (int) $page];

        return $this->run();
    }

    public function catalog(): ResponseInterface
    {
        $this->action = 'catalog';

        return $this->run();
    }

    public function saveCategory(): ResponseInterface
    {
        $this->adminRequest->forScenario('saveCategory');
        $this->action = 'saveCategory';

        return $this->run();
    }

    public function updateCategory(string $category): ResponseInterface
    {
        $this->adminRequest->forScenario('saveCategory');
        $this->action = 'updateCategory';
        $this->context = ['category' => (int) $category];

        return $this->run();
    }

    public function publishCategory(string $category): ResponseInterface
    {
        $this->action = 'publishCategory';
        $this->context = ['category' => (int) $category];

        return $this->run();
    }

    public function unpublishCategory(string $category): ResponseInterface
    {
        $this->action = 'unpublishCategory';
        $this->context = ['category' => (int) $category];

        return $this->run();
    }

    public function deleteCategory(string $category): ResponseInterface
    {
        $this->action = 'deleteCategory';
        $this->context = ['category' => (int) $category];

        return $this->run();
    }

    public function saveProduct(): ResponseInterface
    {
        $this->adminRequest->forScenario('saveProduct');
        $this->action = 'saveProduct';

        return $this->run();
    }

    public function updateProduct(string $product): ResponseInterface
    {
        $this->adminRequest->forScenario('saveProduct');
        $this->action = 'updateProduct';
        $this->context = ['product' => (int) $product];

        return $this->run();
    }

    public function publishProduct(string $product): ResponseInterface
    {
        $this->action = 'publishProduct';
        $this->context = ['product' => (int) $product];

        return $this->run();
    }

    public function draftProduct(string $product): ResponseInterface
    {
        $this->action = 'draftProduct';
        $this->context = ['product' => (int) $product];

        return $this->run();
    }

    public function archiveProduct(string $product): ResponseInterface
    {
        $this->action = 'archiveProduct';
        $this->context = ['product' => (int) $product];

        return $this->run();
    }

    public function deleteProduct(string $product): ResponseInterface
    {
        $this->action = 'deleteProduct';
        $this->context = ['product' => (int) $product];

        return $this->run();
    }

    public function promotions(): ResponseInterface
    {
        $this->action = 'promotions';

        return $this->run();
    }

    public function savePromotion(): ResponseInterface
    {
        $this->adminRequest->forScenario('savePromotion');
        $this->action = 'savePromotion';

        return $this->run();
    }

    public function updatePromotion(string $promotion): ResponseInterface
    {
        $this->adminRequest->forScenario('savePromotion');
        $this->action = 'updatePromotion';
        $this->context = ['promotion' => (int) $promotion];

        return $this->run();
    }

    public function activatePromotion(string $promotion): ResponseInterface
    {
        $this->action = 'activatePromotion';
        $this->context = ['promotion' => (int) $promotion];

        return $this->run();
    }

    public function deactivatePromotion(string $promotion): ResponseInterface
    {
        $this->action = 'deactivatePromotion';
        $this->context = ['promotion' => (int) $promotion];

        return $this->run();
    }

    public function deletePromotion(string $promotion): ResponseInterface
    {
        $this->action = 'deletePromotion';
        $this->context = ['promotion' => (int) $promotion];

        return $this->run();
    }

    public function bulkPromotions(): ResponseInterface
    {
        $this->adminRequest->forScenario('bulkPromotions');
        $this->action = 'bulkPromotions';

        return $this->run();
    }

    public function carts(): ResponseInterface
    {
        $this->action = 'carts';

        return $this->run();
    }

    public function orders(): ResponseInterface
    {
        $this->action = 'orders';

        return $this->run();
    }

    public function order(string $order): ResponseInterface
    {
        $this->action = 'order';
        $this->context = ['order' => (int) $order];

        return $this->run();
    }

    public function captureOrder(string $order): ResponseInterface
    {
        $this->action = 'captureOrder';
        $this->context = ['order' => (int) $order];

        return $this->run();
    }

    public function cancelOrder(string $order): ResponseInterface
    {
        $this->action = 'cancelOrder';
        $this->context = ['order' => (int) $order];

        return $this->run();
    }

    public function refundOrder(string $order): ResponseInterface
    {
        $this->action = 'refundOrder';
        $this->context = ['order' => (int) $order];

        return $this->run();
    }

    public function reconcileOrder(string $order): ResponseInterface
    {
        $this->action = 'reconcileOrder';
        $this->context = ['order' => (int) $order];

        return $this->run();
    }

    public function packOrder(string $order): ResponseInterface
    {
        $this->action = 'packOrder';
        $this->context = ['order' => (int) $order];

        return $this->run();
    }

    public function servicePointsOrder(string $order): ResponseInterface
    {
        $this->action = 'servicePointsOrder';
        $this->context = ['order' => (int) $order];

        return $this->run();
    }

    public function bookShipmentOrder(string $order): ResponseInterface
    {
        $this->action = 'bookShipmentOrder';
        $this->context = ['order' => (int) $order];

        return $this->run();
    }

    public function shipOrder(string $order): ResponseInterface
    {
        $this->action = 'shipOrder';
        $this->context = ['order' => (int) $order];

        return $this->run();
    }

    public function syncTrackingOrder(string $order): ResponseInterface
    {
        $this->action = 'syncTrackingOrder';
        $this->context = ['order' => (int) $order];

        return $this->run();
    }

    public function cancelShipmentOrder(string $order): ResponseInterface
    {
        $this->action = 'cancelShipmentOrder';
        $this->context = ['order' => (int) $order];

        return $this->run();
    }

    public function deliverOrder(string $order): ResponseInterface
    {
        $this->action = 'deliverOrder';
        $this->context = ['order' => (int) $order];

        return $this->run();
    }

    public function createOrderReturn(string $order): ResponseInterface
    {
        $this->adminRequest->forScenario('createOrderReturn');
        $this->action = 'createOrderReturn';
        $this->context = ['order' => (int) $order];

        return $this->run();
    }

    public function approveOrderReturn(string $order, string $return): ResponseInterface
    {
        $this->action = 'approveOrderReturn';
        $this->context = ['order' => (int) $order, 'return' => (int) $return];

        return $this->run();
    }

    public function rejectOrderReturn(string $order, string $return): ResponseInterface
    {
        $this->action = 'rejectOrderReturn';
        $this->context = ['order' => (int) $order, 'return' => (int) $return];

        return $this->run();
    }

    public function completeOrderReturn(string $order, string $return): ResponseInterface
    {
        $this->adminRequest->forScenario('completeOrderReturn');
        $this->action = 'completeOrderReturn';
        $this->context = ['order' => (int) $order, 'return' => (int) $return];

        return $this->run();
    }

    public function issueOrderDocument(string $order, string $type): ResponseInterface
    {
        $this->adminRequest->forScenario('issueOrderDocument');
        $this->action = 'issueOrderDocument';
        $this->context = ['order' => (int) $order, 'type' => $type];

        return $this->run();
    }

    public function activateEntitlement(string $order, string $entitlement): ResponseInterface
    {
        $this->action = 'activateEntitlement';
        $this->context = [
            'order' => (int) $order,
            'entitlement' => (int) $entitlement,
        ];

        return $this->run();
    }

    public function revokeEntitlement(string $order, string $entitlement): ResponseInterface
    {
        $this->action = 'revokeEntitlement';
        $this->context = [
            'order' => (int) $order,
            'entitlement' => (int) $entitlement,
        ];

        return $this->run();
    }

    public function pauseSubscription(string $order, string $subscription): ResponseInterface
    {
        $this->action = 'pauseSubscription';
        $this->context = [
            'order' => (int) $order,
            'subscription' => (int) $subscription,
        ];

        return $this->run();
    }

    public function resumeSubscription(string $order, string $subscription): ResponseInterface
    {
        $this->action = 'resumeSubscription';
        $this->context = [
            'order' => (int) $order,
            'subscription' => (int) $subscription,
        ];

        return $this->run();
    }

    public function cancelSubscription(string $order, string $subscription): ResponseInterface
    {
        $this->action = 'cancelSubscription';
        $this->context = [
            'order' => (int) $order,
            'subscription' => (int) $subscription,
        ];

        return $this->run();
    }

    public function system(): ResponseInterface
    {
        $this->action = 'system';

        return $this->run();
    }

    public function operations(): ResponseInterface
    {
        $this->action = 'operations';

        return $this->run();
    }

    protected function execute(): mixed
    {
        return $this->adminService->forAction($this->action, $this->request->all(), $this->context)->execute();
    }

    protected function finalize(mixed $result): ResponseInterface
    {
        if (!is_array($result)) {
            return parent::finalize($result);
        }

        return $this->respondWithPresentation(
            $result,
            (string) ($result['template'] ?? 'AdminDashboard'),
            AdminResource::class,
            ['X-Module' => 'AdminModule']
        );
    }
}
