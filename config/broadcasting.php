<?php

return [

    # 'default' => env('BROADCAST_DRIVER', 'reverb'),
    'default' => 'reverb',

    'connections' => [

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY', 'local'),
            'secret' => env('PUSHER_APP_SECRET', 'local'),
            'app_id' => env('PUSHER_APP_ID', 'local'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
                'useTLS' => false,
                'host' => env('PUSHER_HOST', '127.0.0.1'),
                'path' => env('PUSHER_PATH', '/apps/local'),
                'port' => env('PUSHER_PORT', 6001),
                'scheme' => env('PUSHER_SCHEME', 'http'),
            ],
        ],

        'reverb' => [
            'driver' => 'pusher',
            'key' => env('REVERB_APP_KEY', 'local'),
            'secret' => env('REVERB_APP_SECRET', 'local'),
            'app_id' => env('REVERB_APP_ID', 'local'),
            'options' => [
                'host' => env('REVERB_HOST', '127.0.0.1'),
                'port' => env('REVERB_PORT', 6001),
                'scheme' => env('REVERB_SCHEME', 'http'),
                'useTLS' => env('REVERB_SCHEME', false) === 'https',
            ]
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
