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

    public function catalog(): ResponseInterface
    {
        $this->action = 'catalog';

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

        return $this->respondWithPresentation($result, 'AdminDashboard', AdminResource::class, ['X-Module' => 'AdminModule']);
    }
}
