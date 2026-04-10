<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Middlewares;

use App\Contracts\Http\ResponseInterface;
use App\Modules\UserModule\Requests\UserRequest;
use App\Modules\UserModule\Responses\UserResponse;
use App\Utilities\Managers\Security\AuthManager;

class AuthenticateMiddleware
{
    public function __construct(
        private readonly UserRequest $request,
        private readonly UserResponse $response,
        private readonly AuthManager $auth
    ) {
    }

    public function handle(): ?ResponseInterface
    {
        if ($this->auth->check()) {
            return null;
        }

        $this->response->setStatus($this->request->expectsJson() ? 401 : 302);

        if ($this->request->expectsJson()) {
            $this->response->addHeader('Content-Type', 'application/json; charset=UTF-8');
            $this->response->setContent([
                'error' => 'Authentication required.',
                'status' => 401,
            ]);

            return $this->response;
        }

        $this->response->addHeader('Location', '/users/login');
        $this->response->setContent('');

        return $this->response;
    }
}
