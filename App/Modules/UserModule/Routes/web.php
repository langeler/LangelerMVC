<?php

use App\Core\Router;
use App\Modules\UserModule\Controllers\AuthController;
use App\Modules\UserModule\Controllers\PasskeyController;
use App\Modules\UserModule\Controllers\ProfileController;
use App\Modules\UserModule\Middlewares\AuthenticateMiddleware;

return static function (Router $router): void {
    $authMiddleware = [[AuthenticateMiddleware::class, 'handle']];

    $router->get('/users/register', AuthController::class, 'registerForm', ['as' => 'user.register']);
    $router->post('/users/register', AuthController::class, 'register', ['as' => 'user.register.submit']);
    $router->get('/users/login', AuthController::class, 'loginForm', ['as' => 'user.login']);
    $router->post('/users/login', AuthController::class, 'login', ['as' => 'user.login.submit']);
    $router->post('/users/logout', AuthController::class, 'logout', ['as' => 'user.logout', 'middleware' => $authMiddleware]);
    $router->get('/users/password/forgot', AuthController::class, 'forgotPasswordForm', ['as' => 'user.password.forgot']);
    $router->post('/users/password/forgot', AuthController::class, 'sendPasswordReset', ['as' => 'user.password.email']);
    $router->get('/users/password/reset/{user:\\d+}/{token}', AuthController::class, 'resetPasswordForm', ['as' => 'user.password.reset.form']);
    $router->post('/users/password/reset/{user:\\d+}/{token}', AuthController::class, 'resetPassword', ['as' => 'user.password.reset']);
    $router->get('/users/email/verify/{user:\\d+}/{token}', AuthController::class, 'verifyEmail', ['as' => 'user.verify']);
    $router->post('/users/email/verify', AuthController::class, 'resendVerification', ['as' => 'user.verify.resend', 'middleware' => $authMiddleware]);
    $router->post('/users/otp/enable', AuthController::class, 'enableOtp', ['as' => 'user.otp.enable', 'middleware' => $authMiddleware]);
    $router->post('/users/otp/verify', AuthController::class, 'verifyOtp', ['as' => 'user.otp.verify', 'middleware' => $authMiddleware]);
    $router->post('/users/otp/recovery-codes/regenerate', AuthController::class, 'regenerateOtpRecoveryCodes', ['as' => 'user.otp.recovery.regenerate', 'middleware' => $authMiddleware]);
    $router->post('/users/otp/trusted-devices/revoke', AuthController::class, 'revokeTrustedDevices', ['as' => 'user.otp.trusted.revoke', 'middleware' => $authMiddleware]);
    $router->post('/users/otp/disable', AuthController::class, 'disableOtp', ['as' => 'user.otp.disable', 'middleware' => $authMiddleware]);
    $router->get('/users/profile', ProfileController::class, 'show', ['as' => 'user.profile', 'middleware' => $authMiddleware]);
    $router->post('/users/profile', ProfileController::class, 'update', ['as' => 'user.profile.update', 'middleware' => $authMiddleware]);
    $router->post('/users/password/change', ProfileController::class, 'changePassword', ['as' => 'user.password.change', 'middleware' => $authMiddleware]);
    $router->post('/users/passkeys/register/options', PasskeyController::class, 'registrationOptions', ['as' => 'user.passkeys.register.options', 'middleware' => $authMiddleware]);
    $router->post('/users/passkeys/register/verify', PasskeyController::class, 'register', ['as' => 'user.passkeys.register.verify', 'middleware' => $authMiddleware]);
    $router->post('/users/passkeys/login/options', PasskeyController::class, 'authenticationOptions', ['as' => 'user.passkeys.login.options']);
    $router->post('/users/passkeys/login/verify', PasskeyController::class, 'authenticate', ['as' => 'user.passkeys.login.verify']);
    $router->post('/users/passkeys/{passkey:\\d+}/delete', PasskeyController::class, 'delete', ['as' => 'user.passkeys.delete', 'middleware' => $authMiddleware]);

    $router->post('/api/users/register', AuthController::class, 'register', ['as' => 'api.user.register']);
    $router->post('/api/users/login', AuthController::class, 'login', ['as' => 'api.user.login']);
    $router->post('/api/users/logout', AuthController::class, 'logout', ['as' => 'api.user.logout', 'middleware' => $authMiddleware]);
    $router->get('/api/users/profile', ProfileController::class, 'show', ['as' => 'api.user.profile', 'middleware' => $authMiddleware]);
    $router->post('/api/users/profile', ProfileController::class, 'update', ['as' => 'api.user.profile.update', 'middleware' => $authMiddleware]);
    $router->post('/api/users/password/change', ProfileController::class, 'changePassword', ['as' => 'api.user.password.change', 'middleware' => $authMiddleware]);
    $router->post('/api/users/password/forgot', AuthController::class, 'sendPasswordReset', ['as' => 'api.user.password.email']);
    $router->post('/api/users/password/reset/{user:\\d+}/{token}', AuthController::class, 'resetPassword', ['as' => 'api.user.password.reset']);
    $router->get('/api/users/email/verify/{user:\\d+}/{token}', AuthController::class, 'verifyEmail', ['as' => 'api.user.verify']);
    $router->post('/api/users/email/verify', AuthController::class, 'resendVerification', ['as' => 'api.user.verify.resend', 'middleware' => $authMiddleware]);
    $router->post('/api/users/otp/enable', AuthController::class, 'enableOtp', ['as' => 'api.user.otp.enable', 'middleware' => $authMiddleware]);
    $router->post('/api/users/otp/verify', AuthController::class, 'verifyOtp', ['as' => 'api.user.otp.verify', 'middleware' => $authMiddleware]);
    $router->post('/api/users/otp/recovery-codes/regenerate', AuthController::class, 'regenerateOtpRecoveryCodes', ['as' => 'api.user.otp.recovery.regenerate', 'middleware' => $authMiddleware]);
    $router->post('/api/users/otp/trusted-devices/revoke', AuthController::class, 'revokeTrustedDevices', ['as' => 'api.user.otp.trusted.revoke', 'middleware' => $authMiddleware]);
    $router->post('/api/users/otp/disable', AuthController::class, 'disableOtp', ['as' => 'api.user.otp.disable', 'middleware' => $authMiddleware]);
    $router->post('/api/users/passkeys/register/options', PasskeyController::class, 'registrationOptions', ['as' => 'api.user.passkeys.register.options', 'middleware' => $authMiddleware]);
    $router->post('/api/users/passkeys/register/verify', PasskeyController::class, 'register', ['as' => 'api.user.passkeys.register.verify', 'middleware' => $authMiddleware]);
    $router->post('/api/users/passkeys/login/options', PasskeyController::class, 'authenticationOptions', ['as' => 'api.user.passkeys.login.options']);
    $router->post('/api/users/passkeys/login/verify', PasskeyController::class, 'authenticate', ['as' => 'api.user.passkeys.login.verify']);
    $router->post('/api/users/passkeys/{passkey:\\d+}/delete', PasskeyController::class, 'delete', ['as' => 'api.user.passkeys.delete', 'middleware' => $authMiddleware]);
};
