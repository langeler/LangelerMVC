<?php

declare(strict_types=1);

namespace App\Modules\AdminModule\Middlewares;

use App\Contracts\Http\ResponseInterface;
use App\Modules\AdminModule\Requests\AdminRequest;
use App\Modules\AdminModule\Responses\AdminResponse;
use App\Utilities\Managers\Security\AuthManager;

class AdminAccessMiddleware
{
    public function __construct(
        private readonly AdminRequest $request,
        private readonly AdminResponse $response,
        private readonly AuthManager $auth
    ) {
    }

    public function handle(): ?ResponseInterface
    {
        if ($this->auth->guest()) {
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

        if ($this->auth->hasPermission('admin.access')) {
            return null;
        }

        $this->response->setStatus(403);

        if ($this->request->expectsJson()) {
            $this->response->addHeader('Content-Type', 'application/json; charset=UTF-8');
            $this->response->setContent([
                'error' => 'Administrator access is required.',
                'status' => 403,
            ]);

            return $this->response;
        }

        $this->response->setContent('<h1>Forbidden</h1><p>Administrator access is required.</p>');

        return $this->response;
    }
}
