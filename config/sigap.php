<?php

return [
    'central_api' => [
        'url' => env('SIGAP_CENTRAL_API_URL', 'https://api.sigap.situbondokab.go.id/v1/prices'),
        'key' => env('SIGAP_CENTRAL_API_KEY', ''),
        'timeout' => env('SIGAP_CENTRAL_API_TIMEOUT', 10),
    ],
];