<?php

declare(strict_types=1);

return [
    'DEFAULT' => 'bootstrap-light',
    'MODE' => 'system',
    'ALLOW_USER_SELECTION' => true,
    'STORAGE_KEY' => 'langelermvc.theme',
    'COOKIE' => 'langelermvc_theme',
    'ASSETS' => [
        'CSS' => '/assets/css/langelermvc-theme.css',
        'JS' => '/assets/js/langelermvc-theme.js',
    ],
    'THEMES' => [
        'bootstrap-light' => [
            'LABEL' => 'Bootstrap Light',
            'MODE' => 'light',
            'BOOTSTRAP' => '5.3 LTS-compatible',
            'DESCRIPTION' => 'Modern professional light theme with Bootstrap-compatible design tokens.',
            'SURFACE' => ['web', 'admin', 'installer', 'auth', 'shop', 'cart', 'order'],
        ],
        'bootstrap-dark' => [
            'LABEL' => 'Bootstrap Dark',
            'MODE' => 'dark',
            'BOOTSTRAP' => '5.3 LTS-compatible',
            'DESCRIPTION' => 'Modern professional dark theme with Bootstrap-compatible design tokens.',
            'SURFACE' => ['web', 'admin', 'installer', 'auth', 'shop', 'cart', 'order'],
        ],
        'bootstrap-system' => [
            'LABEL' => 'Bootstrap System',
            'MODE' => 'system',
            'BOOTSTRAP' => '5.3 LTS-compatible',
            'DESCRIPTION' => 'Follows the visitor system preference while keeping the Bootstrap-compatible token set.',
            'SURFACE' => ['web', 'admin', 'installer', 'auth', 'shop', 'cart', 'order'],
        ],
    ],
];
