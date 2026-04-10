<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Controllers;

use App\Abstracts\Http\Controller;
use App\Contracts\Http\ResponseInterface;
use App\Modules\UserModule\Presenters\UserPresenter;
use App\Modules\UserModule\Presenters\UserResource;
use App\Modules\UserModule\Requests\UserRequest;
use App\Modules\UserModule\Responses\UserResponse;
use App\Modules\UserModule\Services\UserProfileService;
use App\Modules\UserModule\Views\UserView;

class ProfileController extends Controller
{
    private string $action = 'showProfile';

    public function __construct(
        private readonly UserRequest $userRequest,
        UserResponse $response,
        private readonly UserProfileService $profileService,
        UserPresenter $presenter,
        UserView $view
    ) {
        parent::__construct($userRequest, $response, $profileService, $presenter, $view);
    }

    public function show(): ResponseInterface
    {
        $this->action = 'showProfile';

        return $this->run();
    }

    public function update(): ResponseInterface
    {
        $this->userRequest->forScenario('profileUpdate');
        $this->action = 'updateProfile';

        return $this->run();
    }

    public function changePassword(): ResponseInterface
    {
        $this->userRequest->forScenario('passwordChange');
        $this->action = 'changePassword';

        return $this->run();
    }

    protected function execute(): mixed
    {
        return $this->profileService->forAction($this->action, $this->request->all())->execute();
    }

    protected function finalize(mixed $result): ResponseInterface
    {
        if (!is_array($result)) {
            return parent::finalize($result);
        }

        $status = (int) ($result['status'] ?? 200);

        if ($this->request->expectsJson()) {
            return $this->respondWithResource(new UserResource($result), $status, ['X-Module' => 'UserModule']);
        }

        if (isset($result['redirect']) && is_string($result['redirect']) && $result['redirect'] !== '') {
            return $this->respond('', max(200, min($status, 399)), [
                'Location' => $result['redirect'],
                'X-Module' => 'UserModule',
            ]);
        }

        $payload = $this->preparePresenterData('prepare', $result);

        return $this->respondWithView(
            'renderPage',
            (string) ($payload['template'] ?? 'UserProfile'),
            $payload,
            $status,
            ['X-Module' => 'UserModule']
        );
    }
}
