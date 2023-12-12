<?php
return [
    // 'default' => 'mongodb',
    'default' => 'tenant',
    'connections' => [
        'tenant' => [
            'driver' => 'mongodb',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', 27017),
            'database' => null,
            'username' => env('DB_USERNAME', ''),
            'password' => env('DB_PASSWORD', ''),
            'options' => [
                // here you can pass more settings to the Mongo Driver Manager
                // see https://www.php.net/manual/en/mongodb-driver-manager.construct.php under "Uri Options" for a list of complete parameters that you can use

                'database' => env('AUTH_DB_DATABASE', 'admin'), // required with Mongo 3+
            ],
        ],

        'landlord' => [
            'driver' => 'mongodb',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', 27017),
            'database' => env('DB_DATABASE', 'yaraa_master'),
            'username' => env('DB_USERNAME', ''),
            'password' => env('DB_PASSWORD', ''),
            'options' => [
                // here you can pass more settings to the Mongo Driver Manager
                // see https://www.php.net/manual/en/mongodb-driver-manager.construct.php under "Uri Options" for a list of complete parameters that you can use

                'database' => env('AUTH_DB_DATABASE', 'admin'), // required with Mongo 3+
            ],
        ]
    ],
    'migrations' => 'migrations',

    // 'redis' => [

    //     'client' => env('REDIS_CLIENT', 'phpredis'),

    //     'default' => [
    //         'host' => env('REDIS_HOST', '127.0.0.1'),
    //         'password' => env('REDIS_PASSWORD', null),
    //         'port' => env('REDIS_PORT', 6379),
    //         'database' => env('REDIS_DB', 0),
    //     ],

    //     'cache' => [
    //         'host' => env('REDIS_HOST', '127.0.0.1'),
    //         'password' => env('REDIS_PASSWORD', null),
    //         'port' => env('REDIS_PORT', 6379),
    //         'database' => env('REDIS_CACHE_DB', 1),
    //     ],

    // ],

];