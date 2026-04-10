<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Controllers;

use App\Abstracts\Http\Controller;
use App\Contracts\Http\ResponseInterface;
use App\Modules\UserModule\Presenters\UserPresenter;
use App\Modules\UserModule\Presenters\UserResource;
use App\Modules\UserModule\Requests\UserRequest;
use App\Modules\UserModule\Responses\UserResponse;
use App\Modules\UserModule\Services\UserPasskeyService;
use App\Modules\UserModule\Views\UserView;

class PasskeyController extends Controller
{
    private string $action = 'beginAuthentication';
    private string $template = 'UserLogin';

    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    public function __construct(
        private readonly UserRequest $userRequest,
        UserResponse $response,
        private readonly UserPasskeyService $passkeyService,
        UserPresenter $presenter,
        UserView $view
    ) {
        parent::__construct($userRequest, $response, $passkeyService, $presenter, $view);
    }

    public function registrationOptions(): ResponseInterface
    {
        $this->userRequest->forScenario('passkeyRegistrationOptions');
        $this->action = 'beginRegistration';
        $this->template = 'UserStatus';

        return $this->run();
    }

    public function register(): ResponseInterface
    {
        $this->userRequest->forScenario('passkeyRegistration');
        $this->action = 'finishRegistration';
        $this->template = 'UserStatus';

        return $this->run();
    }

    public function authenticationOptions(): ResponseInterface
    {
        $this->userRequest->forScenario('passkeyAuthenticationOptions');
        $this->action = 'beginAuthentication';
        $this->template = 'UserLogin';

        return $this->run();
    }

    public function authenticate(): ResponseInterface
    {
        $this->userRequest->forScenario('passkeyAuthentication');
        $this->action = 'finishAuthentication';
        $this->template = 'UserLogin';

        return $this->run();
    }

    public function delete(string $passkey): ResponseInterface
    {
        $this->action = 'deletePasskey';
        $this->template = 'UserProfile';
        $this->context = ['passkey' => (int) $passkey];

        return $this->run();
    }

    protected function execute(): mixed
    {
        return $this->passkeyService->forAction($this->action, $this->request->all(), $this->context)->execute();
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
            (string) ($payload['template'] ?? $this->template),
            $payload,
            $status,
            ['X-Module' => 'UserModule']
        );
    }
}
