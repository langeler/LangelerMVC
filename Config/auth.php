<?php

return [
    'GUARD' => 'session',
    'OTP' => [
        'DIGITS' => 6,
        'PERIOD' => 30,
        'ALGORITHM' => 'sha1',
    ],
    'VERIFY_EMAIL' => true,
    'PASSWORD_RESET_EXPIRES' => 60,
    'REMEMBER_ME_DAYS' => 30,
];
