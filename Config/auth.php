<?php

declare(strict_types=1);

use App\Modules\UserModule\Models\User;
use App\Modules\UserModule\Repositories\UserAuthTokenRepository;
use App\Modules\UserModule\Repositories\UserRepository;

return [
    'GUARD' => 'session',
    'USER_MODEL' => User::class,
    'USER_REPOSITORY' => UserRepository::class,
    'TOKEN_REPOSITORY' => UserAuthTokenRepository::class,
    'PASSWORD_HASHER' => 'default',
    'SESSION_KEY' => 'auth.user_id',
    'VIA_REMEMBER_KEY' => 'auth.via_remember',
    'REMEMBER_COOKIE' => 'langelermvc_remember',
    'VERIFY_EMAIL' => true,
    'EMAIL_VERIFY_EXPIRES' => 1440,
    'PASSWORD_RESET_EXPIRES' => 60,
    'REMEMBER_ME_DAYS' => 30,
    'DEFAULT_ROLE' => 'customer',
    'ADMIN_ROLE' => 'administrator',
    'PERMISSIONS' => [
        'admin.access',
        'admin.system.view',
        'admin.users.manage',
        'admin.roles.manage',
        'user.profile.view',
        'user.profile.update',
        'shop.catalog.manage',
        'promotion.manage',
        'cart.manage',
        'order.manage',
    ],
    'OTP' => [
        'DIGITS' => 6,
        'PERIOD' => 30,
        'ALGORITHM' => 'sha1',
        'RECOVERY_CODES' => 8,
        'TRUSTED_DEVICE_DAYS' => 30,
        'TRUSTED_DEVICE_COOKIE' => 'langelermvc_otp_trusted',
    ],
    'PASSKEY' => [
        'DRIVER' => 'webauthn',
        'RP_ID' => null,
        'RP_NAME' => null,
        'ORIGINS' => [],
        'ALLOW_SUBDOMAINS' => false,
        'TIMEOUT' => 60000,
        'CHALLENGE_TTL' => 300,
        'CHALLENGE_BYTES' => 32,
        'ATTACHMENT' => null,
        'RESIDENT_KEY' => 'preferred',
        'ATTESTATION' => 'none',
        'REGISTRATION' => [
            'USER_VERIFICATION' => 'preferred',
        ],
        'AUTHENTICATION' => [
            'USER_VERIFICATION' => 'preferred',
        ],
    ],
];
