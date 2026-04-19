<?php

declare(strict_types=1);

namespace App\Modules\UserModule\Requests;

use App\Abstracts\Http\InboundRequest;

class UserRequest extends InboundRequest
{
    private string $scenario = 'default';

    public function forScenario(string $scenario): static
    {
        $this->scenario = $scenario;

        return $this;
    }

    protected function sanitizationRules(): array
    {
        return match ($this->scenario) {
            'register', 'login', 'forgotPassword', 'resetPassword', 'profileUpdate', 'passwordChange', 'passkeyRegistrationOptions', 'passkeyAuthenticationOptions' => [
                'name' => ['methods' => 'string', 'required' => false],
                'email' => ['methods' => 'email', 'required' => false],
                'password' => ['methods' => 'string', 'required' => false],
                'password_confirmation' => ['methods' => 'string', 'required' => false],
                'current_password' => ['methods' => 'string', 'required' => false],
                'otp_code' => ['methods' => 'string', 'required' => false],
                'recovery_code' => ['methods' => 'string', 'required' => false],
                'passkey_name' => ['methods' => 'string', 'required' => false],
                'trust_device' => ['methods' => 'string', 'required' => false],
            ],
            default => [],
        };
    }

    protected function validationRules(): array
    {
        return match ($this->scenario) {
            'register' => [
                'name' => ['methods' => 'string', 'rules' => ['notEmpty', 'minLength' => [2], 'maxLength' => [120]]],
                'email' => ['methods' => 'email'],
                'password' => ['methods' => 'string', 'rules' => ['minLength' => [8], 'maxLength' => [255]]],
                'password_confirmation' => ['methods' => 'string', 'rules' => ['minLength' => [8]]],
            ],
            'login' => [
                'email' => ['methods' => 'email'],
                'password' => ['methods' => 'string', 'rules' => ['notEmpty']],
                'otp_code' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^[0-9]{6}$/']],
                'recovery_code' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^[A-Z0-9-]{6,32}$/i']],
                'trust_device' => ['methods' => 'regexp', 'required' => false, 'options' => ['pattern' => '/^(1|true|on|yes)$/i']],
            ],
            'forgotPassword' => [
                'email' => ['methods' => 'email'],
            ],
            'resetPassword' => [
                'password' => ['methods' => 'string', 'rules' => ['minLength' => [8], 'maxLength' => [255]]],
                'password_confirmation' => ['methods' => 'string', 'rules' => ['minLength' => [8]]],
            ],
            'profileUpdate' => [
                'name' => ['methods' => 'string', 'rules' => ['notEmpty', 'minLength' => [2], 'maxLength' => [120]]],
                'email' => ['methods' => 'email'],
            ],
            'passwordChange' => [
                'current_password' => ['methods' => 'string', 'rules' => ['notEmpty']],
                'password' => ['methods' => 'string', 'rules' => ['minLength' => [8], 'maxLength' => [255]]],
                'password_confirmation' => ['methods' => 'string', 'rules' => ['minLength' => [8]]],
            ],
            'verifyOtp' => [
                'otp_code' => ['methods' => 'regexp', 'options' => ['pattern' => '/^[0-9]{6}$/']],
            ],
            'passkeyRegistrationOptions' => [
                'passkey_name' => ['methods' => 'string', 'required' => false, 'rules' => ['maxLength' => [120]]],
            ],
            'passkeyAuthenticationOptions' => [
                'email' => ['methods' => 'email', 'required' => false],
            ],
            default => [],
        };
    }

    protected function transformInput(array $data): array
    {
        foreach (['name', 'email', 'recovery_code', 'passkey_name'] as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $data[$key] = trim($data[$key]);
            }
        }

        if (isset($data['email']) && is_string($data['email'])) {
            $data['email'] = strtolower($data['email']);
        }

        if (isset($data['remember'])) {
            $data['remember'] = filter_var($data['remember'], FILTER_VALIDATE_BOOL);
        }

        if (isset($data['trust_device'])) {
            $data['trust_device'] = filter_var($data['trust_device'], FILTER_VALIDATE_BOOL);
        }

        return $data;
    }
}
