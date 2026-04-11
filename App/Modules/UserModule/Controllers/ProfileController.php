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

        return $this->respondWithPresentation($result, 'UserProfile', UserResource::class, ['X-Module' => 'UserModule']);
    }
}
