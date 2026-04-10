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

    public function system(): ResponseInterface
    {
        $this->action = 'system';

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

        $status = (int) ($result['status'] ?? 200);

        if ($this->request->expectsJson()) {
            return $this->respondWithResource(new AdminResource($result), $status, ['X-Module' => 'AdminModule']);
        }

        if (isset($result['redirect']) && is_string($result['redirect']) && $result['redirect'] !== '') {
            return $this->respond('', max(200, min($status, 399)), [
                'Location' => $result['redirect'],
                'X-Module' => 'AdminModule',
            ]);
        }

        $payload = $this->preparePresenterData('prepare', $result);

        return $this->respondWithView(
            'renderPage',
            (string) ($payload['template'] ?? 'AdminDashboard'),
            $payload,
            $status,
            ['X-Module' => 'AdminModule']
        );
    }
}
