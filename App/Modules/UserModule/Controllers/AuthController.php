<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Controllers;

use App\Abstracts\Http\Controller;
use App\Contracts\Http\ResponseInterface;
use App\Modules\UserModule\Presenters\UserPresenter;
use App\Modules\UserModule\Presenters\UserResource;
use App\Modules\UserModule\Requests\UserRequest;
use App\Modules\UserModule\Responses\UserResponse;
use App\Modules\UserModule\Services\UserAuthService;
use App\Modules\UserModule\Views\UserView;

class AuthController extends Controller
{
    private string $action = 'showRegisterForm';
    private string $template = 'UserRegister';

    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    public function __construct(
        private readonly UserRequest $userRequest,
        UserResponse $response,
        private readonly UserAuthService $authService,
        UserPresenter $presenter,
        UserView $view
    ) {
        parent::__construct($userRequest, $response, $authService, $presenter, $view);
    }

    public function registerForm(): ResponseInterface
    {
        $this->action = 'showRegisterForm';
        $this->template = 'UserRegister';

        return $this->run();
    }

    public function register(): ResponseInterface
    {
        $this->userRequest->forScenario('register');
        $this->action = 'register';
        $this->template = 'UserRegister';

        return $this->run();
    }

    public function loginForm(): ResponseInterface
    {
        $this->action = 'showLoginForm';
        $this->template = 'UserLogin';

        return $this->run();
    }

    public function login(): ResponseInterface
    {
        $this->userRequest->forScenario('login');
        $this->action = 'login';
        $this->template = 'UserLogin';

        return $this->run();
    }

    public function logout(): ResponseInterface
    {
        $this->action = 'logout';
        $this->template = 'UserStatus';

        return $this->run();
    }

    public function forgotPasswordForm(): ResponseInterface
    {
        $this->action = 'showForgotPasswordForm';
        $this->template = 'UserPasswordForgot';

        return $this->run();
    }

    public function sendPasswordReset(): ResponseInterface
    {
        $this->userRequest->forScenario('forgotPassword');
        $this->action = 'sendPasswordReset';
        $this->template = 'UserPasswordForgot';

        return $this->run();
    }

    public function resetPasswordForm(string $user, string $token): ResponseInterface
    {
        $this->action = 'showResetPasswordForm';
        $this->template = 'UserPasswordReset';
        $this->context = ['user' => (int) $user, 'token' => $token];

        return $this->run();
    }

    public function resetPassword(string $user, string $token): ResponseInterface
    {
        $this->userRequest->forScenario('resetPassword');
        $this->action = 'resetPassword';
        $this->template = 'UserPasswordReset';
        $this->context = ['user' => (int) $user, 'token' => $token];

        return $this->run();
    }

    public function verifyEmail(string $user, string $token): ResponseInterface
    {
        $this->action = 'verifyEmail';
        $this->template = 'UserStatus';
        $this->context = ['user' => (int) $user, 'token' => $token];

        return $this->run();
    }

    public function resendVerification(): ResponseInterface
    {
        $this->action = 'resendVerification';
        $this->template = 'UserStatus';

        return $this->run();
    }

    public function enableOtp(): ResponseInterface
    {
        $this->action = 'enableOtp';
        $this->template = 'UserStatus';

        return $this->run();
    }

    public function verifyOtp(): ResponseInterface
    {
        $this->userRequest->forScenario('verifyOtp');
        $this->action = 'verifyOtp';
        $this->template = 'UserStatus';

        return $this->run();
    }

    public function regenerateOtpRecoveryCodes(): ResponseInterface
    {
        $this->action = 'regenerateOtpRecoveryCodes';
        $this->template = 'UserStatus';

        return $this->run();
    }

    public function disableOtp(): ResponseInterface
    {
        $this->action = 'disableOtp';
        $this->template = 'UserStatus';

        return $this->run();
    }

    protected function execute(): mixed
    {
        return $this->authService->forAction($this->action, $this->request->all(), $this->context)->execute();
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
