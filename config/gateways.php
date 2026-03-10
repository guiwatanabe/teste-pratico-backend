<?php

return [
    'drivers' => [
        'gateway_1' => [
            'base_url' => env('GATEWAY_1_BASE_URL', ''),
            'auth_type' => 'auth_token',
            'auth_email' => env('GATEWAY_1_AUTH_EMAIL', ''),
            'auth_token' => env('GATEWAY_1_AUTH_TOKEN', ''),
        ],
        'gateway_2' => [
            'base_url' => env('GATEWAY_2_BASE_URL', ''),
            'auth_type' => 'header',
            'header_token' => env('GATEWAY_2_HEADER_TOKEN', ''),
            'header_secret' => env('GATEWAY_2_HEADER_SECRET', ''),
        ],
    ],
];
