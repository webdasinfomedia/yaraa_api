<?php

return [
    'defaults' => [
        'guard' => 'api',
        'passwords' => 'users',
    ],
    'guards' => [
        'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
        'admin-api' => [
            'driver' => 'jwt',
            'provider' => 'admins',
        ]
    ],
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => \App\Models\User::class
        ],
        'admins' => [
            'driver' => 'eloquent',
            'model' => \App\Models\SuperAdmin::class,
        ],
    ]

];
