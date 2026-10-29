<?php

return [

    'defaults' => [
        'guard'     => 'sanctum',
        'passwords' => 'pengguna',
    ],

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'pengguna',
        ],
        'sanctum' => [
            'driver'   => 'sanctum',
            'provider' => 'pengguna',
        ],
    ],

    'providers' => [
        'pengguna' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Pengguna::class,
        ],
    ],

    'passwords' => [
        'pengguna' => [
            'provider' => 'pengguna',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
