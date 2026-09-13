<?php
return [
    'default' => env('BROADCAST_DRIVER', 'reverb'),

    'connections' => [
        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'useTLS' => true,
            ],
        ],

        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_KEY'),
            'secret' => env('REVERB_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'host' => env('REVERB_HOST', 'reverb.laravel.com'),
            'port' => env('REVERB_PORT', 443),
            'tls' => env('REVERB_TLS', true),
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],
    ],

    'middleware' => [],
];
